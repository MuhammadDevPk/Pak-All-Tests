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
        Schema::create('domain_flows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained('domains')->cascadeOnDelete();

            // stores JSON for dynamic Vue.js layout
            $table->jsonb('ui_schema')->comment('Stores JSON definitions for component mapping in Vue.');

            // stores logic/rules (like time limits or gating)
            $table->jsonb('learning_schema')->nullable()->comment('Defines test logic and content gating rules.');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_flows');
    }
};
