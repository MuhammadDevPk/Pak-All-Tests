<?php

use App\Services\Ai\McqGeneratorService;

it('parses standard multiline MCQs without AI invocation', function () {
    $service = new McqGeneratorService(driver: 'fake');

    $rawText = <<<'TEXT'
1. What is the capital of Australia?
A) Sydney
B) Melbourne
C) Canberra
D) Brisbane
Answer: C
Explanation: Canberra was selected in 1908 as a compromise between Sydney and Melbourne.

2. Which year was the Pakistan Resolution passed?
A) 1930
B) 1940
C) 1947
D) 1956
Ans: B
TEXT;

    $result = $service->parseCompleteMcqs($rawText);

    expect($result['questions'])->toHaveCount(2);
    expect($result['unparsed_lines'])->toBeEmpty();

    $q1 = $result['questions'][0];
    expect($q1['question_text'])->toBe('What is the capital of Australia?')
        ->and($q1['options'])->toBe(['Sydney', 'Melbourne', 'Canberra', 'Brisbane'])
        ->and($q1['correct_option_index'])->toBe(2) // 0-based for C
        ->and($q1['explanation'])->toContain('compromise between Sydney and Melbourne');

    $q2 = $result['questions'][1];
    expect($q2['question_text'])->toBe('Which year was the Pakistan Resolution passed?')
        ->and($q2['options'])->toBe(['1930', '1940', '1947', '1956'])
        ->and($q2['correct_option_index'])->toBe(1); // 0-based for B
});

it('parses inline space-separated options on a single line', function () {
    $service = new McqGeneratorService(driver: 'fake');

    $rawText = <<<'TEXT'
1. What is the national animal of Pakistan? a) Markhor b) Snow Leopard c) Bengal Tiger d) Chiltan Ibex Ans: A
2. First Governor-General of Pakistan? (A) Liaquat Ali Khan (B) Quaid-e-Azam (C) Khawaja Nazimuddin (D) Ghulam Muhammad Answer: B
TEXT;

    $result = $service->parseCompleteMcqs($rawText);

    expect($result['questions'])->toHaveCount(2);

    $q1 = $result['questions'][0];
    expect($q1['options'])->toHaveCount(4)
        ->and($q1['options'][0])->toBe('Markhor')
        ->and($q1['correct_option_index'])->toBe(0);

    $q2 = $result['questions'][1];
    expect($q2['options'])->toHaveCount(4)
        ->and($q2['options'][1])->toBe('Quaid-e-Azam')
        ->and($q2['correct_option_index'])->toBe(1);
});

it('captures unparsed lines as diagnostics without crashing', function () {
    $service = new McqGeneratorService(driver: 'fake');

    $rawText = <<<'TEXT'
Some random header text that is not a question.
1. What is 2 + 2?
A) 3
B) 4
C) 5
D) 6
Answer: B
Another random footer note.
TEXT;

    $result = $service->parseCompleteMcqs($rawText);

    expect($result['questions'])->toHaveCount(1)
        ->and($result['unparsed_lines'])->toContain('Some random header text that is not a question.')
        ->and($result['unparsed_lines'])->toContain('Another random footer note.');
});

it('generates 4 options and rationale for bare QA pairs with fake driver', function () {
    $service = new McqGeneratorService(driver: 'fake');

    $qaPairs = [
        ['question' => 'Capital of France?', 'answer' => 'Paris'],
        ['question' => 'Largest ocean?', 'answer' => 'Pacific Ocean'],
    ];

    $questions = $service->generateDistractorsForPairs($qaPairs, 'hard');

    expect($questions)->toHaveCount(2);

    foreach ($questions as $q) {
        expect($q['options'])->toHaveCount(4)
            ->and($q['correct_option_index'])->toBe(0)
            ->and($q['explanation'])->not->toBeEmpty()
            ->and($q['difficulty_level'])->toBe('hard');
    }
});

it('generates topic-based questions with deterministic count and difficulty', function () {
    $service = new McqGeneratorService(driver: 'fake');

    $questions = $service->generateFromTopicOrText('Pakistan Studies Ghazwat', count: 5, difficulty: 'medium');

    expect($questions)->toHaveCount(5);
    foreach ($questions as $q) {
        expect($q['options'])->toHaveCount(4)
            ->and($q['difficulty_level'])->toBe('medium');
    }
});

it('parses raw QA pairs into structured array', function () {
    $service = new McqGeneratorService(driver: 'fake');

    $raw = <<<'TEXT'
Q: What is the capital of Australia?
A: Canberra

Q: In which Hijri year was Treaty of Hudaybiyyah signed?
A: 6 AH
TEXT;

    $pairs = $service->parseQaPairs($raw);

    expect($pairs)->toHaveCount(2)
        ->and($pairs[0]['question'])->toBe('What is the capital of Australia?')
        ->and($pairs[0]['answer'])->toBe('Canberra')
        ->and($pairs[1]['question'])->toBe('In which Hijri year was Treaty of Hudaybiyyah signed?')
        ->and($pairs[1]['answer'])->toBe('6 AH');
});
