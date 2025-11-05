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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('firstname');
            $table->string('lastname');
            $table->string('companyname')->nullable();
            $table->string('email')->unique();
            $table->string('address1')->nullable();
            $table->string('address2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postcode')->nullable();
            $table->string('country', 2)->default('US');
            $table->string('phonenumber')->nullable();
            $table->string('tax_id')->nullable();
            $table->enum('status', ['active', 'inactive', 'closed'])->default('active');
            $table->string('language')->default('en');
            $table->string('currency', 3)->default('USD');
            $table->decimal('credit', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('host')->nullable();
            $table->boolean('marketing_emails_opt_in')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('email');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
