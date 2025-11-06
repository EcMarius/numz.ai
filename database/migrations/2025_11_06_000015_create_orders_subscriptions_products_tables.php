<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Products table
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('type')->default('service'); // hosting, domain, ssl, addon, service
            $table->string('category')->nullable();
            $table->string('sku')->unique()->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('setup_fee', 10, 2)->default(0);
            $table->json('billing_cycles')->nullable(); // available billing cycles
            $table->integer('stock_quantity')->nullable();
            $table->string('stock_status')->default('in_stock');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('requires_domain')->default(false);
            $table->boolean('auto_setup')->default(false);
            $table->foreignId('welcome_email_template_id')->nullable()->constrained('email_templates');
            $table->foreignId('server_id')->nullable()->constrained('hosting_servers');
            $table->json('configuration_options')->nullable();
            $table->json('pricing_tiers')->nullable();
            $table->json('features')->nullable();
            $table->json('metadata')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // Subscriptions table
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained();
            $table->string('subscription_number')->unique();
            $table->string('status')->default('active'); // active, trialing, past_due, paused, cancelled, expired
            $table->string('billing_cycle')->default('monthly');
            $table->integer('quantity')->default(1);
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('next_billing_date')->nullable();
            $table->timestamp('last_billing_date')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('gateway_subscription_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index('next_billing_date');
        });

        // Orders table
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained();
            $table->foreignId('subscription_id')->nullable()->constrained();
            $table->string('order_number')->unique();
            $table->string('order_type')->default('new'); // new, renewal, upgrade, downgrade
            $table->string('status')->default('pending'); // pending, active, suspended, cancelled, terminated, completed
            $table->string('billing_cycle')->default('monthly');
            $table->integer('quantity')->default(1);
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('domain')->nullable();
            $table->json('configuration')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('setup_fee', 10, 2)->default(0);
            $table->timestamp('activation_date')->nullable();
            $table->timestamp('next_due_date')->nullable();
            $table->timestamp('next_invoice_date')->nullable();
            $table->timestamp('termination_date')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index('next_due_date');
            $table->index('next_invoice_date');
        });

        // Transactions table
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('invoice_id')->nullable()->constrained();
            $table->foreignId('subscription_id')->nullable()->constrained();
            $table->string('transaction_id')->unique();
            $table->string('type')->default('payment'); // payment, refund, credit, debit
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('payment_method')->nullable();
            $table->string('payment_gateway')->nullable();
            $table->string('gateway_transaction_id')->nullable();
            $table->json('gateway_response')->nullable();
            $table->string('status')->default('pending'); // pending, processing, completed, failed, refunded, cancelled
            $table->text('description')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->decimal('refund_amount', 10, 2)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('invoice_id');
            $table->index('processed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('products');
    }
};
