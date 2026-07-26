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
        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            // Optional parental link for basic traversal
            $table->foreignId('parent_id')->nullable()->constrained('domains')->nullOnDelete();
            // e.g., "Armed Forces", "CSS", "Navy Initial Verbal"
            $table->string('name');
            $table->timestamps();
        });

        // CRITICAL: We add the ltree column via raw SQL because Laravel doesn't support it.
        // The column MUST be named 'path' as referenced in your architectural doc.
        DB::statement('ALTER TABLE domains ADD COLUMN path ltree');

        // Optional: An index for faster hierarchical queries
        DB::statement('CREATE INDEX domains_path_gist_idx ON domains USING GIST (path)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domains');
    }
};
