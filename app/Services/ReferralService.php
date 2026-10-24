<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Domain;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Support\Str;

class ReferralService
{
    public function __construct(
        protected SubscriptionManager $subscriptionManager
    ) {}

    /**
     * Retrieve or generate a deterministic uppercase referral code for a user.
     */
    public function getOrCreateReferralCode(User $user): string
    {
        if ($user->referral_code) {
            return $user->referral_code;
        }

        $code = 'PAK-'.strtoupper(Str::random(6));
        while (User::query()->where('referral_code', $code)->exists()) {
            $code = 'PAK-'.strtoupper(Str::random(6));
        }

        $user->update(['referral_code' => $code]);

        return $code;
    }

    /**
     * Build the shareable referral link for a user and domain.
     */
    public function getReferralLink(User $user, ?Domain $domain = null): string
    {
        $code = $this->getOrCreateReferralCode($user);

        if ($domain) {
            return route('domain.dashboard', ['domainSlug' => $domain->slug, 'ref' => $code]);
        }

        return url("/register?ref={$code}");
    }

    /**
     * Process an incoming referral event when a child user registers.
     *
     * @return array{status: string, message?: string, reward_days?: int, milestone?: string|null}
     */
    public function processReferral(
        User $childUser,
        string $referralCode,
        Domain $domain,
        ?string $ipAddress = null,
        ?string $deviceFingerprint = null
    ): array {
        /** @var User|null $parent */
        $parent = User::query()->where('referral_code', strtoupper(trim($referralCode)))->first();

        if (! $parent) {
            return [
                'status' => 'invalid_code',
                'message' => 'The referral link could not be verified.',
            ];
        }

        // Anti-Abuse 1: Parent cannot refer themselves
        if ($parent->id === $childUser->id) {
            Referral::create([
                'referrer_id' => $parent->id,
                'referred_user_id' => $childUser->id,
                'domain_id' => $domain->id,
                'reward_days' => 0,
                'status' => 'flagged_abuse',
                'abuse_reason' => 'self_referral_same_user',
                'ip_address' => $ipAddress,
                'device_fingerprint' => $deviceFingerprint,
            ]);

            return [
                'status' => 'abuse_blocked',
                'message' => 'Self-referrals are prohibited. Continued abuse will lead to account suspension.',
            ];
        }

        // Anti-Abuse 2: Matching registration IP address heuristic (exempt loopback addresses in development)
        $isLoopback = in_array($ipAddress, ['127.0.0.1', '::1', 'localhost'], true);
        if (! $isLoopback && $ipAddress !== null && $parent->registration_ip !== null && $parent->registration_ip === $ipAddress) {
            Referral::create([
                'referrer_id' => $parent->id,
                'referred_user_id' => $childUser->id,
                'domain_id' => $domain->id,
                'reward_days' => 0,
                'status' => 'flagged_abuse',
                'abuse_reason' => 'matching_registration_ip',
                'ip_address' => $ipAddress,
                'device_fingerprint' => $deviceFingerprint,
            ]);

            return [
                'status' => 'abuse_blocked',
                'message' => 'Multiple accounts from the same network cannot be credited for referrals.',
            ];
        }

        // Idempotency: Prevent duplicate credits for the same child registration on the platform
        $alreadyReferred = Referral::query()
            ->where('referred_user_id', $childUser->id)
            ->exists();

        if ($alreadyReferred) {
            return [
                'status' => 'already_processed',
                'message' => 'This referral has already been credited.',
            ];
        }

        // Count parent's previous valid referrals across platform
        $approvedCount = Referral::query()
            ->where('referrer_id', $parent->id)
            ->approved()
            ->count();

        // Referrals 1 to 4: +7 days each; 5th Referral Milestone: 30 days access
        if ($approvedCount === 4) {
            $rewardDays = 30;
            $milestone = '30_days_milestone';
        } else {
            $rewardDays = 7;
            $milestone = null;
        }

        // Credit Parent's subscription across all active domains (so the parent unlocks the platform)
        $activeDomains = Domain::query()->where('is_active', true)->get();
        foreach ($activeDomains as $activeDomain) {
            $this->subscriptionManager->extendSubscription($parent, $activeDomain, $rewardDays);
        }

        // Record approved referral
        Referral::create([
            'referrer_id' => $parent->id,
            'referred_user_id' => $childUser->id,
            'domain_id' => $domain->id,
            'reward_days' => $rewardDays,
            'milestone_unlocked' => $milestone,
            'status' => 'approved',
            'ip_address' => $ipAddress,
            'device_fingerprint' => $deviceFingerprint,
        ]);

        $childUser->update(['referred_by_id' => $parent->id]);

        return [
            'status' => 'success',
            'reward_days' => $rewardDays,
            'milestone' => $milestone,
        ];
    }

    /**
     * Compile candidate referral statistics for the Domain Dashboard widget.
     *
     * @return array<string, mixed>
     */
    public function getReferralStats(User $user, ?Domain $domain = null): array
    {
        $code = $this->getOrCreateReferralCode($user);
        $link = $this->getReferralLink($user, $domain);

        $approvedCount = Referral::query()
            ->where('referrer_id', $user->id)
            ->approved()
            ->count();

        $daysEarned = (int) Referral::query()
            ->where('referrer_id', $user->id)
            ->approved()
            ->sum('reward_days');

        $referredFriends = Referral::query()
            ->where('referrer_id', $user->id)
            ->approved()
            ->with('referredUser:id,name,email,created_at')
            ->latest()
            ->take(10)
            ->get()
            ->map(fn (Referral $r) => [
                'id' => $r->id,
                'name' => $r->referredUser->name,
                'reward_days' => $r->reward_days,
                'joined_at' => $r->created_at?->diffForHumans() ?? 'Recently',
            ])
            ->values()
            ->all();

        return [
            'referral_code' => $code,
            'referral_link' => $link,
            'referrals_count' => $approvedCount,
            'days_earned' => $daysEarned,
            'milestone_target' => 5,
            'is_milestone_reached' => $approvedCount >= 5,
            'remaining_for_milestone' => max(0, 5 - $approvedCount),
            'milestone_reward_label' => '30 Days Full Access',
            'recent_referrals' => $referredFriends,
        ];
    }
}
