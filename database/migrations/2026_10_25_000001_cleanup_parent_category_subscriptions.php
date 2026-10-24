<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Clean up legacy subscriptions provisioned for parent category domains
     * that do not have an active domain flow (e.g., Pakistan Army, Armed Forces, Civil Services).
     */
    public function up(): void
    {
        DB::table('user_subscriptions')
            ->whereNotIn('domain_id', function ($query) {
                $query->select('domain_id')->from('domain_flows');
            })
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // One-time data cleanup of orphan parent-domain subscriptions; irreversible.
    }
};
