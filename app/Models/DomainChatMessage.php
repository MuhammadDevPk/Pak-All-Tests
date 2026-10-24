<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $domain_id
 * @property string $role
 * @property string $content
 * @property array<string, mixed>|null $context_metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class DomainChatMessage extends Model
{
    protected $table = 'domain_chat_messages';

    protected $guarded = [];

    protected $casts = [
        'context_metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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
     * Scope to messages for a specific candidate and domain.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForUserAndDomain(Builder $query, int $userId, int $domainId): Builder
    {
        return $query->where('user_id', $userId)
            ->where('domain_id', $domainId);
    }
}
