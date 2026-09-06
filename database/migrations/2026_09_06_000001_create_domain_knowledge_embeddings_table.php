<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('domain_knowledge_embeddings', function (Blueprint $table) {
            $table->id();

            // Strict domain partitioning: every vector chunk belongs to a domain
            $table->foreignId('domain_id')->constrained('domains')->cascadeOnDelete();

            // Optional source entity links
            $table->foreignId('content_module_id')->nullable()->constrained('content_modules')->cascadeOnDelete();
            $table->foreignId('question_bank_id')->nullable()->constrained('question_bank')->cascadeOnDelete();

            // Source classification: 'question', 'content_module', 'syllabus', 'rubric'
            $table->string('source_type', 50)->index();

            // Human-readable title or reference code (e.g. Q-VERB-001, Verbal Intelligence Test 1)
            $table->string('title')->nullable();

            // The raw textual content chunk that was vectorized
            $table->text('content');

            // 1536-dimensional pgvector column for semantic similarity
            $table->vector('embedding', 1536);

            // Supplementary metadata (tags, difficulty, marks, options, explanation)
            $table->jsonb('metadata')->nullable();

            $table->timestamps();

            // Composite performance indexes
            $table->index(['domain_id', 'source_type']);
            $table->index(['domain_id', 'content_module_id']);
            $table->index(['domain_id', 'question_bank_id']);

            // PostgreSQL pgvector HNSW cosine distance index
            $table->vectorIndex('embedding', 'domain_knowledge_embeddings_vector_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_knowledge_embeddings');
    }
};
