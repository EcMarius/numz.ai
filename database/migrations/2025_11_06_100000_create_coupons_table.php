<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->enum('type', ['percentage', 'fixed', 'credits'])->default('percentage');
            $table->decimal('value', 10, 2);
            $table->text('description')->nullable();

            // Usage limits
            $table->integer('max_uses')->nullable(); // null = unlimited
            $table->integer('max_uses_per_user')->default(1);
            $table->integer('uses_count')->default(0);

            // Restrictions
            $table->json('product_ids')->nullable(); // null = all products
            $table->json('excluded_product_ids')->nullable();
            $table->decimal('minimum_order_amount', 10, 2)->nullable();
            $table->boolean('applies_to_renewals')->default(true);
            $table->boolean('applies_to_new_orders')->default(true);

            // User restrictions
            $table->json('allowed_user_ids')->nullable(); // null = all users
            $table->json('allowed_email_domains')->nullable(); // e.g., ['@company.com']
            $table->boolean('first_order_only')->default(false);

            // Stacking
            $table->boolean('can_stack')->default(false);
            $table->json('stack_with_coupon_ids')->nullable(); // specific coupons that can stack

            // Dates
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_recurring')->default(false); // applies to all renewals

            // Metadata
            $table->string('created_by')->nullable(); // admin who created it
            $table->json('metadata')->nullable(); // for notes, campaigns, etc.

            $table->timestamps();
            $table->softDeletes();

            $table->index('code');
            $table->index('is_active');
            $table->index(['starts_at', 'expires_at']);
        });

        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('discount_amount', 10, 2);
            $table->string('order_type')->nullable(); // 'new', 'renewal', 'upgrade'
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['coupon_id', 'user_id']);
            $table->index('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_usages');
        Schema::dropIfExists('coupons');
    }
};
