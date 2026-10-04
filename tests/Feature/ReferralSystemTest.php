<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\Referral;
use App\Models\User;
use App\Services\ReferralService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ReferralSystemTest extends TestCase
{
    use DatabaseTransactions;

    protected ReferralService $referralService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->referralService = app(ReferralService::class);
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

    public function test_user_can_generate_deterministic_referral_code_and_link(): void
    {
        $user = User::factory()->create();
        $domain = $this->createDomain(['slug' => 'pma-long-course', 'is_active' => true]);

        $code = $this->referralService->getOrCreateReferralCode($user);
        $this->assertNotEmpty($code);
        $this->assertStringStartsWith('PAK-', $code);

        $link = $this->referralService->getReferralLink($user, $domain);
        $this->assertStringContainsString($domain->slug, $link);
        $this->assertStringContainsString($code, $link);
    }

    public function test_valid_referral_awards_parent_7_days_and_links_child(): void
    {
        $domain = $this->createDomain(['is_active' => true]);
        $parent = User::factory()->create(['registration_ip' => '10.0.0.1']);
        $parentCode = $this->referralService->getOrCreateReferralCode($parent);

        $child = User::factory()->create(['registration_ip' => '10.0.0.2']);

        $result = $this->referralService->processReferral(
            childUser: $child,
            referralCode: $parentCode,
            domain: $domain,
            ipAddress: '10.0.0.2',
            deviceFingerprint: 'device_abc'
        );

        $this->assertSame('success', $result['status']);
        $this->assertSame(7, $result['reward_days']);

        $child->refresh();
        $this->assertSame($parent->id, $child->referred_by_id);

        $referralRecord = Referral::query()
            ->where('referrer_id', $parent->id)
            ->where('referred_user_id', $child->id)
            ->first();

        $this->assertNotNull($referralRecord);
        $this->assertSame('approved', $referralRecord->status);
        $this->assertSame(7, $referralRecord->reward_days);
    }

    public function test_5th_referral_unlocks_30_days_milestone_for_parent(): void
    {
        $domain = $this->createDomain(['is_active' => true]);
        $parent = User::factory()->create(['registration_ip' => '192.168.1.1']);
        $parentCode = $this->referralService->getOrCreateReferralCode($parent);

        // Create 4 prior approved referrals for the parent on this domain
        for ($i = 1; $i <= 4; $i++) {
            $priorChild = User::factory()->create(['registration_ip' => "192.168.1.{$i}0"]);
            $this->referralService->processReferral(
                childUser: $priorChild,
                referralCode: $parentCode,
                domain: $domain,
                ipAddress: "192.168.1.{$i}0",
                deviceFingerprint: "device_{$i}"
            );
        }

        // 5th referral
        $fifthChild = User::factory()->create(['registration_ip' => '192.168.1.50']);
        $fifthResult = $this->referralService->processReferral(
            childUser: $fifthChild,
            referralCode: $parentCode,
            domain: $domain,
            ipAddress: '192.168.1.50',
            deviceFingerprint: 'device_5'
        );

        $this->assertSame('success', $fifthResult['status']);
        $this->assertSame(30, $fifthResult['reward_days']);
        $this->assertSame('30_days_milestone', $fifthResult['milestone']);

        $stats = $this->referralService->getReferralStats($parent, $domain);
        $this->assertSame(5, $stats['referrals_count']);
        $this->assertTrue($stats['is_milestone_reached']);
    }

    public function test_self_referral_attempt_is_blocked_and_flagged_as_abuse(): void
    {
        $domain = $this->createDomain(['is_active' => true]);
        $user = User::factory()->create(['registration_ip' => '10.0.0.100']);
        $code = $this->referralService->getOrCreateReferralCode($user);

        // Attempt to refer own user
        $result = $this->referralService->processReferral(
            childUser: $user,
            referralCode: $code,
            domain: $domain,
            ipAddress: '10.0.0.100',
            deviceFingerprint: 'same_device'
        );

        $this->assertSame('abuse_blocked', $result['status']);

        $flagged = Referral::query()
            ->where('referrer_id', $user->id)
            ->where('referred_user_id', $user->id)
            ->first();

        $this->assertNotNull($flagged);
        $this->assertSame('flagged_abuse', $flagged->status);
        $this->assertSame('self_referral_same_user', $flagged->abuse_reason);
    }

    public function test_matching_ip_referral_is_blocked_and_flagged_as_abuse(): void
    {
        $domain = $this->createDomain(['is_active' => true]);
        $parent = User::factory()->create(['registration_ip' => '203.0.113.195']);
        $code = $this->referralService->getOrCreateReferralCode($parent);

        $child = User::factory()->create(['registration_ip' => '203.0.113.195']);

        $result = $this->referralService->processReferral(
            childUser: $child,
            referralCode: $code,
            domain: $domain,
            ipAddress: '203.0.113.195',
            deviceFingerprint: 'device_xyz'
        );

        $this->assertSame('abuse_blocked', $result['status']);

        $flagged = Referral::query()
            ->where('referrer_id', $parent->id)
            ->where('referred_user_id', $child->id)
            ->first();

        $this->assertNotNull($flagged);
        $this->assertSame('flagged_abuse', $flagged->status);
        $this->assertSame('matching_registration_ip', $flagged->abuse_reason);
    }

    public function test_user_visiting_own_referral_link_triggers_warning_toast(): void
    {
        $domain = $this->createDomain(['is_active' => true, 'requires_subscription' => false]);
        $user = User::factory()->create();
        $code = $this->referralService->getOrCreateReferralCode($user);

        $response = $this->actingAs($user)->get("/d/{$domain->slug}?ref={$code}");

        $response->assertSessionHas('warning', 'Notice: You cannot refer yourself using your own link. Share your referral link with classmates and friends to earn free access!');
    }
}
