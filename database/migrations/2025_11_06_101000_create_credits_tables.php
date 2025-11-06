<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->decimal('balance', 10, 2)->default(0);
            $table->decimal('total_earned', 10, 2)->default(0);
            $table->decimal('total_spent', 10, 2)->default(0);
            $table->decimal('total_purchased', 10, 2)->default(0);
            $table->timestamps();

            $table->index('user_id');
        });

        Schema::create('credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['purchase', 'grant', 'refund', 'payment', 'adjustment', 'bonus', 'expiration']);
            $table->decimal('amount', 10, 2);
            $table->decimal('balance_after', 10, 2);
            $table->text('description')->nullable();

            // References
            $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('payment_transaction_id')->nullable()->constrained()->onDelete('set null');
            $table->string('reference_type')->nullable(); // polymorphic: CreditPackage, Coupon, etc.
            $table->unsignedBigInteger('reference_id')->nullable();

            // Admin actions
            $table->foreignId('admin_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('admin_notes')->nullable();

            // Metadata
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('type');
            $table->index(['reference_type', 'reference_id']);
        });

        Schema::create('credit_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2); // What customer pays
            $table->decimal('credit_amount', 10, 2); // Credits they receive
            $table->decimal('bonus_percentage', 5, 2)->default(0); // e.g., 10 = 10% bonus
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('purchase_limit')->nullable(); // max purchases per user
            $table->timestamp('available_from')->nullable();
            $table->timestamp('available_until')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('is_active');
            $table->index('sort_order');
        });

        Schema::create('credit_package_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('credit_package_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_transaction_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('price_paid', 10, 2);
            $table->decimal('credits_received', 10, 2);
            $table->decimal('bonus_credits', 10, 2)->default(0);
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('credit_package_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_package_purchases');
        Schema::dropIfExists('credit_packages');
        Schema::dropIfExists('credit_transactions');
        Schema::dropIfExists('credit_balances');
    }
};
