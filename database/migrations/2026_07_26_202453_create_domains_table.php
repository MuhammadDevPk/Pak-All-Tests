<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            // Optional parental link for basic traversal
            $table->foreignId('parent_id')->nullable()->constrained('domains')->nullOnDelete();
            // e.g., "Armed Forces", "CSS", "Navy Initial Verbal"
            $table->string('name');
            $table->string('slug')->unique()->comment('Clean URL routing, e.g. armed-forces-navy');
            $table->string('code')->nullable()->unique()->comment('Developer reference code, e.g. PMA_INITIAL');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        // CRITICAL: We add the ltree column via raw SQL because Laravel doesn't support it.
        // The column MUST be named 'path' as referenced in your architectural doc.
        DB::statement('ALTER TABLE domains ADD COLUMN path ltree');

        // Optional: An index for faster hierarchical queries
        DB::statement('CREATE INDEX domains_path_gist_idx ON domains USING GIST (path)');
        // GiST stands for Generalized Search Tree. It is a highly advanced indexing algorithm. Instead of sorting alphabetically, GiST is smart enough to understand "Parent/Child" shapes and relationships.
        // By using GiST on the path column, PostgreSQL can instantly grab all 50 sub-domains inside "Armed Forces" in milliseconds, without having to read the rest of the database.
        // like ArmedForces.Army.Verbal
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domains');
    }
};
