<?php

namespace App\Livewire;

use Livewire\Component;
use Wave\Subscription;
use Carbon\Carbon;

/**
 * PRICE CHANGE BANNER COMPONENT
 *
 * Shows a persistent warning banner when user has pending price change.
 * Banner cannot be permanently dismissed - it reappears until user accepts.
 *
 * Legal requirement: User must be continuously aware of pending price change.
 */
class PriceChangeBanner extends Component
{
    public $subscription;
    public $plan;
    public $currentPrice;
    public $newPrice;
    public $currency;
    public $renewalDate;
    public $daysUntilRenewal;
    public $priceIncrease;
    public $percentIncrease;
    public $showModal = false;
    public $acceptanceConfirmed = false;

    public function mount()
    {
        // Check if user has active subscription with pending price change
        $this->subscription = Subscription::where('billable_id', auth()->id())
            ->where('status', 'active')
            ->where('pending_price_change', true)
            ->with('plan')
            ->first();

        if ($this->subscription) {
            $this->plan = $this->subscription->plan;
            $this->currentPrice = $this->subscription->subscribed_price ?? ($this->subscription->cycle == 'month'
                ? $this->plan->monthly_price
                : $this->plan->yearly_price);
            $this->newPrice = $this->subscription->pending_price;
            $this->currency = $this->subscription->pending_currency;
            $this->renewalDate = Carbon::parse($this->subscription->price_change_effective_date)->format('F j, Y');
            $this->daysUntilRenewal = Carbon::parse($this->subscription->price_change_effective_date)->diffInDays(now());
            $this->priceIncrease = $this->newPrice - $this->currentPrice;
            $this->percentIncrease = round(($this->priceIncrease / $this->currentPrice) * 100, 1);

            // Auto-show modal if URL parameter present
            if (request()->get('show_price_change')) {
                $this->showModal = true;
            }
        }
    }

    public function dismiss()
    {
        // Log dismissal but don't permanently hide
        // Banner will reappear on next page load until user accepts
        $this->subscription->update([
            'price_change_banner_dismissed_at' => now()
        ]);

        $this->subscription = null; // Hide for current page load only
    }

    public function openModal()
    {
        $this->showModal = true;
        $this->acceptanceConfirmed = false;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->acceptanceConfirmed = false;
    }

    public function acceptPriceChange()
    {
        // Validation
        if (!$this->acceptanceConfirmed) {
            session()->flash('error', 'Please check the box to confirm you understand and accept the price change.');
            return;
        }

        try {
            // CRITICAL: Log acceptance timestamp (legal requirement)
            $this->subscription->update([
                'price_change_accepted_at' => now(),
            ]);

            // Log for audit trail
            \Log::info('User accepted price change', [
                'user_id' => auth()->id(),
                'subscription_id' => $this->subscription->id,
                'old_price' => $this->currentPrice,
                'new_price' => $this->newPrice,
                'acceptance_timestamp' => now(),
                'renewal_date' => $this->subscription->price_change_effective_date,
            ]);

            session()->flash('success', 'Thank you! The new price will take effect on ' . $this->renewalDate . '. Your subscription will continue uninterrupted.');

            // Redirect to refresh and hide banner
            return redirect()->to('/dashboard');

        } catch (\Exception $e) {
            \Log::error('Failed to accept price change', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'An error occurred. Please try again or contact support.');
        }
    }

    public function render()
    {
        return view('livewire.price-change-banner');
    }
}
