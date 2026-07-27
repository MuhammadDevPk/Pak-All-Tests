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
        Schema::create('user_subscriptions', function (Blueprint $table) {
            $table->id();

            // linked to standard auth user
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // linked to the specific test/subdomain
            $table->foreignId('domain_id')->constrained('domains')->cascadeOnDelete();

            // Payment state machine: e.g., 'pending', 'active', 'expired', 'rejected'
            $table->string('status')->default('pending');

            // Stores the storage URL for the EasyPaisa/JazzCash screenshot
            $table->string('receipt_url')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_subscriptions');
    }
};
