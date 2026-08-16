<?php

use App\Models\ContentModule;
use App\Models\Domain;
use App\Models\QuestionBank;
use App\Models\User;
use App\Models\UserSubscription;

test('authenticated users can submit and evaluate MCQ test modules', function () {
    $user = User::factory()->create();

    $domain = Domain::create([
        'name' => 'PMA Intelligence Prep',
        'slug' => 'pma-intel',
        'code' => 'PMA',
        'is_active' => true,
    ]);

    $module = ContentModule::create([
        'domain_id' => $domain->id,
        'title' => 'Verbal Quiz',
        'module_type' => 'preparation',
        'is_active' => true,
        'content_meta' => [
            'duration_minutes' => 15,
            'passing_score' => 50,
        ],
    ]);

    $q1 = QuestionBank::create([
        'question_type' => 'mcq',
        'question_text' => 'Option A is correct.',
        'payload' => [
            'options' => ['Option A', 'Option B'],
            'correct_option_index' => 0,
        ],
        'is_active' => true,
    ]);

    $q2 = QuestionBank::create([
        'question_type' => 'mcq',
        'question_text' => 'Option B is correct.',
        'payload' => [
            'options' => ['Option A', 'Option B'],
            'correct_option_index' => 1,
        ],
        'is_active' => true,
    ]);

    $module->questions()->attach($q1->id, ['sort_order' => 1, 'marks' => 1.00]);
    $module->questions()->attach($q2->id, ['sort_order' => 2, 'marks' => 1.00]);

    $response = $this
        ->actingAs($user)
        ->postJson(route('modules.submit', ['module' => $module->id]), [
            'answers' => [
                $q1->id => 0, // Correct
                $q2->id => 0, // Incorrect
            ],
        ]);

    $response->assertOk();
    $response->assertJson([
        'status' => 'success',
        'summary' => [
            'total_questions' => 2,
            'answered_count' => 2,
            'correct_count' => 1,
            'percentage' => 50,
            'is_passed' => true,
        ],
    ]);
});

test('users can fetch module details and questions if subscribed', function () {
    $user = User::factory()->create();

    $domain = Domain::create([
        'name' => 'PMA Intelligence Prep',
        'slug' => 'pma-intel',
        'code' => 'PMA',
        'is_active' => true,
    ]);

    $module = ContentModule::create([
        'domain_id' => $domain->id,
        'title' => 'Verbal Quiz',
        'module_type' => 'preparation',
        'is_active' => true,
        'content_meta' => [
            'duration_minutes' => 15,
            'passing_score' => 50,
        ],
    ]);

    $q1 = QuestionBank::create([
        'question_type' => 'mcq',
        'question_text' => 'Option A is correct.',
        'payload' => [
            'options' => ['Option A', 'Option B'],
            'correct_option_index' => 0,
        ],
        'is_active' => true,
    ]);

    $module->questions()->attach($q1->id, ['sort_order' => 1, 'marks' => 1.00]);

    // Create subscription
    UserSubscription::create([
        'user_id' => $user->id,
        'domain_id' => $domain->id,
        'status' => 'active',
        'starts_at' => now(),
        'expires_at' => now()->addYear(),
    ]);

    $response = $this
        ->actingAs($user)
        ->getJson(route('modules.show', ['module' => $module->id]));

    $response->assertOk();
    $response->assertJson([
        'status' => 'success',
        'module' => [
            'id' => $module->id,
            'title' => 'Verbal Quiz',
        ],
    ]);
});

test('users cannot fetch module details if not subscribed', function () {
    $user = User::factory()->create();

    $domain = Domain::create([
        'name' => 'PMA Intelligence Prep',
        'slug' => 'pma-intel',
        'code' => 'PMA',
        'is_active' => true,
    ]);

    $module = ContentModule::create([
        'domain_id' => $domain->id,
        'title' => 'Verbal Quiz',
        'module_type' => 'preparation',
        'is_active' => true,
        'content_meta' => [],
    ]);

    $response = $this
        ->actingAs($user)
        ->getJson(route('modules.show', ['module' => $module->id]));

    $response->assertStatus(403);
});
