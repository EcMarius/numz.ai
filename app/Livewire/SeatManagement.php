<?php

namespace App\Livewire;

use App\Models\SeatChangeHistory;
use App\Services\StripeService;
use Filament\Notifications\Notification;
use Livewire\Component;
use Stripe\StripeClient;
use Wave\Subscription;

class SeatManagement extends Component
{
    public $totalSeats = 1;
    public $currentSeats = 1;
    public $usedSeats = 0;
    public $processing = false;

    // Confirmation modal properties
    public $showConfirmationModal = false;
    public $proratedAmount = 0;
    public $proratedCurrency = 'eur';
    public $daysRemaining = 0;
    public $isIncrease = false;

    public function mount()
    {
        $user = auth()->user();
        if (!$user->isOrganizationOwner()) {
            abort(403, 'Only organization owners can manage seats.');
        }

        // Load current seat count
        $subscription = Subscription::where('billable_id', $user->id)
            ->where('status', 'active')
            ->first();

        if ($subscription) {
            $this->currentSeats = $subscription->seats_purchased;
            $this->totalSeats = $subscription->seats_purchased;
        }

        // Load used seats
        $organization = $user->ownedOrganization;
        if ($organization) {
            $this->usedSeats = $organization->used_seats;
        }

        // Handle return from Stripe Checkout
        $seatUpdateStatus = request()->get('seat_update');
        if ($seatUpdateStatus === 'success' && session()->has('pending_seat_change')) {
            $this->completePendingSeatChange();
        } elseif ($seatUpdateStatus === 'cancelled') {
            // Clear pending seat change
            session()->forget('pending_seat_change');

            Notification::make()
                ->title('Payment Cancelled')
                ->body('Seat update was cancelled. No changes were made.')
                ->warning()
                ->send();
        }
    }

    public function incrementSeats()
    {
        if ($this->totalSeats < 50) {
            $this->totalSeats++;
        }
    }

    public function decrementSeats()
    {
        // Cannot go below used seats
        if ($this->totalSeats > $this->usedSeats && $this->totalSeats > 1) {
            $this->totalSeats--;
        }
    }

    /**
     * Prepare seat update and show confirmation modal for increases
     */
    public function updateSeats()
    {
        $this->processing = true;

        try {
            $user = auth()->user();
            $subscription = Subscription::where('billable_id', $user->id)
                ->where('status', 'active')
                ->first();

            if (!$subscription) {
                Notification::make()
                    ->title('No Active Subscription')
                    ->body('You don\'t have an active subscription.')
                    ->danger()
                    ->send();
                $this->processing = false;
                return;
            }

            // Check if another seat change is in progress
            if ($subscription->seat_change_in_progress) {
                Notification::make()
                    ->title('Seat Change In Progress')
                    ->body('Another seat change is currently being processed. Please wait a moment and try again.')
                    ->warning()
                    ->send();
                $this->processing = false;
                return;
            }

            // Validate: cannot reduce below used seats
            if ($this->totalSeats < $this->usedSeats) {
                Notification::make()
                    ->title('Cannot Reduce Seats')
                    ->body("You cannot reduce seats below {$this->usedSeats} because they are currently in use by team members.")
                    ->warning()
                    ->send();
                $this->processing = false;
                return;
            }

            // Check if seats actually changed
            $currentQuantity = $subscription->seats_purchased;
            if ($this->totalSeats == $currentQuantity) {
                Notification::make()
                    ->title('No Changes')
                    ->body('The number of seats is already set to ' . $currentQuantity . '.')
                    ->info()
                    ->send();
                $this->processing = false;
                return;
            }

            $this->isIncrease = $this->totalSeats > $currentQuantity;

            // For seat INCREASES: validate payment method and calculate proration
            if ($this->isIncrease) {
                $stripeService = app(StripeService::class);

                // Validate payment method first
                $validation = $stripeService->validatePaymentMethod($subscription->vendor_customer_id);
                if (!$validation['valid']) {
                    // Calculate prorated amount first
                    $plan = $subscription->plan;
                    $pricePerSeat = $subscription->billing_period === 'yearly'
                        ? $plan->yearly_price
                        : $plan->monthly_price;

                    $proration = $stripeService->calculateSeatProration(
                        $currentQuantity,
                        $this->totalSeats,
                        $pricePerSeat,
                        $subscription->vendor_subscription_id
                    );

                    // Store pending seat change in session for after payment
                    session([
                        'pending_seat_change' => [
                            'subscription_id' => $subscription->id,
                            'old_seats' => $currentQuantity,
                            'new_seats' => $this->totalSeats,
                            'prorated_amount' => $proration['amount'],
                            'currency' => $proration['currency'],
                        ]
                    ]);

                    // Create Checkout Session to collect payment + charge immediately
                    $checkout = $stripeService->createPaymentSessionForSeats(
                        $subscription->vendor_customer_id,
                        $proration['amount'],
                        $proration['currency'],
                        $this->totalSeats,
                        $currentQuantity,
                        url('/settings/subscription?seat_update=success'),
                        url('/settings/subscription?seat_update=cancelled')
                    );

                    if ($checkout['success']) {
                        $this->processing = false;

                        // Redirect to Stripe Checkout
                        $this->js("window.location.href = '{$checkout['url']}';");

                        return;
                    } else {
                        Notification::make()
                            ->title('Payment Setup Failed')
                            ->body('Unable to initiate payment. Please contact support.')
                            ->danger()
                            ->send();
                        $this->processing = false;
                        return;
                    }
                }

                // Calculate prorated amount
                $plan = $subscription->plan;
                $pricePerSeat = $subscription->billing_period === 'yearly'
                    ? $plan->yearly_price
                    : $plan->monthly_price;

                $proration = $stripeService->calculateSeatProration(
                    $currentQuantity,
                    $this->totalSeats,
                    $pricePerSeat,
                    $subscription->vendor_subscription_id
                );

                $this->proratedAmount = $proration['amount'];
                $this->proratedCurrency = strtoupper($proration['currency']);
                $this->daysRemaining = $proration['days_remaining'];

                // Show confirmation modal with charge details
                $this->showConfirmationModal = true;
                $this->processing = false;
            } else {
                // For seat DECREASES: process immediately (credit will apply to next invoice)
                $this->confirmSeatUpdate();
            }

        } catch (\Exception $e) {
            \Log::error('Failed to prepare seat update', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            Notification::make()
                ->title('Failed to Prepare Update')
                ->body('Unable to prepare seat update. Please try again or contact support.')
                ->danger()
                ->send();

            $this->processing = false;
        }
    }

    /**
     * Confirm and execute seat update
     */
    public function confirmSeatUpdate()
    {
        $this->showConfirmationModal = false;
        $this->processing = true;

        $historyRecord = null;

        try {
            $user = auth()->user();
            $subscription = Subscription::where('billable_id', $user->id)
                ->where('status', 'active')
                ->first();

            if (!$subscription) {
                throw new \Exception('No active subscription found');
            }

            // Set lock to prevent concurrent modifications
            $subscription->seat_change_in_progress = true;
            $subscription->save();

            $oldQuantity = $subscription->seats_purchased;
            $seatChange = $this->totalSeats - $oldQuantity;
            $isIncrease = $seatChange > 0;

            // Create audit trail record
            $historyRecord = SeatChangeHistory::create([
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'old_seats' => $oldQuantity,
                'new_seats' => $this->totalSeats,
                'seats_changed' => $seatChange,
                'proration_amount' => $isIncrease ? $this->proratedAmount : null,
                'currency' => $this->proratedCurrency,
                'status' => 'pending',
                'initiated_by' => 'user',
                'ip_address' => request()->ip(),
            ]);

            $stripeService = app(StripeService::class);
            $stripe = new StripeClient($stripeService->getSecretKey());
            $stripeSubscription = $stripe->subscriptions->retrieve($subscription->vendor_subscription_id);

            if ($isIncrease) {
                // SEAT INCREASE: Create immediate invoice and charge customer NOW
                // This prevents the vulnerability where users could cancel before being charged

                \Log::info('Processing seat increase with immediate payment', [
                    'user_id' => $user->id,
                    'old_seats' => $oldQuantity,
                    'new_seats' => $this->totalSeats,
                    'prorated_amount' => $this->proratedAmount,
                ]);

                // Step 1: Update subscription quantity with proration
                $stripe->subscriptions->update($subscription->vendor_subscription_id, [
                    'items' => [[
                        'id' => $stripeSubscription->items->data[0]->id,
                        'quantity' => $this->totalSeats,
                    ]],
                    'proration_behavior' => 'create_prorations',
                    'billing_cycle_anchor' => 'unchanged',
                ]);

                // Step 2: Create and finalize invoice IMMEDIATELY to capture payment
                $invoiceResult = $stripeService->createImmediateInvoice(
                    $subscription->vendor_customer_id,
                    $subscription->vendor_subscription_id,
                    "Seat increase from {$oldQuantity} to {$this->totalSeats} seats"
                );

                if (!$invoiceResult['success']) {
                    throw new \Exception('Payment failed: ' . ($invoiceResult['message'] ?? 'Unknown error'));
                }

                // Update history record with payment info
                $historyRecord->update([
                    'stripe_invoice_id' => $invoiceResult['invoice_id'],
                    'payment_status' => $invoiceResult['status'],
                    'proration_amount' => $invoiceResult['amount'],
                    'status' => 'completed',
                ]);

                // Step 3: Only NOW update database after payment succeeded
                $subscription->seats_purchased = $this->totalSeats;
                $subscription->pending_proration_amount = null;
                $subscription->pending_invoice_id = null;
                $subscription->last_seat_change_at = now();
                $subscription->save();

                \Log::info('Seat increase completed with immediate payment', [
                    'user_id' => $user->id,
                    'new_seats' => $this->totalSeats,
                    'invoice_id' => $invoiceResult['invoice_id'],
                    'amount_charged' => $invoiceResult['amount'],
                ]);

                Notification::make()
                    ->title('Seats Increased!')
                    ->body("Successfully added " . abs($seatChange) . " seat(s). You were charged {$this->proratedCurrency} " . number_format($invoiceResult['amount'], 2) . " prorated for the current billing period. You now have {$this->totalSeats} total seats.")
                    ->success()
                    ->send();

            } else {
                // SEAT DECREASE: Credit will apply to next invoice (no immediate payment needed)
                // This is safe and standard practice

                \Log::info('Processing seat decrease', [
                    'user_id' => $user->id,
                    'old_seats' => $oldQuantity,
                    'new_seats' => $this->totalSeats,
                ]);

                $stripe->subscriptions->update($subscription->vendor_subscription_id, [
                    'items' => [[
                        'id' => $stripeSubscription->items->data[0]->id,
                        'quantity' => $this->totalSeats,
                    ]],
                    'proration_behavior' => 'create_prorations',
                    'billing_cycle_anchor' => 'unchanged',
                ]);

                // Update database immediately for decreases
                $subscription->seats_purchased = $this->totalSeats;
                $subscription->last_seat_change_at = now();
                $subscription->save();

                $historyRecord->update([
                    'status' => 'completed',
                    'payment_status' => 'credited',
                ]);

                \Log::info('Seat decrease completed', [
                    'user_id' => $user->id,
                    'new_seats' => $this->totalSeats,
                ]);

                Notification::make()
                    ->title('Seats Decreased!')
                    ->body("Successfully removed " . abs($seatChange) . " seat(s). A prorated credit will be applied to your next invoice. You now have {$this->totalSeats} total seats.")
                    ->success()
                    ->send();
            }

            $this->currentSeats = $this->totalSeats;

        } catch (\Exception $e) {
            \Log::error('Failed to update seats', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);

            // Mark history record as failed
            if ($historyRecord) {
                $historyRecord->update([
                    'status' => 'failed',
                    'failure_reason' => $e->getMessage(),
                ]);
            }

            // Try to revert Stripe subscription if database update failed
            if (isset($subscription) && isset($oldQuantity)) {
                try {
                    $stripe = new StripeClient(app(StripeService::class)->getSecretKey());
                    $stripeSubscription = $stripe->subscriptions->retrieve($subscription->vendor_subscription_id);
                    $stripe->subscriptions->update($subscription->vendor_subscription_id, [
                        'items' => [[
                            'id' => $stripeSubscription->items->data[0]->id,
                            'quantity' => $oldQuantity,
                        ]],
                        'proration_behavior' => 'none', // Don't charge for reverting
                    ]);
                    \Log::info('Reverted Stripe subscription to original quantity', [
                        'subscription_id' => $subscription->vendor_subscription_id,
                        'reverted_to' => $oldQuantity
                    ]);
                } catch (\Exception $revertError) {
                    \Log::error('Failed to revert Stripe subscription', [
                        'error' => $revertError->getMessage()
                    ]);
                }
            }

            Notification::make()
                ->title('Failed to Update Seats')
                ->body('Unable to update seats. Your payment method may have declined, or there was a processing error. Please try again or contact support.')
                ->danger()
                ->send();

        } finally {
            // Always release lock
            if (isset($subscription)) {
                $subscription->seat_change_in_progress = false;
                $subscription->save();
            }
            $this->processing = false;
        }
    }

    /**
     * Cancel confirmation modal
     */
    public function cancelConfirmation()
    {
        $this->showConfirmationModal = false;
        $this->totalSeats = $this->currentSeats; // Reset to current value
        $this->processing = false;
    }

    /**
     * Complete pending seat change after successful Stripe Checkout payment
     */
    public function completePendingSeatChange()
    {
        $pendingChange = session('pending_seat_change');

        if (!$pendingChange) {
            return;
        }

        try {
            $user = auth()->user();
            $subscription = Subscription::find($pendingChange['subscription_id']);

            if (!$subscription || $subscription->billable_id !== $user->id) {
                throw new \Exception('Subscription not found or access denied');
            }

            // Set lock to prevent concurrent modifications
            $subscription->seat_change_in_progress = true;
            $subscription->save();

            $oldSeats = $pendingChange['old_seats'];
            $newSeats = $pendingChange['new_seats'];

            // Create audit trail record
            $historyRecord = SeatChangeHistory::create([
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'old_seats' => $oldSeats,
                'new_seats' => $newSeats,
                'seats_changed' => $newSeats - $oldSeats,
                'proration_amount' => $pendingChange['prorated_amount'],
                'currency' => strtoupper($pendingChange['currency']),
                'status' => 'pending',
                'initiated_by' => 'user',
                'ip_address' => request()->ip(),
            ]);

            $stripeService = app(StripeService::class);
            $stripe = new StripeClient($stripeService->getSecretKey());
            $stripeSubscription = $stripe->subscriptions->retrieve($subscription->vendor_subscription_id);

            \Log::info('Completing seat increase after checkout payment', [
                'user_id' => $user->id,
                'old_seats' => $oldSeats,
                'new_seats' => $newSeats,
                'payment_already_captured' => true
            ]);

            // Payment was already captured by Stripe Checkout
            // Just update the subscription quantity for future billing cycles
            $stripe->subscriptions->update($subscription->vendor_subscription_id, [
                'items' => [[
                    'id' => $stripeSubscription->items->data[0]->id,
                    'quantity' => $newSeats,
                ]],
                'proration_behavior' => 'none', // No additional proration since we already charged
                'billing_cycle_anchor' => 'unchanged',
            ]);

            // Update database
            $subscription->seats_purchased = $newSeats;
            $subscription->last_seat_change_at = now();
            $subscription->save();

            $historyRecord->update([
                'status' => 'completed',
                'payment_status' => 'paid',
            ]);

            \Log::info('Seat increase completed after checkout', [
                'user_id' => $user->id,
                'new_seats' => $newSeats,
            ]);

            // Clear session data
            session()->forget('pending_seat_change');

            // Update local state
            $this->currentSeats = $newSeats;
            $this->totalSeats = $newSeats;

            Notification::make()
                ->title('Seats Increased!')
                ->body("Successfully added " . ($newSeats - $oldSeats) . " seat(s). You were charged " . strtoupper($pendingChange['currency']) . " " . number_format($pendingChange['prorated_amount'], 2) . " for the remaining billing period. You now have {$newSeats} total seats.")
                ->success()
                ->send();

        } catch (\Exception $e) {
            \Log::error('Failed to complete pending seat change', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
                'pending_change' => $pendingChange
            ]);

            // Mark history record as failed if it exists
            if (isset($historyRecord)) {
                $historyRecord->update([
                    'status' => 'failed',
                    'failure_reason' => $e->getMessage(),
                ]);
            }

            session()->forget('pending_seat_change');

            Notification::make()
                ->title('Failed to Complete Seat Update')
                ->body('Payment was processed but seat update failed. Please contact support immediately.')
                ->danger()
                ->send();

        } finally {
            // Always release lock
            if (isset($subscription)) {
                $subscription->seat_change_in_progress = false;
                $subscription->save();
            }
        }
    }

    public function render()
    {
        $user = auth()->user();
        $subscription = Subscription::where('billable_id', $user->id)
            ->where('status', 'active')
            ->with('plan')
            ->first();

        $organization = $user->ownedOrganization;

        return view('livewire.seat-management', [
            'subscription' => $subscription,
            'organization' => $organization,
        ]);
    }
}
