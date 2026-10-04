<?php

declare(strict_types=1);

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
        Schema::table('content_modules', function (Blueprint $table) {
            $table->string('dashboard_section')->nullable()->after('module_type')->comment('Grouping slug on the candidate dashboard (e.g. academic, physical, medical, interview)');
            $table->string('display_mode')->nullable()->after('dashboard_section')->comment('Player experience contract (e.g. mcq_test, study_qa, reading_guide, essay, pdf_repo)');

            $table->index(['domain_id', 'dashboard_section']);
            $table->index('dashboard_section');
            $table->index('display_mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('content_modules', function (Blueprint $table) {
            $table->dropIndex(['domain_id', 'dashboard_section']);
            $table->dropIndex(['dashboard_section']);
            $table->dropIndex(['display_mode']);
            $table->dropColumn(['dashboard_section', 'display_mode']);
        });
    }
};
