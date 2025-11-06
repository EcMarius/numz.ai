<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Affiliate tiers
        Schema::create('affiliate_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Starter, Pro, Elite
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->integer('level')->default(1);

            // Commission structure
            $table->decimal('commission_percentage', 5, 2)->default(0); // First sale
            $table->decimal('recurring_percentage', 5, 2)->default(0); // Recurring commissions
            $table->integer('cookie_lifetime_days')->default(30); // Cookie duration
            $table->integer('commission_lifetime_months')->nullable(); // How long recurring lasts

            // Requirements to reach tier
            $table->integer('min_referrals')->default(0);
            $table->decimal('min_sales', 10, 2)->default(0);

            // Bonuses
            $table->decimal('signup_bonus', 10, 2)->default(0);
            $table->decimal('monthly_bonus_threshold', 10, 2)->nullable(); // Sales needed for bonus
            $table->decimal('monthly_bonus_amount', 10, 2)->nullable();

            // Payout settings
            $table->decimal('minimum_payout', 10, 2)->default(50);

            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Affiliates
        Schema::create('affiliates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('affiliate_tier_id')->constrained()->onDelete('restrict');
            $table->foreignId('referred_by_affiliate_id')->nullable()->constrained('affiliates')->onDelete('set null'); // Multi-tier

            $table->string('affiliate_code')->unique(); // AFF-XXXX
            $table->string('status'); // pending, active, suspended, banned
            $table->string('payment_method'); // paypal, bank_transfer, credit
            $table->json('payment_details')->nullable();

            // Contact info
            $table->string('company_name')->nullable();
            $table->string('website')->nullable();
            $table->text('promotional_methods')->nullable();

            // Stats
            $table->integer('total_clicks')->default(0);
            $table->integer('total_signups')->default(0);
            $table->integer('total_conversions')->default(0);
            $table->decimal('total_sales', 12, 2)->default(0);
            $table->decimal('total_commission_earned', 12, 2)->default(0);
            $table->decimal('total_commission_paid', 12, 2)->default(0);
            $table->decimal('pending_commission', 12, 2)->default(0);

            // Conversion rate
            $table->decimal('conversion_rate', 5, 2)->default(0); // Auto-calculated

            // Dates
            $table->date('approved_at')->nullable();
            $table->date('suspended_at')->nullable();
            $table->text('suspension_reason')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('affiliate_code');
            $table->index('status');
        });

        // Affiliate clicks (tracking)
        Schema::create('affiliate_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained()->onDelete('cascade');
            $table->string('ip_address');
            $table->string('user_agent')->nullable();
            $table->string('referrer_url')->nullable();
            $table->string('landing_page')->nullable();
            $table->string('country')->nullable();
            $table->string('device_type')->nullable(); // desktop, mobile, tablet
            $table->string('browser')->nullable();
            $table->timestamp('clicked_at');
            $table->boolean('converted')->default(false);
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();

            $table->index('affiliate_id');
            $table->index('ip_address');
            $table->index('clicked_at');
        });

        // Affiliate referrals (signups)
        Schema::create('affiliate_referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('click_id')->nullable()->constrained('affiliate_clicks')->onDelete('set null');
            $table->string('status'); // pending, confirmed, cancelled
            $table->string('ip_address')->nullable();
            $table->timestamp('referred_at');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->index('affiliate_id');
            $table->index('user_id');
            $table->unique(['affiliate_id', 'user_id']);
        });

        // Affiliate commissions
        Schema::create('affiliate_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained()->onDelete('cascade');
            $table->foreignId('referral_id')->constrained('affiliate_referrals')->onDelete('cascade');
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->string('type'); // first_sale, recurring, bonus
            $table->string('status'); // pending, approved, paid, cancelled

            $table->decimal('sale_amount', 10, 2);
            $table->decimal('commission_percentage', 5, 2);
            $table->decimal('commission_amount', 10, 2);
            $table->string('currency', 3)->default('USD');

            $table->text('description')->nullable();
            $table->date('earned_date');
            $table->date('approved_date')->nullable();
            $table->date('paid_date')->nullable();

            $table->foreignId('payout_id')->nullable()->constrained('affiliate_payouts')->onDelete('set null');

            $table->timestamps();

            $table->index('affiliate_id');
            $table->index('status');
            $table->index('earned_date');
        });

        // Affiliate payouts
        Schema::create('affiliate_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained()->onDelete('cascade');
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');

            $table->string('payout_number')->unique();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('method'); // paypal, bank_transfer, credit
            $table->string('status'); // pending, processing, completed, failed, cancelled

            $table->date('period_start');
            $table->date('period_end');

            $table->json('payment_details')->nullable();
            $table->text('notes')->nullable();

            $table->timestamp('requested_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();

            $table->timestamps();

            $table->index('affiliate_id');
            $table->index('status');
        });

        // Marketing materials
        Schema::create('affiliate_marketing_materials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // banner, text_link, email_template, landing_page
            $table->text('description')->nullable();
            $table->text('content'); // HTML/Text content
            $table->string('image_url')->nullable(); // For banners
            $table->string('size')->nullable(); // 728x90, 300x250, etc.
            $table->json('metadata')->nullable(); // Additional data
            $table->integer('usage_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('type');
        });

        // Affiliate campaigns (for tracking different campaigns)
        Schema::create('affiliate_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('campaign_code')->unique();
            $table->text('description')->nullable();
            $table->string('landing_page_url')->nullable();
            $table->integer('total_clicks')->default(0);
            $table->integer('total_conversions')->default(0);
            $table->decimal('total_sales', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index('affiliate_id');
            $table->index('campaign_code');
        });

        // Affiliate leaderboard (monthly rankings)
        Schema::create('affiliate_leaderboards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained()->onDelete('cascade');
            $table->integer('year');
            $table->integer('month');
            $table->integer('rank');
            $table->integer('referrals')->default(0);
            $table->decimal('sales', 10, 2)->default(0);
            $table->decimal('commission', 10, 2)->default(0);
            $table->decimal('conversion_rate', 5, 2)->default(0);
            $table->timestamps();

            $table->unique(['affiliate_id', 'year', 'month']);
            $table->index(['year', 'month', 'rank']);
        });

        // Fraud detection
        Schema::create('affiliate_fraud_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained()->onDelete('cascade');
            $table->string('fraud_type'); // duplicate_ip, high_refund_rate, self_referral, etc.
            $table->string('severity'); // low, medium, high, critical
            $table->text('description');
            $table->json('evidence')->nullable();
            $table->string('status'); // open, investigating, resolved, false_positive
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->index('affiliate_id');
            $table->index('status');
            $table->index('severity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_fraud_alerts');
        Schema::dropIfExists('affiliate_leaderboards');
        Schema::dropIfExists('affiliate_campaigns');
        Schema::dropIfExists('affiliate_marketing_materials');
        Schema::dropIfExists('affiliate_payouts');
        Schema::dropIfExists('affiliate_commissions');
        Schema::dropIfExists('affiliate_referrals');
        Schema::dropIfExists('affiliate_clicks');
        Schema::dropIfExists('affiliates');
        Schema::dropIfExists('affiliate_tiers');
    }
};
