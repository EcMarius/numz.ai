<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add advanced fields to invoices table
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('discount', 10, 2)->default(0)->after('tax');
            $table->decimal('amount_paid', 10, 2)->default(0)->after('total');
            $table->enum('type', ['standard', 'proforma', 'credit_note', 'recurring'])->default('standard')->after('invoice_number');
            $table->enum('billing_cycle', ['one_time', 'weekly', 'biweekly', 'monthly', 'quarterly', 'semi_annual', 'annual'])->default('one_time')->after('type');
            $table->foreignId('parent_invoice_id')->nullable()->constrained('invoices')->onDelete('set null')->after('user_id');
            $table->string('po_number')->nullable()->after('invoice_number');
            $table->string('department')->nullable()->after('po_number');
            $table->date('service_start_date')->nullable()->after('due_date');
            $table->date('service_end_date')->nullable()->after('service_start_date');
            $table->boolean('is_proforma')->default(false)->after('type');
            $table->boolean('tax_inclusive')->default(false)->after('tax');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null')->after('user_id');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('admin_notes')->nullable()->after('notes');
            $table->string('locale', 5)->default('en')->after('currency');
            $table->json('custom_fields')->nullable()->after('admin_notes');
            $table->decimal('late_fee', 10, 2)->default(0)->after('discount');
            $table->integer('reminder_count')->default(0)->after('late_fee');
            $table->timestamp('last_reminder_sent_at')->nullable()->after('reminder_count');

            $table->index('type');
            $table->index('billing_cycle');
            $table->index(['status', 'due_date']);
        });

        // Add advanced fields to invoice_items table
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->decimal('unit_price', 10, 2)->after('description');
            $table->text('details')->nullable()->after('description');
            $table->string('tax_rate')->nullable()->after('total');
            $table->decimal('tax_amount', 10, 2)->default(0)->after('tax_rate');
            $table->string('group')->nullable()->after('item_type');
            $table->integer('sort_order')->default(0)->after('group');

            $table->index('sort_order');
        });

        // Create invoice_templates table
        Schema::create('invoice_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->longText('html_template');
            $table->longText('css')->nullable();
            $table->json('variables')->nullable(); // Available template variables
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_default');
        });

        // Create invoice_reminders table
        Schema::create('invoice_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['before_due', 'on_due', 'overdue']);
            $table->integer('days_offset'); // negative for before, 0 for on due, positive for after
            $table->enum('status', ['pending', 'sent', 'failed']);
            $table->timestamp('scheduled_at');
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['invoice_id', 'status']);
            $table->index('scheduled_at');
        });

        // Create invoice_attachments table
        Schema::create('invoice_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->string('filename');
            $table->string('original_filename');
            $table->string('mime_type');
            $table->integer('file_size');
            $table->string('storage_path');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('invoice_id');
        });

        // Create credit_notes table
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('credit_note_number')->unique();
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['pending', 'applied', 'refunded'])->default('pending');
            $table->enum('type', ['overpayment', 'refund', 'goodwill', 'correction'])->default('refund');
            $table->text('reason')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('credit_note_number');
        });

        // Create invoice_disputes table
        Schema::create('invoice_disputes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('reason');
            $table->enum('status', ['open', 'in_review', 'resolved', 'rejected'])->default('open');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['invoice_id', 'status']);
        });

        // Create payment_plans table
        Schema::create('payment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('total_amount', 10, 2);
            $table->integer('installments');
            $table->enum('frequency', ['weekly', 'biweekly', 'monthly'])->default('monthly');
            $table->enum('status', ['active', 'completed', 'defaulted', 'cancelled'])->default('active');
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        // Create payment_plan_installments table
        Schema::create('payment_plan_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_plan_id')->constrained()->onDelete('cascade');
            $table->integer('installment_number');
            $table->decimal('amount', 10, 2);
            $table->date('due_date');
            $table->enum('status', ['pending', 'paid', 'overdue', 'failed'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->string('transaction_id')->nullable();
            $table->timestamps();

            $table->index(['payment_plan_id', 'status']);
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_plan_installments');
        Schema::dropIfExists('payment_plans');
        Schema::dropIfExists('invoice_disputes');
        Schema::dropIfExists('credit_notes');
        Schema::dropIfExists('invoice_attachments');
        Schema::dropIfExists('invoice_reminders');
        Schema::dropIfExists('invoice_templates');

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn([
                'unit_price',
                'details',
                'tax_rate',
                'tax_amount',
                'group',
                'sort_order'
            ]);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['parent_invoice_id']);
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'discount',
                'amount_paid',
                'type',
                'billing_cycle',
                'parent_invoice_id',
                'po_number',
                'department',
                'service_start_date',
                'service_end_date',
                'is_proforma',
                'tax_inclusive',
                'approved_by',
                'approved_at',
                'admin_notes',
                'locale',
                'custom_fields',
                'late_fee',
                'reminder_count',
                'last_reminder_sent_at'
            ]);
        });
    }
};
