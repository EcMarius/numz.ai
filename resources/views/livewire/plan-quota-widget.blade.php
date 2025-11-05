<div @if(!request()->is('plan-selection') && !request()->is('onboarding'))wire:poll.5s="loadQuotas"@endif>
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
            @if(!$this->isUnlimited($limits['campaigns']['limit'] ?? null))
                <div class="flex flex-col space-y-1">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-zinc-600 dark:text-zinc-400">Campaigns</span>
                        <span class="font-medium text-zinc-700 dark:text-zinc-300">
                            {{ $this->formatNumber($limits['campaigns']['used'] ?? 0) }}/{{ $this->formatNumber($limits['campaigns']['limit'] ?? 0) }}
                        </span>
                    </div>
                    <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-1.5">
                        <div class="bg-blue-600 dark:bg-blue-500 h-1.5 rounded-full transition-all duration-300"
                             style="width: {{ $this->getPercentage($limits['campaigns']['used'] ?? 0, $limits['campaigns']['limit'] ?? 0) }}%">
                        </div>
                    </div>
                </div>
            @endif

            {{-- Manual Syncs --}}
            @if(!$this->isUnlimited($limits['manual_syncs']['limit'] ?? null))
                <div class="flex flex-col space-y-1">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-zinc-600 dark:text-zinc-400">Manual Syncs</span>
                        <span class="font-medium text-zinc-700 dark:text-zinc-300">
                            {{ $this->formatNumber($limits['manual_syncs']['used'] ?? 0) }}/{{ $this->formatNumber($limits['manual_syncs']['limit'] ?? 0) }}
                        </span>
                    </div>
                    <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-1.5">
                        <div class="bg-green-600 dark:bg-green-500 h-1.5 rounded-full transition-all duration-300"
                             style="width: {{ $this->getPercentage($limits['manual_syncs']['used'] ?? 0, $limits['manual_syncs']['limit'] ?? 0) }}%">
                        </div>
                    </div>
                </div>
            @endif

            {{-- AI Replies --}}
            @if(!$this->isUnlimited($limits['ai_replies']['limit'] ?? null))
                <div class="flex flex-col space-y-1">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-zinc-600 dark:text-zinc-400">AI Replies</span>
                        <span class="font-medium text-zinc-700 dark:text-zinc-300">
                            {{ $this->formatNumber($limits['ai_replies']['used'] ?? 0) }}/{{ $this->formatNumber($limits['ai_replies']['limit'] ?? 0) }}
                        </span>
                    </div>
                    <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-1.5">
                        <div class="bg-purple-600 dark:bg-purple-500 h-1.5 rounded-full transition-all duration-300"
                             style="width: {{ $this->getPercentage($limits['ai_replies']['used'] ?? 0, $limits['ai_replies']['limit'] ?? 0) }}%">
                        </div>
                    </div>
                </div>
            @endif

            {{-- Leads Storage --}}
            @if(!$this->isUnlimited($limits['leads']['limit'] ?? null))
                <div class="flex flex-col space-y-1">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-zinc-600 dark:text-zinc-400">Leads</span>
                        <span class="font-medium text-zinc-700 dark:text-zinc-300">
                            {{ $this->formatNumber($limits['leads']['used'] ?? 0) }}/{{ $this->formatNumber($limits['leads']['limit'] ?? 0) }}
                        </span>
                    </div>
                    <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-1.5">
                        <div class="bg-orange-600 dark:bg-orange-500 h-1.5 rounded-full transition-all duration-300"
                             style="width: {{ $this->getPercentage($limits['leads']['used'] ?? 0, $limits['leads']['limit'] ?? 0) }}%">
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
</div>
