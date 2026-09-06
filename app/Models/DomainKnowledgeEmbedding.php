<?php

namespace App\Models;

use App\Services\Ai\EmbeddingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $domain_id
 * @property int|null $content_module_id
 * @property int|null $question_bank_id
 * @property string $source_type
 * @property string|null $title
 * @property string $content
 * @property string $embedding
 * @property array|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property float|null $similarity
 */
class DomainKnowledgeEmbedding extends Model
{
    protected $table = 'domain_knowledge_embeddings';

    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Domain, $this>
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    /**
     * @return BelongsTo<ContentModule, $this>
     */
    public function contentModule(): BelongsTo
    {
        return $this->belongsTo(ContentModule::class);
    }

    /**
     * @return BelongsTo<QuestionBank, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'question_bank_id');
    }

    /**
     * Scope query to a specific domain.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForDomain(Builder $query, int $domainId): Builder
    {
        return $query->where('domain_id', $domainId);
    }

    /**
     * Scope query by source type (e.g. 'question', 'content_module', 'syllabus', 'rubric').
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOfType(Builder $query, string $sourceType): Builder
    {
        return $query->where('source_type', $sourceType);
    }

    /**
     * Order by cosine similarity to a given query vector using PostgreSQL pgvector <=> operator.
     * Selects calculated similarity score (1.0 = identical, 0.0 = orthogonal).
     *
     * @param  Builder<static>  $query
     * @param  array<int, float>  $vector
     * @return Builder<static>
     */
    public function scopeNearestCosine(Builder $query, array $vector, int $limit = 5): Builder
    {
        $vectorSql = EmbeddingService::vectorToSql($vector);

        return $query
            ->select('domain_knowledge_embeddings.*')
            ->selectRaw('(1 - (embedding <=> ?::vector)) as similarity', [$vectorSql])
            ->orderByRaw('embedding <=> ?::vector ASC', [$vectorSql])
            ->limit($limit);
    }
}
