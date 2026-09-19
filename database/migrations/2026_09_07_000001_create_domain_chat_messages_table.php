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
        Schema::create('domain_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('domain_id')->constrained('domains')->cascadeOnDelete();
            $table->string('role', 20)->default('user'); // 'user', 'assistant', 'system'
            $table->text('content');
            $table->jsonb('context_metadata')->nullable(); // active_module_id, active_question_id, sources_used
            $table->timestamps();

            // Compound index for sub-millisecond candidate chat history loading
            $table->index(['user_id', 'domain_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_chat_messages');
    }
};
