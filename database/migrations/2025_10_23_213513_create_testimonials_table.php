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
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('name', 191);
            $table->string('position', 191);
            $table->string('company', 191)->nullable();
            $table->text('content');
            $table->string('avatar', 191)->nullable();
            $table->string('avatar_fallback', 10)->nullable();
            $table->string('gradient_from', 50)->default('blue-500');
            $table->string('gradient_to', 50)->default('blue-600');
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};
