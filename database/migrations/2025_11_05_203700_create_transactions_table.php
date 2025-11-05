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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('set null');
            $table->string('transaction_id')->unique();
            $table->string('gateway')->nullable();
            $table->date('date');
            $table->text('description')->nullable();
            $table->decimal('amount_in', 10, 2)->default(0);
            $table->decimal('amount_out', 10, 2)->default(0);
            $table->decimal('fees', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->decimal('rate', 10, 5)->default(1.00000);
            $table->enum('status', ['pending', 'success', 'failed', 'refunded'])->default('pending');
            $table->string('payment_method')->nullable();
            $table->json('gateway_response')->nullable();
            $table->timestamps();

            $table->index('client_id');
            $table->index('invoice_id');
            $table->index('transaction_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
