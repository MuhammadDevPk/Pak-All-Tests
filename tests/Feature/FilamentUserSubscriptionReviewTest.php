<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FilamentUserSubscriptionReviewTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_approve_subscription_and_grant_access(): void
    {
        $user = User::factory()->create();

        $domain = Domain::create([
            'name' => 'PMA Long Course Initial',
            'slug' => 'test-domain-'.uniqid(),
            'code' => 'TEST_'.uniqid(),
            'is_active' => true,
        ]);

        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'domain_id' => $domain->id,
            'status' => 'pending',
            'payment_method' => 'easypaisa',
            'transaction_id' => 'TRX-10293847',
            'receipt_url' => 'receipts/test_receipt.png',
        ]);

        $this->assertEquals('pending', $subscription->status);
        $this->assertNull($subscription->starts_at);
        $this->assertNull($subscription->expires_at);

        // Simulate admin approval action
        $subscription->update([
            'status' => 'active',
            'starts_at' => now(),
            'expires_at' => now()->addYear(),
            'rejection_reason' => null,
        ]);

        $subscription->refresh();
        $this->assertEquals('active', $subscription->status);
        $this->assertNotNull($subscription->starts_at);
        $this->assertNotNull($subscription->expires_at);
        $this->assertNull($subscription->rejection_reason);

        // Verify domain dashboard is now accessible
        $response = $this->actingAs($user)->get(route('domain.dashboard', ['domainSlug' => $domain->slug]));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Domain/Dashboard'));
    }

    public function test_admin_can_reject_subscription_with_custom_reason(): void
    {
        $user = User::factory()->create();

        $domain = Domain::create([
            'name' => 'CSS Competitive Examination',
            'slug' => 'test-domain-'.uniqid(),
            'code' => 'TEST_'.uniqid(),
            'is_active' => true,
        ]);

        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'domain_id' => $domain->id,
            'status' => 'pending',
            'payment_method' => 'jazzcash',
            'transaction_id' => 'TRX-INVALID-55',
            'receipt_url' => 'receipts/blurry_receipt.png',
        ]);

        // Simulate admin rejection action
        $subscription->update([
            'status' => 'rejected',
            'rejection_reason' => 'The transaction ID TRX-INVALID-55 was not found in our JazzCash statements.',
        ]);

        $subscription->refresh();
        $this->assertEquals('rejected', $subscription->status);
        $this->assertEquals('The transaction ID TRX-INVALID-55 was not found in our JazzCash statements.', $subscription->rejection_reason);

        // Verify candidate sees the rejection reason on PendingOrInactive page
        $response = $this->actingAs($user)->get(route('domain.dashboard', ['domainSlug' => $domain->slug]));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Subscription/PendingOrInactive')
            ->where('subscriptionStatus', 'rejected')
            ->where('subscription.rejection_reason', 'The transaction ID TRX-INVALID-55 was not found in our JazzCash statements.')
        );
    }
}
