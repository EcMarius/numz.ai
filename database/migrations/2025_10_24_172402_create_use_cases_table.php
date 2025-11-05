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
        Schema::create('use_cases', function (Blueprint $table) {
            $table->id();
            $table->string('title', 191);
            $table->text('description');
            $table->string('icon', 191)->nullable(); // phosphor icon name
            $table->string('color', 50)->default('blue'); // Color theme
            $table->string('target_audience', 191)->nullable(); // e.g., "Web Developers", "SaaS Companies"
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
        Schema::dropIfExists('use_cases');
    }
};
