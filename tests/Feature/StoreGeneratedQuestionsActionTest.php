<?php

use App\Actions\QuestionBank\StoreGeneratedQuestionsAction;
use App\Models\ContentModule;
use App\Models\Domain;
use App\Models\QuestionBank;
use Illuminate\Support\Facades\DB;

it('persists questions atomically and links to multiple domains without duplicating records', function () {
    $domain1 = Domain::create([
        'name' => 'PMA Long Course Initial',
        'slug' => 'pma-test-'.uniqid(),
        'is_active' => true,
    ]);

    $domain2 = Domain::create([
        'name' => 'CSS Competitive Exam',
        'slug' => 'css-test-'.uniqid(),
        'is_active' => true,
    ]);

    $questions = [
        [
            'question_text' => 'What is the national flower of Pakistan? (Unique '.uniqid().')',
            'options' => ['Jasmine', 'Rose', 'Tulip', 'Sunflower'],
            'correct_option_index' => 0,
            'explanation' => 'Jasmine is the national flower of Pakistan.',
            'difficulty_level' => 'easy',
            'code' => '', // Empty string should be sanitized to null
        ],
        [
            'question_text' => 'Where is K2 situated? (Unique '.uniqid().')',
            'options' => ['Karakoram Range', 'Himalayas', 'Hindu Kush', 'Sulaiman'],
            'correct_option_index' => 0,
            'explanation' => 'K2 is located in the Karakoram range.',
            'difficulty_level' => 'medium',
            'code' => null,
        ],
    ];

    $action = new StoreGeneratedQuestionsAction;

    $result = $action->execute(
        questions: $questions,
        targetDomainIds: [$domain1->id, $domain2->id],
        moduleStrategy: 'auto_create',
        newModuleTitle: 'General Knowledge Multi-Domain Mock'
    );

    expect($result['inserted'])->toBe(2)
        ->and($result['reused'])->toBe(0)
        ->and($result['total'])->toBe(2)
        ->and($result['module_ids'])->toHaveCount(2);

    // Verify exactly 2 modules were created (1 per domain)
    $modules = ContentModule::whereIn('id', $result['module_ids'])->get();
    expect($modules)->toHaveCount(2)
        ->and($modules->pluck('domain_id')->sort()->values()->all())
        ->toBe([$domain1->id, $domain2->id]);

    // Verify pivot records in module_questions
    $pivotCount = DB::table('module_questions')
        ->whereIn('module_id', $result['module_ids'])
        ->count();
    // 2 questions x 2 modules = 4 pivot links
    expect($pivotCount)->toBe(4);

    // Verify questions have null codes, avoiding unique constraint collisions
    $firstQ = QuestionBank::where('question_text', $questions[0]['question_text'])->first();
    expect($firstQ)->not->toBeNull()
        ->and($firstQ->code)->toBeNull();
});

it('deduplicates identical questions by normalized text hash instead of inserting duplicate rows', function () {
    $domain = Domain::create([
        'name' => 'Pak Navy Initial',
        'slug' => 'navy-test-'.uniqid(),
        'is_active' => true,
    ]);

    $questionText = 'What is the speed of sound in air at 20C? (Dedupe '.uniqid().')';

    $questionsBatch1 = [
        [
            'question_text' => $questionText,
            'options' => ['343 m/s', '300 m/s', '1500 m/s', '3000 m/s'],
            'correct_option_index' => 0,
            'explanation' => 'Speed of sound is approximately 343 m/s.',
            'difficulty_level' => 'medium',
            'code' => null,
        ],
    ];

    $action = new StoreGeneratedQuestionsAction;

    $res1 = $action->execute(
        questions: $questionsBatch1,
        targetDomainIds: [$domain->id],
        moduleStrategy: 'auto_create',
        newModuleTitle: 'Physics Test Batch 1'
    );

    expect($res1['inserted'])->toBe(1)
        ->and($res1['reused'])->toBe(0);

    // Now insert identical question with different punctuation / casing in a second batch
    $questionsBatch2 = [
        [
            'question_text' => strtoupper($questionText), // Same normalized text
            'options' => ['343 m/s', '300 m/s', '1500 m/s', '3000 m/s'],
            'correct_option_index' => 0,
            'explanation' => 'Speed of sound is approximately 343 m/s.',
            'difficulty_level' => 'medium',
            'code' => null,
        ],
    ];

    $res2 = $action->execute(
        questions: $questionsBatch2,
        targetDomainIds: [$domain->id],
        moduleStrategy: 'auto_create',
        newModuleTitle: 'Physics Test Batch 2'
    );

    // Reused count should be 1, inserted should be 0!
    expect($res2['inserted'])->toBe(0)
        ->and($res2['reused'])->toBe(1);

    // Verify question_bank only has 1 record for this text
    $dbCount = QuestionBank::where('question_text', 'ilike', '%speed of sound in air at 20C%')->count();
    expect($dbCount)->toBe(1);
});

it('correctly handles non-Latin unicode text without collision or data loss', function () {
    $domain = Domain::create([
        'name' => 'Islamic Studies',
        'slug' => 'islamic-studies-'.uniqid(),
        'is_active' => true,
    ]);

    $qUrdu1 = 'قرآن مجید کی سب سے لمبی سورت کون سی ہے؟';
    $qUrdu2 = 'نماز کس سن ہجری میں فرض ہوئی؟';

    $questions = [
        [
            'question_text' => $qUrdu1,
            'options' => ['سورۃ البقرہ', 'سورۃ آل عمران', 'سورۃ النساء', 'سورۃ المائدہ'],
            'correct_option_index' => 0,
            'explanation' => 'سورۃ البقرہ قرآن کی طویل ترین سورت ہے۔',
            'difficulty_level' => 'easy',
            'code' => null,
        ],
        [
            'question_text' => $qUrdu2,
            'options' => ['10 نبوی', '1 ہجری', '2 ہجری', '3 ہجری'],
            'correct_option_index' => 0,
            'explanation' => 'معراج کے موقع پر 10 نبوی میں نماز فرض ہوئی۔',
            'difficulty_level' => 'medium',
            'code' => null,
        ],
    ];

    $action = new StoreGeneratedQuestionsAction;
    $result = $action->execute(
        questions: $questions,
        targetDomainIds: [$domain->id],
        moduleStrategy: 'auto_create',
        newModuleTitle: 'Urdu Islamic Studies Test'
    );

    // Both distinct Urdu questions must be inserted separately, NOT falsely collided into the same hash!
    expect($result['inserted'])->toBe(2)
        ->and($result['reused'])->toBe(0);
});

it('gracefully sanitizes duplicate non-null import codes without crashing unique DB constraints', function () {
    $domain = Domain::create([
        'name' => 'General Science',
        'slug' => 'gen-sci-'.uniqid(),
        'is_active' => true,
    ]);

    $existingCode = 'COLLISION-CODE-'.uniqid();
    QuestionBank::create([
        'question_type' => 'mcq',
        'code' => $existingCode,
        'question_text' => 'Pre-existing question with unique code '.uniqid(),
        'is_active' => true,
    ]);

    $questions = [
        [
            'question_text' => 'New question attempting to reuse existing code '.uniqid(),
            'options' => ['A', 'B', 'C', 'D'],
            'correct_option_index' => 0,
            'explanation' => null,
            'difficulty_level' => 'easy',
            'code' => $existingCode, // Collision with DB!
        ],
        [
            'question_text' => 'Another question with batch collision code '.uniqid(),
            'options' => ['A', 'B', 'C', 'D'],
            'correct_option_index' => 0,
            'explanation' => null,
            'difficulty_level' => 'easy',
            'code' => 'BATCH-DUP-'.uniqid(),
        ],
        [
            'question_text' => 'Third question repeating the batch collision code '.uniqid(),
            'options' => ['A', 'B', 'C', 'D'],
            'correct_option_index' => 0,
            'explanation' => null,
            'difficulty_level' => 'easy',
            'code' => 'BATCH-DUP-'.uniqid(), // will be checked or sanitized
        ],
    ];

    $action = new StoreGeneratedQuestionsAction;
    $result = $action->execute(
        questions: $questions,
        targetDomainIds: [$domain->id],
        moduleStrategy: 'pool'
    );

    expect($result['inserted'])->toBe(3);
});
