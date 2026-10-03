<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\QuestionBank\SubjectModuleProvisioner;
use App\Models\ContentModule;
use App\Models\Domain;
use App\Models\QuestionBank;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * One-time repair: link already-imported PMA Academic questions to their
 * subject modules.
 *
 * The bulk import (PR #3) inserted the questions but never created the five
 * subject ContentModules, leaving ~792 PMA-ACAD-XXXX rows unlinked. This job
 * reads database/data/pma_academic_subject_map.json (code => subject), creates
 * the five flat `preparation` modules under the chosen domain if missing, and
 * attaches every existing question to its module via the module_questions
 * pivot. Linking only: no question rows are created, modified or deleted, and
 * no vectorization jobs are dispatched.
 *
 * Single-pass stream processing: every map entry deterministically lands in
 * one of the report buckets (linked / already_linked / linked_by_text_match /
 * question_not_found). Zero silent drops.
 *
 * In dry-run mode the job is 100% read-only: module provisioning, pivot
 * writes and cache invalidation are all skipped, while the exact same
 * resolution and counting logic runs so the preview matches a live run.
 */
class LinkPmaSubjectModulesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 1800;

    public const CHUNK_SIZE = 100;

    public const CACHE_TTL_SECONDS = 600;

    public function __construct(
        public int $domainId,
        public bool $dryRun,
        public ?int $adminUserId,
        public string $reportKey,
        public ?string $mapPath = null,
    ) {}

    public function mapFilePath(): string
    {
        return $this->mapPath ?? database_path('data/pma_academic_subject_map.json');
    }

    /**
     * Load and validate the code => subject map.
     *
     * @return array{ok: bool, error: ?string, entries: array<string, array{subject: string, question: string}>}
     */
    public static function loadMap(string $path): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            return ['ok' => false, 'error' => "Subject map not found or unreadable: {$path}", 'entries' => []];
        }

        $raw = file_get_contents($path);
        if (! is_string($raw) || trim($raw) === '') {
            return ['ok' => false, 'error' => 'Subject map file is empty.', 'entries' => []];
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded) || $decoded === []) {
            return ['ok' => false, 'error' => 'Subject map is empty or invalid JSON.', 'entries' => []];
        }

        $entries = [];
        foreach ($decoded as $code => $row) {
            if (! is_string($code) || $code === '' || ! is_array($row)
                || ! isset($row['subject']) || ! is_string($row['subject']) || $row['subject'] === ''
                || ! isset($row['question']) || ! is_string($row['question']) || trim($row['question']) === ''
            ) {
                return ['ok' => false, 'error' => "Subject map entry '{$code}' is malformed.", 'entries' => []];
            }

            $entries[$code] = ['subject' => $row['subject'], 'question' => $row['question']];
        }

        return ['ok' => true, 'error' => null, 'entries' => $entries];
    }

    /**
     * Normalized SHA-256 text hash, identical to
     * StoreGeneratedQuestionsAction::normalizeTextHash().
     */
    public static function normalizeTextHash(string $text): string
    {
        $clean = mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', $text)));

        return hash('sha256', $clean);
    }

    /**
     * Execute the link repair.
     */
    public function handle(): void
    {
        $report = [
            'dry_run' => $this->dryRun,
            'kind' => 'link',
            'domain_id' => $this->domainId,
            'started_at' => now()->toIso8601String(),
            'subjects' => [],
            'totals' => [
                'processed' => 0,
                'linked' => 0,
                'already_linked' => 0,
                'linked_by_text_match' => 0,
                'question_not_found' => 0,
            ],
            'errors' => [],
        ];

        $map = self::loadMap($this->mapFilePath());
        if (! $map['ok']) {
            $report['errors'][] = $map['error'] ?? 'Could not load the subject map.';
            $this->finish($report);

            return;
        }

        $domain = Domain::where('id', $this->domainId)->where('is_active', true)->first();
        if (! $domain instanceof Domain) {
            $report['errors'][] = "Domain #{$this->domainId} was not found or is inactive.";
            $this->finish($report);

            return;
        }

        $entries = $map['entries'];

        $unknownSubjects = array_values(array_diff(
            array_unique(array_column($entries, 'subject')),
            array_keys(SubjectModuleProvisioner::SUBJECTS)
        ));
        if ($unknownSubjects !== []) {
            $report['errors'][] = 'Unknown subject key(s) in map: '.implode(', ', $unknownSubjects).'.';
            $this->finish($report);

            return;
        }

        // Pre-resolve the five subject modules in a single query.
        $slugsBySubject = [];
        foreach (array_keys(SubjectModuleProvisioner::SUBJECTS) as $subjectKey) {
            $slugsBySubject[$subjectKey] = SubjectModuleProvisioner::slugFor(
                SubjectModuleProvisioner::titleFor($subjectKey),
                $domain->id
            );
        }

        /** @var array<string, ContentModule> $modulesBySlug */
        $modulesBySlug = ContentModule::where('domain_id', $domain->id)
            ->whereIn('slug', array_values($slugsBySubject))
            ->get()
            ->keyBy('slug')
            ->all();

        // Batch 1: resolve questions by code in a single query (no N+1).
        $codes = array_keys($entries);
        /** @var array<string, int> $questionIdByCode */
        $questionIdByCode = QuestionBank::whereIn('code', $codes)
            ->pluck('id', 'code')
            ->all();

        // Batch 2: normalized-text-hash fallback for codes with no row (covers
        // questions skipped at import as duplicates under different codes).
        /** @var array<string, int> $questionIdByTextHash */
        $questionIdByTextHash = [];
        $missingCodes = array_diff($codes, array_keys($questionIdByCode));
        if ($missingCodes !== []) {
            $lowerTexts = [];
            foreach ($missingCodes as $code) {
                $lower = mb_strtolower(trim($entries[$code]['question']));
                if ($lower !== '') {
                    $lowerTexts[$lower] = true;
                }
            }

            if ($lowerTexts !== []) {
                $rows = QuestionBank::query()
                    ->whereIn(DB::raw('LOWER(question_text)'), array_keys($lowerTexts))
                    ->select(['id', 'question_text'])
                    ->get();

                foreach ($rows as $row) {
                    $questionIdByTextHash[self::normalizeTextHash($row->question_text)] = $row->id;
                }
            }
        }

        // Single pass over the map: every entry lands in an explicit bucket.
        /** @var array<string, array{title: string, module_id: ?int, module_created: bool, linked: int, already_linked: int, linked_by_text_match: int, question_not_found: int}> $subjectRows */
        $subjectRows = [];
        /** @var array<int, array{module: ?ContentModule, module_created: bool}> $moduleCache */
        $moduleCache = [];
        /** @var list<array{subject: string, module_id: int, question_id: int, via_text: bool}> $pairs */
        $pairs = [];

        foreach ($entries as $code => $row) {
            $subjectKey = $row['subject'];
            $report['totals']['processed']++;

            if (! isset($subjectRows[$subjectKey])) {
                $subjectRows[$subjectKey] = [
                    'title' => SubjectModuleProvisioner::titleFor($subjectKey),
                    'module_id' => null,
                    'module_created' => false,
                    'linked' => 0,
                    'already_linked' => 0,
                    'linked_by_text_match' => 0,
                    'question_not_found' => 0,
                ];
            }

            $questionId = $questionIdByCode[$code] ?? null;
            $viaText = false;
            if ($questionId === null) {
                $hash = self::normalizeTextHash($row['question']);
                $questionId = $questionIdByTextHash[$hash] ?? null;
                $viaText = $questionId !== null;
            }

            if ($questionId === null) {
                $report['totals']['question_not_found']++;
                $subjectRows[$subjectKey]['question_not_found']++;

                continue;
            }

            if (! array_key_exists($subjectKey, $moduleCache)) {
                $slug = $slugsBySubject[$subjectKey];
                $module = $modulesBySlug[$slug] ?? null;
                $created = false;

                if (! $module instanceof ContentModule && ! $this->dryRun) {
                    $module = SubjectModuleProvisioner::findOrCreateQuietly($domain, $subjectKey);
                    $created = true;
                }

                $moduleCache[$subjectKey] = ['module' => $module, 'module_created' => $created];
                $subjectRows[$subjectKey]['module_id'] = $module?->id;
                $subjectRows[$subjectKey]['module_created'] = $created;
            }

            $module = $moduleCache[$subjectKey]['module'];

            if (! $module instanceof ContentModule) {
                // Dry-run with the module not yet existing: project the link.
                $bucket = $viaText ? 'linked_by_text_match' : 'linked';
                $report['totals'][$bucket]++;
                $subjectRows[$subjectKey][$bucket]++;

                continue;
            }

            $pairs[] = [
                'subject' => $subjectKey,
                'module_id' => $module->id,
                'question_id' => $questionId,
                'via_text' => $viaText,
            ];
        }

        // Reconcile pivots per module: one existence query per module, then a
        // single insertOrIgnore per 100-row chunk (sub-200ms lock boundary).
        /** @var array<int, list<array{subject: string, module_id: int, question_id: int, via_text: bool}>> $pairsByModule */
        $pairsByModule = [];
        foreach ($pairs as $pair) {
            $pairsByModule[$pair['module_id']][] = $pair;
        }

        foreach ($pairsByModule as $moduleId => $modulePairs) {
            $questionIds = array_values(array_unique(array_column($modulePairs, 'question_id')));

            /** @var array<int, true> $alreadyAttached */
            $alreadyAttached = [];
            foreach (DB::table('module_questions')->where('module_id', $moduleId)->whereIn('question_id', $questionIds)->pluck('question_id') as $qid) {
                $alreadyAttached[(int) $qid] = true;
            }

            $toAttach = [];
            foreach ($modulePairs as $pair) {
                $qid = $pair['question_id'];
                $bucket = $pair['via_text'] ? 'linked_by_text_match' : 'linked';

                $subject = $pair['subject'];

                if (isset($alreadyAttached[$qid])) {
                    $report['totals']['already_linked']++;
                    if (isset($subjectRows[$subject])) {
                        $subjectRows[$subject]['already_linked']++;
                    }

                    continue;
                }

                $alreadyAttached[$qid] = true;
                $toAttach[] = [
                    'module_id' => $moduleId,
                    'question_id' => $qid,
                    'sort_order' => 0,
                    'marks' => 1.00,
                ];
                $report['totals'][$bucket]++;
                if (isset($subjectRows[$subject])) {
                    $subjectRows[$subject][$bucket]++;
                }
            }

            if ($toAttach !== [] && ! $this->dryRun) {
                foreach (array_chunk($toAttach, self::CHUNK_SIZE) as $chunk) {
                    DB::transaction(static fn (): mixed => DB::table('module_questions')->insertOrIgnore($chunk));
                }
            }
        }

        // Stable canonical subject order in the report.
        $orderedRows = [];
        foreach (array_keys(SubjectModuleProvisioner::SUBJECTS) as $subjectKey) {
            if (isset($subjectRows[$subjectKey])) {
                $orderedRows[$subjectKey] = $subjectRows[$subjectKey];
            }
        }
        $report['subjects'] = $orderedRows;

        $report['completed_at'] = now()->toIso8601String();
        $this->finish($report);
    }

    /**
     * Handle job failure from the queue worker (Governance Rule 9).
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error('LinkPmaSubjectModulesJob permanently failed', [
            'correlation_id' => $this->reportKey,
            'user_id' => $this->adminUserId,
            'domain_id' => $this->domainId,
            'event' => 'pma_link.failed',
            'error' => $exception?->getMessage(),
        ]);

        $report = Cache::get($this->reportKey, [
            'dry_run' => $this->dryRun,
            'kind' => 'link',
            'domain_id' => $this->domainId,
            'totals' => [
                'processed' => 0,
                'linked' => 0,
                'already_linked' => 0,
                'linked_by_text_match' => 0,
                'question_not_found' => 0,
            ],
            'subjects' => [],
            'errors' => [],
        ]);

        $report['errors'][] = 'Fatal background error: '.($exception?->getMessage() ?? 'Unknown error');
        $report['completed_at'] = now()->toIso8601String();

        $this->finish($report);
    }

    /**
     * Persist the report, notify the admin who started the run, and log the outcome.
     *
     * @param  array<string, mixed>  $report
     */
    protected function finish(array $report): void
    {
        Cache::put($this->reportKey, $report, self::CACHE_TTL_SECONDS);

        $mode = $this->dryRun ? 'dry-run' : 'link';
        $failed = $report['errors'] !== [];
        $totals = $report['totals'];

        $title = $failed
            ? "Question {$mode} failed"
            : 'Question '.$mode.' '.($this->dryRun ? 'preview ready' : 'complete');

        $body = $failed
            ? implode(' ', array_slice($report['errors'], 0, 2))
            : sprintf(
                '%d linked, %d already linked, %d via text match, %d not found across %d subject(s).',
                $totals['linked'],
                $totals['already_linked'],
                $totals['linked_by_text_match'],
                $totals['question_not_found'],
                count($report['subjects'])
            );

        $admin = $this->adminUserId ? User::find($this->adminUserId) : null;
        if ($admin instanceof User) {
            $notification = Notification::make()->title($title)->body($body);
            $failed ? $notification->danger() : $notification->success();
            $notification->sendToDatabase($admin);
        }

        Log::info("Question {$mode} finished", [
            'correlation_id' => $this->reportKey,
            'user_id' => $this->adminUserId,
            'domain_id' => $this->domainId,
            'event' => 'pma_link.finished',
            'status' => $failed ? 'failure' : 'success',
            'dry_run' => $this->dryRun,
            'totals' => $totals,
            'errors' => $report['errors'],
        ]);
    }
}
