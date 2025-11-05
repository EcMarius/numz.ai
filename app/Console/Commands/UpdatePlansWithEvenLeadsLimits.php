<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Wave\Plan;

class UpdatePlansWithEvenLeadsLimits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'evenleads:update-plans';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update all plans with EvenLeads default limits';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating plans with EvenLeads limits...');

        $plans = Plan::all();

        foreach ($plans as $plan) {
            $customProperties = is_string($plan->custom_properties)
                ? json_decode($plan->custom_properties, true) ?? []
                : ($plan->custom_properties ?? []);

            // Check if EvenLeads limits are already set
            if (isset($customProperties['evenleads'])) {
                $this->info("Plan '{$plan->name}' already has EvenLeads limits, skipping...");
                continue;
            }

            // Set default EvenLeads limits based on plan name/tier
            $defaults = $this->getDefaultLimitsForPlan($plan);

            $customProperties['evenleads'] = $defaults;

            $plan->custom_properties = $customProperties;
            $plan->save();

            $this->info("✓ Updated plan '{$plan->name}' with EvenLeads limits");
        }

        $this->newLine();
        $this->info('✓ All plans updated successfully!');

        return Command::SUCCESS;
    }

    /**
     * Get default limits based on plan characteristics
     */
    protected function getDefaultLimitsForPlan(Plan $plan): array
    {
        $planName = strtolower($plan->name);

        // Free/Trial plans
        if (str_contains($planName, 'free') || str_contains($planName, 'trial')) {
            return [
                'campaigns' => 1,
                'keywords_per_campaign' => 5,
                'manual_syncs_per_month' => 3,
                'ai_replies_per_month' => 10,
                'leads_per_sync' => 20,
                'soft_limit_leads' => true,
                'leads_storage' => 50,
                'automated_sync_interval_minutes' => 0, // No automated sync
            ];
        }

        // Basic/Starter plans
        if (str_contains($planName, 'basic') || str_contains($planName, 'starter')) {
            return [
                'campaigns' => 3,
                'keywords_per_campaign' => 10,
                'manual_syncs_per_month' => 10,
                'ai_replies_per_month' => 50,
                'leads_per_sync' => 60,
                'soft_limit_leads' => true,
                'leads_storage' => 200,
                'automated_sync_interval_minutes' => 1440, // Daily
            ];
        }

        // Pro/Professional plans
        if (str_contains($planName, 'pro')) {
            return [
                'campaigns' => 10,
                'keywords_per_campaign' => 25,
                'manual_syncs_per_month' => 50,
                'ai_replies_per_month' => 200,
                'leads_per_sync' => 150,
                'soft_limit_leads' => true,
                'leads_storage' => 1000,
                'automated_sync_interval_minutes' => 720, // Every 12 hours
            ];
        }

        // Business/Enterprise plans (unlimited)
        if (str_contains($planName, 'business') || str_contains($planName, 'enterprise') || str_contains($planName, 'unlimited')) {
            return [
                'campaigns' => -1,
                'keywords_per_campaign' => -1,
                'manual_syncs_per_month' => -1,
                'ai_replies_per_month' => -1,
                'leads_per_sync' => 500,
                'soft_limit_leads' => true,
                'leads_storage' => -1,
                'automated_sync_interval_minutes' => 360, // Every 6 hours
            ];
        }

        // Default (moderate limits)
        return [
            'campaigns' => 5,
            'keywords_per_campaign' => 15,
            'manual_syncs_per_month' => 20,
            'ai_replies_per_month' => 100,
            'leads_per_sync' => 60,
            'soft_limit_leads' => true,
            'leads_storage' => 500,
            'automated_sync_interval_minutes' => 1440, // Daily
        ];
    }
}
