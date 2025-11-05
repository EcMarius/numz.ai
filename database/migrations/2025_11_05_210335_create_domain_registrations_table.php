<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domain_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('domain');
            $table->string('registrar')->default('domainnameapi');
            $table->enum('status', ['pending', 'active', 'expired', 'transferred', 'cancelled'])->default('pending');
            $table->date('registration_date');
            $table->date('expiry_date');
            $table->date('next_due_date');
            $table->decimal('renewal_price', 10, 2);
            $table->boolean('auto_renew')->default(true);
            $table->string('nameserver1')->nullable();
            $table->string('nameserver2')->nullable();
            $table->string('nameserver3')->nullable();
            $table->string('nameserver4')->nullable();
            $table->string('epp_code')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index('expiry_date');
            $table->index('next_due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_registrations');
    }
};
