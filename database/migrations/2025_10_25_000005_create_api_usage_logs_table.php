<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('api_usage_logs')) {
            Schema::create('api_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 50); // reddit, facebook, twitter, linkedin
            $table->string('endpoint')->nullable();
            $table->string('method', 10); // GET, POST, PUT, DELETE
            $table->integer('status_code');
            $table->integer('response_time_ms')->nullable();
            $table->integer('rate_limit_remaining')->nullable();
            $table->timestamp('rate_limit_reset')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('campaign_id')->nullable(); // Not FK to avoid cascade issues
            $table->json('request_data')->nullable();
            $table->json('response_data')->nullable();
            $table->timestamp('created_at');

            $table->index(['platform', 'created_at']);
            $table->index('endpoint');
            $table->index('user_id');
            $table->index('created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('api_usage_logs');
    }
};
