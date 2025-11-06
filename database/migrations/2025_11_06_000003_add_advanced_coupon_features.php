<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add advanced coupon fields
        Schema::table('coupons', function (Blueprint $table) {
            $table->enum('discount_type', ['simple', 'tiered', 'bogo', 'bundle', 'volume'])->default('simple')->after('type');
            $table->json('tier_rules')->nullable()->after('discount_type'); // For tiered discounts
            $table->json('bogo_rules')->nullable()->after('tier_rules'); // For BOGO offers
            $table->json('bundle_products')->nullable()->after('bogo_rules'); // For bundle discounts
            $table->json('volume_rules')->nullable()->after('bundle_products'); // For volume discounts
            $table->json('geo_restrictions')->nullable()->after('allowed_email_domains'); // Country/region restrictions
            $table->string('referral_code')->nullable()->unique()->after('code'); // For referral tracking
            $table->foreignId('affiliate_id')->nullable()->constrained('users')->onDelete('set null')->after('created_by');
            $table->decimal('affiliate_commission_percentage', 5, 2)->nullable()->after('affiliate_id');
            $table->boolean('is_seasonal')->default(false)->after('is_recurring');
            $table->string('campaign_name')->nullable()->after('is_seasonal');
            $table->integer('priority')->default(0)->after('campaign_name'); // For coupon ordering
            $table->boolean('auto_apply')->default(false)->after('priority'); // Auto-apply eligible coupons
            $table->json('conditions')->nullable()->after('auto_apply'); // Complex conditions

            $table->index('referral_code');
            $table->index('campaign_name');
        });

        // Create loyalty_programs table
        Schema::create('loyalty_programs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('points_per_dollar')->default(1);
            $table->decimal('minimum_spend', 10, 2)->default(0);
            $table->json('tier_rules')->nullable(); // Different tiers with benefits
            $table->json('redemption_rules')->nullable(); // How points can be redeemed
            $table->integer('points_expiry_days')->nullable(); // Points expiration
            $table->timestamps();
        });

        // Create loyalty_points table
        Schema::create('loyalty_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('loyalty_program_id')->constrained()->onDelete('cascade');
            $table->integer('points')->default(0);
            $table->integer('lifetime_points')->default(0);
            $table->string('tier')->nullable(); // bronze, silver, gold, platinum
            $table->timestamps();

            $table->unique(['user_id', 'loyalty_program_id']);
            $table->index('tier');
        });

        // Create loyalty_transactions table
        Schema::create('loyalty_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('loyalty_program_id')->constrained()->onDelete('cascade');
            $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('type', ['earned', 'redeemed', 'expired', 'adjusted'])->default('earned');
            $table->integer('points');
            $table->integer('balance_after');
            $table->text('description')->nullable();
            $table->date('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
        });

        // Create referral_programs table
        Schema::create('referral_programs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('referrer_reward_amount', 10, 2)->nullable();
            $table->string('referrer_reward_type')->default('credit'); // credit, discount, coupon
            $table->decimal('referee_reward_amount', 10, 2)->nullable();
            $table->string('referee_reward_type')->default('discount');
            $table->integer('minimum_purchase_amount')->nullable();
            $table->integer('max_referrals_per_user')->nullable();
            $table->timestamps();
        });

        // Create referrals table
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referral_program_id')->constrained()->onDelete('cascade');
            $table->foreignId('referrer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('referee_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('referral_code')->unique();
            $table->string('referee_email')->nullable();
            $table->enum('status', ['pending', 'completed', 'rewarded'])->default('pending');
            $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('rewarded_at')->nullable();
            $table->timestamps();

            $table->index(['referrer_id', 'status']);
            $table->index('referral_code');
        });

        // Create coupon_analytics table for A/B testing
        Schema::create('coupon_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->integer('impressions')->default(0);
            $table->integer('clicks')->default(0);
            $table->integer('redemptions')->default(0);
            $table->decimal('revenue', 10, 2)->default(0);
            $table->decimal('discount_given', 10, 2)->default(0);
            $table->decimal('conversion_rate', 5, 2)->default(0);
            $table->timestamps();

            $table->unique(['coupon_id', 'date']);
        });

        // Create seasonal_campaigns table
        Schema::create('seasonal_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('season', ['black_friday', 'cyber_monday', 'christmas', 'new_year', 'summer_sale', 'custom'])->default('custom');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
            $table->json('discount_rules')->nullable();
            $table->timestamps();

            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seasonal_campaigns');
        Schema::dropIfExists('coupon_analytics');
        Schema::dropIfExists('referrals');
        Schema::dropIfExists('referral_programs');
        Schema::dropIfExists('loyalty_transactions');
        Schema::dropIfExists('loyalty_points');
        Schema::dropIfExists('loyalty_programs');

        Schema::table('coupons', function (Blueprint $table) {
            $table->dropForeign(['affiliate_id']);
            $table->dropColumn([
                'discount_type',
                'tier_rules',
                'bogo_rules',
                'bundle_products',
                'volume_rules',
                'geo_restrictions',
                'referral_code',
                'affiliate_id',
                'affiliate_commission_percentage',
                'is_seasonal',
                'campaign_name',
                'priority',
                'auto_apply',
                'conditions',
            ]);
        });
    }
};
