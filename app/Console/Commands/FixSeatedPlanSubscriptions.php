<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Wave\Plan;
use Wave\Subscription;

class FixSeatedPlanSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seated:fix {--enable-org : Enable organization requirement for testing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix existing Business plan subscriptions to add seat data and optionally enable organization features';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 Fixing Business plan subscriptions...');

        // Get Business plan
        $businessPlan = Plan::where('name', 'Business')->first();

        if (!$businessPlan) {
            $this->error('Business plan not found!');
            return 1;
        }

        // Get all Business plan subscriptions
        $subscriptions = Subscription::where('plan_id', $businessPlan->id)
            ->where('status', 'active')
            ->get();

        if ($subscriptions->isEmpty()) {
            $this->warn('No active Business plan subscriptions found.');
            return 0;
        }

        $this->info("Found {$subscriptions->count()} active Business plan subscription(s).");

        $enableOrg = $this->option('enable-org');

        foreach ($subscriptions as $subscription) {
            $user = $subscription->user;

            // Fix seat data if missing
            if (is_null($subscription->seats_purchased) || $subscription->seats_purchased < 1) {
                $subscription->seats_purchased = 1;
                $this->line("  → Set seats_purchased = 1 for subscription #{$subscription->id}");
            }

            if (is_null($subscription->seats_used) || $subscription->seats_used < 1) {
                $subscription->seats_used = 1;
                $this->line("  → Set seats_used = 1 for subscription #{$subscription->id}");
            }

            // Optionally enable organization requirement
            if ($enableOrg) {
                $subscription->requires_organization = true;
                $this->line("  → Enabled organization requirement for subscription #{$subscription->id}");
            }

            $subscription->save();

            $this->info("✓ Fixed subscription #{$subscription->id} for user: {$user->email}");
        }

        $this->newLine();
        $this->info('✅ All subscriptions fixed!');

        if ($enableOrg) {
            $this->warn('Organization requirement enabled. Users will be redirected to /organization/setup on next login.');
        } else {
            $this->line('To enable organization features for testing, run:');
            $this->line('  php artisan seated:fix --enable-org');
        }

        return 0;
    }
}
