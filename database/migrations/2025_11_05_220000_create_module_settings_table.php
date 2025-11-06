<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_settings', function (Blueprint $table) {
            $table->id();
            $table->string('module_type'); // payment_gateway, registrar, provisioning, integration
            $table->string('module_name'); // stripe, paypal, domainnameapi, etc
            $table->string('key');
            $table->text('value')->nullable();
            $table->boolean('encrypted')->default(false);
            $table->timestamps();

            $table->unique(['module_type', 'module_name', 'key']);
            $table->index(['module_type', 'module_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_settings');
    }
};
