<?php

namespace App\Models;

use App\Concerns\CachesTableData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainFlow extends Model
{
    use CachesTableData;

    protected $guarded = [];

    protected $casts = [
        'ui_schema' => 'array',
        'learning_schema' => 'array',
    ];

    /**
     * @return BelongsTo<Domain, $this>
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
