<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainFlow extends Model
{
    protected $guarded = [];

    protected $casts = [
        'ui_schema' => 'array',
        'learning_schema' => 'array',
    ];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
