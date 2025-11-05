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
        Schema::create('seat_change_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->onDelete('set null');

            // Seat change details
            $table->integer('old_seats');
            $table->integer('new_seats');
            $table->integer('seats_changed'); // Calculated: new - old (can be negative for decreases)

            // Financial tracking
            $table->decimal('proration_amount', 10, 2)->nullable(); // Amount charged/credited
            $table->string('currency', 3)->default('eur');
            $table->string('stripe_invoice_id')->nullable();

            // Status tracking
            $table->enum('status', [
                'pending',          // Change initiated, awaiting payment
                'completed',        // Successfully completed
                'failed',           // Payment failed
                'reverted',         // Auto-reverted due to payment failure
                'cancelled'         // User cancelled before completion
            ])->default('pending');

            $table->string('payment_status')->nullable(); // Stripe payment status
            $table->text('failure_reason')->nullable(); // If payment failed, reason

            // Metadata
            $table->string('initiated_by')->default('user'); // 'user', 'admin', 'system'
            $table->ipAddress('ip_address')->nullable();
            $table->text('notes')->nullable(); // Admin notes or additional context

            $table->timestamps();

            // Indexes for querying
            $table->index('user_id');
            $table->index('subscription_id');
            $table->index('status');
            $table->index('created_at');
            $table->index(['user_id', 'created_at']); // For detecting abuse patterns
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seat_change_history');
    }
};
