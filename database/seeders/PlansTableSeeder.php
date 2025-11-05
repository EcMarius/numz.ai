<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Wave\Plan;
use App\Services\StripeService;

class PlansTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     */
    public function run(): void
    {
        // Skip delete - use updateOrCreate instead for idempotency

        $plansData = [
            0 => [
                'id' => 1,
                'name' => 'Starter',
                'description' => 'Perfect for individuals testing lead generation',
                'features' => json_encode(['1 Campaign', '10 Keywords per campaign', '30 Manual Syncs per month', '200 AI Responses per month', '300 Leads Storage', '~20 Leads per sync', 'Email Support', 'Smart Lead Retrieval']),

                'default' => 0,
                'active' => 1,
                'monthly_price' => '14',
                'yearly_price' => '140',
                'currency' => 'EUR',
                'leads_per_sync' => 20,
                'custom_properties' => json_encode([
                    'evenleads' => [
                        'manual_syncs_per_month' => 30,
                        'campaigns' => 1,
                        'keywords_per_campaign' => 10,
                        'allowed_platforms' => ['reddit'],
                        'ai_replies_per_month' => 200,
                        'leads_storage' => 300,
                        'leads_per_sync' => 20,
                        'soft_limit_leads' => true,
                        'forced_openai_model' => 'gpt-3.5-turbo',
                        'allow_model_selection' => false,
                        'automated_sync_interval_minutes' => 1440,
                        'smart_lead_retrieval' => true,
                        'ai_chat_access' => false,
                        'ai_post_management' => false,
                    ],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            1 => [
                'id' => 2,
                'name' => 'Growth',
                'description' => 'For growing businesses scaling their outreach',
                'features' => json_encode(['5 Campaigns', '15 Keywords per campaign', '50 Manual Syncs per month', '300 AI Responses per month', '1,000 Leads Storage', '~40 Leads per sync', 'AI Post Management', 'Post Engagement Analytics', 'Comment Management', 'Priority Email Support', 'Smart Lead Retrieval', 'AI Chat Access']),

                'default' => 1,
                'active' => 1,
                'monthly_price' => '29',
                'yearly_price' => '290',
                'currency' => 'EUR',
                'leads_per_sync' => 40,
                'custom_properties' => json_encode([
                    'evenleads' => [
                        'manual_syncs_per_month' => 50,
                        'campaigns' => 5,
                        'keywords_per_campaign' => 15,
                        'allowed_platforms' => ['reddit'],
                        'ai_replies_per_month' => 300,
                        'leads_storage' => 1000,
                        'leads_per_sync' => 40,
                        'soft_limit_leads' => true,
                        'forced_openai_model' => 'gpt-3.5-turbo',
                        'allow_model_selection' => false,
                        'automated_sync_interval_minutes' => 720,
                        'smart_lead_retrieval' => true,
                        'ai_chat_access' => true,
                        'ai_post_management' => true,
                    ],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            2 => [
                'id' => 3,
                'name' => 'Business',
                'description' => 'Advanced features for teams with per-seat pricing',
                'features' => json_encode(['20 Campaigns per seat', '30 Keywords per campaign', '100 Manual Syncs per month', '1,000 AI Responses per month', '10,000 Leads Storage', '~80 Leads per sync', 'AI Post Management', 'Post Engagement Analytics', 'Comment Management', 'Choose AI Models', 'AI Chat Access', 'Advanced Analytics', 'Priority Support', 'Smart Lead Retrieval', 'Team Management']),

                'default' => 0,
                'active' => 1,
                'monthly_price' => '50',
                'yearly_price' => '500',
                'currency' => 'EUR',
                'is_seated_plan' => true,
                'leads_per_sync' => 80,
                'custom_properties' => json_encode([
                    'evenleads' => [
                        'manual_syncs_per_month' => 100,
                        'campaigns' => 20,
                        'keywords_per_campaign' => 30,
                        'allowed_platforms' => ['reddit'],
                        'ai_replies_per_month' => 1000,
                        'leads_storage' => 10000,
                        'leads_per_sync' => 80,
                        'soft_limit_leads' => true,
                        'forced_openai_model' => null,
                        'allow_model_selection' => true,
                        'available_models' => ['gpt-3.5-turbo', 'gpt-4o-mini'],
                        'automated_sync_interval_minutes' => 360,
                        'smart_lead_retrieval' => true,
                        'ai_chat_access' => true,
                        'ai_post_management' => true,
                    ],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            3 => [
                'id' => 4,
                'name' => 'Enterprise',
                'description' => 'Custom solutions for high-volume lead generation with dedicated support',
                'features' => json_encode(['AI Post Management', 'Post Engagement Analytics', 'Comment Management', 'Custom Campaigns Limit', 'Custom Keywords Limit', 'Unlimited Manual Syncs', 'Custom AI Replies Limit', 'Unlimited Leads Storage', '~500 Leads per sync', 'Custom Sync Interval', 'All AI Models Available', 'Smart Lead Retrieval', 'AI Chat Access', 'Dedicated Account Manager', 'Custom Integrations', '24/7 Priority Support']),

                'default' => 0,
                'active' => 1,
                'monthly_price' => null,
                'yearly_price' => null,
                'currency' => 'EUR',
                'is_on_request' => 1,
                'leads_per_sync' => 500,
                'custom_properties' => json_encode([
                    'evenleads' => [
                        'manual_syncs_per_month' => null,
                        'campaigns' => null,
                        'keywords_per_campaign' => null,
                        'allowed_platforms' => ['reddit'],
                        'ai_replies_per_month' => null,
                        'leads_storage' => null,
                        'leads_per_sync' => 500,
                        'soft_limit_leads' => true,
                        'forced_openai_model' => null,
                        'allow_model_selection' => true,
                        'available_models' => ['gpt-3.5-turbo', 'gpt-4o-mini', 'gpt-4o', 'gpt-4-turbo', 'gpt-4'],
                        'automated_sync_interval_minutes' => null,
                        'smart_lead_retrieval' => true,
                        'ai_chat_access' => true,
                        'ai_post_management' => true,
                    ],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Insert plans one by one to avoid column mismatch
        foreach ($plansData as $planData) {
            Plan::updateOrCreate(
                ['id' => $planData['id']],
                $planData
            );
        }

        // Sync to Stripe if configured
        $stripeService = app(StripeService::class);
        if ($stripeService->isConfigured()) {
            $this->command->info('Syncing plans to Stripe...');

            foreach (Plan::all() as $plan) {
                try {
                    $result = $stripeService->syncPlanToStripe($plan);

                    if ($result['success']) {
                        $this->command->info("✓ {$plan->name} synced to Stripe");
                    } else {
                        $this->command->warn("✗ {$plan->name} failed: " . implode(', ', $result['errors']));
                    }
                } catch (\Exception $e) {
                    $this->command->warn("✗ {$plan->name} error: " . $e->getMessage());
                }
            }
        } else {
            $this->command->warn('Stripe not configured - skipping Stripe sync. Configure in EvenLeads settings to auto-sync.');
        }
    }
}
