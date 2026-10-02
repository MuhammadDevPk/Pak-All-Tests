<?php

namespace App\Jobs;

use App\Actions\QuestionBank\StoreGeneratedQuestionsAction;
use App\Models\ContentModule;
use App\Models\Domain;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Bulk question import from an uploaded JSON file.
 *
 * For every subject in the file's `subjects` map the job finds-or-creates a
 * `preparation` ContentModule directly under the chosen domain (modules are
 * flat — ContentModule has no parent_id), then inserts that subject's questions
 * in batches of 50 through StoreGeneratedQuestionsAction (hash dedup + code
 * uniqueness are inherited). Supports `mcq` and `short_answer` items.
 *
 * In dry-run mode nothing is written: module creation, question inserts,
 * pivot attachments and vectorization dispatches are all skipped, and the
 * report previews exactly what a real run would do.
 */
class ImportQuestionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 1800;

    public const BATCH_SIZE = 50;

    public const CACHE_TTL_SECONDS = 600;

    public function __construct(
        public string $filePath,
        public int $domainId,
        public bool $dryRun,
        public ?int $adminUserId,
        public string $reportKey,
    ) {}

    /**
     * Validate the decoded import payload structure (file-level, before dispatch).
     *
     * @param  mixed  $data
     * @return array{valid: bool, errors: array<int, string>, subjects: array<string, string>, questions: array<int, mixed>}
     */
    public static function validatePayload(mixed $data): array
    {
        $errors = [];

        if (! is_array($data)) {
            return ['valid' => false, 'errors' => ['Payload must be a JSON object.'], 'subjects' => [], 'questions' => []];
        }

        $subjects = $data['subjects'] ?? null;
        if (! is_array($subjects) || $subjects === []) {
            $errors[] = 'Missing or empty "subjects" map (subject_key => module title).';
            $subjects = [];
        } else {
            foreach ($subjects as $key => $title) {
                if (! is_string($key) || $key === '' || ! is_string($title) || trim($title) === '') {
                    $errors[] = 'Each subject entry must map a non-empty key to a non-empty module title.';
                    break;
                }
            }
        }

        $questions = $data['questions'] ?? null;
        if (! is_array($questions)) {
            $errors[] = 'Missing "questions" array.';
            $questions = [];
        }

        return [
            'valid' => $errors === [],
            'errors' => $errors,
            'subjects' => $subjects,
            'questions' => $questions,
        ];
    }

    /**
     * Validate a single question item and normalize it for StoreGeneratedQuestionsAction.
     *
     * @param  mixed  $item
     * @param  array<string, string>  $subjects
     * @return array{valid: bool, error: ?string, normalized: ?array<string, mixed>}
     */
    public static function validateQuestion(mixed $item, array $subjects, int $index): array
    {
        $fail = static fn (string $reason): array => [
            'valid' => false,
            'error' => "Question #{$index}: {$reason}",
            'normalized' => null,
        ];

        if (! is_array($item)) {
            return $fail('must be an object.');
        }

        $subject = $item['subject'] ?? null;
        if (! is_string($subject) || ! isset($subjects[$subject])) {
            return $fail('has an unknown or missing "subject" key.');
        }

        $type = $item['type'] ?? null;
        if (! in_array($type, ['mcq', 'short_answer'], true)) {
            return $fail('has an unknown or missing "type" (expected "mcq" or "short_answer").');
        }

        $questionText = trim((string) ($item['question'] ?? ''));
        if ($questionText === '') {
            return $fail('is missing the "question" text.');
        }

        $normalized = [
            'question_text' => $questionText,
            'question_type' => $type,
            'code' => isset($item['code']) && trim((string) $item['code']) !== '' ? trim((string) $item['code']) : null,
            'difficulty_level' => 'medium',
            'explanation' => null,
        ];

        if ($type === 'mcq') {
            $options = $item['options'] ?? null;
            if (! is_array($options) || count($options) !== 4) {
                return $fail('mcq items need exactly 4 "options".');
            }
            foreach ($options as $option) {
                if (! is_string($option) || trim($option) === '') {
                    return $fail('mcq "options" must all be non-empty strings.');
                }
            }
            $answerIndex = $item['answer_index'] ?? null;
            if (! is_int($answerIndex) || $answerIndex < 0 || $answerIndex > 3) {
                return $fail('mcq "answer_index" must be an integer between 0 and 3.');
            }

            $normalized['options'] = array_values($options);
            $normalized['correct_option_index'] = $answerIndex;
        } else {
            $answer = trim((string) ($item['answer'] ?? ''));
            if ($answer === '') {
                return $fail('short_answer items need a non-empty "answer".');
            }
            $normalized['answer'] = $answer;
        }

        return ['valid' => true, 'error' => null, 'normalized' => $normalized];
    }

    /**
     * Execute the import.
     */
    public function handle(StoreGeneratedQuestionsAction $action): void
    {
        $report = [
            'dry_run' => $this->dryRun,
            'domain_id' => $this->domainId,
            'started_at' => now()->toIso8601String(),
            'subjects' => [],
            'totals' => ['processed' => 0, 'inserted' => 0, 'duplicates_skipped' => 0, 'invalid' => 0],
            'errors' => [],
        ];

        try {
            $raw = Storage::disk('local')->get($this->filePath);
        } catch (\Throwable $e) {
            $report['errors'][] = 'Could not read the import file: '.$e->getMessage();
            $this->finish($report);

            return;
        }

        $validation = self::validatePayload(json_decode($raw, true));
        if (! $validation['valid']) {
            $report['errors'] = $validation['errors'];
            $this->finish($report);

            return;
        }

        $domain = Domain::where('id', $this->domainId)->where('is_active', true)->first();
        if (! $domain) {
            $report['errors'][] = "Domain #{$this->domainId} was not found or is inactive.";
            $this->finish($report);

            return;
        }

        foreach ($validation['subjects'] as $subjectKey => $subjectTitle) {
            $subjectReport = [
                'title' => $subjectTitle,
                'module_id' => null,
                'inserted' => 0,
                'duplicates_skipped' => 0,
                'invalid' => 0,
            ];

            $module = $this->dryRun
                ? null
                : ContentModule::firstOrCreate(
                    ['domain_id' => $domain->id, 'slug' => Str::slug($subjectTitle)."-{$domain->id}"],
                    [
                        'title' => $subjectTitle,
                        'module_type' => 'preparation',
                        'is_active' => true,
                        'content_meta' => ['import_subject_key' => $subjectKey],
                    ]
                );

            $subjectReport['module_id'] = $module?->id;

            $items = [];
            foreach ($validation['questions'] as $index => $rawItem) {
                if (! is_array($rawItem) || ($rawItem['subject'] ?? null) !== $subjectKey) {
                    continue;
                }
                $check = self::validateQuestion($rawItem, $validation['subjects'], $index);
                if (! $check['valid']) {
                    $subjectReport['invalid']++;
                    Log::warning('Question import skipped invalid item', [
                        'report_key' => $this->reportKey,
                        'error' => $check['error'],
                    ]);

                    continue;
                }
                $items[] = $check['normalized'];
            }

            $subjectReport['processed'] = count($items);

            foreach (array_chunk($items, self::BATCH_SIZE) as $chunk) {
                $result = $action->execute(
                    questions: $chunk,
                    targetDomainIds: [],
                    moduleStrategy: 'existing',
                    existingModuleIds: $module ? [$module->id] : [],
                    dryRun: $this->dryRun,
                );
                $subjectReport['inserted'] += $result['inserted'];
                $subjectReport['duplicates_skipped'] += $result['reused'];
            }

            $report['subjects'][$subjectKey] = $subjectReport;
        }

        foreach ($report['subjects'] as $subjectReport) {
            $report['totals']['processed'] += $subjectReport['processed'];
            $report['totals']['inserted'] += $subjectReport['inserted'];
            $report['totals']['duplicates_skipped'] += $subjectReport['duplicates_skipped'];
            $report['totals']['invalid'] += $subjectReport['invalid'];
        }

        $report['completed_at'] = now()->toIso8601String();
        $this->finish($report);
    }

    /**
     * Persist the report, notify the admin who started the import, and log the outcome.
     *
     * @param  array<string, mixed>  $report
     */
    protected function finish(array $report): void
    {
        Cache::put($this->reportKey, $report, self::CACHE_TTL_SECONDS);

        $mode = $this->dryRun ? 'dry-run' : 'import';
        $failed = $report['errors'] !== [];

        $title = $failed
            ? "Question {$mode} failed"
            : 'Question '.$mode.' '.($this->dryRun ? 'preview ready' : 'complete');

        $body = $failed
            ? implode(' ', array_slice($report['errors'], 0, 2))
            : sprintf(
                '%d inserted, %d duplicates skipped, %d invalid across %d subject(s).',
                $report['totals']['inserted'],
                $report['totals']['duplicates_skipped'],
                $report['totals']['invalid'],
                count($report['subjects'])
            );

        $admin = $this->adminUserId ? User::find($this->adminUserId) : null;
        if ($admin) {
            $notification = Notification::make()->title($title)->body($body);
            $failed ? $notification->danger() : $notification->success();
            $notification->sendToDatabase($admin);
        }

        Log::info("Question {$mode} finished", [
            'report_key' => $this->reportKey,
            'dry_run' => $this->dryRun,
            'totals' => $report['totals'],
            'errors' => $report['errors'],
        ]);
    }
}
