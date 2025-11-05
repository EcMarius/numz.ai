<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use Wave\Plugins\EvenLeads\Services\PlanLimitService;

class PlanQuotaWidget extends Component
{
    public $limits = [];
    public $planName = null;
    public $isOnTrial = false;
    public $daysRemaining = null;
    public $isHighestPlan = false;
    public $hasActivePlan = false;

    protected $listeners = ['quotaUpdated' => '$refresh'];

    public function mount()
    {
        $this->loadQuotas();
    }

    #[On('quotaUpdated')]
    public function loadQuotas()
    {
        $user = auth()->user();
        $planLimitService = app(PlanLimitService::class);

        try {
            // Cache key for this user's subscription data
            $cacheKey = "user_quota_{$user->id}";

            // Clear cache immediately when quotaUpdated event fires
            \Cache::forget($cacheKey);

            // Cache for 5 minutes to reduce DB queries (but will be cleared on quota updates)
            $cachedData = \Cache::remember($cacheKey, 300, function () use ($user) {
                $data = [
                    'subscription' => null,
                    'userPlan' => null,
                    'planName' => null,
                    'hasActivePlan' => false,
                    'isOnTrial' => false,
                    'daysRemaining' => null,
                    'isHighestPlan' => false,
                ];

                // Check if user has active subscription
                $subscription = \Wave\Subscription::where('billable_id', $user->id)
                    ->where('billable_type', 'user')
                    ->where('status', 'active')
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($subscription) {
                    // Get plan directly by ID to avoid relationship issues
                    $userPlan = \Wave\Plan::find($subscription->plan_id);
                    $data['userPlan'] = $userPlan;
                    $data['planName'] = $userPlan->name ?? 'Unknown';
                    $data['hasActivePlan'] = true;
                    $data['subscription'] = $subscription;

                    // Check if on trial
                    if ($subscription->trial_ends_at && \Carbon\Carbon::parse($subscription->trial_ends_at)->isFuture()) {
                        $data['isOnTrial'] = true;
                        $trialEnds = \Carbon\Carbon::parse($subscription->trial_ends_at);
                        $data['daysRemaining'] = $trialEnds->diffInDays(now());
                    }
                }

                // Fallback: No subscription - check if user is on trial period
                if (!$data['hasActivePlan'] && $user->trial_ends_at && \Carbon\Carbon::parse($user->trial_ends_at)->isFuture()) {
                    $data['isOnTrial'] = true;
                    $data['hasActivePlan'] = true;
                    $trialEnds = \Carbon\Carbon::parse($user->trial_ends_at);
                    $data['daysRemaining'] = $trialEnds->diffInDays(now());

                    // Get the default trial plan or plan assigned to user's role
                    $userRoles = $user->roles()->pluck('id');
                    if ($userRoles->isNotEmpty()) {
                        $userPlan = \Wave\Plan::where('active', 1)
                            ->whereIn('role_id', $userRoles)
                            ->first();
                        if ($userPlan) {
                            $data['userPlan'] = $userPlan;
                            $data['planName'] = $userPlan->name;
                        }
                    }
                }

                // Check if highest plan
                if ($data['userPlan']) {
                    $allPlans = \Wave\Plan::where('active', 1)->orderBy('monthly_price', 'desc')->get();
                    if ($allPlans->isNotEmpty() && $data['userPlan']->id === $allPlans->first()->id) {
                        $data['isHighestPlan'] = true;
                    }
                }

                return $data;
            });

            // Set properties from cache
            $this->planName = $cachedData['planName'];
            $this->hasActivePlan = $cachedData['hasActivePlan'];
            $this->isOnTrial = $cachedData['isOnTrial'];
            $this->daysRemaining = $cachedData['daysRemaining'];
            $this->isHighestPlan = $cachedData['isHighestPlan'];

            // Get limits and usage (also cache this)
            $this->limits = $this->hasActivePlan ? $planLimitService->getRemainingLimits($user) : [];

        } catch (\Exception $e) {
            \Log::error('Plan quota widget error: ' . $e->getMessage());
        }
    }

    public function getPercentage($used, $limit)
    {
        if ($limit === -1 || $limit === null || $limit === 'unlimited') {
            return 0;
        }
        if ($limit == 0) {
            return 100;
        }
        return min(100, ($used / $limit) * 100);
    }

    public function isUnlimited($limit)
    {
        return $limit === -1 || $limit === null || $limit === 'unlimited';
    }

    public function formatNumber($number)
    {
        if ($number > 999) {
            return number_format($number, 0, ',', '.');
        }
        return $number;
    }

    public function render()
    {
        return view('livewire.plan-quota-widget');
    }
}
