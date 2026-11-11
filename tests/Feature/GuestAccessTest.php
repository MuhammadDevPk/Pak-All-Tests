<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ContentModule;
use App\Models\Domain;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Services\SubscriptionManager;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class GuestAccessTest extends TestCase
{
    use DatabaseTransactions;

    protected SubscriptionManager $subscriptionManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subscriptionManager = app(SubscriptionManager::class);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function createDomain(array $attributes = []): Domain
    {
        $uniq = uniqid();

        return Domain::create(array_merge([
            'name' => 'Test Domain '.$uniq,
            'slug' => 'test-domain-'.$uniq,
            'is_active' => true,
            'requires_subscription' => true,
        ], $attributes));
    }

    public function test_guest_login_endpoint_creates_ephemeral_user_with_24h_ttl(): void
    {
        $domain = $this->createDomain([
            'is_active' => true,
            'requires_subscription' => true,
        ]);

        $response = $this->post('/guest-login', [
            'domain_slug' => $domain->slug,
        ]);

        $response->assertRedirect(route('domain.dashboard', ['domainSlug' => $domain->slug]));

        $this->assertTrue(Auth::check(), 'Guest user must be authenticated after guest-login.');

        /** @var User $guest */
        $guest = Auth::user();
        $this->assertTrue($guest->is_guest);
        $this->assertNotNull($guest->guest_expires_at);
        $this->assertTrue($guest->guest_expires_at->isFuture());
    }

    public function test_guest_has_active_access_within_24h_window_even_if_domain_requires_paid_subscription(): void
    {
        PlatformSetting::set('all_domains_free', false);

        $domain = $this->createDomain([
            'is_active' => true,
            'requires_subscription' => true,
        ]);

        $activeGuest = User::factory()->create([
            'is_guest' => true,
            'guest_expires_at' => now()->addHours(20),
            'is_suspended' => false,
        ]);

        $expiredGuest = User::factory()->create([
            'is_guest' => true,
            'guest_expires_at' => now()->subHour(),
            'is_suspended' => false,
        ]);

        $this->assertTrue(
            $this->subscriptionManager->hasActiveAccess($activeGuest, $domain),
            'Guest within their 24h window must have active domain access.'
        );

        $this->assertFalse(
            $this->subscriptionManager->hasActiveAccess($expiredGuest, $domain),
            'Guest whose 24h window has passed must NOT have active domain access.'
        );
    }

    public function test_guest_can_convert_to_permanent_account_and_receive_7_day_trial(): void
    {
        $guest = User::factory()->create([
            'name' => 'Temporary Guest',
            'is_guest' => true,
            'guest_expires_at' => now()->addHours(12),
        ]);

        $domain = $this->createDomain([
            'is_active' => true,
        ]);

        $response = $this->actingAs($guest)->post('/convert-guest', [
            'name' => 'Converted Student',
            'email' => 'student_'.uniqid().'@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $response->assertSessionHasNoErrors();

        $guest->refresh();
        $this->assertFalse($guest->is_guest);
        $this->assertNull($guest->guest_expires_at);
        $this->assertSame('Converted Student', $guest->name);

        $subscription = $guest->subscriptions()->where('domain_id', $domain->id)->first();
        $this->assertNotNull($subscription, '7-Day Free Trial subscription must be provisioned.');
        $this->assertSame('active', $subscription->status);
        $this->assertSame('free_trial', $subscription->payment_method);
        $this->assertTrue($subscription->expires_at->isFuture());
    }

    public function test_expired_guest_cannot_access_content_modules_even_if_subscription_record_exists(): void
    {
        PlatformSetting::set('all_domains_free', false);

        $domain = $this->createDomain([
            'is_active' => true,
            'requires_subscription' => true,
        ]);

        $module = ContentModule::create([
            'domain_id' => $domain->id,
            'title' => 'Test Subject Module '.uniqid(),
            'slug' => 'test-subject-module-'.uniqid(),
            'module_type' => 'mcq_bank',
            'is_active' => true,
        ]);

        $expiredGuest = User::factory()->create([
            'is_guest' => true,
            'guest_expires_at' => now()->subMinutes(15),
            'is_suspended' => false,
        ]);

        // Even if an expired guest had an old/lingering active user_subscriptions entry
        $this->subscriptionManager->grantFreeTrial($expiredGuest, $domain, 1);

        $this->assertFalse(
            $this->subscriptionManager->hasActiveAccess($expiredGuest, $domain),
            'Expired guest must NEVER have active access even if user_subscriptions row exists.'
        );

        $response = $this->actingAs($expiredGuest)->getJson("/api/modules/{$module->id}");
        $response->assertStatus(403);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Unauthorized. Active subscription required for this domain.',
        ]);
    }

    public function test_expired_guest_dashboard_renders_with_has_access_false_and_guest_expired_flag(): void
    {
        PlatformSetting::set('all_domains_free', false);

        $domain = $this->createDomain([
            'is_active' => true,
            'requires_subscription' => true,
        ]);

        $expiredGuest = User::factory()->create([
            'is_guest' => true,
            'guest_expires_at' => now()->subHours(2),
            'is_suspended' => false,
        ]);

        $response = $this->actingAs($expiredGuest)->get(route('domain.dashboard', ['domainSlug' => $domain->slug]));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Domain/Dashboard')
            ->where('hasAccess', false)
            ->where('guest.is_guest', true)
            ->where('guest.is_expired', true)
        );
    }

    public function test_guest_can_register_via_standard_register_page_and_be_redirected_back_to_domain(): void
    {
        PlatformSetting::set('all_domains_free', false);

        $domain = $this->createDomain([
            'is_active' => true,
            'requires_subscription' => true,
        ]);

        $guest = User::factory()->create([
            'name' => 'Expired Candidate',
            'is_guest' => true,
            'guest_expires_at' => now()->subHour(),
            'is_suspended' => false,
        ]);

        // 1. Guest can view /register without being kicked out by guest middleware
        $viewResponse = $this->actingAs($guest)->get('/register?redirect='.urlencode("/d/{$domain->slug}"));
        $viewResponse->assertOk();

        // 2. Submitting registration converts the guest and redirects back to domain dashboard
        $response = $this->actingAs($guest)->post('/register', [
            'name' => 'Permanent Candidate',
            'email' => 'permanent_'.uniqid().'@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'redirect' => "/d/{$domain->slug}",
        ]);

        $response->assertRedirect("/d/{$domain->slug}");

        $guest->refresh();
        $this->assertFalse($guest->is_guest);
        $this->assertNull($guest->guest_expires_at);
        $this->assertSame('Permanent Candidate', $guest->name);

        $this->assertTrue(
            $this->subscriptionManager->hasActiveAccess($guest, $domain),
            'Newly registered student must have active access on domain flow.'
        );
    }
}
