<?php

namespace App\Services\Ai;

use App\Models\ContentModule;
use App\Models\Domain;
use App\Models\DomainKnowledgeEmbedding;
use App\Models\QuestionBank;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;

class KnowledgeVectorizationService
{
    public function __construct(
        protected EmbeddingService $embeddingService
    ) {}

    /**
     * Vectorize a single question for all associated domains or a specified domain.
     *
     * @return array<int, DomainKnowledgeEmbedding>
     */
    public function vectorizeQuestion(QuestionBank $question, ?int $domainId = null): array
    {
        $domainIds = $domainId
            ? [$domainId]
            : $question->modules()->pluck('domain_id')->unique()->filter()->values()->all();

        if (empty($domainIds)) {
            return [];
        }

        $formattedContent = $this->formatQuestionText($question);
        $vector = $this->embeddingService->embed($formattedContent);
        $vectorSql = EmbeddingService::vectorToSql($vector);

        $results = [];

        foreach ($domainIds as $targetDomainId) {
            $record = DomainKnowledgeEmbedding::updateOrCreate(
                [
                    'domain_id' => $targetDomainId,
                    'question_bank_id' => $question->id,
                    'source_type' => 'question',
                ],
                [
                    'content_module_id' => $question->modules()->where('domain_id', $targetDomainId)->value('content_modules.id'),
                    'title' => $question->code ?? "Question #{$question->id}",
                    'content' => $formattedContent,
                    'embedding' => $vectorSql,
                    'metadata' => [
                        'code' => $question->code,
                        'question_type' => $question->question_type,
                        'difficulty_level' => $question->difficulty_level,
                    ],
                ]
            );

            $results[] = $record;
        }

        if ($domainId === null) {
            DomainKnowledgeEmbedding::where('question_bank_id', $question->id)
                ->whereNotIn('domain_id', $domainIds)
                ->delete();
        }

        return $results;
    }

    /**
     * Vectorize a content module (syllabus guidelines, descriptions, and metadata).
     */
    public function vectorizeModule(ContentModule $module): ?DomainKnowledgeEmbedding
    {
        if (! $module->domain_id) {
            return null;
        }

        $formattedContent = $this->formatModuleText($module);
        $vector = $this->embeddingService->embed($formattedContent);
        $vectorSql = EmbeddingService::vectorToSql($vector);

        $sourceType = match ($module->module_type) {
            'subjective' => 'rubric',
            'pdf_repo' => 'syllabus',
            default => 'content_module',
        };

        return DomainKnowledgeEmbedding::updateOrCreate(
            [
                'domain_id' => $module->domain_id,
                'content_module_id' => $module->id,
                'source_type' => $sourceType,
            ],
            [
                'question_bank_id' => null,
                'title' => $module->title,
                'content' => $formattedContent,
                'embedding' => $vectorSql,
                'metadata' => $module->content_meta ?? [],
            ]
        );
    }

    /**
     * Batch vectorize all knowledge artifacts (content modules + questions) for a domain.
     * Generates embeddings in single batch roundtrips for optimal memory and network performance.
     *
     * @return array{domain_id: int, modules_count: int, questions_count: int, embeddings_count: int}
     */
    public function vectorizeDomain(Domain $domain): array
    {
        $modules = $domain->contentModules()->with('questions')->get();

        $chunks = [];

        // 1. Prepare module chunks
        foreach ($modules as $module) {
            $sourceType = match ($module->module_type) {
                'subjective' => 'rubric',
                'pdf_repo' => 'syllabus',
                default => 'content_module',
            };

            $chunks[] = [
                'type' => 'module',
                'entity' => $module,
                'domain_id' => $domain->id,
                'content_module_id' => $module->id,
                'question_bank_id' => null,
                'source_type' => $sourceType,
                'title' => $module->title,
                'content' => $this->formatModuleText($module),
                'metadata' => $module->content_meta ?? [],
            ];
        }

        // 2. Prepare question chunks (deduplicated by question id)
        $seenQuestionIds = [];
        $uniqueQuestions = [];

        foreach ($modules as $module) {
            foreach ($module->questions as $question) {
                if (! isset($seenQuestionIds[$question->id])) {
                    $seenQuestionIds[$question->id] = true;
                    $uniqueQuestions[] = $question;

                    $chunks[] = [
                        'type' => 'question',
                        'entity' => $question,
                        'domain_id' => $domain->id,
                        'content_module_id' => $module->id,
                        'question_bank_id' => $question->id,
                        'source_type' => 'question',
                        'title' => $question->code ?? "Question #{$question->id}",
                        'content' => $this->formatQuestionText($question),
                        'metadata' => [
                            'code' => $question->code,
                            'question_type' => $question->question_type,
                            'difficulty_level' => $question->difficulty_level,
                        ],
                    ];
                }
            }
        }

        if (empty($chunks)) {
            return [
                'domain_id' => $domain->id,
                'modules_count' => 0,
                'questions_count' => 0,
                'embeddings_count' => 0,
            ];
        }

        // 3. Batch generate embeddings
        $allTexts = array_column($chunks, 'content');
        $vectors = $this->embeddingService->embedBatch($allTexts);

        $embeddingsCount = 0;

        // 4. Batch upsert records inside a database transaction
        DB::transaction(function () use ($chunks, $vectors, &$embeddingsCount, $domain, $modules, $uniqueQuestions) {
            foreach ($chunks as $index => $chunk) {
                $vector = $vectors[$index] ?? null;
                if (! $vector) {
                    continue;
                }

                $vectorSql = EmbeddingService::vectorToSql($vector);

                $matchCriteria = [
                    'domain_id' => $chunk['domain_id'],
                    'source_type' => $chunk['source_type'],
                ];

                if ($chunk['type'] === 'module') {
                    $matchCriteria['content_module_id'] = $chunk['content_module_id'];
                } else {
                    $matchCriteria['question_bank_id'] = $chunk['question_bank_id'];
                }

                DomainKnowledgeEmbedding::updateOrCreate(
                    $matchCriteria,
                    [
                        'content_module_id' => $chunk['content_module_id'],
                        'question_bank_id' => $chunk['question_bank_id'],
                        'title' => $chunk['title'],
                        'content' => $chunk['content'],
                        'embedding' => $vectorSql,
                        'metadata' => $chunk['metadata'],
                    ]
                );

                $embeddingsCount++;
            }

            // Clean up obsolete embeddings that were removed or unlinked from this domain
            $currentQuestionIds = array_map(fn ($q) => $q->id, $uniqueQuestions);
            $currentModuleIds = $modules->pluck('id')->all();

            DomainKnowledgeEmbedding::where('domain_id', $domain->id)
                ->where(function ($q) use ($currentQuestionIds, $currentModuleIds) {
                    $q->where(function ($sub) use ($currentQuestionIds) {
                        $sub->where('source_type', 'question')
                            ->whereNotIn('question_bank_id', $currentQuestionIds ?: [0]);
                    })->orWhere(function ($sub) use ($currentModuleIds) {
                        $sub->where('source_type', '!=', 'question')
                            ->whereNotIn('content_module_id', $currentModuleIds ?: [0]);
                    });
                })
                ->delete();
        });

        return [
            'domain_id' => $domain->id,
            'modules_count' => $modules->count(),
            'questions_count' => count($uniqueQuestions),
            'embeddings_count' => $embeddingsCount,
        ];
    }

    /**
     * Perform domain-scoped nearest neighbor vector similarity search using PostgreSQL pgvector.
     * Supports branch-wide hierarchical queries (ancestors) and similarity confidence gating.
     *
     * @return EloquentCollection<int, DomainKnowledgeEmbedding>
     */
    public function findSimilar(
        int $domainId,
        string $queryText,
        int $limit = 5,
        ?string $sourceType = null,
        float $minSimilarity = 0.0,
        bool $includeHierarchy = false
    ): EloquentCollection {
        $queryVector = $this->embeddingService->embed($queryText);

        $domainIds = [$domainId];
        if ($includeHierarchy) {
            $domain = Domain::find($domainId);
            if ($domain && $domain->path) {
                $ancestorIds = array_map('intval', explode('.', (string) $domain->path));
                $domainIds = array_values(array_unique(array_merge($domainIds, $ancestorIds)));
            }
        }

        $query = DomainKnowledgeEmbedding::query()
            ->whereIn('domain_id', $domainIds);

        if ($sourceType) {
            $query->ofType($sourceType);
        }

        $results = $query->nearestCosine($queryVector, $limit)->get();

        if ($minSimilarity > 0.0) {
            $filtered = $results->filter(fn ($item) => ((float) ($item->similarity ?? 0.0)) >= $minSimilarity)->values();

            return new EloquentCollection($filtered->all());
        }

        return $results;
    }

    /**
     * Format question payload into rich semantic prose for vectorization.
     */
    protected function formatQuestionText(QuestionBank $question): string
    {
        $prompt = trim(strip_tags($question->question_text));
        $lines = ["[QUESTION] {$prompt}"];

        $payload = $question->payload ?? [];

        if (! empty($payload['options']) && is_array($payload['options'])) {
            $lines[] = 'Options:';
            foreach ($payload['options'] as $idx => $opt) {
                $letter = chr(65 + (int) $idx);
                $optText = is_array($opt) ? ($opt['text'] ?? '') : (string) $opt;
                $lines[] = "  {$letter}: {$optText}";
            }
        }

        if (! empty($question->explanation)) {
            $explanation = trim(strip_tags($question->explanation));
            $lines[] = "Explanation: {$explanation}";
        }

        $lines[] = "Classification: Type={$question->question_type}, Difficulty={$question->difficulty_level}, Code={$question->code}";

        return implode("\n", $lines);
    }

    /**
     * Format module metadata into rich semantic prose for vectorization.
     */
    protected function formatModuleText(ContentModule $module): string
    {
        $lines = [
            "[MODULE] {$module->title}",
            "Type: {$module->module_type}",
        ];

        $meta = $module->content_meta ?? [];

        if (! empty($meta['description'])) {
            $lines[] = 'Description: '.trim(strip_tags((string) $meta['description']));
        }

        if (! empty($meta['instructions'])) {
            $lines[] = 'Instructions: '.trim(strip_tags((string) $meta['instructions']));
        }

        if (! empty($meta['file_type'])) {
            $lines[] = "Format: {$meta['file_type']}";
        }

        if (! empty($meta['duration_minutes'])) {
            $lines[] = "Duration: {$meta['duration_minutes']} minutes";
        }

        return implode("\n", $lines);
    }
}
