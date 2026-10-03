<?php

namespace App\Actions\QuestionBank;

use App\Jobs\VectorizeDomainKnowledgeJob;
use App\Models\ContentModule;
use App\Models\QuestionBank;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StoreGeneratedQuestionsAction
{
    /**
     * Atomically persist parsed/generated questions and attach them across target domains.
     *
     * Supports `mcq` items (options + correct_option_index) and `short_answer`
     * items (model answer). When $dryRun is true, no rows are written: text
     * hashes are still resolved against existing records so the returned
     * inserted/reused counts preview exactly what a real run would do.
     *
     * @param  array<int, array{question_text: string, question_type?: string, options?: array<int, string>, correct_option_index?: int, answer?: string, explanation: ?string, difficulty_level: string, code: ?string}>  $questions
     * @param  array<int, int>  $targetDomainIds
     * @param  array<int, int>  $existingModuleIds
     * @return array{inserted: int, reused: int, total: int, module_ids: array<int, int>}
     */
    public function execute(
        array $questions,
        array $targetDomainIds = [],
        string $moduleStrategy = 'pool',
        ?string $newModuleTitle = null,
        array $existingModuleIds = [],
        bool $dryRun = false
    ): array {
        if (empty($questions)) {
            return ['inserted' => 0, 'reused' => 0, 'total' => 0, 'module_ids' => []];
        }

        $allQuestionIds = [];
        $insertedCount = 0;
        $reusedCount = 0;
        $targetModuleIds = [];

        DB::transaction(function () use (
            $questions,
            $targetDomainIds,
            $moduleStrategy,
            $newModuleTitle,
            $existingModuleIds,
            $dryRun,
            &$allQuestionIds,
            &$insertedCount,
            &$reusedCount,
            &$targetModuleIds
        ) {
            // 1. Resolve target ContentModules based on strategy
            $targetModuleIds = $this->resolveTargetModules($targetDomainIds, $moduleStrategy, $newModuleTitle, $existingModuleIds, $dryRun);

            // 2. Fetch existing questions by normalized text hash to prevent duplication
            $incomingTexts = array_values(array_unique(array_filter(array_map(
                static fn (array $q): string => trim($q['question_text']),
                $questions
            ))));

            $existingMap = $this->buildExistingQuestionsMap($incomingTexts);
            $usedCodes = [];

            // Pre-fetch all incoming codes in a single query to eliminate N+1 round-trips over remote WAN connection
            $rawCodes = array_values(array_unique(array_filter(array_map(
                static fn (array $q): ?string => filled($q['code'] ?? null) ? trim((string) $q['code']) : null,
                $questions
            ))));
            $existingCodes = ! empty($rawCodes)
                ? QuestionBank::whereIn('code', $rawCodes)->pluck('code')->flip()->all()
                : [];

            foreach ($questions as $q) {
                $rawText = trim($q['question_text']);
                if ($rawText === '') {
                    continue;
                }

                $hash = $this->normalizeTextHash($rawText);

                if (isset($existingMap[$hash])) {
                    // Reuse existing QuestionBank record
                    $reusedCount++;
                    if (! $dryRun) {
                        $allQuestionIds[] = $existingMap[$hash];
                    }
                } else {
                    // Create new QuestionBank record quietly (prevents vectorization before pivot attachment)
                    $rawCode = filled($q['code'] ?? null) ? trim((string) $q['code']) : null;
                    $code = null;
                    if ($rawCode !== null && ! isset($usedCodes[$rawCode]) && ! isset($existingCodes[$rawCode])) {
                        $code = $rawCode;
                        $usedCodes[$rawCode] = true;
                    }

                    $questionType = ($q['question_type'] ?? 'mcq') === 'short_answer' ? 'short_answer' : 'mcq';

                    if ($dryRun) {
                        // Reserve the hash so intra-batch duplicates are still
                        // detected, without writing anything to the database.
                        $existingMap[$hash] = -1;
                    } else {
                        $record = QuestionBank::createQuietly([
                            'question_type' => $questionType,
                            'code' => $code,
                            'question_text' => $rawText,
                            'payload' => $this->buildPayload($questionType, $q),
                            'difficulty_level' => in_array($q['difficulty_level'], ['easy', 'medium', 'hard'], true)
                                ? $q['difficulty_level']
                                : 'medium',
                            'explanation' => filled($q['explanation'] ?? null) ? trim((string) $q['explanation']) : null,
                            'is_active' => true,
                        ]);

                        $allQuestionIds[] = $record->id;
                        $existingMap[$hash] = $record->id;
                    }

                    $insertedCount++;
                }
            }

            // 3. Bulk attach questions to target modules using insertOrIgnore (sub-200ms lock)
            if (! $dryRun && ! empty($allQuestionIds) && ! empty($targetModuleIds)) {
                $pivotRows = [];
                foreach ($allQuestionIds as $qId) {
                    foreach ($targetModuleIds as $mId) {
                        $pivotRows[] = [
                            'module_id' => $mId,
                            'question_id' => $qId,
                            'sort_order' => 0,
                            'marks' => 1.00,
                        ];
                    }
                }

                DB::table('module_questions')->insertOrIgnore($pivotRows);
            }
        });

        // 4. Safely dispatch background vectorization now that all domain pivots exist
        if (! $dryRun && config('ai.auto_vectorize', true)) {
            foreach ($allQuestionIds as $questionId) {
                VectorizeDomainKnowledgeJob::dispatch(questionId: $questionId);
            }
        }

        return [
            'inserted' => $insertedCount,
            'reused' => $reusedCount,
            'total' => $dryRun ? ($insertedCount + $reusedCount) : count($allQuestionIds),
            'module_ids' => $targetModuleIds,
        ];
    }

    /**
     * Build the JSONB payload for a question based on its type.
     *
     * @param  array<string, mixed>  $question
     * @return array<string, mixed>
     */
    protected function buildPayload(string $questionType, array $question): array
    {
        if ($questionType === 'short_answer') {
            return ['model_answer' => trim((string) ($question['answer'] ?? ''))];
        }

        return [
            'options' => array_values($question['options'] ?? []),
            'correct_option_index' => (int) ($question['correct_option_index'] ?? 0),
        ];
    }

    /**
     * Resolve or auto-create ContentModules across target domains.
     *
     * @param  array<int, int>  $targetDomainIds
     * @param  array<int, int>  $existingModuleIds
     * @return array<int, int>
     */
    protected function resolveTargetModules(
        array $targetDomainIds,
        string $moduleStrategy,
        ?string $newModuleTitle,
        array $existingModuleIds,
        bool $dryRun = false
    ): array {
        if ($dryRun || $moduleStrategy === 'pool') {
            return [];
        }

        if ($moduleStrategy === 'existing') {
            return array_values(array_filter($existingModuleIds));
        }

        if ($moduleStrategy === 'auto_create') {
            $title = filled($newModuleTitle) ? trim($newModuleTitle) : 'Imported Practice Questions';
            $createdIds = [];

            foreach ($targetDomainIds as $domainId) {
                $module = ContentModule::firstOrCreate(
                    [
                        'domain_id' => $domainId,
                        'title' => $title,
                    ],
                    [
                        'module_type' => 'mock_test',
                        'slug' => Str::slug($title)."-{$domainId}",
                        'is_active' => true,
                        'sort_order' => 1,
                        'content_meta' => [
                            'duration_minutes' => 20,
                            'passing_score' => 60,
                        ],
                    ]
                );

                $createdIds[] = $module->id;
            }

            return $createdIds;
        }

        return [];
    }

    /**
     * Compute a normalized SHA-256 hash of question text for collision-free deduplication.
     * Supports English, Urdu, Arabic, and mathematical symbols without collision.
     */
    protected function normalizeTextHash(string $text): string
    {
        $clean = mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', $text)));

        return hash('sha256', $clean);
    }

    /**
     * Build an in-memory hash-map of existing questions matching the incoming batch.
     *
     * @param  array<int, string>  $incomingTexts
     * @return array<string, int>
     */
    protected function buildExistingQuestionsMap(array $incomingTexts): array
    {
        if (empty($incomingTexts)) {
            return [];
        }

        $lowerTexts = array_values(array_unique(array_filter(array_map(
            static fn (string $t): string => mb_strtolower($t),
            $incomingTexts
        ))));

        $map = [];
        $records = QuestionBank::query()
            ->whereIn(DB::raw('LOWER(question_text)'), $lowerTexts)
            ->select(['id', 'question_text'])
            ->get();

        foreach ($records as $r) {
            $h = $this->normalizeTextHash($r->question_text);
            $map[$h] = $r->id;
        }

        return $map;
    }
}
