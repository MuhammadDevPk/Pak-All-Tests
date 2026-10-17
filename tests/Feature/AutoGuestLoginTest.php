<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\PlatformSetting;
use App\Models\User;
use Filament\Panel;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AutoGuestLoginTest extends TestCase
{
    use DatabaseTransactions;

    protected function createDomain(array $attributes = []): Domain
    {
        $uniq = uniqid();

        return Domain::create(array_merge([
            'name' => 'Domain '.$uniq,
            'slug' => 'domain-'.$uniq,
            'is_active' => true,
            'requires_subscription' => true,
        ], $attributes));
    }

    public function test_unauthenticated_visitor_directly_opening_domain_dashboard_is_auto_logged_in_as_guest(): void
    {
        PlatformSetting::set('auto_guest_login', true);

        $domain = $this->createDomain();

        $this->assertFalse(Auth::check());

        $response = $this->get(route('domain.dashboard', ['domainSlug' => $domain->slug]));

        $response->assertOk();

        $this->assertTrue(Auth::check(), 'Direct URL visit must automatically authenticate the candidate.');

        /** @var User $user */
        $user = Auth::user();
        $this->assertTrue($user->is_guest);
        $this->assertNotNull($user->guest_expires_at);
        $this->assertTrue($user->guest_expires_at->isFuture());

        // Verify active trial for the domain
        $hasSubscription = $user->subscriptions()
            ->where('domain_id', $domain->id)
            ->where('status', 'active')
            ->exists();

        $this->assertTrue($hasSubscription, 'Auto guest pass must provision active domain trial.');
    }

    public function test_unauthenticated_visitor_directly_opening_dashboard_is_auto_logged_in_as_guest(): void
    {
        PlatformSetting::set('auto_guest_login', true);

        $this->assertFalse(Auth::check());

        $response = $this->get(route('dashboard'));

        $response->assertOk();

        $this->assertTrue(Auth::check());

        /** @var User $user */
        $user = Auth::user();
        $this->assertTrue($user->is_guest);
        $this->assertNotNull($user->currentTeam, 'Guest user must have personal workspace team provisioned.');
    }

    public function test_already_authenticated_user_is_not_replaced_by_guest(): void
    {
        $permanentUser = User::factory()->create([
            'is_guest' => false,
        ]);

        $domain = $this->createDomain();

        $response = $this->actingAs($permanentUser)
            ->get(route('domain.dashboard', ['domainSlug' => $domain->slug]));

        $this->assertSame($permanentUser->id, Auth::id());
        $this->assertFalse(Auth::user()->is_guest);
    }

    public function test_login_page_remains_unaffected_for_returning_users(): void
    {
        PlatformSetting::set('auto_guest_login', true);

        $response = $this->get(route('login'));

        $response->assertOk();
        $this->assertFalse(Auth::check(), 'Explicit login page visits must NOT auto-authenticate users.');
    }

    public function test_auto_guest_login_can_be_disabled_via_platform_setting(): void
    {
        PlatformSetting::set('auto_guest_login', false);

        $domain = $this->createDomain();

        $response = $this->get(route('domain.dashboard', ['domainSlug' => $domain->slug]));

        $response->assertRedirect(route('login'));
        $this->assertFalse(Auth::check());
    }

    public function test_visitor_with_referral_query_param_is_redirected_to_registration(): void
    {
        PlatformSetting::set('auto_guest_login', true);

        $domain = $this->createDomain();

        $response = $this->get(route('domain.dashboard', [
            'domainSlug' => $domain->slug,
            'ref' => 'PAK-TESTCODE',
        ]));

        $response->assertRedirect(route('register', [
            'ref' => 'PAK-TESTCODE',
            'domain' => $domain->slug,
        ]));

        $this->assertFalse(Auth::check(), 'Referral links should prompt registration so referring parent gets credited.');
    }

    public function test_guest_user_cannot_access_filament_admin_panel(): void
    {
        $guest = User::factory()->create([
            'is_guest' => true,
            'guest_expires_at' => now()->addHours(24),
        ]);

        $panel = new Panel;

        $this->assertFalse(
            $guest->canAccessPanel($panel),
            'Guest accounts must never be allowed access to the Filament admin panel.'
        );
    }
}
