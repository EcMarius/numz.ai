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
        // Categories for organizing marketplace items
        Schema::create('marketplace_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // Main marketplace items/modules table
        Schema::create('marketplace_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Creator
            $table->foreignId('category_id')->nullable()->constrained('marketplace_categories')->onDelete('set null');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('short_description');
            $table->longText('description');
            $table->longText('installation_instructions')->nullable();
            $table->longText('changelog')->nullable();

            // Pricing
            $table->decimal('price', 10, 2); // Item price
            $table->decimal('creator_revenue_percentage', 5, 2)->default(70.00); // Creator gets 70%
            $table->boolean('is_free')->default(false);

            // File & Version
            $table->string('current_version')->default('1.0.0');
            $table->string('file_path')->nullable(); // Path to zip file
            $table->bigInteger('file_size')->nullable(); // In bytes
            $table->string('demo_url')->nullable();
            $table->string('documentation_url')->nullable();
            $table->string('support_url')->nullable();
            $table->string('repository_url')->nullable();

            // Requirements
            $table->string('minimum_php_version')->nullable();
            $table->string('minimum_laravel_version')->nullable();
            $table->json('required_packages')->nullable(); // Array of composer packages

            // Media
            $table->json('screenshots')->nullable(); // Array of image URLs
            $table->string('icon')->nullable();
            $table->string('banner')->nullable();
            $table->string('video_url')->nullable();

            // Status & Moderation
            $table->enum('status', ['draft', 'pending_review', 'approved', 'rejected', 'suspended'])->default('draft');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');

            // Stats
            $table->integer('downloads_count')->default(0);
            $table->integer('purchases_count')->default(0);
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->integer('reviews_count')->default(0);
            $table->integer('views_count')->default(0);

            // Featured
            $table->boolean('is_featured')->default(false);
            $table->timestamp('featured_at')->nullable();

            // Active status
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['status', 'is_active']);
            $table->index(['category_id', 'status']);
            $table->index('user_id');
            $table->index('slug');
        });

        // Purchase history
        Schema::create('marketplace_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Buyer
            $table->foreignId('marketplace_item_id')->constrained()->onDelete('cascade');
            $table->string('transaction_id')->unique(); // Stripe/Paddle transaction ID
            $table->decimal('price_paid', 10, 2); // Price at time of purchase
            $table->decimal('platform_fee', 10, 2); // 30% platform fee
            $table->decimal('creator_earnings', 10, 2); // 70% to creator
            $table->enum('payment_provider', ['stripe', 'paddle'])->default('stripe');
            $table->enum('payment_status', ['pending', 'completed', 'refunded', 'failed'])->default('pending');
            $table->timestamp('refunded_at')->nullable();
            $table->text('refund_reason')->nullable();
            $table->string('license_key')->nullable(); // Optional license key
            $table->timestamps();

            // Prevent duplicate purchases
            $table->unique(['user_id', 'marketplace_item_id'], 'user_item_unique');
            $table->index('transaction_id');
            $table->index(['user_id', 'marketplace_item_id']);
        });

        // Reviews and ratings
        Schema::create('marketplace_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('marketplace_item_id')->constrained()->onDelete('cascade');
            $table->foreignId('purchase_id')->constrained('marketplace_purchases')->onDelete('cascade');
            $table->integer('rating'); // 1-5 stars
            $table->string('title')->nullable();
            $table->text('review')->nullable();
            $table->json('pros')->nullable(); // Array of pros
            $table->json('cons')->nullable(); // Array of cons
            $table->boolean('is_verified_purchase')->default(false);
            $table->boolean('is_approved')->default(true);
            $table->integer('helpful_count')->default(0);
            $table->integer('not_helpful_count')->default(0);
            $table->timestamps();

            // One review per purchase
            $table->unique(['user_id', 'marketplace_item_id'], 'user_item_review_unique');
            $table->index('marketplace_item_id');
            $table->index(['marketplace_item_id', 'is_approved']);
        });

        // Download logs for tracking
        Schema::create('marketplace_download_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('marketplace_item_id')->constrained()->onDelete('cascade');
            $table->foreignId('purchase_id')->nullable()->constrained('marketplace_purchases')->onDelete('set null');
            $table->string('version_downloaded');
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'marketplace_item_id']);
            $table->index('marketplace_item_id');
        });

        // Creator earnings tracking
        Schema::create('marketplace_earnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('marketplace_item_id')->constrained()->onDelete('cascade');
            $table->foreignId('purchase_id')->constrained('marketplace_purchases')->onDelete('cascade');
            $table->decimal('amount', 10, 2); // Creator's share
            $table->decimal('platform_fee', 10, 2); // Platform's share
            $table->enum('status', ['pending', 'available', 'paid', 'refunded'])->default('pending');
            $table->timestamp('available_at')->nullable(); // When funds become available (after 7 days)
            $table->foreignId('payout_id')->nullable()->constrained('marketplace_payouts')->onDelete('set null');
            $table->timestamps();

            $table->index(['creator_id', 'status']);
            $table->index(['marketplace_item_id', 'status']);
        });

        // Payout requests and history
        Schema::create('marketplace_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_id')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 10, 2); // Total payout amount
            $table->integer('earnings_count'); // Number of earnings included
            $table->enum('method', ['stripe', 'paypal', 'bank_transfer'])->default('stripe');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->string('transaction_id')->nullable(); // Payment provider transaction ID
            $table->text('payout_details')->nullable(); // JSON with payout info
            $table->text('failure_reason')->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['creator_id', 'status']);
            $table->index('status');
        });

        // Creator profiles/settings
        Schema::create('marketplace_creator_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->string('business_name')->nullable();
            $table->text('bio')->nullable();
            $table->string('website')->nullable();
            $table->string('github')->nullable();
            $table->string('twitter')->nullable();
            $table->string('avatar')->nullable();

            // Payout settings
            $table->enum('preferred_payout_method', ['stripe', 'paypal', 'bank_transfer'])->default('stripe');
            $table->string('stripe_account_id')->nullable(); // Stripe Connect account
            $table->string('paypal_email')->nullable();
            $table->json('bank_details')->nullable(); // Encrypted bank details

            // Tax info
            $table->string('tax_id')->nullable();
            $table->string('country')->nullable();
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();

            // Status
            $table->boolean('is_verified')->default(false);
            $table->boolean('can_receive_payouts')->default(false);
            $table->timestamp('verified_at')->nullable();

            // Stats
            $table->decimal('total_earnings', 10, 2)->default(0);
            $table->decimal('available_balance', 10, 2)->default(0);
            $table->decimal('pending_balance', 10, 2)->default(0);
            $table->integer('total_sales')->default(0);

            $table->timestamps();
        });

        // Item updates/versions
        Schema::create('marketplace_item_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_item_id')->constrained()->onDelete('cascade');
            $table->string('version');
            $table->text('changelog');
            $table->string('file_path');
            $table->bigInteger('file_size');
            $table->boolean('is_current')->default(false);
            $table->integer('downloads_count')->default(0);
            $table->timestamps();

            $table->index(['marketplace_item_id', 'is_current']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_item_versions');
        Schema::dropIfExists('marketplace_creator_profiles');
        Schema::dropIfExists('marketplace_payouts');
        Schema::dropIfExists('marketplace_earnings');
        Schema::dropIfExists('marketplace_download_logs');
        Schema::dropIfExists('marketplace_reviews');
        Schema::dropIfExists('marketplace_purchases');
        Schema::dropIfExists('marketplace_items');
        Schema::dropIfExists('marketplace_categories');
    }
};
