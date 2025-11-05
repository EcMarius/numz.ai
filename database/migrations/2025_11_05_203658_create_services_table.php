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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('restrict');
            $table->foreignId('server_id')->nullable()->constrained()->onDelete('set null');
            $table->string('domain')->nullable();
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->enum('billing_cycle', ['monthly', 'quarterly', 'semiannually', 'annually', 'biennially', 'triennially', 'onetime'])->default('monthly');
            $table->decimal('amount', 10, 2)->default(0);
            $table->enum('status', ['pending', 'active', 'suspended', 'terminated', 'cancelled', 'fraud'])->default('pending');
            $table->date('registration_date');
            $table->date('next_due_date')->nullable();
            $table->date('next_invoice_date')->nullable();
            $table->date('termination_date')->nullable();
            $table->boolean('auto_terminate')->default(false);
            $table->boolean('override_auto_suspend')->default(false);
            $table->boolean('override_suspend_until')->nullable();
            $table->boolean('dedicated_ip')->default(false);
            $table->string('assigned_ip')->nullable();
            $table->integer('disk_limit')->nullable();
            $table->integer('disk_usage')->nullable();
            $table->integer('bandwidth_limit')->nullable();
            $table->integer('bandwidth_usage')->nullable();
            $table->date('last_updated')->nullable();
            $table->json('configoptions')->nullable();
            $table->text('notes')->nullable();
            $table->string('subscription_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('client_id');
            $table->index('product_id');
            $table->index('status');
            $table->index('next_due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
