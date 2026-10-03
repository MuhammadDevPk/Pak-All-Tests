<?php

declare(strict_types=1);

use App\Jobs\LinkPmaSubjectModulesJob;
use App\Models\ContentModule;
use App\Models\Domain;
use App\Models\QuestionBank;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

function makeLinkTestDomain(): Domain
{
    config(['ai.auto_vectorize' => false]);
    $uniq = Str::random(8);

    return Domain::create([
        'name' => 'Link Test Domain '.$uniq,
        'slug' => 'link-test-'.strtolower($uniq),
        'code' => 'LINK_TEST_'.strtoupper($uniq),
        'is_active' => true,
    ]);
}

function makeLinkTestQuestion(string $code, string $text): QuestionBank
{
    return QuestionBank::createQuietly([
        'question_type' => 'mcq',
        'code' => $code,
        'question_text' => $text,
        'payload' => ['options' => ['Alpha', 'Beta', 'Gamma', 'Delta'], 'correct_option_index' => 1],
        'difficulty_level' => 'medium',
        'is_active' => true,
    ]);
}

function writeLinkMapFixture(array $entries): string
{
    $path = sys_get_temp_dir().'/pma_link_map_'.Str::random(10).'.json';
    file_put_contents($path, json_encode($entries));

    return $path;
}

/**
 * @return array{key: string, report: array<string, mixed>}
 */
function runLinkJob(Domain $domain, bool $dryRun, string $mapPath): array
{
    $reportKey = 'test_link_'.Str::uuid()->toString();

    LinkPmaSubjectModulesJob::dispatchSync(
        domainId: $domain->id,
        dryRun: $dryRun,
        adminUserId: null,
        reportKey: $reportKey,
        mapPath: $mapPath,
    );

    return ['key' => $reportKey, 'report' => Cache::get($reportKey)];
}

function linkFixtureMap(): array
{
    return [
        'PMA-ACAD-T001' => ['subject' => 'islamiyat', 'question' => 'What is the test capital of Linkistan?'],
        'PMA-ACAD-T002' => ['subject' => 'geography', 'question' => 'Which test river flows through Linkistan?'],
    ];
}

it('performs a fully read-only dry run', function (): void {
    $domain = makeLinkTestDomain();
    $q1 = makeLinkTestQuestion('PMA-ACAD-T001', 'What is the test capital of Linkistan?');
    $q2 = makeLinkTestQuestion('PMA-ACAD-T002', 'Which test river flows through Linkistan?');
    $mapPath = writeLinkMapFixture(linkFixtureMap());

    ['report' => $report] = runLinkJob($domain, true, $mapPath);

    expect($report['dry_run'])->toBeTrue();
    expect($report['errors'])->toBe([]);
    // Zero writes: no modules, no pivot rows.
    expect(ContentModule::where('domain_id', $domain->id)->count())->toBe(0);
    expect(DB::table('module_questions')->whereIn('question_id', [$q1->id, $q2->id])->count())->toBe(0);
    // ...but the projection matches a live run.
    expect($report['totals']['processed'])->toBe(2);
    expect($report['totals']['linked'])->toBe(2);
    expect($report['totals']['already_linked'])->toBe(0);
    expect($report['totals']['question_not_found'])->toBe(0);
});

it('creates subject modules and links questions on a live run', function (): void {
    $domain = makeLinkTestDomain();
    $q1 = makeLinkTestQuestion('PMA-ACAD-T001', 'What is the test capital of Linkistan?');
    $q2 = makeLinkTestQuestion('PMA-ACAD-T002', 'Which test river flows through Linkistan?');
    $mapPath = writeLinkMapFixture(linkFixtureMap());

    ['report' => $report] = runLinkJob($domain, false, $mapPath);

    expect($report['errors'])->toBe([]);
    expect($report['totals']['linked'])->toBe(2);

    $islamiyatSlug = Str::slug('Islamic Studies (Islamiat)')."-{$domain->id}";
    $geographySlug = Str::slug('Geography (Pakistan & World)')."-{$domain->id}";

    $islamiyat = ContentModule::where('domain_id', $domain->id)->where('slug', $islamiyatSlug)->first();
    $geography = ContentModule::where('domain_id', $domain->id)->where('slug', $geographySlug)->first();

    expect($islamiyat)->not->toBeNull();
    expect($geography)->not->toBeNull();
    expect($islamiyat->module_type)->toBe('preparation');
    expect($islamiyat->is_active)->toBeTrue();

    expect(DB::table('module_questions')->where('module_id', $islamiyat->id)->where('question_id', $q1->id)->count())->toBe(1);
    expect(DB::table('module_questions')->where('module_id', $geography->id)->where('question_id', $q2->id)->count())->toBe(1);

    expect($report['subjects']['islamiyat']['module_created'])->toBeTrue();
    expect($report['subjects']['islamiyat']['module_id'])->toBe($islamiyat->id);
});

it('is idempotent across re-runs', function (): void {
    $domain = makeLinkTestDomain();
    makeLinkTestQuestion('PMA-ACAD-T001', 'What is the test capital of Linkistan?');
    makeLinkTestQuestion('PMA-ACAD-T002', 'Which test river flows through Linkistan?');
    $mapPath = writeLinkMapFixture(linkFixtureMap());

    runLinkJob($domain, false, $mapPath);
    $pivotCount = DB::table('module_questions')
        ->whereIn('module_id', ContentModule::where('domain_id', $domain->id)->pluck('id'))
        ->count();

    ['report' => $second] = runLinkJob($domain, false, $mapPath);

    expect($second['totals']['linked'])->toBe(0);
    expect($second['totals']['already_linked'])->toBe(2);
    expect(DB::table('module_questions')
        ->whereIn('module_id', ContentModule::where('domain_id', $domain->id)->pluck('id'))
        ->count())->toBe($pivotCount);
    expect($second['subjects']['islamiyat']['module_created'])->toBeFalse();
});

it('reports unknown codes instead of dropping them', function (): void {
    $domain = makeLinkTestDomain();
    makeLinkTestQuestion('PMA-ACAD-T001', 'What is the test capital of Linkistan?');
    $mapPath = writeLinkMapFixture([
        'PMA-ACAD-T001' => ['subject' => 'islamiyat', 'question' => 'What is the test capital of Linkistan?'],
        'PMA-ACAD-NOPE' => ['subject' => 'gk', 'question' => 'This code does not exist anywhere.'],
    ]);

    ['report' => $report] = runLinkJob($domain, false, $mapPath);

    expect($report['errors'])->toBe([]);
    expect($report['totals']['processed'])->toBe(2);
    expect($report['totals']['linked'])->toBe(1);
    expect($report['totals']['question_not_found'])->toBe(1);
});

it('links via normalized text match when the code row is missing', function (): void {
    $domain = makeLinkTestDomain();
    // Same text as the map entry, but stored under a different code (the
    // import skipped the original as a text duplicate).
    $ghost = makeLinkTestQuestion('OTHER-999', 'What is the test capital of Linkistan?');
    $mapPath = writeLinkMapFixture([
        'PMA-ACAD-GHOST' => ['subject' => 'islamiyat', 'question' => 'What is the test capital of Linkistan?'],
    ]);

    ['report' => $report] = runLinkJob($domain, false, $mapPath);

    expect($report['totals']['linked_by_text_match'])->toBe(1);
    expect($report['totals']['question_not_found'])->toBe(0);

    $moduleId = ContentModule::where('domain_id', $domain->id)
        ->where('slug', Str::slug('Islamic Studies (Islamiat)')."-{$domain->id}")
        ->value('id');

    expect(DB::table('module_questions')->where('module_id', $moduleId)->where('question_id', $ghost->id)->count())->toBe(1);
});

it('reports a missing map file as an error without throwing', function (): void {
    $domain = makeLinkTestDomain();

    ['report' => $report] = runLinkJob($domain, false, sys_get_temp_dir().'/pma_link_map_missing_'.Str::random(10).'.json');

    expect($report['errors'])->not->toBe([]);
    expect($report['totals']['processed'])->toBe(0);
});
