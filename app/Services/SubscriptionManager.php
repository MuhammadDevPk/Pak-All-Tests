<?php

namespace App\Services;

use App\Models\Domain;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Models\UserSubscription;
use Carbon\Carbon;

class SubscriptionManager
{
    /**
     * Determine if a user holds an active, non-expired subscription for a domain.
     * Evaluates suspension, global free bypass, domain free bypass, and guest passes.
     */
    public function hasActiveAccess(User $user, Domain $domain): bool
    {
        // 0. Account Suspension Check
        if ($user->is_suspended) {
            return false;
        }

        // 1. Global Platform Override (1-click Admin master switch)
        if (PlatformSetting::isAllDomainsFree()) {
            return true;
        }

        // 2. Per-Domain Free Toggle
        if ($domain->requires_subscription === false) {
            return true;
        }

        // 3. Active 24-Hour Guest Access
        if ($user->is_guest && $user->guest_expires_at?->isFuture()) {
            return true;
        }

        // 4. Standard Active Subscription / Free Trial
        return $this->getActiveSubscription($user, $domain) !== null;
    }

    /**
     * Retrieve the active subscription for a user and domain.
     */
    public function getActiveSubscription(User $user, Domain $domain): ?UserSubscription
    {
        return $user->subscriptions()
            ->where('domain_id', $domain->id)
            ->active()
            ->first();
    }

    /**
     * Retrieve the latest subscription for a user and domain regardless of status.
     */
    public function getLatestSubscription(User $user, Domain $domain): ?UserSubscription
    {
        return $user->subscriptions()
            ->where('domain_id', $domain->id)
            ->latest('id')
            ->first();
    }

    /**
     * Transition a subscription to active status and configure validity duration.
     */
    public function activate(UserSubscription $subscription, ?Carbon $expiresAt = null): UserSubscription
    {
        $subscription->update([
            'status' => 'active',
            'starts_at' => now(),
            'expires_at' => $expiresAt ?? now()->addYear(),
            'rejection_reason' => null,
        ]);

        return $subscription;
    }

    /**
     * Transition a subscription to rejected status and record rationale.
     */
    public function reject(UserSubscription $subscription, string $reason): UserSubscription
    {
        $subscription->update([
            'status' => 'rejected',
            'rejection_reason' => trim($reason),
        ]);

        return $subscription;
    }

    /**
     * Batch-expire all active subscriptions that have passed their expiration timestamp.
     * Executes in 1 atomic SQL query.
     */
    public function expireLapsedSubscriptions(): int
    {
        return UserSubscription::query()
            ->where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update([
                'status' => 'expired',
            ]);
    }

    /**
     * Provision an initial free trial for a user on a domain.
     */
    public function grantFreeTrial(User $user, Domain $domain, int $days = 7): UserSubscription
    {
        /** @var UserSubscription $subscription */
        $subscription = UserSubscription::query()->firstOrNew([
            'user_id' => $user->id,
            'domain_id' => $domain->id,
        ]);

        $subscription->fill([
            'status' => 'active',
            'payment_method' => 'free_trial',
            'starts_at' => now(),
            'expires_at' => now()->addDays($days),
            'rejection_reason' => null,
        ])->save();

        return $subscription;
    }

    /**
     * Extend a user's subscription on a domain by a specified number of days.
     */
    public function extendSubscription(User $user, Domain $domain, int $additionalDays): UserSubscription
    {
        /** @var UserSubscription $subscription */
        $subscription = UserSubscription::query()->firstOrNew([
            'user_id' => $user->id,
            'domain_id' => $domain->id,
        ]);

        $baseExpiry = ($subscription->exists && $subscription->expires_at && $subscription->expires_at->isFuture())
            ? $subscription->expires_at
            : now();

        $subscription->fill([
            'status' => 'active',
            'payment_method' => $subscription->payment_method ?? 'referral_reward',
            'starts_at' => $subscription->starts_at ?? now(),
            'expires_at' => $baseExpiry->copy()->addDays($additionalDays),
            'rejection_reason' => null,
        ])->save();

        return $subscription;
    }
}
