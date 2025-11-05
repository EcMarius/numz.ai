<?php

namespace Wave\Http\Livewire\Billing;

use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Livewire\Attributes\On;
use Livewire\Component;
use Stripe\StripeClient;
use Wave\Actions\Billing\Paddle\AddSubscriptionIdFromTransaction;
use Wave\Plan;
use Wave\Subscription;

class Checkout extends Component
{
    public $billing_cycle_available = 'month'; // month, year, or both;

    public $billing_cycle_selected = 'month';

    public $billing_provider;

    public $paddle_url;

    public $change = false;

    public $userSubscription = null;

    public $userPlan = null;

    // Modal properties for scheduled downgrade conflict
    public $showDowngradeConflictModal = false;
    public $conflictPlanName = '';
    public $conflictPlanId = null;
    public $scheduledPlanName = '';
    public $scheduledDate = '';

    public function mount()
    {
        $this->billing_provider = config('wave.billing_provider', 'stripe');
        $this->paddle_url = (config('wave.paddle.env') == 'sandbox') ? 'https://sandbox-api.paddle.com' : 'https://api.paddle.com';
        $this->updateCycleBasedOnPlans();

        // Check for billing cycle from URL parameter (from pricing page)
        $billingParam = request()->get('billing');
        if ($billingParam && in_array($billingParam, ['monthly', 'yearly', 'month', 'year'])) {
            // Normalize to 'month' or 'year'
            $this->billing_cycle_selected = in_array($billingParam, ['monthly', 'month']) ? 'month' : 'year';
        }

        if ($this->change) {
            // if we are changing the user plan as opposecd to checking out the first time.
            $this->userSubscription = auth()->user()->subscription;
            $this->userPlan = auth()->user()->subscription->plan;
        }
    }

    public function redirectToStripeCheckout(Plan $plan, $seats = null)
    {
        // Get seat quantity from parameter or request (for seated plans)
        if ($seats === null) {
            $seats = request()->get('seats', 1);
        }
        $seats = max(1, min(50, (int) $seats)); // Ensure seats is between 1 and 50

        \Log::info('Stripe checkout initiated', [
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'is_seated_plan' => $plan->is_seated_plan,
            'seats_requested' => $seats,
            'user_id' => auth()->id()
        ]);

        // DETECT DOWNGRADE - Check if user has an active subscription
        $currentSub = Subscription::where('billable_id', auth()->user()->id)
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($currentSub) {
            // Check if user already has a scheduled downgrade
            if ($currentSub->scheduled_plan_id) {
                $scheduledPlan = Plan::find($currentSub->scheduled_plan_id);
                $scheduledDate = \Carbon\Carbon::parse($currentSub->scheduled_plan_date);

                // Show modal instead of notification
                $this->showDowngradeConflictModal = true;
                $this->conflictPlanName = $plan->name;
                $this->conflictPlanId = $plan->id;
                $this->scheduledPlanName = $scheduledPlan->name;
                $this->scheduledDate = $scheduledDate->format('M d, Y');

                return;
            }

            $currentPlan = $currentSub->plan;

            // Compare plan prices to detect downgrade - normalize to same billing cycle
            // Get current plan price based on their current billing cycle
            $currentPrice = 0;
            if ($currentSub->cycle == 'month') {
                $currentPrice = (float) ($currentPlan->monthly_price ?? 0);
            } else {
                $currentPrice = (float) ($currentPlan->yearly_price ?? 0);
            }

            // Get new plan price based on selected billing cycle
            $newPrice = 0;
            if ($this->billing_cycle_selected == 'month') {
                $newPrice = (float) ($plan->monthly_price ?? 0);
            } else {
                $newPrice = (float) ($plan->yearly_price ?? 0);
            }

            // Only allow downgrade if SAME billing cycle and lower price
            // Prevent billing cycle changes during downgrade (legal complexity)
            if ($currentSub->cycle !== $this->billing_cycle_selected) {
                Notification::make()
                    ->title('Billing Cycle Change Not Allowed')
                    ->body('To change your billing cycle, please cancel your current subscription first, then subscribe to the new plan.')
                    ->warning()
                    ->send();
                return redirect()->to('/settings/subscription');
            }

            if ($newPrice < $currentPrice) {
                // THIS IS A DOWNGRADE - Schedule it instead of creating new subscription
                return $this->scheduleDowngrade($currentSub, $plan);
            } elseif ($newPrice > $currentPrice) {
                // THIS IS AN UPGRADE - Apply immediately with proration
                return $this->upgradeSubscription($currentSub, $plan);
            } else {
                // Same price, different plan? Block it
                Notification::make()
                    ->title('Same Plan Price')
                    ->body('This plan has the same price as your current plan.')
                    ->warning()
                    ->send();
                return redirect()->to('/settings/subscription');
            }
        }

        // Use StripeService to get correct credentials (respects test/live mode)
        $stripeService = app(\App\Services\StripeService::class);

        if (!$stripeService->isConfigured()) {
            Notification::make()
                ->title('Stripe Not Configured')
                ->body('Please contact support.')
                ->danger()
                ->send();
            return redirect()->route('plan-selection');
        }

        $stripe = new StripeClient($stripeService->getSecretKey());

        $price_id = $this->billing_cycle_selected == 'month' ? $plan->monthly_price_id : $plan->yearly_price_id ?? null;

        $sessionData = [
            'line_items' => [[
                'price' => $price_id,
                'quantity' => $plan->is_seated_plan ? $seats : 1,
            ]],
            'metadata' => [
                'billable_type' => 'user',
                'billable_id' => auth()->user()->id,
                'plan_id' => $plan->id,
                'billing_cycle' => $this->billing_cycle_selected,
                'seats' => $plan->is_seated_plan ? $seats : 1,
            ],
            'mode' => 'subscription',
            'success_url' => url('subscription/welcome'),
            'cancel_url' => url('settings/subscription'),
        ];

        // Add trial period if configured, user doesn't have a subscription, and hasn't used trial before
        // Only apply trial if:
        // 1. This is the designated trial plan
        // 2. Trial days > 0
        // 3. User doesn't have an active subscription
        // 4. User hasn't already used their trial (trial_ends_at OR trial_activated_at is not set)
        $trialDays = (int) setting('trial_days', 0);
        $trialPlanId = setting('trial_plan_id');
        $userHasUsedTrial = !empty(auth()->user()->trial_ends_at) || !empty(auth()->user()->trial_activated_at);

        if ($trialDays > 0
            && $trialPlanId == $plan->id
            && !auth()->user()->subscription
            && !$userHasUsedTrial) {
            $sessionData['subscription_data'] = [
                'trial_period_days' => $trialDays,
            ];
        }

        $checkout_session = $stripe->checkout->sessions->create($sessionData);

        return redirect()->to($checkout_session->url);
    }

    protected function scheduleDowngrade($currentSub, $newPlan)
    {
        // Get subscription from Stripe to find period end
        $stripeService = app(\App\Services\StripeService::class);
        $stripe = new StripeClient($stripeService->getSecretKey());

        try {
            $stripeSubscription = $stripe->subscriptions->retrieve($currentSub->vendor_subscription_id);

            // Determine the scheduled date
            $scheduledDate = null;

            // Check if subscription has current_period_end
            if (!empty($stripeSubscription->current_period_end)) {
                $scheduledDate = \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_end);
            }
            // If in trial, use trial_end
            elseif (!empty($stripeSubscription->trial_end)) {
                $scheduledDate = \Carbon\Carbon::createFromTimestamp($stripeSubscription->trial_end);
            }
            // Fallback: schedule for 30 days from now
            else {
                $scheduledDate = now()->addDays(30);
                \Log::warning('No period_end or trial_end found, scheduling downgrade for 30 days', [
                    'subscription_id' => $currentSub->id
                ]);
            }

            // Get plan limits for the new plan
            $planLimits = null;
            if (!empty($newPlan->custom_properties)) {
                $customProps = is_string($newPlan->custom_properties)
                    ? json_decode($newPlan->custom_properties, true)
                    : $newPlan->custom_properties;
                $planLimits = $customProps['evenleads'] ?? null;
            }

            // SAVE scheduled downgrade FIRST (before updating Stripe)
            // This prevents the webhook from applying changes immediately
            $currentSub->scheduled_plan_id = $newPlan->id;
            $currentSub->scheduled_plan_date = $scheduledDate;
            $currentSub->scheduled_plan_limits = $planLimits ? json_encode($planLimits) : null;
            $currentSub->save();

            // CRITICAL: Update the Stripe subscription to charge correct amount on renewal
            // This ensures the user is charged $9 (Starter) instead of $49 (Growth) on next billing cycle
            $price_id = $this->billing_cycle_selected == 'month' ? $newPlan->monthly_price_id : $newPlan->yearly_price_id;

            $stripe->subscriptions->update($currentSub->vendor_subscription_id, [
                'items' => [
                    [
                        'id' => $stripeSubscription->items->data[0]->id,
                        'price' => $price_id,
                    ],
                ],
                'proration_behavior' => 'none', // Don't charge or refund immediately
                'billing_cycle_anchor' => 'unchanged', // Keep same renewal date
            ]);

            \Log::info('Stripe subscription updated for scheduled downgrade', [
                'subscription_id' => $currentSub->id,
                'stripe_subscription_id' => $currentSub->vendor_subscription_id,
                'new_plan' => $newPlan->name,
                'new_price_id' => $price_id,
                'scheduled_date' => $scheduledDate->toDateTimeString()
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to schedule downgrade in Stripe', [
                'error' => $e->getMessage(),
                'subscription_id' => $currentSub->id
            ]);

            // Rollback database changes if Stripe update failed
            $currentSub->scheduled_plan_id = null;
            $currentSub->scheduled_plan_date = null;
            $currentSub->scheduled_plan_limits = null;
            $currentSub->save();

            Notification::make()
                ->title('Downgrade Failed')
                ->body('Unable to schedule plan change. Please try again or contact support.')
                ->danger()
                ->send();

            return redirect()->to('/settings/subscription');
        }

        // Show success notification
        Notification::make()
            ->title('Downgrade Scheduled')
            ->body("Your plan will change to {$newPlan->name} on " . $scheduledDate->format('M d, Y') . '. You will keep your current plan features until then.')
            ->success()
            ->send();

        return redirect()->to('/settings/subscription');
    }

    protected function upgradeSubscription($currentSub, $newPlan)
    {
        $stripeService = app(\App\Services\StripeService::class);
        $stripe = new StripeClient($stripeService->getSecretKey());

        try {
            $stripeSubscription = $stripe->subscriptions->retrieve($currentSub->vendor_subscription_id);

            // Get new plan price_id based on billing cycle
            $price_id = $this->billing_cycle_selected == 'month'
                ? $newPlan->monthly_price_id
                : $newPlan->yearly_price_id;

            // CRITICAL: Update existing Stripe subscription (don't create new one!)
            // This prevents double billing
            $stripe->subscriptions->update($currentSub->vendor_subscription_id, [
                'items' => [
                    [
                        'id' => $stripeSubscription->items->data[0]->id,
                        'price' => $price_id,
                    ],
                ],
                'proration_behavior' => 'create_prorations', // Charge prorated amount immediately
                'billing_cycle_anchor' => 'unchanged', // Keep same renewal date
            ]);

            // Get plan limits for the new plan
            $planLimits = null;
            if (!empty($newPlan->custom_properties)) {
                $customProps = is_string($newPlan->custom_properties)
                    ? json_decode($newPlan->custom_properties, true)
                    : $newPlan->custom_properties;
                $planLimits = $customProps['evenleads'] ?? null;
            }

            // Update database IMMEDIATELY (upgrade is instant, not scheduled)
            $currentSub->plan_id = $newPlan->id;
            $currentSub->plan_limits_snapshot = $planLimits ? json_encode($planLimits) : null;
            $currentSub->save();

            // Clear user cache after plan change
            $user = auth()->user();
            $user->clearUserCache();

            \Log::info('Subscription upgraded successfully', [
                'subscription_id' => $currentSub->id,
                'stripe_subscription_id' => $currentSub->vendor_subscription_id,
                'new_plan' => $newPlan->name,
                'new_price_id' => $price_id,
                'proration' => 'immediate'
            ]);

            Notification::make()
                ->title('Upgrade Successful!')
                ->body("You've been upgraded to {$newPlan->name}! You now have access to all {$newPlan->name} features. You'll see a prorated charge on your payment method.")
                ->success()
                ->send();

            return redirect()->to('/subscription/welcome');

        } catch (\Exception $e) {
            \Log::error('Failed to upgrade subscription', [
                'error' => $e->getMessage(),
                'subscription_id' => $currentSub->id
            ]);

            Notification::make()
                ->title('Upgrade Failed')
                ->body('Unable to upgrade your plan. Please try again or contact support.')
                ->danger()
                ->send();

            return redirect()->to('/settings/subscription');
        }
    }

    public function cancelScheduledDowngrade()
    {
        $subscription = Subscription::where('billable_id', auth()->user()->id)
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$subscription || !$subscription->scheduled_plan_id) {
            Notification::make()
                ->title('No Scheduled Downgrade')
                ->body('You don\'t have any scheduled plan changes.')
                ->warning()
                ->send();
            return;
        }

        try {
            $stripeService = app(\App\Services\StripeService::class);
            $stripe = new StripeClient($stripeService->getSecretKey());

            // Revert Stripe subscription back to current plan
            $stripeSubscription = $stripe->subscriptions->retrieve($subscription->vendor_subscription_id);
            $currentPlan = $subscription->plan;

            $current_price_id = $subscription->cycle == 'month'
                ? $currentPlan->monthly_price_id
                : $currentPlan->yearly_price_id;

            $stripe->subscriptions->update($subscription->vendor_subscription_id, [
                'items' => [
                    [
                        'id' => $stripeSubscription->items->data[0]->id,
                        'price' => $current_price_id,
                    ],
                ],
                'proration_behavior' => 'none',
            ]);

            // Clear scheduled downgrade from database
            $scheduledPlanName = Plan::find($subscription->scheduled_plan_id)->name ?? 'Unknown';
            $subscription->scheduled_plan_id = null;
            $subscription->scheduled_plan_date = null;
            $subscription->scheduled_plan_limits = null;
            $subscription->save();

            \Log::info('Scheduled downgrade cancelled', [
                'subscription_id' => $subscription->id,
                'cancelled_plan' => $scheduledPlanName
            ]);

            Notification::make()
                ->title('Downgrade Cancelled')
                ->body('Your scheduled plan change has been cancelled. You will continue on your current plan.')
                ->success()
                ->send();

        } catch (\Exception $e) {
            \Log::error('Failed to cancel scheduled downgrade', [
                'error' => $e->getMessage(),
                'subscription_id' => $subscription->id
            ]);

            Notification::make()
                ->title('Cancellation Failed')
                ->body('Unable to cancel scheduled downgrade. Please contact support.')
                ->danger()
                ->send();
        }
    }

    public function cancelDowngradeAndUpgrade()
    {
        // First, cancel the scheduled downgrade
        $subscription = Subscription::where('billable_id', auth()->user()->id)
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$subscription || !$subscription->scheduled_plan_id) {
            Notification::make()
                ->title('Error')
                ->body('No scheduled downgrade found.')
                ->danger()
                ->send();
            return;
        }

        try {
            $stripeService = app(\App\Services\StripeService::class);
            $stripe = new StripeClient($stripeService->getSecretKey());

            // Revert Stripe subscription back to current plan
            $stripeSubscription = $stripe->subscriptions->retrieve($subscription->vendor_subscription_id);
            $currentPlan = $subscription->plan;

            $current_price_id = $subscription->cycle == 'month'
                ? $currentPlan->monthly_price_id
                : $currentPlan->yearly_price_id;

            $stripe->subscriptions->update($subscription->vendor_subscription_id, [
                'items' => [
                    [
                        'id' => $stripeSubscription->items->data[0]->id,
                        'price' => $current_price_id,
                    ],
                ],
                'proration_behavior' => 'none',
            ]);

            // Clear scheduled downgrade from database
            $subscription->scheduled_plan_id = null;
            $subscription->scheduled_plan_date = null;
            $subscription->scheduled_plan_limits = null;
            $subscription->save();

            \Log::info('Scheduled downgrade cancelled for upgrade', [
                'subscription_id' => $subscription->id
            ]);

            // Close modal
            $this->showDowngradeConflictModal = false;

            // Now proceed with upgrade to the new plan (use upgradeSubscription, not checkout!)
            $newPlan = Plan::find($this->conflictPlanId);
            if ($newPlan) {
                // Use upgradeSubscription to update existing subscription with proration
                return $this->upgradeSubscription($subscription, $newPlan);
            }

        } catch (\Exception $e) {
            \Log::error('Failed to cancel scheduled downgrade for upgrade', [
                'error' => $e->getMessage(),
                'subscription_id' => $subscription->id
            ]);

            Notification::make()
                ->title('Operation Failed')
                ->body('Unable to process your request. Please try again or contact support.')
                ->danger()
                ->send();
        }
    }

    public function updateCycleBasedOnPlans()
    {
        $plans = Plan::where('active', 1)->get();
        $hasMonthly = false;
        $hasYearly = false;
        foreach ($plans as $plan) {
            if (! empty($plan->monthly_price_id)) {
                $hasMonthly = true;
            }
            if (! empty($plan->yearly_price_id)) {
                $hasYearly = true;
            }
        }
        if ($hasMonthly && $hasYearly) {
            $this->billing_cycle_available = 'both';
        } elseif ($hasMonthly) {
            $this->billing_cycle_available = 'month';
        } elseif ($hasYearly) {
            $this->billing_cycle_available = 'year';
            $this->billing_cycle_selected = 'year';
        }
    }

    #[On('savePaddleSubscription')]
    public function savePaddleSubscription($transactionId)
    {
        $subscription = app(AddSubscriptionIdFromTransaction::class)($transactionId);
        if (! is_null($subscription)) {
            return redirect()->to('/subscription/welcome');
        }

        $this->js('closeLoader()');
        Notification::make()
            ->title('Unable to obtain subscription information from payment provider.')
            ->danger()
            ->send();
    }

    #[On('verifyPaddleTransaction')]
    public function verifyPaddleTransaction($transactionId)
    {

        $transaction = null;

        $response = Http::withToken(config('wave.paddle.api_key'))->get($this->paddle_url.'/transactions/'.$transactionId);

        if ($response->successful()) {
            $resBody = json_decode($response->body());
            if (isset($resBody->data->status) && ($resBody->data->status == 'paid' || $resBody->data->status == 'completed' || $resBody->data->status == 'ready')) {
                $transaction = $resBody->data;
            }
        }

        if ($transaction) {
            // Proceed with processing the transaction

            $user = auth()->user();

            if ($this->billing_cycle_selected == 'month') {
                $plan = Plan::where('monthly_price_id', $transaction->items[0]->price->id)->first();
            } else {
                $plan = Plan::where('yearly_price_id', $transaction->items[0]->price->id)->first();
            }

            if (! isset($plan->id)) {
                $this->js('Paddle.Checkout.close()');
                Notification::make()
                    ->title('Plan Price ID not found. Something went wrong during the checkout process')
                    ->success()
                    ->send();

                return;
            }

            // DO NOT assign roles - roles are managed separately from plans

            Subscription::create([
                'billable_type' => 'user',
                'billable_id' => auth()->user()->id,
                'plan_id' => $plan->id,
                'vendor_slug' => 'paddle',
                'vendor_transaction_id' => $transactionId,
                'vendor_customer_id' => $transaction->customer_id,
                'vendor_subscription_id' => $transaction->subscription_id,
                'cycle' => $this->billing_cycle_selected,
                'status' => 'active',
                'seats' => 1,
            ]);

            $this->js('savePaddleSubscription("'.$transactionId.'")');

        } else {
            $this->js('Paddle.Checkout.close()');
            Notification::make()
                ->title('Error processing the transaction. Please try again.')
                ->danger()
                ->send();
        }

        // if we got here something went wrong and we need to let the user know.

    }

    public function switchPlan(Plan $plan)
    {
        $subscription = auth()->user()->subscription;

        $price_id = ($this->billing_cycle_selected == 'month') ? $plan->monthly_price_id : $plan->yearly_price_id ?? null;

        $response = Http::withToken(config('wave.paddle.api_key'))->patch(
            $this->paddle_url.'/subscriptions/'.$subscription->vendor_subscription_id,
            [
                'items' => [
                    [
                        'price_id' => $price_id,
                        'quantity' => 1,
                    ],
                ],
                'proration_billing_mode' => 'prorated_immediately',
            ]
        );

        if ($response->successful()) {
            $subscription->plan_id = $plan->id;
            $subscription->cycle = $this->billing_cycle_selected;
            $subscription->save();
            $subscription->user->switchPlans($plan);

            return redirect()->to('/settings/subscription')->with(['update' => true]);
        }
    }

    public function render()
    {
        return view('wave::livewire.billing.checkout', [
            'plans' => Plan::where('active', 1)->get(),
        ]);
    }
}
