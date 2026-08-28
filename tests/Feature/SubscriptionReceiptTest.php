<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubscriptionReceiptTest extends TestCase
{
    use DatabaseTransactions;

    public function test_authenticated_user_can_upload_receipt_and_create_pending_subscription(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $domain = Domain::create([
            'name' => 'PMA Long Course Initial',
            'slug' => 'test-domain-'.uniqid(),
            'code' => 'TEST_'.uniqid(),
            'is_active' => true,
        ]);

        $file = UploadedFile::fake()->image('receipt.png', 600, 800);

        $response = $this->actingAs($user)->post(route('subscription.receipt.upload'), [
            'domain_id' => $domain->id,
            'payment_method' => 'easypaisa',
            'transaction_id' => 'TRX-99887766',
            'receipt_file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('user_subscriptions', [
            'user_id' => $user->id,
            'domain_id' => $domain->id,
            'status' => 'pending',
            'payment_method' => 'easypaisa',
            'transaction_id' => 'TRX-99887766',
            'rejection_reason' => null,
        ]);

        $subscription = UserSubscription::where('user_id', $user->id)
            ->where('domain_id', $domain->id)
            ->first();

        $this->assertNotNull($subscription->receipt_url);
        Storage::disk('public')->assertExists($subscription->receipt_url);
    }

    public function test_resubmission_clears_prior_rejection_reason(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $domain = Domain::create([
            'name' => 'CSS Competitive Exam',
            'slug' => 'test-domain-'.uniqid(),
            'code' => 'TEST_'.uniqid(),
            'is_active' => true,
        ]);

        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'domain_id' => $domain->id,
            'status' => 'rejected',
            'payment_method' => 'jazzcash',
            'transaction_id' => 'TRX-OLD',
            'rejection_reason' => 'Invalid transaction screenshot',
        ]);

        $file = UploadedFile::fake()->image('new_receipt.jpg', 600, 800);

        $response = $this->actingAs($user)->post(route('subscription.receipt.upload'), [
            'domain_id' => $domain->id,
            'payment_method' => 'jazzcash',
            'transaction_id' => 'TRX-NEW-112233',
            'receipt_file' => $file,
        ]);

        $response->assertRedirect();

        $subscription->refresh();
        $this->assertEquals('pending', $subscription->status);
        $this->assertEquals('TRX-NEW-112233', $subscription->transaction_id);
        $this->assertNull($subscription->rejection_reason);
    }

    public function test_upload_fails_on_missing_required_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('subscription.receipt.upload'), []);

        $response->assertSessionHasErrors(['domain_id', 'payment_method', 'transaction_id', 'receipt_file']);
    }

    public function test_unauthenticated_users_cannot_upload_receipt(): void
    {
        $response = $this->post(route('subscription.receipt.upload'), []);

        $response->assertRedirect(route('login'));
    }

    public function test_domain_dashboard_renders_pending_subscription_state(): void
    {
        $user = User::factory()->create();

        $domain = Domain::create([
            'name' => 'PMA Long Course Initial',
            'slug' => 'test-domain-'.uniqid(),
            'code' => 'TEST_'.uniqid(),
            'is_active' => true,
        ]);

        UserSubscription::create([
            'user_id' => $user->id,
            'domain_id' => $domain->id,
            'status' => 'pending',
            'payment_method' => 'easypaisa',
            'transaction_id' => 'TRX-PENDING-99',
        ]);

        $response = $this->actingAs($user)->get(route('domain.dashboard', ['domainSlug' => $domain->slug]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Subscription/PendingOrInactive')
            ->where('subscriptionStatus', 'pending')
            ->where('subscription.transaction_id', 'TRX-PENDING-99')
            ->where('subscription.payment_method', 'easypaisa')
        );
    }
}
