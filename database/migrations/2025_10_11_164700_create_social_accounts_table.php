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
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('provider'); // google, facebook, github, twitter, etc.
            $table->string('provider_id'); // unique ID from the provider
            $table->string('email')->nullable();
            $table->string('name')->nullable();
            $table->string('avatar')->nullable();
            $table->text('token')->nullable(); // access token
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamps();

            // Unique constraint: one provider account per user
            $table->unique(['user_id', 'provider']);
            // Index for faster lookups
            $table->index(['provider', 'provider_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
