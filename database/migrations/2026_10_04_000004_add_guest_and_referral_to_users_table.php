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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_guest')->default(false)->after('password');
            $table->timestamp('guest_expires_at')->nullable()->after('is_guest');
            $table->boolean('is_suspended')->default(false)->after('guest_expires_at');
            $table->string('suspension_reason')->nullable()->after('is_suspended');
            $table->string('referral_code', 32)->nullable()->unique()->after('suspension_reason');
            $table->foreignId('referred_by_id')->nullable()->after('referral_code')->constrained('users')->nullOnDelete();
            $table->string('registration_ip', 45)->nullable()->after('referred_by_id');

            $table->index('is_guest');
            $table->index('is_suspended');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referred_by_id']);
            $table->dropIndex(['is_guest']);
            $table->dropIndex(['is_suspended']);
            $table->dropColumn([
                'is_guest',
                'guest_expires_at',
                'is_suspended',
                'suspension_reason',
                'referral_code',
                'referred_by_id',
                'registration_ip',
            ]);
        });
    }
};
