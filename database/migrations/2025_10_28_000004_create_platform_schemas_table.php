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
        Schema::create('platform_schemas', function (Blueprint $table) {
            $table->id();
            $table->string('platform'); // linkedin, reddit, facebook, x, etc.
            $table->string('page_type'); // post, person, group, comment, etc.
            $table->string('element_type'); // post_wrapper, post_title, author_name, etc.
            $table->text('css_selector')->nullable();
            $table->text('xpath_selector')->nullable();
            $table->boolean('is_required')->default(false);
            $table->text('fallback_value')->nullable();
            $table->string('parent_element')->nullable(); // For nested elements
            $table->boolean('multiple')->default(false); // Can match multiple elements
            $table->boolean('is_wrapper')->default(false); // True for wrapper elements (post_wrapper, etc.)
            $table->string('version')->default('1.0.0');
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->integer('order')->default(0); // Display order
            $table->timestamps();

            // Indexes for better performance
            $table->index(['platform', 'page_type', 'is_active']);
            $table->index(['element_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_schemas');
    }
};
