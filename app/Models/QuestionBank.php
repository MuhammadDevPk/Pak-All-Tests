<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class QuestionBank extends Model
{

    protected $table = 'question_bank';

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
    ];

    /**
     * @return BelongsToMany<ContentModule, $this>
     */
    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(ContentModule::class, 'module_questions', 'question_id', 'module_id')
            ->withPivot(['sort_order', 'marks']);
    }
}
