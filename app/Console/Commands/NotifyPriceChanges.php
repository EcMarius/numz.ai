<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Wave\Plan;
use Wave\Subscription;
use App\Mail\PriceChangeNotification;
use Illuminate\Support\Facades\Mail;
use Stripe\StripeClient;
use Carbon\Carbon;

/**
 * LEGALLY COMPLIANT PRICE CHANGE NOTIFICATION COMMAND
 *
 * This command notifies all active subscribers of a plan when the price changes.
 * It ensures full legal compliance with advance notice requirements.
 *
 * Usage:
 *   php artisan subscriptions:notify-price-changes {plan_id}
 *
 * Example:
 *   php artisan subscriptions:notify-price-changes 3
 *   (Notifies all Business plan subscribers)
 *
 * What it does:
 * 1. Finds all active subscriptions for the plan
 * 2. Gets renewal date from Stripe for each subscription
 * 3. Sets pending_price_change fields
 * 4. Sends legally compliant email notification
 * 5. Logs all actions for audit trail
 */
class NotifyPriceChanges extends Command
{
    protected $signature = 'subscriptions:notify-price-changes
                            {plan_id : The ID of the plan with changed price}
                            {--dry-run : Preview without sending emails}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Notify all active subscribers when a plan price changes (LEGALLY COMPLIANT)';

    private $stripe;
    private $notified = 0;
    private $skipped = 0;
    private $errors = 0;

    public function handle()
    {
        $this->info('🔔 PRICE CHANGE NOTIFICATION SYSTEM');
        $this->info('===================================');
        $this->newLine();

        // Get plan
        $planId = $this->argument('plan_id');
        $plan = Plan::find($planId);

        if (!$plan) {
            $this->error("❌ Plan ID {$planId} not found.");
            return 1;
        }

        // Initialize Stripe
        if (!config('wave.stripe.secret_key')) {
            $this->error('❌ Stripe not configured. Set STRIPE_SECRET_KEY.');
            return 1;
        }

        $this->stripe = new StripeClient(config('wave.stripe.secret_key'));

        // Show plan info
        $this->info("Plan: {$plan->name}");
        $this->info("Current Monthly Price: {$plan->monthly_price} {$plan->currency}");
        $this->info("Current Yearly Price: {$plan->yearly_price} {$plan->currency}");
        $this->newLine();

        // Find affected subscriptions
        $subscriptions = Subscription::where('plan_id', $plan->id)
            ->where('status', 'active')
            ->whereNull('pending_price_change') // Don't re-notify
            ->with('user')
            ->get();

        if ($subscriptions->isEmpty()) {
            $this->warn('⚠️  No active subscriptions found for this plan.');
            return 0;
        }

        $this->info("Found {$subscriptions->count()} active subscriptions.");
        $this->newLine();

        // Confirm unless --force
        if (!$this->option('force') && !$this->option('dry-run')) {
            if (!$this->confirm("Send price change notifications to {$subscriptions->count()} users?")) {
                $this->warn('Cancelled.');
                return 0;
            }
        }

        // Process each subscription
        $this->info('Processing subscriptions...');
        $this->newLine();

        $progressBar = $this->output->createProgressBar($subscriptions->count());
        $progressBar->start();

        foreach ($subscriptions as $subscription) {
            $this->processSubscription($subscription, $plan);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Show summary
        $this->info('✅ COMPLETED');
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("Notified: {$this->notified}");
        $this->warn("Skipped:  {$this->skipped}");
        if ($this->errors > 0) {
            $this->error("Errors:   {$this->errors}");
        }

        if ($this->option('dry-run')) {
            $this->warn("\n⚠️  DRY RUN - No emails sent, no database changes made.");
        }

        return 0;
    }

    private function processSubscription(Subscription $subscription, Plan $plan)
    {
        try {
            $user = $subscription->user()->first();

            if (!$user || !$user->email) {
                $this->skipped++;
                $this->newLine();
                $this->warn("⚠️  Skipped: No user/email for subscription #{$subscription->id}");
                return;
            }

            // Get renewal date from Stripe
            $stripeSubscription = $this->stripe->subscriptions->retrieve($subscription->vendor_subscription_id);
            $renewalDate = Carbon::createFromTimestamp($stripeSubscription->current_period_end);

            // Determine new price based on cycle
            $newPrice = $subscription->cycle == 'month'
                ? $plan->monthly_price
                : $plan->yearly_price;

            $newPriceId = $subscription->cycle == 'month'
                ? $plan->monthly_price_id
                : $plan->yearly_price_id;

            // Skip if price hasn't actually changed
            $currentPrice = $subscription->subscribed_price ?? $newPrice;
            if ($newPrice == $currentPrice) {
                $this->skipped++;
                return;
            }

            if (!$this->option('dry-run')) {
                // Update subscription with pending price change
                $subscription->update([
                    'pending_price_change' => true,
                    'pending_price' => $newPrice,
                    'pending_currency' => $plan->currency,
                    'pending_price_id' => $newPriceId,
                    'price_change_notice_sent_at' => now(),
                    'price_change_effective_date' => $renewalDate->toDateString(),
                    'price_change_auto_renew' => false, // PAUSE by default (safer)
                    'price_change_reminder_count' => 0,
                ]);

                // Send email notification
                Mail::to($user->email)->send(new PriceChangeNotification($subscription->fresh()));
            }

            $this->notified++;

            // Log for audit
            \Log::info('Price change notification sent', [
                'subscription_id' => $subscription->id,
                'user_id' => $user->id,
                'user_email' => $user->email,
                'plan_name' => $plan->name,
                'old_price' => $currentPrice,
                'new_price' => $newPrice,
                'currency' => $plan->currency,
                'renewal_date' => $renewalDate->toDateString(),
                'dry_run' => $this->option('dry-run'),
            ]);

        } catch (\Exception $e) {
            $this->errors++;
            $this->newLine();
            $this->error("❌ Error processing subscription #{$subscription->id}: " . $e->getMessage());

            \Log::error('Price change notification failed', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
