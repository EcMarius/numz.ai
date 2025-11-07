<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations - Add constraints to prevent edge cases and race conditions.
     */
    public function up(): void
    {
        // Prevent duplicate renewal invoices (EC-034)
        Schema::table('invoices', function (Blueprint $table) {
            $table->unique(['order_id', 'invoice_type', 'due_date'], 'unique_renewal_invoice');
        });

        // Prevent duplicate affiliate referrals (EC-035)
        Schema::table('affiliate_referrals', function (Blueprint $table) {
            $table->unique('user_id', 'unique_affiliate_referral_per_user');
        });

        // Prevent duplicate reseller customer assignments (EC-041)
        Schema::table('reseller_customers', function (Blueprint $table) {
            $table->unique(['reseller_id', 'user_id'], 'unique_reseller_customer');
        });

        // Prevent duplicate coupon usages per invoice (EC-048)
        Schema::table('coupon_usages', function (Blueprint $table) {
            $table->unique(['coupon_id', 'invoice_id'], 'unique_coupon_per_invoice');
        });

        // Add check constraints for positive amounts
        // Note: Check constraints require MySQL 8.0.16+ or PostgreSQL

        // Positive invoice totals (EC-011)
        DB::statement('ALTER TABLE invoices ADD CONSTRAINT check_positive_total CHECK (total >= 0)');

        // Positive transaction amounts (EC-009)
        DB::statement('ALTER TABLE transactions ADD CONSTRAINT check_positive_amount CHECK (amount >= 0)');

        // Positive credit balances (EC-045, EC-047)
        DB::statement('ALTER TABLE credit_balances ADD CONSTRAINT check_positive_balance CHECK (balance >= 0)');

        // Positive chargeback amounts (EC-053)
        DB::statement('ALTER TABLE chargebacks ADD CONSTRAINT check_positive_chargeback CHECK (amount > 0)');

        // Positive payment plan amounts (EC-042, EC-043)
        DB::statement('ALTER TABLE payment_plans ADD CONSTRAINT check_positive_plan_amount CHECK (total_amount >= 0)');
        DB::statement('ALTER TABLE payment_plans ADD CONSTRAINT check_positive_installments CHECK (installments > 0)');

        // Positive coupon max uses
        DB::statement('ALTER TABLE coupons ADD CONSTRAINT check_coupon_max_uses CHECK (max_uses IS NULL OR max_uses > 0)');
        DB::statement('ALTER TABLE coupons ADD CONSTRAINT check_coupon_max_uses_per_user CHECK (max_uses_per_user IS NULL OR max_uses_per_user > 0)');

        // Add indexes for performance on frequently queried fields
        Schema::table('orders', function (Blueprint $table) {
            $table->index('next_invoice_date', 'idx_orders_next_invoice_date');
            $table->index(['status', 'next_invoice_date'], 'idx_orders_status_next_invoice');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->index('next_billing_date', 'idx_subscriptions_next_billing');
            $table->index(['status', 'next_billing_date'], 'idx_subscriptions_status_billing');
        });

        Schema::table('affiliate_clicks', function (Blueprint $table) {
            $table->index(['ip_address', 'converted'], 'idx_clicks_ip_converted');
        });

        Schema::table('coupons', function (Blueprint $table) {
            $table->index(['is_active', 'expires_at'], 'idx_coupons_active_expires');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop unique constraints
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique('unique_renewal_invoice');
        });

        Schema::table('affiliate_referrals', function (Blueprint $table) {
            $table->dropUnique('unique_affiliate_referral_per_user');
        });

        Schema::table('reseller_customers', function (Blueprint $table) {
            $table->dropUnique('unique_reseller_customer');
        });

        Schema::table('coupon_usages', function (Blueprint $table) {
            $table->dropUnique('unique_coupon_per_invoice');
        });

        // Drop check constraints
        DB::statement('ALTER TABLE invoices DROP CONSTRAINT check_positive_total');
        DB::statement('ALTER TABLE transactions DROP CONSTRAINT check_positive_amount');
        DB::statement('ALTER TABLE credit_balances DROP CONSTRAINT check_positive_balance');
        DB::statement('ALTER TABLE chargebacks DROP CONSTRAINT check_positive_chargeback');
        DB::statement('ALTER TABLE payment_plans DROP CONSTRAINT check_positive_plan_amount');
        DB::statement('ALTER TABLE payment_plans DROP CONSTRAINT check_positive_installments');
        DB::statement('ALTER TABLE coupons DROP CONSTRAINT check_coupon_max_uses');
        DB::statement('ALTER TABLE coupons DROP CONSTRAINT check_coupon_max_uses_per_user');

        // Drop indexes
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('idx_orders_next_invoice_date');
            $table->dropIndex('idx_orders_status_next_invoice');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex('idx_subscriptions_next_billing');
            $table->dropIndex('idx_subscriptions_status_billing');
        });

        Schema::table('affiliate_clicks', function (Blueprint $table) {
            $table->dropIndex('idx_clicks_ip_converted');
        });

        Schema::table('coupons', function (Blueprint $table) {
            $table->dropIndex('idx_coupons_active_expires');
        });
    }
};
