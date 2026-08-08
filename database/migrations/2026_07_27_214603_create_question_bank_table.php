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
        Schema::create('question_bank', function (Blueprint $table) {
            $table->id();

            // Defines the UI and logic: 'mcq', 'short_answer', 'descriptive', 'essay'
            $table->string('question_type')->index()->comment('Dictates how the Vue frontend renders this question.');
            $table->string('code')->nullable()->unique()->comment('Import/Export identifier e.g. Q-VERB-001');
            // The prompt (e.g., "What is the capital?", or "Write an essay on the economy.")
            $table->text('question_text');

            // The magic column: Stores whatever specific data this type of question needs
            $table->jsonb('payload')->nullable()->comment('Stores varying data like MCQ options, essay word limits, or model answers.');
            $table->string('difficulty_level')->default('medium')->index(); // easy, medium, hard
            $table->text('explanation')->nullable()->comment('Detailed solution/explanation for post-test review');
            $table->boolean('is_active')->default(true);

            $table->index(['is_active', 'created_at']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_bank');
    }
};
