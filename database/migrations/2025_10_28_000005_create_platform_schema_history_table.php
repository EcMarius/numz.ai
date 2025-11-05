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
        Schema::create('platform_schema_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_schema_id')->constrained('platform_schemas')->onDelete('cascade');
            $table->string('action'); // created, updated, deleted, activated, deactivated
            $table->json('old_data')->nullable(); // Previous state
            $table->json('new_data')->nullable(); // New state
            $table->string('version'); // Schema version at time of change
            $table->text('change_description')->nullable();
            $table->integer('changed_by_user_id')->nullable(); // Who made the change
            $table->timestamp('created_at');

            // Indexes
            $table->index(['platform_schema_id', 'created_at']);
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_schema_history');
    }
};
