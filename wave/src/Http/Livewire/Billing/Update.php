<?php

namespace Wave\Http\Livewire\Billing;

use Exception;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Livewire\Component;
use Wave\Actions\Billing\Paddle\AddSubscriptionIdFromTransaction;
use Wave\Subscription;

class Update extends Component
{
    public $update_url;

    public $cancel_url;

    public $paddle_url;

    public $cancellation_scheduled = false;

    public $subscription_ends_at;

    public $error_retrieving_data = false;

    public $subscription;

    public $showCancelForm = false;

    public $cancellation_reason = '';

    public $cancellation_details = '';

    public function mount()
    {
        $this->subscription = auth()->user()->subscription;

        if (config('wave.billing_provider') == 'paddle' && auth()->user()->subscriber()) {
            $subscription = $this->subscription;

            if (is_null($this->subscription->vendor_subscription_id)) {
                // If we did not obtain the user subscription id, try to get it again.
                $subscription = app(AddSubscriptionIdFromTransaction::class)($this->subscription->vendor_transaction_id);
                if (is_null($subscription)) {
                    $this->error_retrieving_data = true;

                    return;
                }
            }

            $this->paddle_url = (config('wave.paddle.env') == 'sandbox') ? 'https://sandbox-api.paddle.com' : 'https://api.paddle.com';

            if (isset($subscription->id)) {
                try {
                    $response = Http::withToken(config('wave.paddle.api_key'))->get($this->paddle_url.'/subscriptions/'.$subscription->vendor_subscription_id, []);
                    $paddle_subscription = json_decode($response->body());
                    $paddle_subscription = $paddle_subscription->data;
                } catch (Exception $e) {
                    $this->error_retrieving_data = true;

                    return;
                }

                if (isset($paddle_subscription->scheduled_change->action) && $paddle_subscription->scheduled_change->action == 'cancel') {
                    $this->cancellation_scheduled = true;
                }

                $this->subscription_ends_at = $paddle_subscription->current_billing_period->ends_at;

                $this->cancel_url = $paddle_subscription->management_urls->cancel;
                $this->update_url = $paddle_subscription->management_urls->update_payment_method;
            }
        } elseif (config('wave.billing_provider') == 'stripe') {
            // Correctly fetch Stripe's `ends_at`
            $this->subscription_ends_at = $this->subscription?->ends_at;
        }
    }

    public function cancel()
    {

        $subscription = auth()->user()->latestSubscription();
        $response = Http::withToken(config('wave.paddle.api_key'))->post($this->paddle_url.'/subscriptions/'.$subscription->vendor_subscription_id.'/cancel', [
            'reason' => 'Customer requested cancellation',
        ]);

        if ($response->successful()) {
            $this->cancellation_scheduled = true;

            $responseObject = json_decode($response->body());
            $subscription->ends_at = $responseObject->data->current_billing_period->ends_at;
            $subscription->save();

            $this->js("window.dispatchEvent(new CustomEvent('close-modal', { detail: { id: 'cancel-modal' }}));");
            Notification::make()
                ->title('Cancellation scheduled.')
                ->success()
                ->send();
        }
    }

    public function cancelImmediately()
    {
        $subscription = auth()->user()->subscription;

        $response = Http::withToken(config('wave.paddle.api_key'))->post($this->paddle_url.'/subscriptions/'.$subscription->vendor_subscription_id.'/cancel', [
            'effective_from' => 'immediately',
        ]);

        if ($response->successful()) {
            $subscription->cancel();

            return redirect()->to('/settings/subscription');
        }
    }

    public function openCancelForm()
    {
        $this->showCancelForm = true;
    }

    public function closeCancelForm()
    {
        $this->showCancelForm = false;
        $this->cancellation_reason = '';
        $this->cancellation_details = '';
    }

    public function cancelSubscription()
    {
        // Validate the reason
        $this->validate([
            'cancellation_reason' => 'required|string',
            'cancellation_details' => 'nullable|string|max:1000',
        ]);

        $subscription = auth()->user()->subscription;

        if (!$subscription) {
            Notification::make()
                ->title('No active subscription found.')
                ->danger()
                ->send();
            return;
        }

        if (config('wave.billing_provider') == 'stripe') {
            // Cancel Stripe subscription
            try {
                $stripeService = app(\App\Services\StripeService::class);
                \Stripe\Stripe::setApiKey($stripeService->getSecretKey());

                // CRITICAL SECURITY FIX: Check for pending prorations before allowing cancellation
                // This prevents users from increasing seats and cancelling before being charged
                $pendingProrations = $stripeService->checkPendingProrations(
                    $subscription->vendor_customer_id,
                    $subscription->vendor_subscription_id
                );

                if ($pendingProrations['has_prorations']) {
                    \Log::warning('User attempting to cancel with pending prorations - forcing immediate payment', [
                        'user_id' => auth()->id(),
                        'subscription_id' => $subscription->id,
                        'pending_amount' => $pendingProrations['amount'],
                    ]);

                    // Force immediate invoice and payment of pending charges
                    try {
                        $invoiceResult = $stripeService->createImmediateInvoice(
                            $subscription->vendor_customer_id,
                            $subscription->vendor_subscription_id,
                            'Pending charges due before cancellation'
                        );

                        if (!$invoiceResult['success']) {
                            throw new \Exception('Payment of pending charges failed. Please update your payment method before cancelling.');
                        }

                        \Log::info('Forced payment of pending prorations before cancellation', [
                            'user_id' => auth()->id(),
                            'invoice_id' => $invoiceResult['invoice_id'],
                            'amount_charged' => $invoiceResult['amount'],
                        ]);

                        // Clear pending proration tracking
                        $subscription->pending_proration_amount = null;
                        $subscription->pending_invoice_id = null;
                        $subscription->save();

                    } catch (\Exception $paymentError) {
                        \Log::error('Failed to charge pending prorations before cancellation', [
                            'user_id' => auth()->id(),
                            'error' => $paymentError->getMessage(),
                        ]);

                        Notification::make()
                            ->title('Payment Required Before Cancellation')
                            ->body('You have pending charges of ' . strtoupper($pendingProrations['currency']) . ' ' . number_format($pendingProrations['amount'], 2) . ' that must be paid before cancellation. ' . $paymentError->getMessage())
                            ->danger()
                            ->duration(10000)
                            ->send();

                        return;
                    }
                }

                // Cancel the subscription at the end of the current period
                \Stripe\Subscription::update(
                    $subscription->vendor_subscription_id,
                    [
                        'cancel_at_period_end' => true,
                        'metadata' => [
                            'cancellation_reason' => $this->cancellation_reason,
                            'cancellation_details' => $this->cancellation_details ?? '',
                        ],
                    ]
                );

                // Get the subscription to get the current period end
                $stripeSubscription = \Stripe\Subscription::retrieve($subscription->vendor_subscription_id);

                // Update our database
                $subscription->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'ends_at' => $stripeSubscription->current_period_end
                        ? \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_end)
                        : now()->addDays(30), // Fallback to 30 days from now if period_end is null
                    'cancellation_reason' => $this->cancellation_reason,
                    'cancellation_details' => $this->cancellation_details,
                ]);

                $this->showCancelForm = false;
                $this->cancellation_scheduled = true;
                $this->subscription_ends_at = $subscription->ends_at;

                Notification::make()
                    ->title('Subscription Cancelled')
                    ->body('Your subscription will remain active until ' . \Carbon\Carbon::parse($subscription->ends_at)->format('F jS, Y'))
                    ->success()
                    ->send();

            } catch (\Exception $e) {
                \Log::error('Error cancelling subscription', [
                    'user_id' => auth()->id(),
                    'error' => $e->getMessage(),
                ]);

                Notification::make()
                    ->title('Error cancelling subscription')
                    ->body($e->getMessage())
                    ->danger()
                    ->send();
            }
        } elseif (config('wave.billing_provider') == 'paddle') {
            // For Paddle, use the existing cancel method but add reason and details
            $subscription->update([
                'cancellation_reason' => $this->cancellation_reason,
                'cancellation_details' => $this->cancellation_details,
            ]);

            $this->cancel();
        }
    }

    public function render()
    {
        return view('wave::livewire.billing.update');
    }
}
