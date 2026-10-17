<?php

namespace Tests\Feature;

use App\Models\ContentModule;
use App\Models\Domain;
use App\Models\QuestionBank;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\SubscriptionManager;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SubscriptionStateMachineTest extends TestCase
{
    use DatabaseTransactions;

    public function test_subscription_manager_activates_and_rejects_subscriptions(): void
    {
        $manager = app(SubscriptionManager::class);
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
            'transaction_id' => 'TRX-112233',
        ]);

        $this->assertTrue($subscription->isPending());
        $this->assertFalse($subscription->isActive());
        $this->assertFalse($manager->hasActiveAccess($user, $domain));

        // Test activation
        $manager->activate($subscription);
        $subscription->refresh();

        $this->assertTrue($subscription->isActive());
        $this->assertFalse($subscription->isPending());
        $this->assertTrue($manager->hasActiveAccess($user, $domain));
        $this->assertTrue($user->hasActiveSubscriptionFor($domain));
        $this->assertNotNull($user->activeSubscriptionFor($domain));
        $this->assertNotNull($subscription->starts_at);
        $this->assertNotNull($subscription->expires_at);

        // Test rejection
        $manager->reject($subscription, 'Receipt was blurred and unreadable');
        $subscription->refresh();

        $this->assertTrue($subscription->isRejected());
        $this->assertFalse($subscription->isActive());
        $this->assertFalse($manager->hasActiveAccess($user, $domain));
        $this->assertEquals('Receipt was blurred and unreadable', $subscription->rejection_reason);
    }

    public function test_expire_lapsed_subscriptions_command_batches_expired_records(): void
    {
        $manager = app(SubscriptionManager::class);
        $user = User::factory()->create();

        $domain = Domain::create([
            'name' => 'CSS 2027',
            'slug' => 'test-domain-'.uniqid(),
            'code' => 'TEST_'.uniqid(),
            'is_active' => true,
        ]);

        // Expire any preexisting lapsed subscriptions so test batch count is strictly deterministic
        $manager->expireLapsedSubscriptions();

        // Subscription that expired yesterday
        $lapsedSubscription = UserSubscription::create([
            'user_id' => $user->id,
            'domain_id' => $domain->id,
            'status' => 'active',
            'payment_method' => 'jazzcash',
            'transaction_id' => 'TRX-PAST',
            'starts_at' => now()->subYear()->subDay(),
            'expires_at' => now()->subDay(),
        ]);

        $this->assertTrue($lapsedSubscription->isExpired());
        $this->assertFalse($manager->hasActiveAccess($user, $domain));

        // Run artisan command
        $this->artisan('subscriptions:expire-lapsed')
            ->assertSuccessful()
            ->expectsOutputToContain('Successfully expired 1 lapsed subscription(s).');

        $lapsedSubscription->refresh();
        $this->assertEquals('expired', $lapsedSubscription->status);
    }

    public function test_lifetime_subscription_remains_active(): void
    {
        $manager = app(SubscriptionManager::class);
        $user = User::factory()->create();

        $domain = Domain::create([
            'name' => 'Lifetime Test Domain',
            'slug' => 'test-domain-'.uniqid(),
            'code' => 'TEST_'.uniqid(),
            'is_active' => true,
        ]);

        $lifetimeSub = UserSubscription::create([
            'user_id' => $user->id,
            'domain_id' => $domain->id,
            'status' => 'active',
            'payment_method' => 'bank_transfer',
            'transaction_id' => 'TRX-LIFETIME',
            'starts_at' => now(),
            'expires_at' => null, // null = Lifetime
        ]);

        $this->assertTrue($lifetimeSub->isActive());
        $this->assertFalse($lifetimeSub->isExpired());
        $this->assertTrue($manager->hasActiveAccess($user, $domain));
        $this->assertTrue($user->hasActiveSubscriptionFor($domain));
    }

    public function test_mcq_execution_controller_blocks_unauthorized_user(): void
    {
        $user = User::factory()->create();

        $domain = Domain::create([
            'name' => 'PMA Verbal Domain',
            'slug' => 'test-domain-'.uniqid(),
            'code' => 'TEST_'.uniqid(),
            'is_active' => true,
        ]);

        $module = ContentModule::create([
            'domain_id' => $domain->id,
            'title' => 'Verbal Test 1',
            'slug' => 'verbal-test-1-'.uniqid(),
            'module_type' => 'mock_test',
            'is_active' => true,
            'sort_order' => 1,
            'content_meta' => ['passing_score' => 75],
        ]);

        $question = QuestionBank::create([
            'question_type' => 'mcq',
            'code' => 'Q-TEST-'.uniqid(),
            'question_text' => 'Sample Test Question?',
            'payload' => [
                'options' => [
                    ['text' => 'Choice A'],
                    ['text' => 'Choice B'],
                ],
                'correct_option_index' => 0,
            ],
            'is_active' => true,
        ]);

        $module->questions()->attach($question->id, ['sort_order' => 1, 'marks' => 1.00]);

        // Attempt submit without active subscription -> Expect 403
        $response = $this->actingAs($user)->postJson(route('modules.submit', ['module' => $module->id]), [
            'answers' => [$question->id => 0],
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Unauthorized. Active subscription required to submit test responses.',
        ]);

        // Grant active subscription
        UserSubscription::create([
            'user_id' => $user->id,
            'domain_id' => $domain->id,
            'status' => 'active',
            'payment_method' => 'easypaisa',
            'transaction_id' => 'TRX-AUTHORIZED',
            'starts_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        // Re-attempt submit with active subscription -> Expect 200 Success
        $successResponse = $this->actingAs($user)->postJson(route('modules.submit', ['module' => $module->id]), [
            'answers' => [$question->id => 0],
        ]);

        $successResponse->assertOk();
        $successResponse->assertJson([
            'status' => 'success',
            'summary' => [
                'total_questions' => 1,
                'correct_count' => 1,
                'percentage' => 100,
                'is_passed' => true,
            ],
        ]);
    }
}
