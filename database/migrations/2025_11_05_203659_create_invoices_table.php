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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('invoice_number')->unique();
            $table->date('date');
            $table->date('due_date');
            $table->date('date_paid')->nullable();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('credit', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('tax2', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->string('tax_rate')->nullable();
            $table->string('tax_rate2')->nullable();
            $table->enum('status', ['unpaid', 'paid', 'cancelled', 'refunded', 'collections', 'payment_pending'])->default('unpaid');
            $table->string('payment_method')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('client_id');
            $table->index('invoice_number');
            $table->index('status');
            $table->index('due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
