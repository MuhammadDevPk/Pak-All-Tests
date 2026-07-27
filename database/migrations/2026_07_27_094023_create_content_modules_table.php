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
        Schema::create('content_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained('domains')->cascadeOnDelete();

            // generic grouping type, e.g., 'preparation', 'mock_test', 'pdf_repo'
            $table->string('module_type')->comment('Categorizes the logical grouping, e.g., preparation, test, pdf.');

            // configurations (like passing score or test duration)
            $table->jsonb('content_meta')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_modules');
    }
};
