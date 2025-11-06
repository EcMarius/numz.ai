<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // General Settings
            ['group' => 'general', 'key' => 'company_name', 'value' => 'NUMZ.AI', 'type' => 'string', 'is_public' => true, 'description' => 'Company or business name'],
            ['group' => 'general', 'key' => 'company_url', 'value' => 'https://numz.ai', 'type' => 'string', 'is_public' => true, 'description' => 'Company website URL'],
            ['group' => 'general', 'key' => 'support_email', 'value' => 'support@numz.ai', 'type' => 'string', 'is_public' => true, 'description' => 'Support email address'],
            ['group' => 'general', 'key' => 'default_currency', 'value' => 'USD', 'type' => 'string', 'is_public' => true, 'description' => 'Default currency code'],
            ['group' => 'general', 'key' => 'default_timezone', 'value' => 'UTC', 'type' => 'string', 'is_public' => true, 'description' => 'Default timezone'],
            ['group' => 'general', 'key' => 'date_format', 'value' => 'Y-m-d', 'type' => 'string', 'is_public' => true, 'description' => 'Date format'],
            ['group' => 'general', 'key' => 'time_format', 'value' => 'H:i:s', 'type' => 'string', 'is_public' => true, 'description' => 'Time format'],

            // Billing Settings
            ['group' => 'billing', 'key' => 'invoice_prefix', 'value' => 'INV-', 'type' => 'string', 'is_public' => false, 'description' => 'Invoice number prefix'],
            ['group' => 'billing', 'key' => 'invoice_due_days', 'value' => '14', 'type' => 'integer', 'is_public' => false, 'description' => 'Default invoice due days'],
            ['group' => 'billing', 'key' => 'auto_suspend_days', 'value' => '7', 'type' => 'integer', 'is_public' => false, 'description' => 'Days after due date to auto-suspend'],
            ['group' => 'billing', 'key' => 'auto_terminate_days', 'value' => '30', 'type' => 'integer', 'is_public' => false, 'description' => 'Days after suspension to auto-terminate'],
            ['group' => 'billing', 'key' => 'late_fee_percentage', 'value' => '5', 'type' => 'decimal', 'is_public' => false, 'description' => 'Late payment fee percentage'],
            ['group' => 'billing', 'key' => 'enable_credit_system', 'value' => 'true', 'type' => 'boolean', 'is_public' => false, 'description' => 'Enable account credit system'],
            ['group' => 'billing', 'key' => 'enable_partial_payments', 'value' => 'true', 'type' => 'boolean', 'is_public' => false, 'description' => 'Allow partial invoice payments'],
            ['group' => 'billing', 'key' => 'tax_inclusive_pricing', 'value' => 'false', 'type' => 'boolean', 'is_public' => true, 'description' => 'Display prices with tax included'],

            // Email Settings
            ['group' => 'email', 'key' => 'mail_driver', 'value' => 'smtp', 'type' => 'string', 'is_public' => false, 'description' => 'Mail driver (smtp, sendmail, mailgun, etc)'],
            ['group' => 'email', 'key' => 'mail_from_address', 'value' => 'noreply@numz.ai', 'type' => 'string', 'is_public' => false, 'description' => 'From email address'],
            ['group' => 'email', 'key' => 'mail_from_name', 'value' => 'NUMZ.AI', 'type' => 'string', 'is_public' => false, 'description' => 'From name'],
            ['group' => 'email', 'key' => 'enable_email_notifications', 'value' => 'true', 'type' => 'boolean', 'is_public' => false, 'description' => 'Enable email notifications'],

            // AI & Automation Settings
            ['group' => 'ai', 'key' => 'enable_ai_features', 'value' => 'false', 'type' => 'boolean', 'is_public' => false, 'description' => 'Enable AI-powered features'],
            ['group' => 'ai', 'key' => 'ai_provider', 'value' => 'openai', 'type' => 'string', 'is_public' => false, 'description' => 'AI provider (openai, anthropic)'],
            ['group' => 'ai', 'key' => 'ai_model', 'value' => 'gpt-4', 'type' => 'string', 'is_public' => false, 'description' => 'AI model to use'],
            ['group' => 'ai', 'key' => 'enable_ai_chatbot', 'value' => 'false', 'type' => 'boolean', 'is_public' => false, 'description' => 'Enable AI chatbot'],
            ['group' => 'ai', 'key' => 'enable_churn_prediction', 'value' => 'false', 'type' => 'boolean', 'is_public' => false, 'description' => 'Enable churn prediction'],
            ['group' => 'ai', 'key' => 'enable_fraud_detection', 'value' => 'false', 'type' => 'boolean', 'is_public' => false, 'description' => 'Enable fraud detection'],
            ['group' => 'ai', 'key' => 'enable_sentiment_analysis', 'value' => 'false', 'type' => 'boolean', 'is_public' => false, 'description' => 'Enable sentiment analysis'],

            // Security Settings
            ['group' => 'security', 'key' => 'enforce_2fa_admin', 'value' => 'false', 'type' => 'boolean', 'is_public' => false, 'description' => 'Enforce 2FA for admin users'],
            ['group' => 'security', 'key' => 'admin_ip_whitelist', 'value' => '', 'type' => 'string', 'is_public' => false, 'description' => 'Admin IP whitelist (comma-separated)'],
            ['group' => 'security', 'key' => 'session_lifetime', 'value' => '120', 'type' => 'integer', 'is_public' => false, 'description' => 'Session lifetime in minutes'],
            ['group' => 'security', 'key' => 'password_min_length', 'value' => '8', 'type' => 'integer', 'is_public' => true, 'description' => 'Minimum password length'],
            ['group' => 'security', 'key' => 'enable_audit_log', 'value' => 'true', 'type' => 'boolean', 'is_public' => false, 'description' => 'Enable audit logging'],

            // Support Settings
            ['group' => 'support', 'key' => 'enable_support_tickets', 'value' => 'true', 'type' => 'boolean', 'is_public' => true, 'description' => 'Enable support ticket system'],
            ['group' => 'support', 'key' => 'enable_live_chat', 'value' => 'false', 'type' => 'boolean', 'is_public' => true, 'description' => 'Enable live chat'],
            ['group' => 'support', 'key' => 'enable_knowledge_base', 'value' => 'true', 'type' => 'boolean', 'is_public' => true, 'description' => 'Enable knowledge base'],
            ['group' => 'support', 'key' => 'default_ticket_priority', 'value' => 'medium', 'type' => 'string', 'is_public' => false, 'description' => 'Default ticket priority'],
            ['group' => 'support', 'key' => 'ticket_auto_close_days', 'value' => '7', 'type' => 'integer', 'is_public' => false, 'description' => 'Auto-close tickets after days of inactivity'],

            // Provisioning Settings
            ['group' => 'provisioning', 'key' => 'auto_provision', 'value' => 'true', 'type' => 'boolean', 'is_public' => false, 'description' => 'Auto-provision services on payment'],
            ['group' => 'provisioning', 'key' => 'provision_timeout', 'value' => '300', 'type' => 'integer', 'is_public' => false, 'description' => 'Provisioning timeout in seconds'],
            ['group' => 'provisioning', 'key' => 'enable_auto_ssl', 'value' => 'true', 'type' => 'boolean', 'is_public' => false, 'description' => 'Enable automatic SSL provisioning'],

            // Performance Settings
            ['group' => 'performance', 'key' => 'enable_cache', 'value' => 'true', 'type' => 'boolean', 'is_public' => false, 'description' => 'Enable application caching'],
            ['group' => 'performance', 'key' => 'cache_driver', 'value' => 'redis', 'type' => 'string', 'is_public' => false, 'description' => 'Cache driver (file, redis, memcached)'],
            ['group' => 'performance', 'key' => 'queue_driver', 'value' => 'database', 'type' => 'string', 'is_public' => false, 'description' => 'Queue driver (sync, database, redis)'],
        ];

        foreach ($settings as $setting) {
            SystemSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
