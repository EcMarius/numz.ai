<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Module configurations (payment gateways, provisioning modules, etc.)
        Schema::create('module_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('module_type'); // payment_gateway, provisioning, registrar, integration
            $table->string('module_name'); // stripe, paypal, cpanel, etc.
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->boolean('is_available')->default(true); // Can be disabled if requirements not met
            $table->json('configuration')->nullable(); // All module settings
            $table->json('credentials')->nullable(); // Encrypted credentials
            $table->boolean('test_mode')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamp('last_tested_at')->nullable();
            $table->boolean('test_successful')->nullable();
            $table->text('test_error')->nullable();
            $table->json('capabilities')->nullable(); // What features this module supports
            $table->json('required_fields')->nullable(); // What fields are required
            $table->string('version')->nullable();
            $table->timestamps();

            $table->unique(['module_type', 'module_name']);
            $table->index(['module_type', 'is_enabled']);
        });

        // System settings (all configurable settings)
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('group'); // billing, email, notifications, ai, security, etc.
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, boolean, integer, json, encrypted
            $table->text('description')->nullable();
            $table->json('validation_rules')->nullable();
            $table->json('options')->nullable(); // For select fields
            $table->boolean('is_public')->default(false); // Can customers see this?
            $table->boolean('requires_restart')->default(false);
            $table->timestamps();

            $table->index(['group', 'key']);
        });

        // Email templates (customizable through admin)
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category'); // billing, support, system, custom
            $table->string('subject');
            $table->longText('html_body');
            $table->longText('text_body')->nullable();
            $table->json('available_variables')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false); // System templates can't be deleted
            $table->json('attachments')->nullable();
            $table->string('from_name')->nullable();
            $table->string('from_email')->nullable();
            $table->string('reply_to')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('slug');
            $table->index(['category', 'is_active']);
        });

        // Module webhooks/callbacks
        Schema::create('module_webhooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_configuration_id')->constrained()->onDelete('cascade');
            $table->string('event_type'); // payment_received, service_created, etc.
            $table->string('webhook_url')->nullable();
            $table->string('secret')->nullable();
            $table->json('payload')->nullable();
            $table->json('response')->nullable();
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            $table->integer('retry_count')->default(0);
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['module_configuration_id', 'event_type']);
            $table->index(['status', 'created_at']);
        });

        // Module test results
        Schema::create('module_test_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_configuration_id')->constrained()->onDelete('cascade');
            $table->foreignId('tested_by')->constrained('users')->onDelete('cascade');
            $table->string('test_type'); // connection, credentials, api, webhook
            $table->boolean('success');
            $table->text('message')->nullable();
            $table->json('details')->nullable();
            $table->float('response_time')->nullable(); // in seconds
            $table->timestamps();

            $table->index('module_configuration_id');
        });

        // API credentials (for external services)
        Schema::create('api_credentials', function (Blueprint $table) {
            $table->id();
            $table->string('service_name'); // openai, claude, sendgrid, etc.
            $table->string('display_name');
            $table->string('credential_type'); // api_key, oauth, username_password
            $table->text('api_key')->nullable(); // Encrypted
            $table->text('api_secret')->nullable(); // Encrypted
            $table->text('access_token')->nullable(); // Encrypted
            $table->text('refresh_token')->nullable(); // Encrypted
            $table->json('additional_config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->integer('usage_count')->default(0);
            $table->integer('rate_limit')->nullable();
            $table->integer('rate_limit_remaining')->nullable();
            $table->timestamp('rate_limit_reset_at')->nullable();
            $table->timestamps();

            $table->unique('service_name');
        });

        // Automation rules
        Schema::create('automation_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('trigger_event'); // invoice_overdue, service_created, etc.
            $table->json('conditions')->nullable(); // When to trigger
            $table->json('actions')->nullable(); // What to do
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);
            $table->integer('execution_count')->default(0);
            $table->timestamp('last_executed_at')->nullable();
            $table->timestamps();

            $table->index(['trigger_event', 'is_active']);
        });

        // Automation execution log
        Schema::create('automation_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_rule_id')->constrained()->onDelete('cascade');
            $table->string('trigger_event');
            $table->json('trigger_data')->nullable();
            $table->boolean('conditions_met');
            $table->json('actions_taken')->nullable();
            $table->boolean('success');
            $table->text('error_message')->nullable();
            $table->float('execution_time')->nullable(); // in seconds
            $table->timestamps();

            $table->index('automation_rule_id');
            $table->index('created_at');
        });

        // Integration marketplace
        Schema::create('integration_marketplace', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('category'); // payment, provisioning, communication, analytics
            $table->string('provider');
            $table->string('provider_url')->nullable();
            $table->json('features')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('documentation_url')->nullable();
            $table->decimal('rating', 2, 1)->default(0);
            $table->integer('installations')->default(0);
            $table->boolean('is_official')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->boolean('requires_subscription')->default(false);
            $table->decimal('monthly_cost', 10, 2)->nullable();
            $table->string('version')->nullable();
            $table->timestamps();

            $table->index('category');
            $table->index(['is_featured', 'rating']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_marketplace');
        Schema::dropIfExists('automation_executions');
        Schema::dropIfExists('automation_rules');
        Schema::dropIfExists('api_credentials');
        Schema::dropIfExists('module_test_results');
        Schema::dropIfExists('module_webhooks');
        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('module_configurations');
    }
};
