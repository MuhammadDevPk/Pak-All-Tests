<?php

namespace App\Models;

use App\Concerns\CachesTableData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSubscription extends Model
{
    use CachesTableData;

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
}
