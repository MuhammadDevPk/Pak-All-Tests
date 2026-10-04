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
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('referred_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('domain_id')->constrained('domains')->cascadeOnDelete();
            $table->unsignedSmallInteger('reward_days')->default(7);
            $table->string('milestone_unlocked')->nullable();
            $table->string('status', 32)->default('approved'); // approved, flagged_abuse, rejected
            $table->string('abuse_reason')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('device_fingerprint')->nullable();
            $table->timestamps();

            $table->unique(['referrer_id', 'referred_user_id', 'domain_id'], 'referrals_parent_child_domain_unique');
            $table->index(['referrer_id', 'domain_id', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
