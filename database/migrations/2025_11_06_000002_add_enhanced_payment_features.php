<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Enhance payment_transactions table
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'refunded', 'cancelled'])->default('pending')->change();
            $table->string('payment_intent_id')->nullable()->after('transaction_id');
            $table->string('refund_id')->nullable()->after('payment_intent_id');
            $table->decimal('refund_amount', 10, 2)->default(0)->after('refund_id');
            $table->timestamp('refunded_at')->nullable()->after('refund_amount');
            $table->string('failure_code')->nullable()->after('refunded_at');
            $table->text('failure_message')->nullable()->after('failure_code');
            $table->integer('retry_count')->default(0)->after('failure_message');
            $table->timestamp('next_retry_at')->nullable()->after('retry_count');
            $table->json('gateway_response')->nullable()->after('next_retry_at');
            $table->string('customer_ip')->nullable()->after('gateway_response');
            $table->string('customer_country', 2)->nullable()->after('customer_ip');

            $table->index(['status', 'next_retry_at']);
        });

        // Create payment_methods table for stored payment methods
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('gateway'); // stripe, paypal, etc
            $table->string('gateway_payment_method_id'); // Token from gateway
            $table->string('type'); // card, bank_account, paypal, etc
            $table->string('last_four')->nullable();
            $table->string('brand')->nullable(); // visa, mastercard, etc
            $table->string('exp_month', 2)->nullable();
            $table->string('exp_year', 4)->nullable();
            $table->string('holder_name')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_default']);
        });

        // Create payment_retries table
        Schema::create('payment_retries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_transaction_id')->nullable()->constrained()->onDelete('cascade');
            $table->integer('attempt_number');
            $table->enum('status', ['pending', 'success', 'failed', 'cancelled'])->default('pending');
            $table->timestamp('scheduled_at');
            $table->timestamp('attempted_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['invoice_id', 'status']);
            $table->index('scheduled_at');
        });

        // Create chargebacks table
        Schema::create('chargebacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_transaction_id')->constrained()->onDelete('cascade');
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('chargeback_id'); // ID from payment gateway
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3);
            $table->string('reason_code')->nullable();
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'won', 'lost', 'under_review'])->default('pending');
            $table->date('due_date')->nullable();
            $table->text('evidence')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('chargeback_id');
        });

        // Create refunds table
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_transaction_id')->constrained()->onDelete('cascade');
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('refund_id'); // ID from payment gateway
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->enum('type', ['full', 'partial'])->default('full');
            $table->text('reason')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('refund_id');
        });

        // Create split_payments table
        Schema::create('split_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('total_amount', 10, 2);
            $table->enum('status', ['pending', 'partially_paid', 'completed'])->default('pending');
            $table->timestamps();

            $table->index(['invoice_id', 'status']);
        });

        // Create split_payment_parts table
        Schema::create('split_payment_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('split_payment_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_method_id')->nullable()->constrained()->onDelete('set null');
            $table->string('gateway');
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->string('transaction_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index('split_payment_id');
        });

        // Create dunning_campaigns table for automated payment recovery
        Schema::create('dunning_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->json('retry_schedule'); // [1, 3, 7, 14] days
            $table->integer('max_retries')->default(4);
            $table->boolean('suspend_on_failure')->default(true);
            $table->boolean('cancel_on_failure')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Create dunning_attempts table
        Schema::create('dunning_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dunning_campaign_id')->constrained()->onDelete('cascade');
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('attempt_number');
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            $table->timestamp('scheduled_at');
            $table->timestamp('attempted_at')->nullable();
            $table->string('gateway')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['invoice_id', 'status']);
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dunning_attempts');
        Schema::dropIfExists('dunning_campaigns');
        Schema::dropIfExists('split_payment_parts');
        Schema::dropIfExists('split_payments');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('chargebacks');
        Schema::dropIfExists('payment_retries');
        Schema::dropIfExists('payment_methods');

        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropColumn([
                'payment_intent_id',
                'refund_id',
                'refund_amount',
                'refunded_at',
                'failure_code',
                'failure_message',
                'retry_count',
                'next_retry_at',
                'gateway_response',
                'customer_ip',
                'customer_country',
            ]);
        });
    }
};
