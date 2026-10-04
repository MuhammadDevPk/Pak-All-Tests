<?php

namespace App\Models;

use App\Concerns\CachesTableData;
use App\Jobs\VectorizeDomainKnowledgeJob;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentModule extends Model
{
    use CachesTableData;

    protected $guarded = [];

    protected $casts = [
        'content_meta' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (ContentModule $module) {
            if ((int) ($module->sort_order ?? 0) <= 0 && $module->domain_id) {
                $module->sort_order = (static::where('domain_id', $module->domain_id)->max('sort_order') ?? 0) + 1;
            }
        });

        static::deleted(function (ContentModule $module) {
            if ($module->domain_id) {
                static::reorderDomainModules($module->domain_id);
            }
        });

        static::saved(function (ContentModule $module) {
            if (config('ai.auto_vectorize', true) && $module->domain_id && $module->is_active) {
                VectorizeDomainKnowledgeJob::dispatch(moduleId: $module->id);
            }
        });
    }

    /**
     * Compact and re-index sort_order for all modules in a domain sequentially (1, 2, 3...).
     */
    public static function reorderDomainModules(?int $domainId): void
    {
        if (! $domainId) {
            return;
        }

        $modules = static::where('domain_id', $domainId)
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        foreach ($modules as $index => $item) {
            $expected = $index + 1;
            if ($item->sort_order !== $expected) {
                $item->updateQuietly(['sort_order' => $expected]);
            }
        }
    }

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

    /**
     * @return HasMany<DomainKnowledgeEmbedding, $this>
     */
    public function knowledgeEmbeddings(): HasMany
    {
        return $this->hasMany(DomainKnowledgeEmbedding::class);
    }

    /**
     * Resolve the candidate-facing display/player mode.
     */
    public function resolveDisplayMode(): string
    {
        if (filled($this->display_mode) && $this->display_mode !== 'auto') {
            return (string) $this->display_mode;
        }

        if ($this->module_type === 'pdf_repo') {
            return 'pdf_repo';
        }

        if ($this->relationLoaded('questions') && $this->questions->isNotEmpty()) {
            $onlyShortAnswer = $this->questions->every(
                fn (mixed $q): bool => ($q->question_type ?? '') === 'short_answer'
            );

            if ($onlyShortAnswer) {
                return 'study_qa';
            }
        }

        return 'mcq_test';
    }
}
