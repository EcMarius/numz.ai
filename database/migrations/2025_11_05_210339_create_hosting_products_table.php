<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hosting_products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['shared', 'vps', 'dedicated', 'cloud'])->default('shared');
            $table->integer('disk_space')->comment('MB');
            $table->integer('bandwidth')->comment('MB');
            $table->integer('databases')->default(0);
            $table->integer('email_accounts')->default(0);
            $table->boolean('ssl_included')->default(false);
            $table->decimal('monthly_price', 10, 2);
            $table->decimal('yearly_price', 10, 2);
            $table->decimal('setup_fee', 10, 2)->default(0);
            $table->string('module')->default('cpanel');
            $table->json('module_config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hosting_products');
    }
};
