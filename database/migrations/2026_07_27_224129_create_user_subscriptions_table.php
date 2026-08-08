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
            $table->string('receipt_url')->nullable();

            // Stores the storage URL for the EasyPaisa/JazzCash screenshot
            $table->string('payment_method')->nullable(); // easypaisa, jazzcash, bank_transfer
            $table->string('transaction_id')->nullable()->index(); // TRX ID from bank SMS
            $table->text('rejection_reason')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();

            // Indexes for optimized user lookups
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'domain_id', 'status']);

            // Admin table sorting/filtering index
            $table->index(['status', 'created_at']);
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
