<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Wave\Plan;
use Spatie\Permission\Models\Role;

class NewPlansTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure user role exists
        $userRole = Role::firstOrCreate(['name' => 'user']);

        // For plans, disable foreign key checks temporarily to clear old plans
        // Active subscriptions are safe - they use plan snapshots
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Plan::truncate();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Starter Plan
        Plan::create([
            'name' => 'Starter',
            'description' => 'Perfect for individuals testing lead generation',
            'features' => json_encode([
                '1 Campaign',
                '10 Keywords per campaign',
                '15 Manual Syncs per month',
                '400 AI Responses per month',
                '300 Leads Storage',
                'Smart Lead Retrieval (AI)',
                'AI Assistant Chat',
                'Post Management',
                'Email Support'
            ]),
            'custom_properties' => json_encode([
                'evenleads' => [
                    'campaigns' => 1,
                    'keywords_per_campaign' => 10,
                    'manual_syncs_per_month' => 15,
                    'ai_replies_per_month' => 400,
                    'leads_storage' => 300,
                    'automated_sync_interval_minutes' => 1440, // 24 hours
                    'leads_per_sync' => 50,
                    'soft_limit_leads' => true,
                    'ai_chat_access' => true,
                    'ai_post_management' => false,
                    'has_smart_search' => true,
                    'ai_models' => ['gpt-5-nano']
                ]
            ]),
            'monthly_price' => '19.00',
            'yearly_price' => '190.00',
            'currency' => 'EUR',
            'active' => true,
            'default' => false,
            'custom_plan' => false,
            'leads_per_sync' => 50,
            'is_seated_plan' => false,
        ]);

        // Growth Plan
        Plan::create([
            'name' => 'Growth',
            'description' => 'For growing businesses scaling their outreach',
            'features' => json_encode([
                '5 Campaigns',
                '15 Keywords per campaign',
                '30 Manual Syncs per month',
                '1,000 AI Responses per month',
                '1,000 Leads Storage',
                'Smart Lead Retrieval (AI)',
                'AI Assistant Chat',
                'Post Management',
                'AI Post Management',
                'Automated Follow-Ups',
                'Priority Email Support'
            ]),
            'custom_properties' => json_encode([
                'evenleads' => [
                    'campaigns' => 5,
                    'keywords_per_campaign' => 15,
                    'manual_syncs_per_month' => 30,
                    'ai_replies_per_month' => 1000,
                    'leads_storage' => 1000,
                    'automated_sync_interval_minutes' => 720, // 12 hours
                    'leads_per_sync' => 70,
                    'soft_limit_leads' => true,
                    'ai_chat_access' => true,
                    'ai_post_management' => true,
                    'follow_up_enabled' => false,
                    'has_smart_search' => true,
                    'ai_models' => ['gpt-5-nano', 'gpt-5-mini']
                ]
            ]),
            'monthly_price' => '29.00',
            'yearly_price' => '290.00',
            'currency' => 'EUR',
            'active' => true,
            'default' => true, // This is the recommended plan
            'custom_plan' => false,
            'leads_per_sync' => 70,
            'is_seated_plan' => false,
        ]);

        // Business Plan (SEATED PLAN)
        Plan::create([
            'name' => 'Business',
            'description' => 'Advanced features for serious lead generation',
            'features' => json_encode([
                '20 Campaigns',
                '30 Keywords per campaign',
                '100 Manual Syncs per month',
                '10,000 AI Responses per month',
                '10,000 Leads Storage',
                'Smart Lead Retrieval (AI)',
                'AI Chat Access',
                'Post Management',
                'AI Post Management',
                'Automated Follow-Ups',
                'Priority Support'
            ]),
            'custom_properties' => json_encode([
                'evenleads' => [
                    'campaigns' => 20,
                    'keywords_per_campaign' => 30,
                    'manual_syncs_per_month' => 100,
                    'ai_replies_per_month' => 10000,
                    'leads_storage' => 10000,
                    'automated_sync_interval_minutes' => 360, // 6 hours
                    'leads_per_sync' => 100,
                    'soft_limit_leads' => true,
                    'ai_models' => ['gpt-5-nano', 'gpt-5-mini'],
                    'ai_chat_access' => true,
                    'ai_post_management' => true,
                    'follow_up_enabled' => false,
                    'has_smart_search' => true
                ]
            ]),
            'monthly_price' => '60.00',
            'yearly_price' => '600.00',
            'currency' => 'EUR',
            'active' => true,
            'default' => false,
            'custom_plan' => false,
            'leads_per_sync' => 100,
            'is_seated_plan' => true, // SEATED PLAN
        ]);

        // Enterprise Plan (SEATED PLAN, ON REQUEST)
        Plan::create([
            'name' => 'Enterprise',
            'description' => 'Custom solutions for high-volume lead generation with dedicated support',
            'features' => json_encode([
                'Custom Campaigns Limit',
                'Custom Keywords Limit',
                'Unlimited Manual Syncs',
                'Custom AI Replies Limit',
                'Unlimited Leads Storage',
                'Custom Sync Interval',
                'Smart Lead Retrieval (AI)',
                'AI Chat Access',
                'Post Management',
                'AI Post Management',
                'Automated Follow-Ups',
                'Dedicated Account Manager',
                'Custom Integrations',
                'SLA Agreement',
                '24/7 Priority Support'
            ]),
            'custom_properties' => json_encode([
                'evenleads' => [
                    'campaigns' => -1,
                    'keywords_per_campaign' => -1,
                    'manual_syncs_per_month' => -1,
                    'ai_replies_per_month' => -1,
                    'leads_storage' => -1,
                    'automated_sync_interval_minutes' => 30, // 30 minutes
                    'leads_per_sync' => 999999,
                    'soft_limit_leads' => false,
                    'ai_models' => ['gpt-5-nano', 'gpt-5-mini', 'gpt-5'],
                    'ai_chat_access' => true,
                    'ai_post_management' => true,
                    'follow_up_enabled' => false,
                    'has_smart_search' => true
                ]
            ]),
            'monthly_price' => null,
            'yearly_price' => null,
            'currency' => 'EUR',
            'active' => true,
            'default' => false,
            'custom_plan' => true,
            'is_on_request' => true,
            'custom_plan_description' => 'Contact our sales team for custom pricing based on your specific requirements.',
            'leads_per_sync' => 999999,
            'is_seated_plan' => true, // SEATED PLAN
        ]);

        $this->command->info('✅ Plans seeded successfully!');
        $this->command->info('📊 Created 4 plans: Starter (€14), Growth (€29), Business (€50 - Seated), Enterprise (On Request - Seated)');
        $this->command->info('');
        $this->command->warn('⚠️  IMPORTANT: Active subscriptions are safe (using plan snapshots)');
        $this->command->warn('⚠️  NEXT STEP: Run Stripe price sync command to create Stripe prices:');
        $this->command->info('   php artisan stripe:sync-prices');
    }
}
