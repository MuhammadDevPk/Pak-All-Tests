<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Services\SubscriptionManager;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PaymentBypassTest extends TestCase
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

    public function test_global_all_domains_free_switch_grants_immediate_access(): void
    {
        PlatformSetting::set('all_domains_free', true);

        $domain = $this->createDomain([
            'is_active' => true,
            'requires_subscription' => true,
        ]);

        $user = User::factory()->create([
            'is_suspended' => false,
        ]);

        $this->assertTrue(
            $this->subscriptionManager->hasActiveAccess($user, $domain),
            'When global all_domains_free is enabled, user must have active access even if domain requires subscription.'
        );
    }

    public function test_per_domain_free_toggle_grants_access_when_global_switch_is_disabled(): void
    {
        PlatformSetting::set('all_domains_free', false);

        $freeDomain = $this->createDomain([
            'is_active' => true,
            'requires_subscription' => false,
        ]);

        $paidDomain = $this->createDomain([
            'is_active' => true,
            'requires_subscription' => true,
        ]);

        $user = User::factory()->create([
            'is_suspended' => false,
        ]);

        $this->assertTrue(
            $this->subscriptionManager->hasActiveAccess($user, $freeDomain),
            'When domain requires_subscription is false, user must have active access.'
        );

        $this->assertFalse(
            $this->subscriptionManager->hasActiveAccess($user, $paidDomain),
            'When domain requires_subscription is true and global free is off, user without subscription must NOT have access.'
        );
    }

    public function test_suspended_user_is_denied_access_even_when_free_mode_is_active(): void
    {
        PlatformSetting::set('all_domains_free', true);

        $domain = $this->createDomain([
            'is_active' => true,
            'requires_subscription' => false,
        ]);

        $suspendedUser = User::factory()->create([
            'is_suspended' => true,
            'suspension_reason' => 'Referral fraud',
        ]);

        $this->assertFalse(
            $this->subscriptionManager->hasActiveAccess($suspendedUser, $domain),
            'Suspended users must be strictly denied access across all domains.'
        );

        $response = $this->actingAs($suspendedUser)->get("/d/{$domain->slug}");
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Subscription/PendingOrInactive')
            ->where('isSuspended', true)
            ->where('suspensionReason', 'Referral fraud')
        );
    }
}
