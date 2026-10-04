<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Services\SubscriptionManager;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
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
}
