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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_group_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('type', ['hosting', 'domain', 'server', 'other'])->default('hosting');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('welcome_email')->nullable();
            $table->boolean('hidden')->default(false);
            $table->boolean('show_domain_options')->default(true);
            $table->integer('stock_control')->nullable();
            $table->integer('qty')->nullable();
            $table->decimal('monthly_price', 10, 2)->default(0);
            $table->decimal('quarterly_price', 10, 2)->default(0);
            $table->decimal('semiannually_price', 10, 2)->default(0);
            $table->decimal('annually_price', 10, 2)->default(0);
            $table->decimal('biennially_price', 10, 2)->default(0);
            $table->decimal('triennially_price', 10, 2)->default(0);
            $table->decimal('setup_fee', 10, 2)->default(0);
            $table->string('payment_type')->default('recurring');
            $table->boolean('allow_quantity')->default(false);
            $table->string('module_name')->nullable();
            $table->json('module_config')->nullable();
            $table->string('server_group')->nullable();
            $table->json('configoptions')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
