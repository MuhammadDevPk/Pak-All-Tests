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
}
