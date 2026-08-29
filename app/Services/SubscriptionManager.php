<?php

namespace App\Services;

use App\Models\Domain;
use App\Models\User;
use App\Models\UserSubscription;
use Carbon\Carbon;

class SubscriptionManager
{
    /**
     * Determine if a user holds an active, non-expired subscription for a domain.
     */
    public function hasActiveAccess(User $user, Domain $domain): bool
    {
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
}
