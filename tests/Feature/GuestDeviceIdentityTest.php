<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\DomainFlow;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\GuestAuthService;
use App\Services\SubscriptionManager;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Tests\TestCase;

class GuestDeviceIdentityTest extends TestCase
{
    use DatabaseTransactions;

    protected SubscriptionManager $subscriptionManager;

    protected GuestAuthService $guestAuthService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subscriptionManager = app(SubscriptionManager::class);
        $this->guestAuthService = app(GuestAuthService::class);
        PlatformSetting::set('auto_guest_login', true);
        PlatformSetting::set('all_domains_free', false);
    }

    protected function createDomainWithFlow(string $name = 'Device Auth Target Flow'): Domain
    {
        $slug = Str::slug($name).'-'.uniqid();
        $domain = Domain::create([
            'name' => $name,
            'slug' => $slug,
            'is_active' => true,
            'requires_subscription' => true,
        ]);

        DomainFlow::create([
            'domain_id' => $domain->id,
            'ui_schema' => ['type' => 'VerticalStack', 'props' => []],
            'version' => 1,
            'is_active' => true,
        ]);

        return $domain;
    }

    public function test_first_time_guest_visit_provisions_account_and_sets_device_token_cookie(): void
    {
        $this->assertFalse(Auth::check());

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertCookie(GuestAuthService::COOKIE_NAME);

        $this->assertTrue(Auth::check());
        /** @var User $guest */
        $guest = Auth::user();
        $this->assertTrue($guest->is_guest);
        $this->assertNotNull($guest->guest_token_hash);

        // Verify cookie value matches token in database
        $cookieToken = $response->getCookie(GuestAuthService::COOKIE_NAME)?->getValue();
        $this->assertNotNull($cookieToken);
        $this->assertSame((string) $cookieToken, $guest->guest_token_hash);
    }

    public function test_returning_guest_with_cookie_re_authenticates_same_user_without_creating_new_record(): void
    {
        // 1. Initial guest visit
        $initialResponse = $this->get(route('dashboard'));
        $initialResponse->assertOk();

        /** @var User $originalGuest */
        $originalGuest = Auth::user();
        $originalGuestId = $originalGuest->id;
        $cookieToken = (string) $initialResponse->getCookie(GuestAuthService::COOKIE_NAME)?->getValue();

        $guestCountBefore = User::where('is_guest', true)->count();

        // 2. Simulate browser restart: flush session and unauthenticate
        Auth::logout();
        session()->flush();
        $this->assertFalse(Auth::check());

        // 3. Returning request with persistent device token cookie
        $returningResponse = $this->withCookie(GuestAuthService::COOKIE_NAME, $cookieToken)
            ->get(route('dashboard'));

        $returningResponse->assertOk();
        $this->assertTrue(Auth::check());
        $this->assertSame($originalGuestId, Auth::id(), 'Returning guest must be re-authenticated into identical user.');

        $guestCountAfter = User::where('is_guest', true)->count();
        $this->assertSame($guestCountBefore, $guestCountAfter, 'Zero duplicate guest users must be created on return.');
    }

    public function test_returning_guest_does_not_duplicate_domain_subscriptions(): void
    {
        $domain = $this->createDomainWithFlow('Single Trial Test Domain');

        // 1. Initial visit provisions 1-day domain pass
        $firstVisit = $this->get(route('domain.dashboard', ['domainSlug' => $domain->slug]));
        $firstVisit->assertOk();

        /** @var User $guest */
        $guest = Auth::user();
        $cookieToken = (string) $firstVisit->getCookie(GuestAuthService::COOKIE_NAME)?->getValue();

        $initialSubs = UserSubscription::where('user_id', $guest->id)
            ->where('domain_id', $domain->id)
            ->count();
        $this->assertSame(1, $initialSubs);

        // 2. Simulate closing and reopening browser 5 consecutive times
        for ($i = 0; $i < 5; $i++) {
            Auth::logout();
            session()->flush();

            $subsequentVisit = $this->withCookie(GuestAuthService::COOKIE_NAME, $cookieToken)
                ->get(route('domain.dashboard', ['domainSlug' => $domain->slug]));

            $subsequentVisit->assertOk();
            $this->assertSame($guest->id, Auth::id());
        }

        $finalSubs = UserSubscription::where('user_id', $guest->id)
            ->where('domain_id', $domain->id)
            ->count();
        $this->assertSame(1, $finalSubs, 'Multiple visits by returning guest must never duplicate subscriptions.');
    }

    public function test_expired_guest_returns_to_same_account_without_new_trial(): void
    {
        $domain = $this->createDomainWithFlow('Expired Pass Verification');

        $plainToken = Str::random(64);
        $tokenHash = hash('sha256', $plainToken);

        $expiredGuest = User::create([
            'name' => 'Expired Guest',
            'email' => 'expired_'.uniqid().'@guest.pakalltests.local',
            'password' => bcrypt('Secret123!'),
            'is_guest' => true,
            'guest_expires_at' => now()->subHours(2), // Expired 2 hours ago
            'guest_token_hash' => $tokenHash,
        ]);

        UserSubscription::create([
            'user_id' => $expiredGuest->id,
            'domain_id' => $domain->id,
            'status' => 'expired',
            'payment_method' => 'free_trial',
            'starts_at' => now()->subDays(2),
            'expires_at' => now()->subHours(2),
        ]);

        $this->assertFalse($this->subscriptionManager->hasActiveAccess($expiredGuest, $domain));

        // Revisit domain with cookie
        $response = $this->withCookie(GuestAuthService::COOKIE_NAME, $plainToken)
            ->get(route('domain.dashboard', ['domainSlug' => $domain->slug]));

        $response->assertOk();
        $this->assertSame($expiredGuest->id, Auth::id(), 'Expired guest re-authenticates to same account.');

        // Verify zero new subscriptions were created
        $subsCount = UserSubscription::where('user_id', $expiredGuest->id)
            ->where('domain_id', $domain->id)
            ->count();
        $this->assertSame(1, $subsCount);

        // Guest access remains expired
        $this->assertFalse(
            $this->subscriptionManager->hasActiveAccess($expiredGuest->fresh(), $domain),
            'Expired guest must NOT receive additional free trial.'
        );
    }

    public function test_different_browser_on_same_ip_reuses_existing_guest_account(): void
    {
        $sharedIp = '192.168.10.45';

        // 1. First browser (e.g. Chrome) visits and creates guest
        $responseChrome = $this->withServerVariables(['REMOTE_ADDR' => $sharedIp])
            ->get(route('dashboard'));
        $responseChrome->assertOk();
        $userChromeId = Auth::id();
        $tokenChrome = (string) $responseChrome->getCookie(GuestAuthService::COOKIE_NAME)?->getValue();

        Auth::logout();
        session()->flush();

        // 2. Second browser (e.g. Safari on same machine) visits without cookie
        $responseSafari = $this->withServerVariables(['REMOTE_ADDR' => $sharedIp])
            ->get(route('dashboard'));
        $responseSafari->assertOk();
        $userSafariId = Auth::id();
        $tokenSafari = (string) $responseSafari->getCookie(GuestAuthService::COOKIE_NAME)?->getValue();

        $this->assertSame($userChromeId, $userSafariId, 'Different browser on same system IP must reuse existing guest user.');
        $this->assertSame($tokenChrome, $tokenSafari, 'Device token must be synchronized to second browser.');

        // 3. Third browser on a DIFFERENT IP visits
        Auth::logout();
        session()->flush();

        $responseDifferentIp = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.88'])
            ->get(route('dashboard'));
        $responseDifferentIp->assertOk();
        $differentUserId = Auth::id();

        $this->assertNotSame($userChromeId, $differentUserId, 'Different IP must receive an independent guest user.');
    }

    public function test_converting_guest_to_permanent_revokes_guest_device_token_and_cookie(): void
    {
        $plainToken = Str::random(64);
        $tokenHash = hash('sha256', $plainToken);

        $guest = User::create([
            'name' => 'Convert Candidate',
            'email' => 'conv_'.uniqid().'@guest.pakalltests.local',
            'password' => bcrypt('Secret123!'),
            'is_guest' => true,
            'guest_expires_at' => now()->addHours(12),
            'guest_token_hash' => $tokenHash,
        ]);

        $response = $this->actingAs($guest)
            ->withCookie(GuestAuthService::COOKIE_NAME, $plainToken)
            ->post('/convert-guest', [
                'name' => 'Permanent Candidate',
                'email' => 'permanent_'.uniqid().'@example.com',
                'password' => 'SecurePass123!',
                'password_confirmation' => 'SecurePass123!',
            ]);

        $response->assertSessionHasNoErrors();

        $guest->refresh();
        $this->assertFalse($guest->is_guest);
        $this->assertNull($guest->guest_token_hash, 'Guest token hash must be cleared upon conversion.');

        // Cookie forget check
        $response->assertCookieExpired(GuestAuthService::COOKIE_NAME);
    }
}
