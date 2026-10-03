<?php

use App\Models\ContentModule;
use App\Models\Domain;
use App\Models\QuestionBank;
use App\Models\User;
use App\Models\UserSubscription;
use App\Models\UserWeakQuestion;

test('candidate revealing an answer receives correct solution, forfeits marks, and creates weak question record', function () {
    $user = User::factory()->create();

    $domain = Domain::create([
        'name' => 'PMA Long Course Initial',
        'slug' => 'pma-test-'.uniqid(),
        'code' => 'PMA_'.uniqid(),
        'is_active' => true,
    ]);

    UserSubscription::create([
        'user_id' => $user->id,
        'domain_id' => $domain->id,
        'status' => 'active',
        'starts_at' => now(),
        'expires_at' => now()->addYear(),
    ]);

    $question = QuestionBank::create([
        'question_type' => 'mcq',
        'question_text' => 'What is the capital of Pakistan?',
        'explanation' => 'Islamabad was declared the federal capital in 1960.',
        'payload' => [
            'options' => ['Karachi', 'Lahore', 'Islamabad', 'Rawalpindi'],
            'correct_option_index' => 2,
        ],
        'is_active' => true,
    ]);

    $response = $this
        ->actingAs($user)
        ->postJson(route('questions.reveal', ['question' => $question->id]), [
            'domain_id' => $domain->id,
        ]);

    $response->assertOk();
    $response->assertJson([
        'status' => 'success',
        'question_id' => $question->id,
        'correct_option_index' => 2,
        'correct_option_letter' => 'C',
        'correct_option_text' => 'Islamabad',
        'explanation' => 'Islamabad was declared the federal capital in 1960.',
        'weak_status' => [
            'consecutive_correct' => 0,
            'status' => 'active',
            'revealed_count' => 1,
        ],
    ]);

    // Verify database record in user_weak_questions
    $this->assertDatabaseHas('user_weak_questions', [
        'user_id' => $user->id,
        'question_id' => $question->id,
        'status' => 'active',
        'consecutive_correct' => 0,
        'revealed_count' => 1,
    ]);
});

test('candidate answering correctly twice consecutively graduates question to mastered', function () {
    $user = User::factory()->create();

    $domain = Domain::create([
        'name' => 'PMA Long Course Initial',
        'slug' => 'pma-test-'.uniqid(),
        'code' => 'PMA_'.uniqid(),
        'is_active' => true,
    ]);

    $question = QuestionBank::create([
        'question_type' => 'mcq',
        'question_text' => 'Where is PMA located?',
        'explanation' => 'Pakistan Military Academy is located in Kakul, Abbottabad.',
        'payload' => [
            'options' => ['Kakul, Abbottabad', 'Nowshera', 'Quetta', 'Risalpur'],
            'correct_option_index' => 0,
        ],
        'is_active' => true,
    ]);

    // 1. Initial wrong attempt creates weak record
    $resp1 = $this
        ->actingAs($user)
        ->postJson(route('questions.record-attempt', ['question' => $question->id]), [
            'selected_index' => 1, // Wrong
            'domain_id' => $domain->id,
        ]);

    $resp1->assertOk();
    $resp1->assertJson([
        'status' => 'success',
        'is_correct' => false,
        'consecutive_correct' => 0,
        'is_mastered' => false,
    ]);

    $this->assertDatabaseHas('user_weak_questions', [
        'user_id' => $user->id,
        'question_id' => $question->id,
        'status' => 'active',
        'failed_count' => 1,
        'consecutive_correct' => 0,
    ]);

    // 2. First correct attempt increments consecutive_correct to 1
    $resp2 = $this
        ->actingAs($user)
        ->postJson(route('questions.record-attempt', ['question' => $question->id]), [
            'selected_index' => 0, // Correct
            'domain_id' => $domain->id,
        ]);

    $resp2->assertOk();
    $resp2->assertJson([
        'status' => 'success',
        'is_correct' => true,
        'consecutive_correct' => 1,
        'is_mastered' => false,
    ]);

    // 3. Second consecutive correct attempt graduates question to mastered
    $resp3 = $this
        ->actingAs($user)
        ->postJson(route('questions.record-attempt', ['question' => $question->id]), [
            'selected_index' => 0, // Correct again!
            'domain_id' => $domain->id,
        ]);

    $resp3->assertOk();
    $resp3->assertJson([
        'status' => 'success',
        'is_correct' => true,
        'consecutive_correct' => 2,
        'is_mastered' => true,
    ]);

    $this->assertDatabaseHas('user_weak_questions', [
        'user_id' => $user->id,
        'question_id' => $question->id,
        'status' => 'mastered',
        'consecutive_correct' => 2,
    ]);
});

test('test submission enforces forfeiture for revealed questions and logs wrong answers into weak questions', function () {
    $user = User::factory()->create();

    $domain = Domain::create([
        'name' => 'General Knowledge Test',
        'slug' => 'gk-test-'.uniqid(),
        'code' => 'GK_'.uniqid(),
        'is_active' => true,
    ]);

    UserSubscription::create([
        'user_id' => $user->id,
        'domain_id' => $domain->id,
        'status' => 'active',
        'starts_at' => now(),
        'expires_at' => now()->addYear(),
    ]);

    $module = ContentModule::create([
        'domain_id' => $domain->id,
        'title' => 'Islamic Studies Quiz',
        'module_type' => 'preparation',
        'is_active' => true,
        'content_meta' => [
            'duration_minutes' => 15,
            'passing_score' => 50,
        ],
    ]);

    $q1 = QuestionBank::create([
        'question_type' => 'mcq',
        'question_text' => 'Question 1',
        'payload' => ['options' => ['A', 'B'], 'correct_option_index' => 0],
        'is_active' => true,
    ]);

    $q2 = QuestionBank::create([
        'question_type' => 'mcq',
        'question_text' => 'Question 2',
        'payload' => ['options' => ['A', 'B'], 'correct_option_index' => 1],
        'is_active' => true,
    ]);

    $module->questions()->attach($q1->id, ['sort_order' => 1, 'marks' => 1.00]);
    $module->questions()->attach($q2->id, ['sort_order' => 2, 'marks' => 1.00]);

    // Submit: q1 had answer revealed (should forfeit), q2 was answered incorrectly
    $response = $this
        ->actingAs($user)
        ->postJson(route('modules.submit', ['module' => $module->id]), [
            'answers' => [
                $q1->id => 0, // selected matching option after reveal
                $q2->id => 0, // incorrect (correct is 1)
            ],
            'revealed' => [
                $q1->id, // revealed!
            ],
        ]);

    $response->assertOk();
    $response->assertJson([
        'status' => 'success',
        'summary' => [
            'total_questions' => 2,
            'answered_count' => 2,
            'correct_count' => 0, // 0 because q1 was revealed (forfeited) and q2 was wrong!
            'percentage' => 0,
        ],
        'questions' => [
            $q1->id => [
                'correct' => false,
                'revealed' => true,
            ],
            $q2->id => [
                'correct' => false,
                'revealed' => false,
            ],
        ],
    ]);

    // Both questions should now be in user_weak_questions
    $this->assertDatabaseHas('user_weak_questions', [
        'user_id' => $user->id,
        'question_id' => $q1->id,
        'revealed_count' => 1,
        'status' => 'active',
    ]);

    $this->assertDatabaseHas('user_weak_questions', [
        'user_id' => $user->id,
        'question_id' => $q2->id,
        'failed_count' => 1,
        'status' => 'active',
    ]);
});

test('weak questions practice endpoint returns scrubbed questions without answer keys', function () {
    $user = User::factory()->create();

    $domain = Domain::create([
        'name' => 'History Domain',
        'slug' => 'history-'.uniqid(),
        'code' => 'HIST_'.uniqid(),
        'is_active' => true,
    ]);

    $qActive = QuestionBank::create([
        'question_type' => 'mcq',
        'question_text' => 'When did Pakistan gain independence?',
        'payload' => [
            'options' => ['1945', '1947', '1950'],
            'correct_option_index' => 1,
        ],
        'is_active' => true,
    ]);

    $qMastered = QuestionBank::create([
        'question_type' => 'mcq',
        'question_text' => 'Who was the founder of Pakistan?',
        'payload' => [
            'options' => ['Quaid-e-Azam', 'Allama Iqbal'],
            'correct_option_index' => 0,
        ],
        'is_active' => true,
    ]);

    UserWeakQuestion::create([
        'user_id' => $user->id,
        'question_id' => $qActive->id,
        'domain_id' => $domain->id,
        'status' => 'active',
        'consecutive_correct' => 1,
        'failed_count' => 1,
        'last_practiced_at' => now(),
    ]);

    UserWeakQuestion::create([
        'user_id' => $user->id,
        'question_id' => $qMastered->id,
        'domain_id' => $domain->id,
        'status' => 'mastered',
        'consecutive_correct' => 2,
        'failed_count' => 0,
        'last_practiced_at' => now(),
    ]);

    $response = $this
        ->actingAs($user)
        ->getJson(route('weak-questions.practice'));

    $response->assertOk();
    $data = $response->json();

    expect($data['status'])->toBe('success');
    expect($data['questions'])->toHaveCount(1);
    expect($data['questions'][0]['id'])->toBe($qActive->id);

    // Assert zero-knowledge answer key scrubbing
    expect($data['questions'][0]['payload'])->not->toHaveKey('correct_option_index');
});

test('weak questions index endpoint returns summary and items', function () {
    $user = User::factory()->create();

    $domain = Domain::create([
        'name' => 'Islamic Studies',
        'slug' => 'islamic-'.uniqid(),
        'code' => 'ISL_'.uniqid(),
        'is_active' => true,
    ]);

    $question = QuestionBank::create([
        'question_type' => 'mcq',
        'question_text' => 'How many verses in Surah Al-Fatihah?',
        'payload' => ['options' => ['5', '6', '7'], 'correct_option_index' => 2],
        'is_active' => true,
    ]);

    UserWeakQuestion::create([
        'user_id' => $user->id,
        'question_id' => $question->id,
        'domain_id' => $domain->id,
        'status' => 'active',
        'consecutive_correct' => 1,
        'revealed_count' => 1,
        'failed_count' => 2,
        'last_practiced_at' => now(),
    ]);

    $response = $this
        ->actingAs($user)
        ->getJson(route('weak-questions.api.index'));

    $response->assertOk();
    $response->assertJson([
        'status' => 'success',
        'summary' => [
            'total_active' => 1,
            'total_mastered' => 0,
            'total_tracked' => 1,
            'by_subject' => [
                'Islamic Studies' => 1,
            ],
        ],
    ]);
});
