<?php

use App\Actions\QuestionBank\StoreGeneratedQuestionsAction;
use App\Jobs\ImportQuestionsJob;
use App\Jobs\VectorizeDomainKnowledgeJob;
use App\Models\ContentModule;
use App\Models\Domain;
use App\Models\QuestionBank;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

function importTestPayload(string $suffix): array
{
    return [
        'meta' => ['source' => 'pest', 'total' => 3],
        'subjects' => [
            'islamiyat' => 'Islamic Studies '.$suffix,
            'pak-history' => 'Pakistan History '.$suffix,
        ],
        'questions' => [
            [
                'code' => 'PMA-T-'.$suffix.'-001',
                'subject' => 'islamiyat',
                'type' => 'mcq',
                'question' => 'Which Surah was revealed first? ('.$suffix.')',
                'options' => ['Surah Alaq', 'Surah Fatiha', 'Surah Baqarah', 'Surah Ikhlas'],
                'answer_index' => 0,
            ],
            [
                'code' => 'PMA-T-'.$suffix.'-002',
                'subject' => 'islamiyat',
                'type' => 'short_answer',
                'question' => 'Age of the Prophet at first revelation? ('.$suffix.')',
                'answer' => '40 years',
            ],
            [
                'code' => 'PMA-T-'.$suffix.'-003',
                'subject' => 'pak-history',
                'type' => 'mcq',
                'question' => 'In which year did the Hijrah take place? ('.$suffix.')',
                'options' => ['620 AD', '621 AD', '622 AD', '623 AD'],
                'answer_index' => 2,
            ],
        ],
    ];
}

function importTestDomain(string $suffix): Domain
{
    return Domain::create([
        'name' => 'Import Test Domain',
        'slug' => 'import-test-'.$suffix,
        'is_active' => true,
    ]);
}

function runImportJobForTest(array $payload, Domain $domain, bool $dryRun, string $reportKey): void
{
    Storage::disk('local')->put('imports/'.$reportKey.'.json', json_encode($payload));

    (new ImportQuestionsJob(
        filePath: 'imports/'.$reportKey.'.json',
        domainId: $domain->id,
        dryRun: $dryRun,
        adminUserId: null,
        reportKey: $reportKey,
    ))->handle(app(StoreGeneratedQuestionsAction::class));
}

it('dry-run previews counts without writing anything to the database', function () {
    Queue::fake();
    Storage::fake('local');

    $suffix = uniqid();
    $domain = importTestDomain($suffix);
    $reportKey = 'import-dryrun-'.$suffix;

    runImportJobForTest(importTestPayload($suffix), $domain, true, $reportKey);

    expect(QuestionBank::count())->toBe(0)
        ->and(ContentModule::where('domain_id', $domain->id)->count())->toBe(0);

    $report = Cache::get($reportKey);

    expect($report)->not->toBeNull()
        ->and($report['dry_run'])->toBeTrue()
        ->and($report['totals']['inserted'])->toBe(3)
        ->and($report['totals']['duplicates_skipped'])->toBe(0)
        ->and($report['totals']['invalid'])->toBe(0)
        ->and($report['subjects'])->toHaveCount(2);
});

it('inserts mcqs and short answers into their correct subject modules', function () {
    Queue::fake();
    Storage::fake('local');

    $suffix = uniqid();
    $domain = importTestDomain($suffix);
    $reportKey = 'import-live-'.$suffix;

    runImportJobForTest(importTestPayload($suffix), $domain, false, $reportKey);

    expect(QuestionBank::count())->toBe(3);

    $mcq = QuestionBank::where('code', 'PMA-T-'.$suffix.'-001')->first();
    expect($mcq)->not->toBeNull()
        ->and($mcq->question_type)->toBe('mcq')
        ->and($mcq->payload['options'])->toHaveCount(4)
        ->and($mcq->payload['correct_option_index'])->toBe(0);

    $shortAnswer = QuestionBank::where('code', 'PMA-T-'.$suffix.'-002')->first();
    expect($shortAnswer)->not->toBeNull()
        ->and($shortAnswer->question_type)->toBe('short_answer')
        ->and($shortAnswer->payload['model_answer'])->toBe('40 years');

    $islamiyat = ContentModule::where('domain_id', $domain->id)
        ->where('title', 'Islamic Studies '.$suffix)
        ->first();
    expect($islamiyat)->not->toBeNull()
        ->and($islamiyat->module_type)->toBe('preparation')
        ->and($islamiyat->questions()->count())->toBe(2);

    $history = ContentModule::where('domain_id', $domain->id)
        ->where('title', 'Pakistan History '.$suffix)
        ->first();
    expect($history)->not->toBeNull()
        ->and($history->questions()->count())->toBe(1);

    // Exactly the two subject modules exist directly under the domain (flat — no parent).
    expect(ContentModule::where('domain_id', $domain->id)->count())->toBe(2);

    Queue::assertPushed(VectorizeDomainKnowledgeJob::class);

    $report = Cache::get($reportKey);
    expect($report['totals']['inserted'])->toBe(3)
        ->and($report['subjects']['islamiyat']['module_id'])->toBe($islamiyat->id)
        ->and($report['subjects']['pak-history']['module_id'])->toBe($history->id);
});

it('skips exact duplicates when the same file is imported twice', function () {
    Queue::fake();
    Storage::fake('local');

    $suffix = uniqid();
    $domain = importTestDomain($suffix);
    $payload = importTestPayload($suffix);

    runImportJobForTest($payload, $domain, false, 'import-dedupe-a-'.$suffix);
    expect(QuestionBank::count())->toBe(3);

    runImportJobForTest($payload, $domain, false, 'import-dedupe-b-'.$suffix);
    expect(QuestionBank::count())->toBe(3);

    $report = Cache::get('import-dedupe-b-'.$suffix);
    expect($report['totals']['inserted'])->toBe(0)
        ->and($report['totals']['duplicates_skipped'])->toBe(3);
});

it('rejects malformed items and reports them without inserting', function () {
    Queue::fake();
    Storage::fake('local');

    $suffix = uniqid();
    $domain = importTestDomain($suffix);
    $reportKey = 'import-malformed-'.$suffix;

    $payload = [
        'subjects' => ['islamiyat' => 'Islamic Studies '.$suffix],
        'questions' => [
            // Only 2 options — invalid.
            ['code' => 'BAD-1', 'subject' => 'islamiyat', 'type' => 'mcq', 'question' => 'Bad options', 'options' => ['a', 'b'], 'answer_index' => 0],
            // Unknown subject — invalid.
            ['code' => 'BAD-2', 'subject' => 'unknown', 'type' => 'mcq', 'question' => 'Bad subject', 'options' => ['a', 'b', 'c', 'd'], 'answer_index' => 0],
            // Missing answer — invalid.
            ['code' => 'BAD-3', 'subject' => 'islamiyat', 'type' => 'short_answer', 'question' => 'No answer here'],
            // answer_index out of range — invalid.
            ['code' => 'BAD-4', 'subject' => 'islamiyat', 'type' => 'mcq', 'question' => 'Bad index', 'options' => ['a', 'b', 'c', 'd'], 'answer_index' => 9],
            // Valid.
            ['code' => 'GOOD-1', 'subject' => 'islamiyat', 'type' => 'mcq', 'question' => 'Valid question ('.$suffix.')', 'options' => ['a', 'b', 'c', 'd'], 'answer_index' => 3],
        ],
    ];

    runImportJobForTest($payload, $domain, false, $reportKey);

    expect(QuestionBank::count())->toBe(1)
        ->and(QuestionBank::where('code', 'GOOD-1')->exists())->toBeTrue();

    $report = Cache::get($reportKey);
    expect($report['totals']['invalid'])->toBe(4)
        ->and($report['totals']['inserted'])->toBe(1)
        ->and($report['subjects']['islamiyat']['invalid'])->toBe(3)
        ->and($report['unmapped_invalid'])->toBe(1);
});

it('rejects payloads missing the subjects map or questions array', function () {
    $missing = ImportQuestionsJob::validatePayload(['meta' => []]);
    expect($missing['valid'])->toBeFalse()
        ->and($missing['errors'])->not->toBeEmpty();

    $notArray = ImportQuestionsJob::validatePayload('not json');
    expect($notArray['valid'])->toBeFalse();

    $ok = ImportQuestionsJob::validatePayload(importTestPayload(uniqid()));
    expect($ok['valid'])->toBeTrue()
        ->and($ok['subjects'])->toHaveCount(2)
        ->and($ok['questions'])->toHaveCount(3);
});

it('completes the dry run even when the notifications table is missing', function () {
    Queue::fake();
    Storage::fake('local');

    $suffix = uniqid();
    $domain = importTestDomain($suffix);
    $admin = User::factory()->create(['email' => 'import-admin-'.$suffix.'@example.com']);

    // Simulate production, where the notifications table was never migrated.
    Schema::dropIfExists('notifications');

    $reportKey = 'import-no-notif-table-'.$suffix;
    Storage::disk('local')->put('imports/'.$reportKey.'.json', json_encode(importTestPayload($suffix)));

    try {
        (new ImportQuestionsJob(
            filePath: 'imports/'.$reportKey.'.json',
            domainId: $domain->id,
            dryRun: true,
            adminUserId: $admin->id,
            reportKey: $reportKey,
        ))->handle(app(StoreGeneratedQuestionsAction::class));

        $report = Cache::get($reportKey);

        expect($report)->not->toBeNull()
            ->and($report['dry_run'])->toBeTrue()
            ->and($report['errors'])->toBeEmpty()
            ->and($report['totals']['inserted'])->toBe(3)
            ->and($report['totals']['duplicates_skipped'])->toBe(0)
            ->and($report['totals']['invalid'])->toBe(0)
            ->and(QuestionBank::count())->toBe(0);
    } finally {
        Artisan::call('migrate', ['--path' => 'database/migrations/2026_10_04_000002_create_notifications_table.php']);
    }
});
