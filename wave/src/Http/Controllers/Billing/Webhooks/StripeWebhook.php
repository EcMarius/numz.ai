<?php

namespace Wave\Http\Controllers\Billing\Webhooks;

use Stripe\Webhook;
use UnexpectedValueException;
use Stripe\Exception\SignatureVerificationException;
use Carbon\Carbon;
use Stripe\Stripe;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Stripe\Checkout\Session;
use Wave\Plan;
use Wave\Subscription;

class StripeWebhook extends Controller
{
    public function handler(Request $request)
    {
        \Log::info('Stripe webhook received', ['ip' => $request->ip()]);

        $payload = $request->getContent();

        $sig_header = $request->server('HTTP_STRIPE_SIGNATURE');
        $event = null;

        // Use StripeService to get correct webhook secret
        $stripeService = app(\App\Services\StripeService::class);
        $webhookSecret = $stripeService->getWebhookSecret();

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sig_header,
                $webhookSecret
            );
            \Log::info('Stripe webhook event constructed', ['type' => $event->type, 'id' => $event->id]);
        } catch (UnexpectedValueException $e) {
            // Invalid payload
            \Log::error('Stripe webhook invalid payload', ['error' => $e->getMessage()]);
            http_response_code(400);
            exit();
        } catch (SignatureVerificationException $e) {
            // Invalid signature
            \Log::error('Stripe webhook signature verification failed', ['error' => $e->getMessage()]);
            http_response_code(400);
            exit();
        }

        if ($event->type == 'checkout.session.completed'
            || $event->type == 'checkout.session.async_payment_succeeded') {
            $this->fulfill_checkout($event->data->object->id, $event);
        }

        // Handle subscription creation directly from Stripe (e.g., via Portal, API, or if checkout.session.completed fires late)
        if ($event->type == 'customer.subscription.created') {
            $this->handleSubscriptionCreated($event->data->object, $event);
        }

        // This event occurs when someone updates information in their customer portal.
        // This could be cancelling a subscription or it could be changing their plan.
        if ($event->type == 'customer.subscription.updated') {
            $stripeSubscription = $event->data->object;

            $subscription = Subscription::where('vendor_subscription_id', $stripeSubscription->id)->first();
            if (isset($subscription)) {
                // Update seats quantity if it changed (for seated plans)
                if (isset($stripeSubscription->items->data[0]->quantity)) {
                    $newQuantity = $stripeSubscription->items->data[0]->quantity;
                    if ($subscription->seats_purchased != $newQuantity) {
                        \Log::info('Updating subscription quantity', [
                            'subscription_id' => $subscription->id,
                            'old_quantity' => $subscription->seats_purchased,
                            'new_quantity' => $newQuantity
                        ]);
                        $subscription->seats_purchased = $newQuantity;
                    }
                }

                // Interval should be 'year' or 'month'
                $subscriptionCycle = $stripeSubscription->plan->interval;
                $plan_price_column = ($subscriptionCycle == 'year') ? 'yearly_price_id' : 'monthly_price_id';
                $updatedPlan = Plan::where($plan_price_column, $stripeSubscription->plan->id)->first();

                // IMPORTANT: Check if this is a scheduled downgrade
                // If scheduled_plan_date is in the future, DON'T apply role changes yet
                // The user should keep their current plan features until the scheduled date
                $hasScheduledDowngrade = $subscription->scheduled_plan_id
                    && $subscription->scheduled_plan_date
                    && Carbon::parse($subscription->scheduled_plan_date)->isFuture();

                if (!$hasScheduledDowngrade) {
                    // No scheduled downgrade, apply changes immediately (upgrade or portal change)
                    $subscription->user->switchPlans($updatedPlan);
                    $subscription->plan_id = $updatedPlan->id;

                    // IMPORTANT: Update plan limits snapshot for the new plan
                    $planLimits = null;
                    if (!empty($updatedPlan->custom_properties)) {
                        $customProps = is_string($updatedPlan->custom_properties)
                            ? json_decode($updatedPlan->custom_properties, true)
                            : $updatedPlan->custom_properties;
                        $planLimits = $customProps['evenleads'] ?? null;
                    }
                    $subscription->plan_limits_snapshot = $planLimits ? json_encode($planLimits) : null;

                    \Log::info('Subscription updated immediately', [
                        'subscription_id' => $subscription->id,
                        'new_plan' => $updatedPlan->name,
                        'limits_updated' => !empty($planLimits)
                    ]);
                } else {
                    // Scheduled downgrade exists, don't change role yet
                    // Just update the database plan_id for Stripe sync purposes
                    // But keep the user on their current role until scheduled_plan_date
                    \Log::info('Subscription updated but role change deferred due to scheduled downgrade', [
                        'subscription_id' => $subscription->id,
                        'current_plan' => $subscription->plan->name,
                        'scheduled_plan' => $updatedPlan->name,
                        'scheduled_date' => $subscription->scheduled_plan_date
                    ]);
                }

                $subscription->cycle = $subscriptionCycle;

                // Update billing period dates (important for accurate limit tracking)
                $subscription->current_period_start = Carbon::createFromTimestamp($stripeSubscription->current_period_start);
                $subscription->current_period_end = Carbon::createFromTimestamp($stripeSubscription->current_period_end);

                // this would be true if the user decides to cancel their subscription
                if (is_null($stripeSubscription->cancel_at)) {
                    $subscription->ends_at = null;
                } else {
                    $subscription->ends_at = Carbon::createFromTimestamp($stripeSubscription->cancel_at)->toDateTimeString();
                }

                $subscription->save();
            }
        }

        // Status docs here: https://docs.stripe.com/api/events/types#event_types-customer.subscription.deleted
        if ($event->type == 'customer.subscription.deleted') {
            $stripeSubscription = $event->data->object;

            $subscription = Subscription::where('vendor_subscription_id', $stripeSubscription->id)->first();
            if (isset($subscription)) {
                // If this is a seated plan subscription, handle organization cleanup
                if ($subscription->plan && $subscription->plan->is_seated_plan) {
                    $user = User::find($subscription->billable_id);
                    if ($user && $user->ownedOrganization) {
                        $organization = $user->ownedOrganization;

                        // Delete all team members (not the owner)
                        $organization->members()->delete();

                        // Delete the organization
                        $organization->delete();

                        // Clear user's organization reference
                        $user->organization_id = null;
                        $user->team_role = 'owner';
                        $user->save();

                        \Log::info('Organization deleted due to subscription cancellation', [
                            'user_id' => $user->id,
                            'organization_id' => $organization->id
                        ]);
                    }
                }

                $subscription->cancel();
            }
        }

        // Handle scheduled downgrades on renewal
        if ($event->type == 'invoice.payment_succeeded') {
            $invoice = $event->data->object;

            // Only process subscription renewals (not first payment)
            if ($invoice->billing_reason == 'subscription_cycle') {
                $subscription = Subscription::where('vendor_subscription_id', $invoice->subscription)->first();

                // Update billing period dates on every renewal
                // This effectively "resets" monthly limits (manual syncs, AI replies, etc.)
                if ($subscription) {
                    $subscription->current_period_start = Carbon::createFromTimestamp($invoice->period_start);
                    $subscription->current_period_end = Carbon::createFromTimestamp($invoice->period_end);
                    $subscription->save();

                    \Log::info('Updated billing period on renewal', [
                        'subscription_id' => $subscription->id,
                        'period_start' => $subscription->current_period_start,
                        'period_end' => $subscription->current_period_end
                    ]);
                }

                if ($subscription && $subscription->scheduled_plan_id && $subscription->scheduled_plan_date) {
                    $now = now();
                    $scheduledDate = Carbon::parse($subscription->scheduled_plan_date);

                    // If scheduled date has passed, apply the downgrade
                    if ($now->gte($scheduledDate)) {
                        $newPlan = Plan::find($subscription->scheduled_plan_id);

                        if ($newPlan) {
                            \Log::info('Applying scheduled downgrade', [
                                'subscription_id' => $subscription->id,
                                'from_plan' => $subscription->plan->name,
                                'to_plan' => $newPlan->name
                            ]);

                            // Get new plan limits
                            $newLimits = is_string($subscription->scheduled_plan_limits)
                                ? json_decode($subscription->scheduled_plan_limits, true)
                                : $subscription->scheduled_plan_limits;

                            $user = User::find($subscription->billable_id);

                            if ($user && $newLimits) {
                                // ENFORCE HARD LIMITS - Disable excess resources

                                // 1. CAMPAIGNS: Disable excess campaigns (keep newest ones active)
                                $maxCampaigns = $newLimits['campaigns'] ?? 0;
                                $campaigns = \Wave\Plugins\EvenLeads\Models\Campaign::where('user_id', $user->id)
                                    ->whereNotIn('status', ['disabled_by_downgrade', 'archived'])
                                    ->orderBy('created_at', 'desc')
                                    ->get();

                                if ($campaigns->count() > $maxCampaigns) {
                                    $excessCount = $campaigns->count() - $maxCampaigns;
                                    $excessCampaigns = $campaigns->slice($maxCampaigns);

                                    foreach ($excessCampaigns as $campaign) {
                                        $campaign->status = 'disabled_by_downgrade';
                                        $campaign->save();
                                    }

                                    \Log::info('Disabled excess campaigns on downgrade', [
                                        'user_id' => $user->id,
                                        'disabled_count' => $excessCount
                                    ]);
                                }

                                // 2. LEADS: Archive excess leads (keep newest ones)
                                $maxLeads = $newLimits['leads_storage'] ?? 0;
                                $leadsCount = \Wave\Plugins\EvenLeads\Models\Lead::where('user_id', $user->id)
                                    ->where('archived', false)
                                    ->count();

                                if ($leadsCount > $maxLeads) {
                                    $excessLeadsCount = $leadsCount - $maxLeads;

                                    // Archive oldest leads
                                    \Wave\Plugins\EvenLeads\Models\Lead::where('user_id', $user->id)
                                        ->where('archived', false)
                                        ->orderBy('created_at', 'asc')
                                        ->limit($excessLeadsCount)
                                        ->update(['archived' => true]);

                                    \Log::info('Archived excess leads on downgrade', [
                                        'user_id' => $user->id,
                                        'archived_count' => $excessLeadsCount
                                    ]);
                                }
                            }

                            // Update subscription with new plan
                            $subscription->plan_id = $newPlan->id;
                            $subscription->plan_limits_snapshot = $subscription->scheduled_plan_limits;

                            // Clear scheduled downgrade fields
                            $subscription->scheduled_plan_id = null;
                            $subscription->scheduled_plan_date = null;
                            $subscription->scheduled_plan_limits = null;
                            $subscription->save();

                            // Clear user cache after plan change
                            if ($user) {
                                $user->clearUserCache();
                            }

                            \Log::info('Scheduled downgrade applied successfully', [
                                'subscription_id' => $subscription->id,
                                'new_plan' => $newPlan->name
                            ]);
                        } else {
                            \Log::error('Scheduled plan not found', [
                                'subscription_id' => $subscription->id,
                                'scheduled_plan_id' => $subscription->scheduled_plan_id
                            ]);
                        }
                    }
                }
            }
        }

        // CRITICAL SECURITY: Handle failed payments for seat increases
        // This automatically reverts seat increases if payment fails
        if ($event->type == 'invoice.payment_failed') {
            $invoice = $event->data->object;

            \Log::warning('Invoice payment failed', [
                'invoice_id' => $invoice->id,
                'subscription_id' => $invoice->subscription,
                'amount' => $invoice->amount_due / 100,
                'customer_id' => $invoice->customer,
            ]);

            // Check if this invoice has prorated charges (likely from seat increase)
            $hasProrations = false;
            foreach ($invoice->lines->data ?? [] as $line) {
                if ($line->proration ?? false) {
                    $hasProrations = true;
                    break;
                }
            }

            if ($hasProrations && $invoice->subscription) {
                $subscription = Subscription::where('vendor_subscription_id', $invoice->subscription)->first();

                if ($subscription) {
                    \Log::warning('Failed payment for prorated charges detected - checking for recent seat changes', [
                        'subscription_id' => $subscription->id,
                        'user_id' => $subscription->billable_id,
                    ]);

                    // Find recent seat changes that might be related
                    $recentSeatChange = \App\Models\SeatChangeHistory::where('subscription_id', $subscription->id)
                        ->where('status', 'completed')
                        ->where('stripe_invoice_id', $invoice->id)
                        ->orWhere(function ($q) use ($subscription) {
                            $q->where('subscription_id', $subscription->id)
                              ->where('status', 'pending')
                              ->where('created_at', '>=', now()->subHours(1));
                        })
                        ->orderBy('created_at', 'desc')
                        ->first();

                    if ($recentSeatChange && $recentSeatChange->isIncrease()) {
                        \Log::error('CRITICAL: Payment failed for seat increase - reverting seats', [
                            'user_id' => $subscription->billable_id,
                            'old_seats' => $recentSeatChange->old_seats,
                            'new_seats' => $recentSeatChange->new_seats,
                            'failed_amount' => $invoice->amount_due / 100,
                        ]);

                        try {
                            // Revert seats in Stripe
                            $stripeService = app(\App\Services\StripeService::class);
                            \Stripe\Stripe::setApiKey($stripeService->getSecretKey());

                            $stripeSubscription = \Stripe\Subscription::retrieve($subscription->vendor_subscription_id);
                            \Stripe\Subscription::update($subscription->vendor_subscription_id, [
                                'items' => [[
                                    'id' => $stripeSubscription->items->data[0]->id,
                                    'quantity' => $recentSeatChange->old_seats,
                                ]],
                                'proration_behavior' => 'none', // Don't charge/credit for reverting
                            ]);

                            // Revert seats in database
                            $subscription->seats_purchased = $recentSeatChange->old_seats;
                            $subscription->pending_proration_amount = null;
                            $subscription->pending_invoice_id = null;
                            $subscription->save();

                            // Update history record
                            $recentSeatChange->update([
                                'status' => 'reverted',
                                'failure_reason' => 'Payment failed - seats automatically reverted to ' . $recentSeatChange->old_seats,
                            ]);

                            \Log::info('Successfully reverted seat increase after payment failure', [
                                'subscription_id' => $subscription->id,
                                'reverted_to' => $recentSeatChange->old_seats,
                            ]);

                            // Notify user
                            $user = User::find($subscription->billable_id);
                            if ($user && $user->email) {
                                // TODO: Send email notification to user about failed payment and seat reversion
                                \Log::info('Should notify user about seat reversion', ['user_id' => $user->id]);
                            }

                        } catch (\Exception $e) {
                            \Log::error('CRITICAL: Failed to revert seats after payment failure', [
                                'error' => $e->getMessage(),
                                'subscription_id' => $subscription->id,
                            ]);
                        }
                    }
                }
            }
        }

        // SECURITY AUDIT: Log voided invoices (potential exploit attempts)
        // Voided invoices indicate cancelled subscriptions with pending charges
        if ($event->type == 'invoice.voided') {
            $invoice = $event->data->object;

            \Log::warning('SECURITY AUDIT: Invoice voided - potential revenue loss', [
                'invoice_id' => $invoice->id,
                'subscription_id' => $invoice->subscription,
                'amount_lost' => $invoice->amount_due / 100,
                'customer_id' => $invoice->customer,
                'voided_at' => now()->toDateTimeString(),
            ]);

            // Check if this voided invoice had prorated charges
            $hasProrations = false;
            $proratedAmount = 0;
            foreach ($invoice->lines->data ?? [] as $line) {
                if ($line->proration ?? false) {
                    $hasProrations = true;
                    $proratedAmount += $line->amount;
                }
            }

            if ($hasProrations && $invoice->subscription) {
                $subscription = Subscription::where('vendor_subscription_id', $invoice->subscription)->first();

                if ($subscription) {
                    \Log::critical('REVENUE LOSS: Voided invoice with prorations - user may have exploited seat increase vulnerability', [
                        'subscription_id' => $subscription->id,
                        'user_id' => $subscription->billable_id,
                        'amount_lost' => $proratedAmount / 100,
                        'invoice_id' => $invoice->id,
                        'status' => $subscription->status,
                        'cancelled_at' => $subscription->cancelled_at,
                    ]);

                    // Log in seat change history for audit trail
                    \App\Models\SeatChangeHistory::create([
                        'user_id' => $subscription->billable_id,
                        'subscription_id' => $subscription->id,
                        'old_seats' => $subscription->seats_purchased,
                        'new_seats' => $subscription->seats_purchased,
                        'seats_changed' => 0,
                        'proration_amount' => $proratedAmount / 100,
                        'stripe_invoice_id' => $invoice->id,
                        'status' => 'failed',
                        'payment_status' => 'voided',
                        'failure_reason' => 'Invoice voided - potential exploitation of seat increase before cancellation',
                        'initiated_by' => 'system',
                        'notes' => 'SECURITY ALERT: This invoice was voided, indicating potential revenue loss from cancelled subscription with pending charges',
                    ]);

                    // TODO: Send alert to admin about potential exploitation
                    // TODO: Consider flagging user account for review
                }
            }
        }

        http_response_code(200);
    }

    /**
     * Handle customer.subscription.created event
     * PRIMARY subscription creator - creates subscriptions from scratch if they don't exist
     */
    public function handleSubscriptionCreated($stripeSubscription, $event): void
    {
        \Log::info('Processing customer.subscription.created', [
            'subscription_id' => $stripeSubscription->id,
            'customer_id' => $stripeSubscription->customer,
            'event_id' => $event->id
        ]);

        // Check if subscription already exists
        $subscription = Subscription::where('vendor_subscription_id', $stripeSubscription->id)->first();

        if ($subscription) {
            // Subscription exists - just verify/fix seat count
            $quantity = $this->extractQuantityFromStripeSubscription($stripeSubscription);

            if ($subscription->seats_purchased != $quantity) {
                \Log::warning('FIXING seat count mismatch!', [
                    'subscription_id' => $subscription->id,
                    'database_seats' => $subscription->seats_purchased,
                    'stripe_seats' => $quantity,
                    'updating_to' => $quantity
                ]);

                $subscription->seats_purchased = $quantity;
                $subscription->save();

                \Log::info('Seat count fixed successfully', [
                    'subscription_id' => $subscription->id,
                    'new_seats' => $quantity
                ]);
            } else {
                \Log::info('Seat count already correct', [
                    'subscription_id' => $subscription->id,
                    'seats' => $quantity
                ]);
            }

            return; // Early return - subscription already exists
        }

        // =====================================================================
        // SUBSCRIPTION DOESN'T EXIST - CREATE IT FROM SCRATCH
        // This handles cases where checkout.session.completed never fires
        // =====================================================================

        \Log::info('Subscription not in database - creating from scratch');

        // Step 1: Extract quantity
        $quantity = $this->extractQuantityFromStripeSubscription($stripeSubscription);

        // Step 2: Extract price_id and determine plan + billing cycle
        $priceId = $stripeSubscription->items->data[0]->price->id ?? null;

        if (!$priceId) {
            \Log::error('Cannot create subscription - no price_id found', [
                'subscription_id' => $stripeSubscription->id
            ]);
            return;
        }

        \Log::info('Extracted price_id from subscription', ['price_id' => $priceId]);

        // Step 3: Look up plan by price_id (check both monthly and yearly)
        $plan = Plan::where('monthly_price_id', $priceId)
            ->orWhere('yearly_price_id', $priceId)
            ->first();

        if (!$plan) {
            \Log::error('Cannot create subscription - plan not found for price_id', [
                'price_id' => $priceId,
                'subscription_id' => $stripeSubscription->id
            ]);
            return;
        }

        // Step 4: Determine billing cycle based on which price_id matched
        $billingCycle = 'month'; // default
        if ($priceId === $plan->yearly_price_id) {
            $billingCycle = 'year';
        } elseif ($priceId === $plan->monthly_price_id) {
            $billingCycle = 'month';
        }

        \Log::info('Determined plan and billing cycle', [
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'billing_cycle' => $billingCycle,
            'quantity' => $quantity
        ]);

        // Step 5: Find user by Stripe customer_id
        $user = User::where('vendor_customer_id', $stripeSubscription->customer)->first();

        if (!$user) {
            \Log::error('Cannot create subscription - user not found', [
                'customer_id' => $stripeSubscription->customer,
                'subscription_id' => $stripeSubscription->id
            ]);
            return;
        }

        // Step 6: Get plan limits snapshot
        $planLimits = null;
        if (!empty($plan->custom_properties)) {
            $customProps = is_string($plan->custom_properties)
                ? json_decode($plan->custom_properties, true)
                : $plan->custom_properties;
            $planLimits = $customProps['evenleads'] ?? null;
        }

        // Step 7: Get billing period dates from Stripe
        $currentPeriodStart = null;
        $currentPeriodEnd = null;

        if (isset($stripeSubscription->current_period_start) && $stripeSubscription->current_period_start) {
            $currentPeriodStart = Carbon::createFromTimestamp($stripeSubscription->current_period_start);
        }

        if (isset($stripeSubscription->current_period_end) && $stripeSubscription->current_period_end) {
            $currentPeriodEnd = Carbon::createFromTimestamp($stripeSubscription->current_period_end);
        }

        // Step 8: CREATE SUBSCRIPTION
        $subscription = Subscription::create([
            'billable_type' => 'user',
            'billable_id' => $user->id,
            'plan_id' => $plan->id,
            'vendor_slug' => 'stripe',
            'vendor_customer_id' => $stripeSubscription->customer,
            'vendor_subscription_id' => $stripeSubscription->id,
            'cycle' => $billingCycle,
            'status' => $stripeSubscription->status ?? 'active',
            'seats_purchased' => $quantity,
            'seats_used' => 1, // Owner takes 1 seat
            'requires_organization' => $plan->is_seated_plan ?? false,
            'plan_limits_snapshot' => $planLimits,
            'current_period_start' => $currentPeriodStart,
            'current_period_end' => $currentPeriodEnd,
        ]);

        \Log::info('✅ Subscription created successfully from customer.subscription.created', [
            'subscription_id' => $subscription->id,
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'billing_cycle' => $billingCycle,
            'is_seated_plan' => $plan->is_seated_plan,
            'seats_purchased' => $subscription->seats_purchased,
            'vendor_subscription_id' => $stripeSubscription->id,
        ]);

        // Step 9: Clear user cache
        $user->clearUserCache();

        // Step 10: Send welcome email
        try {
            $isTrial = $subscription->on_trial ?? false;

            \Illuminate\Support\Facades\Mail::to($user->email)->send(
                new \App\Mail\SubscriptionWelcomeMail($user, $subscription, $plan, $isTrial)
            );

            \Log::info('Welcome email sent successfully', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'is_trial' => $isTrial
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to send welcome email', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            // Don't fail the webhook if email fails
        }
    }

    /**
     * Helper method to extract quantity from Stripe subscription
     */
    private function extractQuantityFromStripeSubscription($stripeSubscription): int
    {
        $quantity = 1; // Default

        // Method 1: From subscription items (preferred)
        if (isset($stripeSubscription->items->data[0]->quantity)) {
            $quantity = (int) $stripeSubscription->items->data[0]->quantity;
        }

        // Method 2: Top-level quantity field (deprecated but sometimes used)
        if (isset($stripeSubscription->quantity) && $stripeSubscription->quantity > $quantity) {
            $quantity = (int) $stripeSubscription->quantity;
        }

        return $quantity;
    }

    public function fulfill_checkout($session_id, $event): void
    {
        \Log::info('Fulfilling checkout', ['session_id' => $session_id]);

        $stripeService = app(\App\Services\StripeService::class);
        $stripe = Stripe::setApiKey($stripeService->getSecretKey());

        // Make this function safe to run multiple times,
        // even concurrently, with the same session ID
        $cacheKey = 'stripe_checkout_session_'.$session_id;
        if (Cache::has($cacheKey)) {
            \Log::info('Checkout session already processed, skipping', ['session_id' => $session_id]);
            return; // Session ID already processed, exit early
        }

        Cache::put($cacheKey, true, now()->addHours(24)); // Store session ID in cache for 24 hours

        // Retrieve the Checkout Session from the API with line_items expanded
        $checkout_session = Session::retrieve($session_id);
        \Log::info('Retrieved checkout session', [
            'session_id' => $session_id,
            'payment_status' => $checkout_session->payment_status,
            'subscription_id' => $checkout_session->subscription ?? 'none'
        ]);

        // Check the Checkout Session's payment_status property
        // to determine if fulfillment should be peformed
        if ($checkout_session->payment_status != 'unpaid') {

            $existingSubscription = Subscription::where('vendor_subscription_id', $checkout_session->subscription)->first();
            if ($existingSubscription) {
                \Log::info('Subscription already exists, skipping', ['subscription_id' => $checkout_session->subscription]);
                // This is a failsafe to make sure this method doesn't get called multiple times, if existing subscription, return
                return;
            }

            $billable_id = $checkout_session->metadata->billable_id;
            $billable_type = $checkout_session->metadata->billable_type;
            $plan_id = $checkout_session->metadata->plan_id;
            $billing_cycle = $checkout_session->metadata->billing_cycle;

            // Get seats from metadata, or fall back to subscription item quantity
            $seats = isset($checkout_session->metadata->seats) ? (int) $checkout_session->metadata->seats : 1;

            \Log::info('Seats from checkout session metadata', [
                'seats_from_metadata' => $seats,
                'has_metadata_seats' => isset($checkout_session->metadata->seats),
                'subscription_id' => $checkout_session->subscription
            ]);

            // CRITICAL FIX: Always read from Stripe subscription quantity to ensure accuracy
            // This handles cases where metadata might be missing or incorrect
            if ($checkout_session->subscription) {
                try {
                    $stripe = new \Stripe\StripeClient($stripeService->getSecretKey());
                    $stripeSubscription = $stripe->subscriptions->retrieve($checkout_session->subscription);
                    if (isset($stripeSubscription->items->data[0]->quantity)) {
                        $stripeQuantity = $stripeSubscription->items->data[0]->quantity;

                        // Log if there's a mismatch between metadata and Stripe quantity
                        if ($seats !== $stripeQuantity) {
                            \Log::warning('Seats mismatch between metadata and Stripe quantity', [
                                'metadata_seats' => $seats,
                                'stripe_quantity' => $stripeQuantity,
                                'using_stripe_quantity' => true
                            ]);
                        }

                        // Always use Stripe's quantity as source of truth
                        $seats = $stripeQuantity;
                        \Log::info('Using quantity from Stripe subscription as source of truth', ['seats' => $seats]);
                    }
                } catch (\Exception $e) {
                    \Log::error('Failed to get quantity from Stripe subscription', [
                        'error' => $e->getMessage(),
                        'falling_back_to_metadata' => $seats
                    ]);
                }
            }

            \Log::info('Creating subscription', [
                'user_id' => $billable_id,
                'plan_id' => $plan_id,
                'billing_cycle' => $billing_cycle,
                'seats' => $seats
            ]);

            $user = User::find($billable_id);

            if (!$user) {
                \Log::error('User not found for subscription', ['billable_id' => $billable_id]);
                return;
            }

            $plan = Plan::find($plan_id);

            if (!$plan) {
                \Log::error('Plan not found for subscription', ['plan_id' => $plan_id]);
                return;
            }

            // DO NOT assign roles - roles are managed separately from plans
            // User roles should remain unchanged when subscribing to plans

            // Get plan limits snapshot - this locks in the limits the user paid for
            $planLimits = null;
            if (!empty($plan->custom_properties)) {
                $customProps = is_string($plan->custom_properties)
                    ? json_decode($plan->custom_properties, true)
                    : $plan->custom_properties;

                $planLimits = $customProps['evenleads'] ?? null;
            }

            // Fetch full subscription object from Stripe to get billing period dates
            $stripeSubscription = \Stripe\Subscription::retrieve($checkout_session->subscription);

            // Safely handle billing period timestamps (may be null during trial or initial setup)
            $currentPeriodStart = null;
            $currentPeriodEnd = null;

            if (isset($stripeSubscription->current_period_start) && $stripeSubscription->current_period_start) {
                $currentPeriodStart = Carbon::createFromTimestamp($stripeSubscription->current_period_start);
            }

            if (isset($stripeSubscription->current_period_end) && $stripeSubscription->current_period_end) {
                $currentPeriodEnd = Carbon::createFromTimestamp($stripeSubscription->current_period_end);
            }

            $subscription = Subscription::create([
                'billable_type' => $billable_type,
                'billable_id' => $billable_id,
                'plan_id' => $plan_id,
                'vendor_slug' => 'stripe',
                'vendor_customer_id' => $checkout_session->customer,
                'vendor_subscription_id' => $checkout_session->subscription,
                'cycle' => $billing_cycle,
                'status' => 'active',
                'seats_purchased' => $seats,
                'seats_used' => 1, // Owner takes 1 seat
                'requires_organization' => $plan->is_seated_plan, // Require org setup for seated plans
                'plan_limits_snapshot' => $planLimits, // Save limits snapshot
                'current_period_start' => $currentPeriodStart,
                'current_period_end' => $currentPeriodEnd,
            ]);

            \Log::info('Subscription created successfully', [
                'subscription_id' => $subscription->id,
                'user_id' => $billable_id,
                'plan_id' => $plan_id,
                'plan_name' => $plan->name,
                'is_seated_plan' => $plan->is_seated_plan,
                'seats_purchased' => $subscription->seats_purchased,
                'seats_used' => $subscription->seats_used,
                'requires_organization' => $subscription->requires_organization,
                'plan_limits_saved' => !empty($planLimits),
                'vendor_subscription_id' => $checkout_session->subscription,
                'vendor_customer_id' => $checkout_session->customer
            ]);

            // CRITICAL: Verify seats were saved correctly
            if ($plan->is_seated_plan && $subscription->seats_purchased != $seats) {
                \Log::error('CRITICAL: Seats mismatch detected after subscription creation!', [
                    'expected_seats' => $seats,
                    'saved_seats' => $subscription->seats_purchased,
                    'subscription_id' => $subscription->id,
                    'user_id' => $billable_id
                ]);
            } elseif ($plan->is_seated_plan && $subscription->seats_purchased == $seats) {
                \Log::info('✓ Seats verified correctly', [
                    'seats_purchased' => $subscription->seats_purchased,
                    'subscription_id' => $subscription->id
                ]);
            }

            // CRITICAL: Clear user cache so sidebar and plan checks update immediately
            $user->clearUserCache();

            // Send welcome email
            try {
                $plan = \Wave\Plan::find($plan_id);
                $isTrial = $subscription->on_trial ?? false;

                \Illuminate\Support\Facades\Mail::to($user->email)->send(
                    new \App\Mail\SubscriptionWelcomeMail($user, $subscription, $plan, $isTrial)
                );

                \Log::info('Welcome email sent successfully', [
                    'user_id' => $user->id,
                    'subscription_id' => $subscription->id,
                    'is_trial' => $isTrial
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to send welcome email', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
                // Don't fail the webhook if email fails
            }
        } else {
            \Log::warning('Checkout session payment status is unpaid', ['session_id' => $session_id]);
        }
    }
}
