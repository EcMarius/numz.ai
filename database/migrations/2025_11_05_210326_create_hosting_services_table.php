<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hosting_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('hosting_product_id')->constrained()->onDelete('restrict');
            $table->foreignId('hosting_server_id')->nullable()->constrained()->onDelete('set null');
            $table->string('domain');
            $table->string('username')->nullable();
            $table->text('password')->nullable();
            $table->enum('billing_cycle', ['monthly', 'yearly'])->default('monthly');
            $table->decimal('price', 10, 2);
            $table->enum('status', ['pending', 'active', 'suspended', 'terminated', 'cancelled'])->default('pending');
            $table->date('next_due_date')->nullable();
            $table->date('activated_at')->nullable();
            $table->date('suspended_at')->nullable();
            $table->date('terminated_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index('next_due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hosting_services');
    }
};
