<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * LEGAL COMPLIANCE SYSTEM FOR PRICE CHANGES
     * ==========================================
     * This migration implements a legally compliant price change notification system
     * that meets EU GDPR, US consumer protection laws, and Stripe best practices.
     *
     * KEY COMPLIANCE FEATURES:
     * - Advance notice before price changes (recorded timestamp)
     * - Explicit user consent requirement (checkbox + accept button)
     * - Right to cancel before change (cancel link always visible)
     * - Complete audit trail (all actions timestamped)
     * - Auto-pause option (prevents charging without consent)
     */
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            // PRICE CHANGE STATUS
            $table->boolean('pending_price_change')->default(false)->after('status')
                ->comment('TRUE when admin has changed plan price and user needs to accept');

            // NEW PRICE DETAILS (what user will pay at next renewal if they accept)
            $table->decimal('pending_price', 10, 2)->nullable()->after('pending_price_change')
                ->comment('New price per seat/month to be charged at next renewal');

            $table->string('pending_currency', 3)->nullable()->after('pending_price')
                ->comment('Currency for new price (EUR, USD, etc)');

            $table->string('pending_price_id')->nullable()->after('pending_currency')
                ->comment('New Stripe price_id to be used at next renewal');

            // LEGAL COMPLIANCE TIMESTAMPS (audit trail)
            $table->timestamp('price_change_notice_sent_at')->nullable()->after('pending_price_id')
                ->comment('When we sent the email notification to user (LEGAL REQUIREMENT)');

            $table->timestamp('price_change_accepted_at')->nullable()->after('price_change_notice_sent_at')
                ->comment('When user explicitly accepted the new price (CONSENT)');

            $table->date('price_change_effective_date')->nullable()->after('price_change_accepted_at')
                ->comment('Date when price change will take effect (their next renewal date)');

            // ADMIN CONFIGURATION
            $table->boolean('price_change_auto_renew')->default(false)->after('price_change_effective_date')
                ->comment('FALSE=pause at renewal (safer), TRUE=auto-accept (riskier). Recommend FALSE for legal safety.');

            // USER INTERACTION TRACKING
            $table->timestamp('price_change_banner_dismissed_at')->nullable()->after('price_change_auto_renew')
                ->comment('When user dismissed the dashboard banner (banner reappears if not accepted)');

            $table->integer('price_change_reminder_count')->default(0)->after('price_change_banner_dismissed_at')
                ->comment('Number of reminder emails sent (prevent spam, max 3)');

            // CANCELLATION TRACKING (if user chooses to cancel instead of accepting)
            $table->text('price_change_cancellation_reason')->nullable()->after('price_change_reminder_count')
                ->comment('Reason user gave for cancelling due to price change (feedback for admin)');

            // Add indexes for performance
            $table->index('pending_price_change');
            $table->index('price_change_effective_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'pending_price_change',
                'pending_price',
                'pending_currency',
                'pending_price_id',
                'price_change_notice_sent_at',
                'price_change_accepted_at',
                'price_change_effective_date',
                'price_change_auto_renew',
                'price_change_banner_dismissed_at',
                'price_change_reminder_count',
                'price_change_cancellation_reason',
            ]);
        });
    }
};
