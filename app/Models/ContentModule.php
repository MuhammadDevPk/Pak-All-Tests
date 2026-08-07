<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ContentModule extends Model
{
    protected $guarded = [];

    protected $casts = [
        'content_meta' => 'array',
    ];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    // Connects to the reusable question bank
    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(QuestionBank::class, 'module_questions', 'module_id', 'question_id')
            ->withPivot(['sort_order', 'marks'])
            ->orderByPivot('sort_order', 'asc');
    }
}
