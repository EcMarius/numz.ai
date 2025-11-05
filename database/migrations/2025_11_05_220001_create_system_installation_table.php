<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_installation', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_installed')->default(false);
            $table->string('license_key')->nullable();
            $table->string('license_email')->nullable();
            $table->enum('license_status', ['active', 'expired', 'invalid', 'suspended'])->default('active');
            $table->timestamp('license_verified_at')->nullable();
            $table->timestamp('installed_at')->nullable();
            $table->string('installation_id')->unique();
            $table->string('app_version')->default('1.0.0');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_installation');
    }
};
