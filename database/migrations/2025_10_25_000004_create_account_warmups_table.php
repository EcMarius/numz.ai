<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('account_warmups')) {
            Schema::create('account_warmups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('social_account_id')->constrained()->onDelete('cascade');
            $table->string('platform', 50); // reddit, facebook, twitter, linkedin
            $table->string('status', 20)->default('pending'); // pending, active, paused, completed, failed
            $table->string('current_phase', 50)->nullable(); // introduction, engagement, reputation
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->integer('scheduled_days')->default(14);
            $table->integer('current_day')->default(0);
            $table->integer('posts_per_day_min')->default(1);
            $table->integer('posts_per_day_max')->default(3);
            $table->integer('comments_per_day_min')->default(2);
            $table->integer('comments_per_day_max')->default(5);
            $table->timestamp('last_activity_at')->nullable();
            $table->json('settings')->nullable(); // Platform-specific settings
            $table->json('stats')->nullable(); // Track posts/comments made, karma, etc.
            $table->timestamps();

            $table->index(['user_id', 'platform']);
            $table->index(['status', 'current_day']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('account_warmups');
    }
};
