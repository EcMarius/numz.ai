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
        Schema::table('subscriptions', function (Blueprint $table) {
            // Track pending proration charges to prevent cancellation exploits
            $table->decimal('pending_proration_amount', 10, 2)->nullable()->after('seats_used');

            // Track pending invoice ID from Stripe for verification
            $table->string('pending_invoice_id')->nullable()->after('pending_proration_amount');

            // Lock mechanism to prevent concurrent seat modifications
            $table->boolean('seat_change_in_progress')->default(false)->after('pending_invoice_id');

            // Track when last seat change occurred for rate limiting and auditing
            $table->timestamp('last_seat_change_at')->nullable()->after('seat_change_in_progress');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'pending_proration_amount',
                'pending_invoice_id',
                'seat_change_in_progress',
                'last_seat_change_at',
            ]);
        });
    }
};
