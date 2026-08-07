<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class Domain extends Model
{
    protected $guarded = [];

    // Gets the parent domain (e.g., gets "Army" if this is "PMA")
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Domain::class, 'parent_id');
    }

    // Gets the subdomains (e.g., gets "PMA", "Navy" if this is "Armed Forces")
    public function children(): HasMany
    {
        return $this->hasMany(Domain::class, 'parent_id');
    }

    // Links to the dynamic UI schema for this specific test
    public function flow(): HasOne
    {
        return $this->hasOne(DomainFlow::class);
    }

    // Links to all PDFs, Tests, and Content for this domain
    public function contentModules(): HasMany
    {
        return $this->hasMany(ContentModule::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

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

        // 1. Triggered right AFTER a new domain is inserted into the DB
        static::created(function ($domain) {
            if ($domain->parent_id) {
                $parent = Domain::find($domain->parent_id);
                $domain->path = $parent->path.'.'.$domain->id;
            } else {
                $domain->path = (string) $domain->id;
            }
            $domain->saveQuietly();
        });

        static::updated(function ($domain) {
            // Only execute if the Admin actually moved the category to a new parent
            if ($domain->wasChanged('parent_id')) {
                // A. Capture the exact path BEFORE the update happened
                $oldPath = $domain->getOriginal('path');

                if ($domain->parent_id) {
                    $parent = Domain::find($domain->parent_id);
                    $newPath = $parent->path.'.'.$domain->id;
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
