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

            // The prompt (e.g., "What is the capital?", or "Write an essay on the economy.")
            $table->text('question_text');

            // The magic column: Stores whatever specific data this type of question needs
            $table->jsonb('payload')->nullable()->comment('Stores varying data like MCQ options, essay word limits, or model answers.');

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
