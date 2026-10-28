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
        Schema::table('users', function (Blueprint $table) {
            $table->string('guest_token_hash', 64)->nullable()->unique()->after('guest_expires_at');
            $table->timestamp('last_active_at')->nullable()->after('guest_token_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['guest_token_hash']);
            $table->dropColumn(['guest_token_hash', 'last_active_at']);
        });
    }
};

