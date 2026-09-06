<?php

namespace App\Models;

use App\Jobs\VectorizeDomainKnowledgeJob;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionBank extends Model
{
    protected $table = 'question_bank';

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
    ];

    protected static function booted(): void
    {
        static::saved(function (QuestionBank $question) {
            if (config('ai.auto_vectorize', true) && $question->is_active) {
                VectorizeDomainKnowledgeJob::dispatch(questionId: $question->id);
            }
        });
    }

    /**
     * @return BelongsToMany<ContentModule, $this>
     */
    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(ContentModule::class, 'module_questions', 'question_id', 'module_id')
            ->withPivot(['sort_order', 'marks']);
    }

    /**
     * @return HasMany<DomainKnowledgeEmbedding, $this>
     */
    public function knowledgeEmbeddings(): HasMany
    {
        return $this->hasMany(DomainKnowledgeEmbedding::class, 'question_bank_id');
    }
}
