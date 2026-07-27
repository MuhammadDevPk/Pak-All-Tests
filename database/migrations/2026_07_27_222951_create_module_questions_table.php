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
        Schema::create('module_questions', function (Blueprint $table) {
            $table->foreignId('module_id')->constrained('content_modules')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('question_bank')->cascadeOnDelete();

            // Ensure a question is not duplicated in the same module
            $table->primary(['module_id', 'question_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('module_questions');
    }
};
