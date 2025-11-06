<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Reseller tiers/levels
        Schema::create('reseller_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Bronze, Silver, Gold, Platinum
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->integer('level')->default(1); // 1=basic, 5=highest

            // Pricing discount percentages
            $table->decimal('discount_percentage', 5, 2)->default(0); // 0-100%

            // Limits
            $table->integer('max_customers')->nullable(); // null = unlimited
            $table->integer('max_services')->nullable();
            $table->integer('max_domains')->nullable();

            // Commission rates
            $table->decimal('commission_rate', 5, 2)->default(0); // Percentage
            $table->boolean('recurring_commission')->default(false); // Get commission on renewals

            // Features
            $table->boolean('white_label_enabled')->default(false);
            $table->boolean('custom_branding')->default(false);
            $table->boolean('custom_domain')->default(false);
            $table->boolean('api_access')->default(false);
            $table->boolean('priority_support')->default(false);

            // Pricing
            $table->decimal('monthly_fee', 10, 2)->default(0);
            $table->decimal('setup_fee', 10, 2)->default(0);

            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Reseller accounts
        Schema::create('resellers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('parent_reseller_id')->nullable()->constrained('resellers')->onDelete('set null'); // Multi-level
            $table->foreignId('reseller_tier_id')->constrained()->onDelete('restrict');

            $table->string('company_name');
            $table->string('status'); // pending, active, suspended, cancelled
            $table->string('reseller_code')->unique(); // REF-XXXX

            // Contact info
            $table->string('primary_contact_name')->nullable();
            $table->string('primary_contact_email')->nullable();
            $table->string('primary_contact_phone')->nullable();

            // Business info
            $table->string('business_type')->nullable(); // individual, company, corporation
            $table->string('tax_id')->nullable();
            $table->text('business_address')->nullable();

            // White-label settings
            $table->string('custom_domain')->nullable();
            $table->string('company_logo')->nullable(); // Path to logo
            $table->string('company_favicon')->nullable();
            $table->json('brand_colors')->nullable(); // Primary, secondary colors
            $table->string('support_email')->nullable();
            $table->string('support_phone')->nullable();
            $table->text('terms_of_service')->nullable();
            $table->text('privacy_policy')->nullable();

            // Pricing override
            $table->boolean('custom_pricing_enabled')->default(false);
            $table->decimal('global_discount_percentage', 5, 2)->default(0);

            // Commission settings
            $table->decimal('commission_rate', 5, 2)->nullable(); // Override tier rate
            $table->boolean('recurring_commission')->default(false);
            $table->string('payout_method')->nullable(); // bank_transfer, paypal, credit
            $table->json('payout_details')->nullable(); // Bank account, PayPal email, etc.
            $table->decimal('minimum_payout', 10, 2)->default(50); // Minimum for payout

            // Stats
            $table->integer('total_customers')->default(0);
            $table->integer('total_services')->default(0);
            $table->integer('total_domains')->default(0);
            $table->decimal('total_revenue', 12, 2)->default(0);
            $table->decimal('total_commission_earned', 12, 2)->default(0);
            $table->decimal('total_commission_paid', 12, 2)->default(0);
            $table->decimal('pending_commission', 12, 2)->default(0);

            // Dates
            $table->date('activated_at')->nullable();
            $table->date('suspended_at')->nullable();
            $table->date('cancelled_at')->nullable();

            // API
            $table->string('api_key')->nullable()->unique();
            $table->timestamp('api_key_last_used_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('reseller_code');
            $table->index('status');
        });

        // Reseller customers (track which customers belong to which reseller)
        Schema::create('reseller_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reseller_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('assigned_at');
            $table->timestamps();

            $table->unique(['reseller_id', 'user_id']);
            $table->index('reseller_id');
            $table->index('user_id');
        });

        // Reseller pricing (custom pricing per reseller per product)
        Schema::create('reseller_pricing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reseller_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained('hosting_products')->onDelete('cascade');

            // Pricing
            $table->decimal('monthly_price', 10, 2)->nullable();
            $table->decimal('quarterly_price', 10, 2)->nullable();
            $table->decimal('semi_annual_price', 10, 2)->nullable();
            $table->decimal('annual_price', 10, 2)->nullable();
            $table->decimal('biennial_price', 10, 2)->nullable();
            $table->decimal('triennial_price', 10, 2)->nullable();
            $table->decimal('setup_fee', 10, 2)->nullable();

            // Override costs (what reseller pays)
            $table->decimal('cost_price', 10, 2)->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['reseller_id', 'product_id']);
        });

        // Reseller commission transactions
        Schema::create('reseller_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reseller_id')->constrained()->onDelete('cascade');
            $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Customer who paid

            $table->string('type'); // sale, renewal, upgrade, addon
            $table->string('status'); // pending, approved, paid, cancelled

            $table->decimal('order_amount', 10, 2);
            $table->decimal('commission_rate', 5, 2);
            $table->decimal('commission_amount', 10, 2);
            $table->string('currency', 3)->default('USD');

            $table->text('description')->nullable();
            $table->date('earned_date');
            $table->date('approved_date')->nullable();
            $table->date('paid_date')->nullable();

            $table->foreignId('payout_id')->nullable()->constrained('reseller_payouts')->onDelete('set null');

            $table->timestamps();

            $table->index('reseller_id');
            $table->index('status');
            $table->index('earned_date');
        });

        // Reseller payouts
        Schema::create('reseller_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reseller_id')->constrained()->onDelete('cascade');
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');

            $table->string('payout_number')->unique();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('method'); // bank_transfer, paypal, credit, check
            $table->string('status'); // pending, processing, completed, failed, cancelled

            $table->date('period_start');
            $table->date('period_end');

            $table->json('payment_details')->nullable(); // Transaction ID, reference, etc.
            $table->text('notes')->nullable();

            $table->timestamp('requested_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();

            $table->timestamps();

            $table->index('reseller_id');
            $table->index('status');
            $table->index('payout_number');
        });

        // Reseller reports/analytics
        Schema::create('reseller_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reseller_id')->constrained()->onDelete('cascade');

            $table->date('report_date');
            $table->string('period_type'); // daily, weekly, monthly

            // Revenue metrics
            $table->decimal('new_sales', 10, 2)->default(0);
            $table->decimal('renewals', 10, 2)->default(0);
            $table->decimal('upgrades', 10, 2)->default(0);
            $table->decimal('total_revenue', 10, 2)->default(0);

            // Commission metrics
            $table->decimal('commission_earned', 10, 2)->default(0);
            $table->decimal('commission_paid', 10, 2)->default(0);

            // Customer metrics
            $table->integer('new_customers')->default(0);
            $table->integer('active_customers')->default(0);
            $table->integer('churned_customers')->default(0);

            // Service metrics
            $table->integer('new_services')->default(0);
            $table->integer('active_services')->default(0);
            $table->integer('cancelled_services')->default(0);

            $table->timestamps();

            $table->unique(['reseller_id', 'report_date', 'period_type']);
            $table->index('reseller_id');
            $table->index('report_date');
        });

        // Reseller support tickets (separate from main tickets)
        Schema::create('reseller_support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reseller_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');

            $table->string('ticket_number')->unique();
            $table->string('subject');
            $table->text('message');
            $table->string('status'); // open, answered, customer_reply, on_hold, closed
            $table->string('priority'); // low, medium, high, urgent
            $table->string('department')->nullable(); // reseller_support, technical, billing

            $table->timestamp('last_reply_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->timestamps();

            $table->index('reseller_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reseller_support_tickets');
        Schema::dropIfExists('reseller_reports');
        Schema::dropIfExists('reseller_payouts');
        Schema::dropIfExists('reseller_commissions');
        Schema::dropIfExists('reseller_pricing');
        Schema::dropIfExists('reseller_customers');
        Schema::dropIfExists('resellers');
        Schema::dropIfExists('reseller_tiers');
    }
};
