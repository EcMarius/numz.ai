@php
    $user = auth()->user();
    $planLimitService = app(\Wave\Plugins\EvenLeads\Services\PlanLimitService::class);

    // Get user's plan - check subscription first, then user's role
    $planName = null;
    $isOnTrial = false;
    $daysRemaining = null;
    $isHighestPlan = false;
    $userPlan = null;
    $hasActivePlan = false;

    try {
        // Check if user has active subscription - query database directly
        $subscription = \Wave\Subscription::where('billable_id', $user->id)
            ->where('billable_type', 'user')
            ->where('status', 'active')
            ->with('plan')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($subscription) {
            $userPlan = $subscription->plan;
            $planName = $userPlan->name ?? 'Unknown';
            $hasActivePlan = true;

            // Check if on trial
            if ($subscription->trial_ends_at && \Carbon\Carbon::parse($subscription->trial_ends_at)->isFuture()) {
                $isOnTrial = true;
                $trialEnds = \Carbon\Carbon::parse($subscription->trial_ends_at);
                $daysRemaining = $trialEnds->diffInDays(now());
            }
        }

        // Fallback: No subscription - check if user is on trial period
        if (!$hasActivePlan && $user->trial_ends_at && \Carbon\Carbon::parse($user->trial_ends_at)->isFuture()) {
            $isOnTrial = true;
            $hasActivePlan = true;
            $trialEnds = \Carbon\Carbon::parse($user->trial_ends_at);
            $daysRemaining = $trialEnds->diffInDays(now());

            // Get the default trial plan or plan assigned to user's role
            $userRoles = $user->roles()->pluck('id');
            if ($userRoles->isNotEmpty()) {
                $userPlan = \Wave\Plan::where('active', 1)
                    ->whereIn('role_id', $userRoles)
                    ->first();
                if ($userPlan) {
                    $planName = $userPlan->name;
                }
            }
        }

        // Check if highest plan
        if ($userPlan) {
            $allPlans = \Wave\Plan::where('active', 1)->orderBy('monthly_price', 'desc')->get();
            if ($allPlans->isNotEmpty() && $userPlan->id === $allPlans->first()->id) {
                $isHighestPlan = true;
            }
        }
    } catch (\Exception $e) {
        // Handle error silently - defaults already set
        \Log::error('Plan card error: ' . $e->getMessage());
    }

    // Get limits and usage only if user has active plan
    $limits = $hasActivePlan ? $planLimitService->getRemainingLimits($user) : [];

    // Helper closures to avoid function redeclaration
    $getPercentage = function($used, $limit) {
        if ($limit === -1 || $limit === null || $limit === 'unlimited') {
            return 0;
        }
        if ($limit == 0) {
            return 100;
        }
        return min(100, ($used / $limit) * 100);
    };

    $isUnlimited = function($limit) {
        return $limit === -1 || $limit === null || $limit === 'unlimited';
    };
@endphp

@if($hasActivePlan && $planName)
<div class="flex flex-col space-y-3 p-3 bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700">
    {{-- Plan Information --}}
    <div class="flex flex-col space-y-1">
        <div class="flex items-center justify-between">
            <span class="text-sm font-semibold text-zinc-900 dark:text-white">
                {{ $planName }}
                @if($isOnTrial)
                    <span class="text-xs text-orange-600 dark:text-orange-400">(Trial)</span>
                @endif
            </span>
        </div>

        @if($isOnTrial && $daysRemaining !== null)
            <div class="text-xs text-zinc-600 dark:text-zinc-400">
                {{ $daysRemaining }} {{ $daysRemaining == 1 ? 'day' : 'days' }} remaining
            </div>
        @endif
    </div>

    {{-- Limits Progress Bars --}}
    <div class="flex flex-col space-y-2.5">
        {{-- Campaigns --}}
        @if(!$isUnlimited($limits['campaigns']['limit']))
            <div class="flex flex-col space-y-1">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-zinc-600 dark:text-zinc-400">Campaigns</span>
                    <span class="font-medium text-zinc-700 dark:text-zinc-300">
                        {{ $limits['campaigns']['used'] }}/{{ $limits['campaigns']['limit'] }}
                    </span>
                </div>
                <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-1.5">
                    <div class="bg-blue-600 dark:bg-blue-500 h-1.5 rounded-full transition-all duration-300"
                         style="width: {{ $getPercentage($limits['campaigns']['used'], $limits['campaigns']['limit']) }}%">
                    </div>
                </div>
            </div>
        @endif

        {{-- Manual Syncs --}}
        @if(!$isUnlimited($limits['manual_syncs']['limit']))
            <div class="flex flex-col space-y-1">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-zinc-600 dark:text-zinc-400">Manual Syncs</span>
                    <span class="font-medium text-zinc-700 dark:text-zinc-300">
                        {{ $limits['manual_syncs']['used'] }}/{{ $limits['manual_syncs']['limit'] }}
                    </span>
                </div>
                <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-1.5">
                    <div class="bg-green-600 dark:bg-green-500 h-1.5 rounded-full transition-all duration-300"
                         style="width: {{ $getPercentage($limits['manual_syncs']['used'], $limits['manual_syncs']['limit']) }}%">
                    </div>
                </div>
            </div>
        @endif

        {{-- AI Replies --}}
        @if(!$isUnlimited($limits['ai_replies']['limit']))
            <div class="flex flex-col space-y-1">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-zinc-600 dark:text-zinc-400">AI Replies</span>
                    <span class="font-medium text-zinc-700 dark:text-zinc-300">
                        {{ $limits['ai_replies']['used'] }}/{{ $limits['ai_replies']['limit'] }}
                    </span>
                </div>
                <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-1.5">
                    <div class="bg-purple-600 dark:bg-purple-500 h-1.5 rounded-full transition-all duration-300"
                         style="width: {{ $getPercentage($limits['ai_replies']['used'], $limits['ai_replies']['limit']) }}%">
                    </div>
                </div>
            </div>
        @endif

        {{-- Leads Storage --}}
        @if(!$isUnlimited($limits['leads']['limit']))
            <div class="flex flex-col space-y-1">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-zinc-600 dark:text-zinc-400">Leads</span>
                    <span class="font-medium text-zinc-700 dark:text-zinc-300">
                        {{ $limits['leads']['used'] }}/{{ $limits['leads']['limit'] }}
                    </span>
                </div>
                <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-1.5">
                    <div class="bg-orange-600 dark:bg-orange-500 h-1.5 rounded-full transition-all duration-300"
                         style="width: {{ $getPercentage($limits['leads']['used'], $limits['leads']['limit']) }}%">
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Upgrade Button --}}
    @if(!$isHighestPlan)
        <a href="/settings/subscription"
           class="flex items-center justify-center w-full px-3 py-2 text-xs font-medium text-white bg-zinc-900 hover:bg-black dark:bg-zinc-600 dark:hover:bg-zinc-500 rounded-lg transition-colors duration-150">
            <x-phosphor-sparkle-duotone class="w-4 h-4 mr-1.5" />
            Upgrade Plan
        </a>
    @endif
</div>
@else
{{-- No Active Plan - Show Upgrade Message --}}
<div class="flex flex-col space-y-3 p-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg border border-orange-200 dark:border-orange-800">
    <div class="flex flex-col space-y-1">
        <span class="text-sm font-semibold text-orange-900 dark:text-orange-200">
            No Active Plan
        </span>
        <p class="text-xs text-orange-700 dark:text-orange-300">
            Subscribe to a plan to unlock all features
        </p>
    </div>

    <a href="/settings/subscription"
       class="flex items-center justify-center w-full px-3 py-2 text-xs font-medium text-white bg-orange-600 hover:bg-orange-700 dark:bg-orange-700 dark:hover:bg-orange-600 rounded-lg transition-colors duration-150">
        <x-phosphor-sparkle-duotone class="w-4 h-4 mr-1.5" />
        View Plans
    </a>
</div>
@endif
