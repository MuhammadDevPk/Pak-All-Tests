<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * This enables the extensions within PostgreSQL.
     */
    public function up(): void
    {
        // For Handling Infinite category nesting
        DB::statement('CREATE EXTENSION IF NOT EXISTS ltree;');

        // Prepare for AI sub-agents/Chatbots
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP EXTENSION IF EXISTS vector;');
        DB::statement('DROP EXTENSION IF EXISTS ltree;');
    }
};
