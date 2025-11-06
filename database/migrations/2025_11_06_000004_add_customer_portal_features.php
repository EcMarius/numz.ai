<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Dashboard widgets configuration
        Schema::create('dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('widget_type'); // service_status, bandwidth_usage, recent_invoices, etc.
            $table->string('title');
            $table->integer('position_x')->default(0);
            $table->integer('position_y')->default(0);
            $table->integer('width')->default(1);
            $table->integer('height')->default(1);
            $table->boolean('is_visible')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_visible']);
        });

        // Announcements
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->enum('type', ['info', 'warning', 'success', 'danger'])->default('info');
            $table->enum('target', ['all', 'customers', 'admins', 'specific'])->default('all');
            $table->json('target_user_ids')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_published')->default(false);
            $table->boolean('show_on_dashboard')->default(true);
            $table->boolean('require_acknowledgment')->default(false);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index(['is_published', 'start_date', 'end_date']);
        });

        // Announcement acknowledgments
        Schema::create('announcement_acknowledgments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('acknowledged_at');
            $table->timestamps();

            $table->unique(['announcement_id', 'user_id']);
        });

        // Service usage tracking
        Schema::create('service_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hosting_service_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->bigInteger('bandwidth_used')->default(0); // in bytes
            $table->bigInteger('disk_used')->default(0); // in bytes
            $table->integer('email_sent')->default(0);
            $table->integer('database_queries')->default(0);
            $table->decimal('cpu_usage_avg', 5, 2)->default(0); // percentage
            $table->decimal('memory_usage_avg', 5, 2)->default(0); // percentage
            $table->json('additional_metrics')->nullable();
            $table->timestamps();

            $table->unique(['hosting_service_id', 'date']);
            $table->index('date');
        });

        // Knowledge base categories
        Schema::create('kb_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('kb_categories')->onDelete('cascade');
            $table->string('icon')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        // Knowledge base articles
        Schema::create('kb_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kb_category_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content');
            $table->text('excerpt')->nullable();
            $table->json('tags')->nullable();
            $table->integer('views')->default(0);
            $table->integer('helpful_count')->default(0);
            $table->integer('not_helpful_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(false);
            $table->foreignId('author_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['is_published', 'is_featured']);
            $table->index('slug');
            $table->fullText(['title', 'content']);
        });

        // Article feedback
        Schema::create('kb_article_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kb_article_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->boolean('is_helpful');
            $table->text('comment')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();

            $table->index('kb_article_id');
        });

        // User preferences
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('key');
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, boolean, json, integer
            $table->timestamps();

            $table->unique(['user_id', 'key']);
        });

        // User notifications
        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // invoice, service, ticket, announcement
            $table->string('title');
            $table->text('message');
            $table->string('link')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_read']);
            $table->index('created_at');
        });

        // Activity log
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('action'); // login, logout, invoice_paid, service_created, etc.
            $table->string('entity_type')->nullable(); // Invoice, HostingService, etc.
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->text('description');
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'action']);
            $table->index('created_at');
        });

        // Quick actions
        Schema::create('quick_actions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('icon');
            $table->string('action_type'); // link, api_call, modal
            $table->string('action_value'); // URL or API endpoint
            $table->text('description')->nullable();
            $table->enum('visibility', ['all', 'admins', 'customers'])->default('all');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('conditions')->nullable(); // Show based on conditions
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quick_actions');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('user_notifications');
        Schema::dropIfExists('user_preferences');
        Schema::dropIfExists('kb_article_feedback');
        Schema::dropIfExists('kb_articles');
        Schema::dropIfExists('kb_categories');
        Schema::dropIfExists('service_usage');
        Schema::dropIfExists('announcement_acknowledgments');
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('dashboard_widgets');
    }
};
