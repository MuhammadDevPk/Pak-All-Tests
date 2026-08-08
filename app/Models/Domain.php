<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property int|null $parent_id
 * @property string $name
 * @property string $slug
 * @property string|null $code
 * @property string|null $path
 * @property bool $is_active
 */
class Domain extends Model
{
    protected $guarded = [];

    /**
     * @return BelongsTo<Domain, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Domain::class, 'parent_id');
    }

    /**
     * @return HasMany<Domain, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Domain::class, 'parent_id');
    }

    /**
     * @return HasOne<DomainFlow, $this>
     */
    public function flow(): HasOne
    {
        return $this->hasOne(DomainFlow::class);
    }

    /**
     * @return HasMany<ContentModule, $this>
     */
    public function contentModules(): HasMany
    {
        return $this->hasMany(ContentModule::class);
    }

    /**
     * @return HasMany<UserSubscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function subscribers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_subscriptions', 'domain_id', 'user_id')
            ->withPivot([
                'status',
                'receipt_url',
                'payment_method',
                'transaction_id',
                'rejection_reason',
                'starts_at',
                'expires_at',
            ])
            ->withTimestamps();
    }


    protected static function booted()
    {

        static::created(function (Domain $domain) {
            if ($domain->parent_id && $domain->parent) {
                $domain->path = $domain->parent->path.'.'.$domain->id;
            } else {
                $domain->path = (string) $domain->id;
            }
            $domain->saveQuietly();
        });

        static::updated(function (Domain $domain) {
            // Only execute if the Admin actually moved the category to a new parent
            if ($domain->wasChanged('parent_id')) {
                // A. Capture the exact path BEFORE the update happened
                $oldPath = $domain->getOriginal('path');

                if ($domain->parent_id && $domain->parent) {
                    $newPath = $domain->parent->path.'.'.$domain->id;
                } else {
                    $newPath = (string) $domain->id;
                }

                $domain->path = $newPath;
                $domain->saveQuietly();

                if ($oldPath) {
                    // SAFE: Uses positional ? bindings throughout — zero string interpolation.
                    // The <@ operator ("is descendant of") is backed by the GiST index for speed.
                    // subpath(path, nlevel(old)) trims the old prefix; prepending $newPath rebuilds the correct path.
                    DB::statement('
                        UPDATE domains
                        SET    path       = ?::ltree || subpath(path, nlevel(?::ltree)),
                               updated_at = NOW()
                        WHERE  path <@ ?::ltree
                        AND    id != ?
                    ', [$newPath, $oldPath, $oldPath, $domain->id]);
                }
            }
        });

    }
}
