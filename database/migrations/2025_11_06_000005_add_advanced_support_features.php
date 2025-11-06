<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Enhance support tickets table
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->foreignId('sla_policy_id')->nullable()->constrained()->onDelete('set null')->after('department');
            $table->timestamp('first_response_at')->nullable()->after('last_reply_at');
            $table->timestamp('resolved_at')->nullable()->after('closed_at');
            $table->timestamp('sla_breach_at')->nullable()->after('resolved_at');
            $table->integer('time_to_first_response')->nullable()->after('sla_breach_at'); // in minutes
            $table->integer('time_to_resolution')->nullable()->after('time_to_first_response'); // in minutes
            $table->decimal('satisfaction_rating', 2, 1)->nullable()->after('time_to_resolution');
            $table->text('satisfaction_comment')->nullable()->after('satisfaction_rating');
            $table->json('tags')->nullable()->after('satisfaction_comment');
            $table->string('source')->default('portal')->after('tags'); // portal, email, chat, api
            $table->json('custom_fields')->nullable()->after('source');

            $table->index('first_response_at');
            $table->index('resolved_at');
            $table->index('satisfaction_rating');
        });

        // SLA policies
        Schema::create('sla_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->integer('first_response_time'); // in minutes
            $table->integer('resolution_time'); // in minutes
            $table->json('priority_multipliers')->nullable(); // Different times per priority
            $table->json('working_hours')->nullable(); // Business hours only
            $table->json('holidays')->nullable(); // Exclude holidays
            $table->timestamps();

            $table->index('is_active');
        });

        // Ticket templates
        Schema::create('ticket_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('subject');
            $table->text('message');
            $table->string('department')->nullable();
            $table->string('priority')->nullable();
            $table->json('tags')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('usage_count')->default(0);
            $table->timestamps();
        });

        // Canned responses / macros
        Schema::create('canned_responses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('shortcode')->unique();
            $table->text('content');
            $table->string('category')->nullable();
            $table->boolean('is_public')->default(true); // Available to all staff
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->integer('usage_count')->default(0);
            $table->timestamps();

            $table->index('shortcode');
        });

        // Ticket watchers (CC users)
        Schema::create('ticket_watchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('added_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['support_ticket_id', 'user_id']);
        });

        // Ticket merges (track merged tickets)
        Schema::create('ticket_merges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_ticket_id')->constrained('support_tickets')->onDelete('cascade');
            $table->foreignId('merged_ticket_id')->constrained('support_tickets')->onDelete('cascade');
            $table->foreignId('merged_by')->constrained('users')->onDelete('cascade');
            $table->text('merge_reason')->nullable();
            $table->timestamps();

            $table->index('parent_ticket_id');
        });

        // Ticket auto-close rules
        Schema::create('ticket_auto_close_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->integer('days_after_last_reply')->default(7);
            $table->json('conditions')->nullable(); // Status, department, etc.
            $table->text('close_message')->nullable();
            $table->timestamps();
        });

        // Support departments
        Schema::create('support_departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('email')->unique();
            $table->foreignId('sla_policy_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('assignment_type', ['round_robin', 'load_based', 'skill_based', 'manual'])->default('round_robin');
            $table->json('working_hours')->nullable();
            $table->string('auto_response_message')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('slug');
            $table->index(['is_active', 'sort_order']);
        });

        // Department staff assignments
        Schema::create('department_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_department_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('max_active_tickets')->default(10);
            $table->json('skills')->nullable(); // For skill-based routing
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['support_department_id', 'user_id']);
        });

        // Live chat sessions
        Schema::create('chat_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('visitor_name')->nullable();
            $table->string('visitor_email')->nullable();
            $table->foreignId('agent_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('department_id')->nullable()->constrained('support_departments')->onDelete('set null');
            $table->enum('status', ['waiting', 'active', 'ended', 'transferred'])->default('waiting');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->integer('duration')->nullable(); // in seconds
            $table->decimal('satisfaction_rating', 2, 1)->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('page_url')->nullable();
            $table->timestamps();

            $table->index(['status', 'agent_id']);
        });

        // Chat messages
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_session_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('sender_type', ['customer', 'agent', 'bot'])->default('customer');
            $table->text('message');
            $table->json('attachments')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index('chat_session_id');
        });

        // AI chatbot configurations
        Schema::create('chatbot_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->text('welcome_message');
            $table->text('fallback_message');
            $table->boolean('auto_transfer_to_human')->default(true);
            $table->integer('transfer_threshold')->default(3); // After X failed attempts
            $table->json('trained_intents')->nullable();
            $table->json('working_hours')->nullable();
            $table->string('ai_provider')->default('openai'); // openai, claude, custom
            $table->string('ai_model')->default('gpt-4');
            $table->text('system_prompt')->nullable();
            $table->timestamps();
        });

        // Chatbot conversation logs
        Schema::create('chatbot_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_session_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->text('user_message');
            $table->text('bot_response');
            $table->string('intent_detected')->nullable();
            $table->decimal('confidence_score', 5, 4)->nullable();
            $table->json('entities_extracted')->nullable();
            $table->boolean('was_helpful')->nullable();
            $table->boolean('transferred_to_human')->default(false);
            $table->timestamps();

            $table->index('intent_detected');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_conversations');
        Schema::dropIfExists('chatbot_configurations');
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_sessions');
        Schema::dropIfExists('department_staff');
        Schema::dropIfExists('support_departments');
        Schema::dropIfExists('ticket_auto_close_rules');
        Schema::dropIfExists('ticket_merges');
        Schema::dropIfExists('ticket_watchers');
        Schema::dropIfExists('canned_responses');
        Schema::dropIfExists('ticket_templates');
        Schema::dropIfExists('sla_policies');

        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropForeign(['sla_policy_id']);
            $table->dropColumn([
                'sla_policy_id',
                'first_response_at',
                'resolved_at',
                'sla_breach_at',
                'time_to_first_response',
                'time_to_resolution',
                'satisfaction_rating',
                'satisfaction_comment',
                'tags',
                'source',
                'custom_fields',
            ]);
        });
    }
};
