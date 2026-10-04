<?php

namespace Tests\Feature;

use App\Filament\Resources\Domains\Pages\ListDomains;
use App\Jobs\VectorizeDomainKnowledgeJob;
use App\Models\ContentModule;
use App\Models\Domain;
use App\Models\DomainKnowledgeEmbedding;
use App\Models\QuestionBank;
use App\Models\User;
use App\Services\Ai\EmbeddingService;
use App\Services\Ai\KnowledgeVectorizationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class DomainKnowledgeVectorizationTest extends TestCase
{
    use DatabaseTransactions;

    protected EmbeddingService $embeddingService;

    protected KnowledgeVectorizationService $vectorizationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->embeddingService = new EmbeddingService('fake');
        $this->vectorizationService = new KnowledgeVectorizationService($this->embeddingService);
    }

    public function test_embedding_service_generates_normalized_vectors_with_exact_dimensions(): void
    {
        $vector = $this->embeddingService->embed('Verbal intelligence analogy test');

        $this->assertCount(1536, $vector);
        $this->assertSame(1536, $this->embeddingService->dimension());

        // L2 Norm should be approximately 1.0 (normalized unit vector)
        $norm = sqrt(array_sum(array_map(fn ($x) => $x * $x, $vector)));
        $this->assertEqualsWithDelta(1.0, $norm, 0.001);

        $sql = EmbeddingService::vectorToSql($vector);
        $this->assertStringStartsWith('[', $sql);
        $this->assertStringEndsWith(']', $sql);
    }

    public function test_vectorize_module_creates_knowledge_embedding(): void
    {
        $domain = Domain::create([
            'name' => 'PMA Long Course',
            'slug' => 'pma-long-course',
            'code' => 'PMA_LC',
            'is_active' => true,
        ]);

        $module = ContentModule::create([
            'domain_id' => $domain->id,
            'title' => 'Verbal Intelligence Test 1',
            'slug' => 'verbal-test-1',
            'module_type' => 'preparation',
            'sort_order' => 1,
            'is_active' => true,
            'content_meta' => [
                'description' => 'Master verbal analogy and number series questions.',
                'instructions' => 'Complete all 50 questions within 30 minutes.',
            ],
        ]);

        $record = $this->vectorizationService->vectorizeModule($module);

        $this->assertNotNull($record);
        $this->assertInstanceOf(DomainKnowledgeEmbedding::class, $record);
        $this->assertSame($domain->id, $record->domain_id);
        $this->assertSame($module->id, $record->content_module_id);
        $this->assertSame('content_module', $record->source_type);
        $this->assertStringContainsString('Verbal Intelligence Test 1', $record->content);
        $this->assertStringContainsString('Master verbal analogy', $record->content);

        $this->assertDatabaseHas('domain_knowledge_embeddings', [
            'domain_id' => $domain->id,
            'content_module_id' => $module->id,
            'source_type' => 'content_module',
        ]);
    }

    public function test_vectorize_question_creates_knowledge_embedding_for_associated_domain(): void
    {
        $domain = Domain::create([
            'name' => 'Pakistan Air Force Initial',
            'slug' => 'paf-initial',
            'code' => 'PAF_INIT',
            'is_active' => true,
        ]);

        $module = ContentModule::create([
            'domain_id' => $domain->id,
            'title' => 'Physics Mechanics',
            'slug' => 'physics-mechanics',
            'module_type' => 'preparation',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $question = QuestionBank::create([
            'question_type' => 'mcq',
            'code' => 'Q-PHY-001',
            'question_text' => '<p>What is the SI unit of force?</p>',
            'payload' => [
                'options' => [
                    ['text' => 'Joule', 'is_correct' => false],
                    ['text' => 'Newton', 'is_correct' => true],
                    ['text' => 'Watt', 'is_correct' => false],
                    ['text' => 'Pascal', 'is_correct' => false],
                ],
                'correct_option_index' => 1,
            ],
            'difficulty_level' => 'easy',
            'explanation' => 'One Newton is the force required to accelerate 1 kg by 1 m/s^2.',
            'is_active' => true,
        ]);

        $module->questions()->attach($question->id, ['sort_order' => 1, 'marks' => 1.00]);

        $records = $this->vectorizationService->vectorizeQuestion($question);

        $this->assertCount(1, $records);
        $record = $records[0];

        $this->assertSame($domain->id, $record->domain_id);
        $this->assertSame($question->id, $record->question_bank_id);
        $this->assertSame('question', $record->source_type);
        $this->assertStringContainsString('What is the SI unit of force?', $record->content);
        $this->assertStringContainsString('Newton', $record->content);
        $this->assertStringContainsString('One Newton is the force required', $record->content);

        // Verify relationships
        $this->assertTrue($question->knowledgeEmbeddings()->exists());
        $this->assertTrue($domain->knowledgeEmbeddings()->exists());
    }

    public function test_vectorize_domain_batch_processes_all_modules_and_questions(): void
    {
        $domain = Domain::create([
            'name' => 'CSS Competitive Exam',
            'slug' => 'css-exam',
            'code' => 'CSS',
            'is_active' => true,
        ]);

        $moduleA = ContentModule::create([
            'domain_id' => $domain->id,
            'title' => 'Pakistan Affairs',
            'slug' => 'pak-affairs',
            'module_type' => 'preparation',
            'sort_order' => 1,
            'is_active' => true,
            'content_meta' => ['description' => 'Constitutional history and political developments.'],
        ]);

        $moduleB = ContentModule::create([
            'domain_id' => $domain->id,
            'title' => 'English Essay Guide',
            'slug' => 'english-essay-guide',
            'module_type' => 'pdf_repo',
            'sort_order' => 2,
            'is_active' => true,
            'content_meta' => ['file_type' => 'PDF Document', 'description' => 'Essay outline templates.'],
        ]);

        $q1 = QuestionBank::create([
            'question_type' => 'mcq',
            'code' => 'Q-CSS-001',
            'question_text' => 'In which year was the Lahore Resolution passed?',
            'payload' => ['options' => ['1930', '1940', '1947']],
            'difficulty_level' => 'easy',
            'is_active' => true,
        ]);

        $moduleA->questions()->attach($q1->id, ['sort_order' => 1, 'marks' => 1.00]);

        $result = $this->vectorizationService->vectorizeDomain($domain);

        $this->assertSame($domain->id, $result['domain_id']);
        $this->assertSame(2, $result['modules_count']);
        $this->assertSame(1, $result['questions_count']);
        $this->assertSame(3, $result['embeddings_count']); // 2 modules + 1 question

        $this->assertSame(3, DomainKnowledgeEmbedding::where('domain_id', $domain->id)->count());
    }

    public function test_nearest_cosine_similarity_search_respects_domain_partition(): void
    {
        // 1. Create PMA Domain with Verbal Analogy question
        $pmaDomain = Domain::create([
            'name' => 'PMA Long Course',
            'slug' => 'pma-long-course',
            'code' => 'PMA',
            'is_active' => true,
        ]);

        $pmaModule = ContentModule::create([
            'domain_id' => $pmaDomain->id,
            'title' => 'Verbal Intelligence',
            'slug' => 'verbal-intelligence',
            'module_type' => 'preparation',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $pmaQuestion = QuestionBank::create([
            'question_type' => 'mcq',
            'code' => 'Q-VERB-ANALOGY',
            'question_text' => 'Tree is to Leaf as Book is to Page in verbal analogy test.',
            'payload' => ['options' => ['Word', 'Cover', 'Page', 'Library']],
            'difficulty_level' => 'easy',
            'is_active' => true,
        ]);
        $pmaModule->questions()->attach($pmaQuestion->id, ['sort_order' => 1, 'marks' => 1.00]);

        // 2. Create CSS Domain with Economics Essay rubric
        $cssDomain = Domain::create([
            'name' => 'CSS Competitive Exam',
            'slug' => 'css-competitive',
            'code' => 'CSS',
            'is_active' => true,
        ]);

        $cssModule = ContentModule::create([
            'domain_id' => $cssDomain->id,
            'title' => 'Geo-Economics and Monetary Policy',
            'slug' => 'geo-economics',
            'module_type' => 'subjective',
            'sort_order' => 1,
            'is_active' => true,
            'content_meta' => ['description' => 'Macroeconomic policy analysis and foreign debt restructuring in Pakistan.'],
        ]);

        // 3. Vectorize both domains
        $this->vectorizationService->vectorizeDomain($pmaDomain);
        $this->vectorizationService->vectorizeDomain($cssDomain);

        // 4. Perform search scoped to PMA Domain
        $pmaResults = $this->vectorizationService->findSimilar(
            domainId: $pmaDomain->id,
            queryText: 'Book page verbal analogy leaf tree',
            limit: 3
        );

        $this->assertNotEmpty($pmaResults);
        $topMatch = $pmaResults->first();
        $this->assertSame($pmaDomain->id, $topMatch->domain_id);
        $this->assertSame($pmaQuestion->id, $topMatch->question_bank_id);
        $this->assertGreaterThan(0.0, (float) $topMatch->similarity);

        // 5. Verify strict domain partition: CSS search for "verbal analogy" must NOT return PMA question
        $cssResults = $this->vectorizationService->findSimilar(
            domainId: $cssDomain->id,
            queryText: 'Book page verbal analogy leaf tree',
            limit: 3
        );

        foreach ($cssResults as $result) {
            $this->assertSame($cssDomain->id, $result->domain_id);
            $this->assertNotEquals($pmaQuestion->id, $result->question_bank_id);
        }
    }

    public function test_cascade_deletion_cleans_up_domain_knowledge_embeddings(): void
    {
        $domain = Domain::create([
            'name' => 'MDCAT Medical Entrance',
            'slug' => 'mdcat-test',
            'code' => 'MDCAT',
            'is_active' => true,
        ]);

        $module = ContentModule::create([
            'domain_id' => $domain->id,
            'title' => 'Biology Cell Division',
            'slug' => 'bio-cells',
            'module_type' => 'preparation',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $question = QuestionBank::create([
            'question_type' => 'mcq',
            'code' => 'Q-BIO-001',
            'question_text' => 'During which phase of mitosis do chromosomes align on the equator?',
            'payload' => ['options' => ['Prophase', 'Metaphase', 'Anaphase', 'Telophase']],
            'difficulty_level' => 'medium',
            'is_active' => true,
        ]);
        $module->questions()->attach($question->id, ['sort_order' => 1, 'marks' => 1.00]);

        $this->vectorizationService->vectorizeDomain($domain);

        $this->assertSame(2, DomainKnowledgeEmbedding::where('domain_id', $domain->id)->count());

        // Deleting the domain must cascade and remove all its embeddings
        $domain->delete();

        $this->assertSame(0, DomainKnowledgeEmbedding::where('domain_id', $domain->id)->count());
    }

    public function test_vectorize_domain_knowledge_job_executes_successfully(): void
    {
        $domain = Domain::create([
            'name' => 'Pakistan Navy Cadet',
            'slug' => 'navy-cadet',
            'code' => 'NAVY',
            'is_active' => true,
        ]);

        ContentModule::create([
            'domain_id' => $domain->id,
            'title' => 'General Science & Navigation',
            'slug' => 'science-navigation',
            'module_type' => 'preparation',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $job = new VectorizeDomainKnowledgeJob(domainId: $domain->id);
        $job->handle($this->vectorizationService);

        $this->assertTrue(DomainKnowledgeEmbedding::where('domain_id', $domain->id)->exists());
    }

    public function test_filament_admin_can_trigger_sync_ai_vectors_action(): void
    {
        $admin = User::factory()->create();

        $domain = Domain::create([
            'name' => 'CSS Pakistan Affairs Domain',
            'slug' => 'css-pak-affairs-domain',
            'code' => 'CSS_PAK_AFF',
            'is_active' => true,
        ]);

        ContentModule::create([
            'domain_id' => $domain->id,
            'title' => 'Constitutional Amendments',
            'slug' => 'constitutional-amendments',
            'module_type' => 'preparation',
            'sort_order' => 1,
            'is_active' => true,
            'content_meta' => ['description' => 'Detailed timeline of all constitutional amendments.'],
        ]);

        Livewire::actingAs($admin)
            ->test(ListDomains::class)
            ->callTableAction('syncKnowledge', $domain)
            ->assertHasNoTableActionErrors();

        $this->assertTrue(
            DomainKnowledgeEmbedding::where('domain_id', $domain->id)
                ->where('title', 'Constitutional Amendments')
                ->exists()
        );
    }

    public function test_orphan_cleanup_removes_unassociated_embeddings_when_question_moves(): void
    {
        $domainA = Domain::create(['name' => 'Domain A', 'slug' => 'domain-a', 'code' => 'DOM_A', 'is_active' => true]);
        $domainB = Domain::create(['name' => 'Domain B', 'slug' => 'domain-b', 'code' => 'DOM_B', 'is_active' => true]);

        $moduleA = ContentModule::create(['domain_id' => $domainA->id, 'title' => 'Module A', 'slug' => 'mod-a', 'module_type' => 'preparation', 'is_active' => true]);
        $moduleB = ContentModule::create(['domain_id' => $domainB->id, 'title' => 'Module B', 'slug' => 'mod-b', 'module_type' => 'preparation', 'is_active' => true]);

        $question = QuestionBank::create([
            'question_type' => 'mcq',
            'code' => 'Q-ORPHAN-01',
            'question_text' => 'Sample test question',
            'is_active' => true,
        ]);

        // Attach to Domain A and vectorize
        $moduleA->questions()->attach($question->id);
        $this->vectorizationService->vectorizeQuestion($question);

        $this->assertSame(1, DomainKnowledgeEmbedding::where('question_bank_id', $question->id)->where('domain_id', $domainA->id)->count());

        // Now move to Domain B (detach from A, attach to B)
        $moduleA->questions()->detach($question->id);
        $moduleB->questions()->attach($question->id);

        $this->vectorizationService->vectorizeQuestion($question);

        // Domain A's embedding must be cleaned up, Domain B must have the embedding
        $this->assertSame(0, DomainKnowledgeEmbedding::where('question_bank_id', $question->id)->where('domain_id', $domainA->id)->count());
        $this->assertSame(1, DomainKnowledgeEmbedding::where('question_bank_id', $question->id)->where('domain_id', $domainB->id)->count());
    }

    public function test_find_similar_filters_by_minimum_similarity_threshold(): void
    {
        $domain = Domain::create(['name' => 'Threshold Domain', 'slug' => 'threshold-domain', 'code' => 'THRESH', 'is_active' => true]);
        $module = ContentModule::create(['domain_id' => $domain->id, 'title' => 'General Science Physics', 'slug' => 'gen-sci', 'module_type' => 'preparation', 'is_active' => true]);

        $this->vectorizationService->vectorizeModule($module);

        // Matching query
        $results = $this->vectorizationService->findSimilar($domain->id, 'Physics Science', limit: 5, minSimilarity: 0.2);
        $this->assertNotEmpty($results);

        // Query with impossible threshold
        $strictResults = $this->vectorizationService->findSimilar($domain->id, 'Completely Unrelated Subject', limit: 5, minSimilarity: 0.999);
        $this->assertEmpty($strictResults);
    }

    public function test_find_similar_supports_branch_wide_hierarchy_queries(): void
    {
        $rootDomain = Domain::create(['name' => 'Armed Forces', 'slug' => 'armed-forces-h', 'code' => 'AF_H', 'is_active' => true]);
        $childDomain = Domain::create(['parent_id' => $rootDomain->id, 'name' => 'Pakistan Army', 'slug' => 'pak-army-h', 'code' => 'PA_H', 'is_active' => true]);

        $rootModule = ContentModule::create([
            'domain_id' => $rootDomain->id,
            'title' => 'General Military Code of Conduct',
            'slug' => 'military-code',
            'module_type' => 'pdf_repo',
            'is_active' => true,
            'content_meta' => ['description' => 'Discipline and standard military ethics guidelines.'],
        ]);

        $this->vectorizationService->vectorizeModule($rootModule);

        // Without hierarchy, searching childDomain returns 0 (scoped strictly to child)
        $directResults = $this->vectorizationService->findSimilar($childDomain->id, 'Military Code Conduct Ethics', limit: 3, includeHierarchy: false);
        $this->assertEmpty($directResults);

        // With hierarchy (includeHierarchy = true), childDomain inherits rootDomain's knowledge
        $inheritedResults = $this->vectorizationService->findSimilar($childDomain->id, 'Military Code Conduct Ethics', limit: 3, includeHierarchy: true);
        $this->assertNotEmpty($inheritedResults);
        $this->assertSame($rootDomain->id, $inheritedResults->first()->domain_id);
    }
}
