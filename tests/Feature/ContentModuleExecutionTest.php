<?php

namespace Tests\Feature;

use App\Models\ContentModule;
use App\Models\Domain;
use App\Models\QuestionBank;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ContentModuleExecutionTest extends TestCase
{
    use DatabaseTransactions;

    protected Domain $domain;

    protected User $subscriber;

    protected ContentModule $mockTestModule;

    protected QuestionBank $questionA;

    protected QuestionBank $questionB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->domain = Domain::create([
            'name' => 'PMA Long Course',
            'slug' => 'pma-long-course',
            'code' => 'PMA_LC',
            'is_active' => true,
        ]);

        $this->subscriber = User::factory()->create([
            'name' => 'Cadet Bilal',
            'email' => 'bilal@example.com',
        ]);

        UserSubscription::create([
            'user_id' => $this->subscriber->id,
            'domain_id' => $this->domain->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addDays(30),
        ]);

        $this->mockTestModule = ContentModule::create([
            'domain_id' => $this->domain->id,
            'title' => 'Verbal Intelligence Mock Test 1',
            'slug' => 'verbal-mock-1',
            'module_type' => 'mock_test',
            'is_active' => true,
            'content_meta' => [
                'passing_score' => 50,
                'duration_minutes' => 30,
            ],
        ]);

        $this->questionA = QuestionBank::create([
            'code' => 'Q-VERB-01',
            'question_text' => 'What comes next in the sequence 2, 4, 8, 16, ?',
            'question_type' => 'mcq',
            'difficulty_level' => 'easy',
            'payload' => [
                'options' => [
                    ['text' => '24', 'is_correct' => false],
                    ['text' => '32', 'is_correct' => true],
                    ['text' => '64', 'is_correct' => false],
                ],
                'correct_option_index' => 1,
            ],
            'explanation' => 'Each number is multiplied by 2.',
            'is_active' => true,
        ]);

        $this->questionB = QuestionBank::create([
            'code' => 'Q-VERB-02',
            'question_text' => 'Day is to Night as White is to ?',
            'question_type' => 'mcq',
            'difficulty_level' => 'easy',
            'payload' => [
                'options' => [
                    ['text' => 'Black', 'is_correct' => true],
                    ['text' => 'Grey', 'is_correct' => false],
                ],
                'correct_option_index' => 0,
            ],
            'explanation' => 'Antonym relationship.',
            'is_active' => true,
        ]);

        $this->mockTestModule->questions()->attach($this->questionA->id, ['sort_order' => 1, 'marks' => 1.00]);
        $this->mockTestModule->questions()->attach($this->questionB->id, ['sort_order' => 2, 'marks' => 1.00]);
    }

    public function test_module_endpoint_scrubs_correct_answers_and_explanations(): void
    {
        $response = $this->actingAs($this->subscriber)
            ->getJson("/api/modules/{$this->mockTestModule->id}");

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
            ]);

        $questions = $response->json('questions');
        $this->assertCount(2, $questions);

        foreach ($questions as $q) {
            // Verify Zero-Knowledge scrubbing
            $this->assertArrayNotHasKey('correct_option_index', $q['payload']);
            $this->assertNull($q['explanation']);

            foreach ($q['payload']['options'] as $option) {
                // Must be normalized string or have is_correct unset
                $this->assertIsString($option);
            }
        }
    }

    public function test_candidate_can_submit_answers_for_server_authoritative_grading(): void
    {
        // Candidate selects index 1 for questionA (correct) and index 1 for questionB (incorrect: correct is 0)
        $response = $this->actingAs($this->subscriber)
            ->postJson("/api/modules/{$this->mockTestModule->id}/submit", [
                'answers' => [
                    $this->questionA->id => 1,
                    $this->questionB->id => 1,
                ],
            ]);

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'summary' => [
                    'total_questions' => 2,
                    'answered_count' => 2,
                    'correct_count' => 1,
                    'percentage' => 50,
                    'passing_score' => 50,
                    'is_passed' => true,
                ],
            ]);

        $results = $response->json('questions');

        // Question A was correct
        $this->assertTrue($results[$this->questionA->id]['correct']);
        $this->assertSame(1, $results[$this->questionA->id]['correct_index']);
        $this->assertSame('Each number is multiplied by 2.', $results[$this->questionA->id]['explanation']);

        // Question B was incorrect
        $this->assertFalse($results[$this->questionB->id]['correct']);
        $this->assertSame(0, $results[$this->questionB->id]['correct_index']);
        $this->assertSame('Antonym relationship.', $results[$this->questionB->id]['explanation']);
    }

    public function test_submitting_empty_answers_array_succeeds_without_validation_crash(): void
    {
        $response = $this->actingAs($this->subscriber)
            ->postJson("/api/modules/{$this->mockTestModule->id}/submit", [
                'answers' => [],
            ]);

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'summary' => [
                    'total_questions' => 2,
                    'answered_count' => 0,
                    'correct_count' => 0,
                    'percentage' => 0,
                    'is_passed' => false,
                ],
            ]);
    }

    public function test_malicious_non_numeric_answers_do_not_falsely_match_zero_index(): void
    {
        // Question B correct index is 0. Sending 'hacked' or 'banana' must NOT evaluate as 0!
        $response = $this->actingAs($this->subscriber)
            ->postJson("/api/modules/{$this->mockTestModule->id}/submit", [
                'answers' => [
                    $this->questionB->id => 'hacked_string',
                ],
            ]);

        $response->assertOk();
        $results = $response->json('questions');
        $this->assertFalse($results[$this->questionB->id]['correct']);
    }
}
