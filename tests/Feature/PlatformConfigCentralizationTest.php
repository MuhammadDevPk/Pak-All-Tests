<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Fortify\CreateNewUser;
use App\Models\Domain;
use App\Models\User;
use App\Services\GuestAuthService;
use App\Services\ReferralService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PlatformConfigCentralizationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_platform_config_exposes_expected_structure(): void
    {
        $platform = config('platform');

        $this->assertIsArray($platform);
        $this->assertArrayHasKey('name', $platform);
        $this->assertArrayHasKey('portal_name', $platform);
        $this->assertArrayHasKey('tagline', $platform);
        $this->assertArrayHasKey('support_email', $platform);
        $this->assertArrayHasKey('trials', $platform);
        $this->assertArrayHasKey('referrals', $platform);
        $this->assertArrayHasKey('payment_accounts', $platform);

        $this->assertSame(7, config('platform.trials.free_trial_days'));
        $this->assertSame(24, config('platform.trials.guest_pass_hours'));
        $this->assertSame(1, config('platform.trials.guest_domain_pass_days'));
        $this->assertSame(7, config('platform.referrals.days_per_referral'));
        $this->assertSame(5, config('platform.referrals.milestone_target'));
        $this->assertSame(30, config('platform.referrals.milestone_days'));

        $this->assertArrayHasKey('easypaisa', config('platform.payment_accounts'));
        $this->assertArrayHasKey('jazzcash', config('platform.payment_accounts'));
        $this->assertArrayHasKey('bank_transfer', config('platform.payment_accounts'));
    }

    public function test_inertia_middleware_shares_platform_configuration(): void
    {
        $response = $this->get('/');

        $response->assertInertia(fn ($page) => $page
            ->has('platform')
            ->where('platform.name', config('platform.name'))
            ->where('platform.portal_name', config('platform.portal_name'))
            ->where('platform.support_email', config('platform.support_email'))
            ->has('platform.payment_accounts.easypaisa')
            ->has('platform.trials')
            ->has('platform.referrals')
        );
    }

    public function test_guest_auth_service_uses_configured_expiration_and_domain(): void
    {
        config(['platform.trials.guest_pass_hours' => 48]);
        config(['platform.trials.guest_email_domain' => 'test-guest.paktests.local']);

        /** @var GuestAuthService $service */
        $service = app(GuestAuthService::class);
        $guest = $service->createGuestUser('127.0.0.1');

        $this->assertTrue($guest->is_guest);
        $this->assertStringEndsWith('@test-guest.paktests.local', $guest->email);
        $this->assertNotNull($guest->guest_expires_at);
        $this->assertTrue($guest->guest_expires_at->gt(now()->addHours(47)));
    }

    public function test_referral_service_uses_configured_reward_days_and_milestones(): void
    {
        config(['platform.referrals.days_per_referral' => 10]);
        config(['platform.referrals.milestone_target' => 3]);
        config(['platform.referrals.milestone_days' => 50]);

        $domain = Domain::create([
            'name' => 'Config Test Domain',
            'slug' => 'config-test-domain-'.uniqid(),
            'is_active' => true,
            'requires_subscription' => true,
        ]);

        /** @var ReferralService $referralService */
        $referralService = app(ReferralService::class);

        $parent = User::factory()->create(['registration_ip' => '10.10.1.1']);
        $parentCode = $referralService->getOrCreateReferralCode($parent);

        // 1st referral should award 10 days
        $child1 = User::factory()->create(['registration_ip' => '10.10.1.2']);
        $result1 = $referralService->processReferral($child1, $parentCode, $domain, '10.10.1.2', 'dev_1');
        $this->assertSame(10, $result1['reward_days'] ?? null);
        $this->assertNull($result1['milestone'] ?? null);

        // 2nd referral should award 10 days
        $child2 = User::factory()->create(['registration_ip' => '10.10.1.3']);
        $result2 = $referralService->processReferral($child2, $parentCode, $domain, '10.10.1.3', 'dev_2');
        $this->assertSame(10, $result2['reward_days'] ?? null);

        // 3rd referral (milestone target = 3) should award 50 days
        $child3 = User::factory()->create(['registration_ip' => '10.10.1.4']);
        $result3 = $referralService->processReferral($child3, $parentCode, $domain, '10.10.1.4', 'dev_3');
        $this->assertSame(50, $result3['reward_days'] ?? null);
        $this->assertSame('50_days_milestone', $result3['milestone'] ?? null);
    }

    public function test_registration_uses_configured_trial_days(): void
    {
        config(['platform.trials.free_trial_days' => 14]);

        $domain = Domain::create([
            'name' => 'Trial Flow Domain',
            'slug' => 'trial-flow-'.uniqid(),
            'is_active' => true,
            'requires_subscription' => true,
        ]);

        /** @var CreateNewUser $action */
        $action = app(CreateNewUser::class);
        $user = $action->create([
            'name' => 'Config Trial Student',
            'email' => 'config_trial_'.uniqid().'@paktests.pk',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $sub = $user->subscriptions()->where('domain_id', $domain->id)->first();
        $this->assertNotNull($sub);
        $this->assertSame('active', $sub->status);
        $this->assertTrue($sub->expires_at->gt(now()->addDays(13)));
    }
}
