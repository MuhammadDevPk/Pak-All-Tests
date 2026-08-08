<?php

namespace App\Models;

use App\Concerns\CachesTableData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ContentModule extends Model
{
    use CachesTableData;

    protected $guarded = [];

    protected $casts = [
        'content_meta' => 'array',
    ];

    /**
     * @return BelongsTo<Domain, $this>
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    /**
     * @return BelongsToMany<QuestionBank, $this>
     */
    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(QuestionBank::class, 'module_questions', 'module_id', 'question_id')
            ->withPivot(['sort_order', 'marks'])
            ->orderByPivot('sort_order', 'asc');
    }
}
