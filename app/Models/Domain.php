<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
            ->withPivot(['status', 'receipt_url'])
            ->withTimestamps();
    }

}
