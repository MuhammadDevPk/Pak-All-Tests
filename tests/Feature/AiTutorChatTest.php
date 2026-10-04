<?php

namespace Tests\Feature;

use App\Models\ContentModule;
use App\Models\Domain;
use App\Models\DomainChatMessage;
use App\Models\QuestionBank;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\Ai\EmbeddingService;
use App\Services\Ai\KnowledgeVectorizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiTutorChatTest extends TestCase
{
    use RefreshDatabase;

    protected Domain $domain;

    protected User $subscriber;

    protected User $guestUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create active domain
        $this->domain = Domain::create([
            'name' => 'PMA Long Course',
            'slug' => 'pma-long-course',
            'code' => 'PMA_LC',
            'is_active' => true,
        ]);

        // Create subscribed user
        $this->subscriber = User::factory()->create([
            'name' => 'Cadet Tariq',
            'email' => 'tariq@example.com',
        ]);

        UserSubscription::create([
            'user_id' => $this->subscriber->id,
            'domain_id' => $this->domain->id,
            'status' => 'active',
            'payment_method' => 'easypaisa',
            'transaction_id' => 'TRX-998877',
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addDays(30),
        ]);

        // Create unsubscribed user
        $this->guestUser = User::factory()->create([
            'name' => 'Unsubscribed User',
            'email' => 'guest@example.com',
        ]);
    }

    public function test_unauthenticated_user_cannot_access_ai_tutor(): void
    {
        $this->getJson("/api/domains/{$this->domain->slug}/ai-tutor/messages")
            ->assertUnauthorized();

        $this->postJson("/api/domains/{$this->domain->slug}/ai-tutor/ask", [
            'message' => 'Hello tutor',
        ])->assertUnauthorized();

        $this->postJson("/api/domains/{$this->domain->slug}/ai-tutor/clear")
            ->assertUnauthorized();
    }

    public function test_unsubscribed_candidate_is_forbidden(): void
    {
        $this->actingAs($this->guestUser)
            ->getJson("/api/domains/{$this->domain->slug}/ai-tutor/messages")
            ->assertForbidden();

        $this->actingAs($this->guestUser)
            ->postJson("/api/domains/{$this->domain->slug}/ai-tutor/ask", [
                'message' => 'Give me a hint',
            ])
            ->assertForbidden();

        $this->actingAs($this->guestUser)
            ->postJson("/api/domains/{$this->domain->slug}/ai-tutor/clear")
            ->assertForbidden();
    }

    public function test_subscribed_candidate_can_fetch_empty_conversation_history(): void
    {
        $response = $this->actingAs($this->subscriber)
            ->getJson("/api/domains/{$this->domain->slug}/ai-tutor/messages");

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'domain' => [
                    'id' => $this->domain->id,
                    'name' => 'PMA Long Course',
                    'slug' => 'pma-long-course',
                ],
                'messages' => [],
            ]);
    }

    public function test_subscribed_candidate_receives_sse_stream_and_persists_messages(): void
    {
        $response = $this->actingAs($this->subscriber)
            ->postJson("/api/domains/{$this->domain->slug}/ai-tutor/ask", [
                'message' => 'Can you give me a pattern breakdown for verbal analogy?',
                'stream' => true,
            ]);

        $response->assertOk();
        $this->assertStringContainsString('text/event-stream', (string) $response->headers->get('Content-Type'));

        // Capture streamed response content
        $streamedContent = $response->streamedContent();

        $this->assertStringContainsString('data: {"token":', $streamedContent);
        $this->assertStringContainsString('data: [DONE]', $streamedContent);

        // Verify messages persisted in database
        $this->assertDatabaseHas('domain_chat_messages', [
            'domain_id' => $this->domain->id,
            'user_id' => $this->subscriber->id,
            'role' => 'user',
            'content' => 'Can you give me a pattern breakdown for verbal analogy?',
        ]);

        $this->assertDatabaseHas('domain_chat_messages', [
            'domain_id' => $this->domain->id,
            'user_id' => $this->subscriber->id,
            'role' => 'assistant',
        ]);

        // Verify history endpoint returns both messages
        $historyResponse = $this->actingAs($this->subscriber)
            ->getJson("/api/domains/{$this->domain->slug}/ai-tutor/messages");

        $historyResponse->assertOk();
        $messages = $historyResponse->json('messages');
        $this->assertCount(2, $messages);
        $this->assertSame('user', $messages[0]['role']);
        $this->assertSame('assistant', $messages[1]['role']);
    }

    public function test_candidate_can_query_json_fallback_mode(): void
    {
        $response = $this->actingAs($this->subscriber)
            ->postJson("/api/domains/{$this->domain->slug}/ai-tutor/ask", [
                'message' => 'What is the passing criteria and time strategy?',
                'stream' => false,
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'response',
            ]);

        $this->assertSame('success', $response->json('status'));
        $this->assertNotEmpty($response->json('response'));
        $this->assertStringContainsString('Exam Strategy', $response->json('response'));
    }

    public function test_ai_tutor_incorporates_rag_vector_chunks_and_question_context(): void
    {
        // 1. Seed module and question
        $module = ContentModule::create([
            'domain_id' => $this->domain->id,
            'title' => 'Verbal Reasoning Module 1',
            'slug' => 'verbal-reasoning-1',
            'module_type' => 'preparation',
            'is_active' => true,
        ]);

        $question = QuestionBank::create([
            'code' => 'VR-MCQ-001',
            'question_text' => 'Architect is to Blueprint as Author is to ___?',
            'question_type' => 'mcq',
            'difficulty_level' => 'easy',
            'payload' => [
                'options' => ['Chapter', 'Novel', 'Pen', 'Publishing'],
                'correct_option_index' => 1,
            ],
            'is_active' => true,
        ]);

        $question->modules()->attach($module->id);

        // 2. Vectorize the question
        $embeddingService = new EmbeddingService('fake');
        $vectorizer = new KnowledgeVectorizationService($embeddingService);
        $vectorizer->vectorizeQuestion($question, $this->domain->id);

        // 3. Candidate asks question with active question context
        $response = $this->actingAs($this->subscriber)
            ->postJson("/api/domains/{$this->domain->slug}/ai-tutor/ask", [
                'message' => 'Give me a hint for this analogy',
                'context' => [
                    'active_module_id' => $module->id,
                    'active_question_id' => $question->id,
                    'active_question_text' => 'Architect is to Blueprint as Author is to ___?',
                ],
                'stream' => true,
            ]);

        $response->assertOk();
        $response->streamedContent();

        // 4. Verify assistant message was saved with RAG metadata
        $assistantMsg = DomainChatMessage::where('domain_id', $this->domain->id)
            ->where('user_id', $this->subscriber->id)
            ->where('role', 'assistant')
            ->latest('id')
            ->first();

        $this->assertNotNull($assistantMsg);
        $this->assertIsArray($assistantMsg->context_metadata);
        $this->assertArrayHasKey('sources', $assistantMsg->context_metadata);
        $this->assertNotEmpty($assistantMsg->context_metadata['sources']);
        $this->assertSame('VR-MCQ-001', $assistantMsg->context_metadata['sources'][0]['title']);
    }

    public function test_candidate_can_clear_history_strictly_scoped_to_user_and_domain(): void
    {
        $otherDomain = Domain::create([
            'name' => 'CSS Competitive Exam',
            'slug' => 'css-exam',
            'code' => 'CSS_EXAM',
            'is_active' => true,
        ]);

        $otherUser = User::factory()->create(['name' => 'Other Candidate']);

        // 1. Messages for subscriber in current domain
        DomainChatMessage::create([
            'domain_id' => $this->domain->id,
            'user_id' => $this->subscriber->id,
            'role' => 'user',
            'content' => 'Question 1',
        ]);
        DomainChatMessage::create([
            'domain_id' => $this->domain->id,
            'user_id' => $this->subscriber->id,
            'role' => 'assistant',
            'content' => 'Answer 1',
        ]);

        // 2. Messages for other user in current domain
        DomainChatMessage::create([
            'domain_id' => $this->domain->id,
            'user_id' => $otherUser->id,
            'role' => 'user',
            'content' => 'Other user question',
        ]);

        // 3. Messages for subscriber in other domain
        DomainChatMessage::create([
            'domain_id' => $otherDomain->id,
            'user_id' => $this->subscriber->id,
            'role' => 'user',
            'content' => 'CSS question',
        ]);

        $this->assertSame(2, DomainChatMessage::where('domain_id', $this->domain->id)->where('user_id', $this->subscriber->id)->count());

        // Clear history for current domain
        $response = $this->actingAs($this->subscriber)
            ->postJson("/api/domains/{$this->domain->slug}/ai-tutor/clear");

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'message' => 'Conversation history cleared successfully.',
            ]);

        // Subscriber messages in this domain are deleted
        $this->assertSame(0, DomainChatMessage::where('domain_id', $this->domain->id)->where('user_id', $this->subscriber->id)->count());

        // Other user's messages in this domain are NOT deleted
        $this->assertSame(1, DomainChatMessage::where('domain_id', $this->domain->id)->where('user_id', $otherUser->id)->count());

        // Subscriber's messages in other domain are NOT deleted
        $this->assertSame(1, DomainChatMessage::where('domain_id', $otherDomain->id)->where('user_id', $this->subscriber->id)->count());
    }

    public function test_ai_tutor_accurately_identifies_current_page_and_visible_questions_in_page_view(): void
    {
        $response = $this->actingAs($this->subscriber)
            ->postJson("/api/domains/{$this->domain->slug}/ai-tutor/ask", [
                'message' => "Now I'm on which question? what content/questions are on my page",
                'context' => [
                    'active_module_id' => null,
                    'active_mcq' => [
                        'id' => 697,
                        'number' => 81,
                        'statement' => 'In which year was Dr. Abdus Salam awarded the Nobel Prize in Physics?',
                        'options' => ['A) 1974', 'B) 1979', 'C) 1984', 'D) 1998'],
                        'user_selected' => 'B) 1979',
                        'attempts_made' => 0,
                    ],
                    'page_context' => [
                        'view_mode' => 'page',
                        'current_page' => 9,
                        'total_pages' => 9,
                        'page_size' => 10,
                        'showing_from' => 81,
                        'showing_to' => 85,
                        'total_questions' => 85,
                        'active_question_number' => 81,
                        'visible_questions' => [
                            [
                                'number' => 81,
                                'id' => 697,
                                'statement' => 'In which year was Dr. Abdus Salam awarded the Nobel Prize in Physics?',
                                'options' => ['A) 1974', 'B) 1979', 'C) 1984', 'D) 1998'],
                                'user_selected' => 'B) 1979',
                                'is_answered' => true,
                            ],
                            [
                                'number' => 82,
                                'id' => 698,
                                'statement' => 'Which national football team won the FIFA World Cup 2022 in Qatar?',
                                'options' => ['A) France', 'B) Argentina', 'C) Brazil', 'D) Morocco'],
                                'user_selected' => null,
                                'is_answered' => false,
                            ],
                        ],
                    ],
                ],
                'stream' => true,
            ]);

        $response->assertOk();
        $streamedContent = $response->streamedContent();

        $this->assertStringContainsString('data: {"token":', $streamedContent);
        $this->assertStringContainsString('data: [DONE]', $streamedContent);

        // Verify assistant message content in database
        $assistantMsg = DomainChatMessage::where('domain_id', $this->domain->id)
            ->where('user_id', $this->subscriber->id)
            ->where('role', 'assistant')
            ->latest('id')
            ->first();

        $this->assertNotNull($assistantMsg);
        $this->assertStringContainsString('Page 9 of 9', $assistantMsg->content);
        $this->assertStringContainsString('Questions 81 to 85', $assistantMsg->content);
        $this->assertStringContainsString('Question 81', $assistantMsg->content);

        // Verify persisted context
        $savedMessage = DomainChatMessage::where('domain_id', $this->domain->id)
            ->where('user_id', $this->subscriber->id)
            ->where('role', 'user')
            ->latest('id')
            ->first();

        $this->assertNotNull($savedMessage);
        $this->assertSame(9, $savedMessage->context_metadata['page_context']['current_page']);
        $this->assertSame(81, $savedMessage->context_metadata['page_context']['showing_from']);
        $this->assertSame(85, $savedMessage->context_metadata['page_context']['showing_to']);
    }

    public function test_ai_tutor_accurately_identifies_question_in_focus_mode(): void
    {
        $response = $this->actingAs($this->subscriber)
            ->postJson("/api/domains/{$this->domain->slug}/ai-tutor/ask", [
                'message' => 'Which question am I on right now?',
                'context' => [
                    'active_mcq' => [
                        'id' => 697,
                        'number' => 81,
                        'statement' => 'In which year was Dr. Abdus Salam awarded the Nobel Prize in Physics?',
                    ],
                    'page_context' => [
                        'view_mode' => 'focus',
                        'current_page' => 1,
                        'total_pages' => 85,
                        'page_size' => 1,
                        'showing_from' => 81,
                        'showing_to' => 81,
                        'total_questions' => 85,
                        'active_question_number' => 81,
                        'visible_questions' => [
                            [
                                'number' => 81,
                                'id' => 697,
                                'statement' => 'In which year was Dr. Abdus Salam awarded the Nobel Prize in Physics?',
                            ],
                        ],
                    ],
                ],
                'stream' => true,
            ]);

        $response->assertOk();
        $streamedContent = $response->streamedContent();

        $this->assertStringContainsString('data: {"token":', $streamedContent);
        $this->assertStringContainsString('data: [DONE]', $streamedContent);

        $assistantMsg = DomainChatMessage::where('domain_id', $this->domain->id)
            ->where('user_id', $this->subscriber->id)
            ->where('role', 'assistant')
            ->latest('id')
            ->first();

        $this->assertNotNull($assistantMsg);
        $this->assertStringContainsString('Focus Mode', $assistantMsg->content);
        $this->assertStringContainsString('Question 81', $assistantMsg->content);
    }
}
