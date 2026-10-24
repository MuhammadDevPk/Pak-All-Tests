<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSubscription extends Model
{
    protected $guarded = [];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Domain, $this>
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    /**
     * Scope query to only include active, non-expired subscriptions.
     *
     * @param  Builder<UserSubscription>  $query
     * @return Builder<UserSubscription>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where(function (Builder $q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope query to pending verification subscriptions.
     *
     * @param  Builder<UserSubscription>  $query
     * @return Builder<UserSubscription>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope query to rejected subscriptions.
     *
     * @param  Builder<UserSubscription>  $query
     * @return Builder<UserSubscription>
     */
    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope query to expired subscriptions.
     *
     * @param  Builder<UserSubscription>  $query
     * @return Builder<UserSubscription>
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('status', 'expired')
                ->orWhere(function (Builder $sub) {
                    $sub->where('status', 'active')
                        ->whereNotNull('expires_at')
                        ->where('expires_at', '<=', now());
                });
        });
    }

    /**
     * Check if the subscription is currently active and non-expired.
     */
    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->expires_at === null) {
            return true;
        }

        return $this->expires_at->isFuture();
    }

    /**
     * Check if the subscription has expired.
     */
    public function isExpired(): bool
    {
        if ($this->status === 'expired') {
            return true;
        }

        return $this->status === 'active' && $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Check if the subscription is pending verification.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the subscription was rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
