<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Platform Selector -->
        <div class="flex gap-2">
            @foreach(['reddit', 'facebook', 'twitter', 'linkedin'] as $platformName)
                <button wire:click="changePlatform('{{ $platformName }}')"
                        class="px-4 py-2 rounded-lg font-medium text-sm transition
                            @if($platform === $platformName)
                                bg-primary-600 text-white
                            @else
                                bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700
                            @endif">
                    {{ ucfirst($platformName) }}
                </button>
            @endforeach
        </div>

        <!-- Metrics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Requests Per Minute -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Requests/Minute</h3>
                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['per_minute'] ?? 0 }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Last 60 minutes</p>
            </div>

            <!-- Requests Per Hour -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Requests/Hour</h3>
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['per_hour'] ?? 0 }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Last 24 hours</p>
            </div>

            <!-- Requests Per Day -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Requests/Day</h3>
                    <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['per_day'] ?? 0 }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Today</p>
            </div>

            <!-- Requests Per Month -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Requests/Month</h3>
                    <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['per_month'] ?? 0 }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">This month</p>
            </div>
        </div>

        <!-- Rate Limit Status -->
        @if($rateLimitStatus)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Rate Limit Status</h3>
                <div class="space-y-4">
                    <div>
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-gray-600 dark:text-gray-400">
                                {{ $rateLimitStatus['remaining'] }} / {{ $rateLimitStatus['limit'] }} remaining
                            </span>
                            <span class="font-medium
                                @if($rateLimitStatus['percentage_used'] >= 95) text-red-600 dark:text-red-400
                                @elseif($rateLimitStatus['percentage_used'] >= 80) text-yellow-600 dark:text-yellow-400
                                @else text-green-600 dark:text-green-400
                                @endif">
                                {{ number_format($rateLimitStatus['percentage_used'], 1) }}% used
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3 dark:bg-gray-700">
                            <div class="h-3 rounded-full transition-all
                                @if($rateLimitStatus['percentage_used'] >= 95) bg-red-600
                                @elseif($rateLimitStatus['percentage_used'] >= 80) bg-yellow-500
                                @else bg-green-600
                                @endif"
                                 style="width: {{ min(100, $rateLimitStatus['percentage_used']) }}%"></div>
                        </div>
                    </div>
                    @if($rateLimitStatus['resets_in'])
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Resets {{ $rateLimitStatus['resets_in'] }}
                        </p>
                    @endif

                    @if($rateLimitStatus['percentage_used'] >= 95)
                        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-3">
                            <p class="text-sm text-red-800 dark:text-red-400 font-medium">
                                Critical: Rate limit almost exhausted. Syncs may be delayed.
                            </p>
                        </div>
                    @elseif($rateLimitStatus['percentage_used'] >= 80)
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-3">
                            <p class="text-sm text-yellow-800 dark:text-yellow-400 font-medium">
                                Warning: Approaching rate limit threshold.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <!-- Top Endpoints -->
        @if(!empty($stats['top_endpoints']))
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Top Endpoints</h3>
                <div class="space-y-2">
                    @foreach($stats['top_endpoints'] as $endpoint => $count)
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400 font-mono">{{ $endpoint }}</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $count }} requests</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Status Codes -->
        @if(!empty($stats['status_codes']))
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Status Code Distribution (Last 24h)</h3>
                <div class="space-y-2">
                    @foreach($stats['status_codes'] as $code => $count)
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium
                                @if($code >= 200 && $code < 300) text-green-600 dark:text-green-400
                                @elseif($code >= 400 && $code < 500) text-yellow-600 dark:text-yellow-400
                                @else text-red-600 dark:text-red-400
                                @endif">
                                {{ $code }}
                                @if($code == 200) Success
                                @elseif($code == 429) Rate Limited
                                @elseif($code >= 400) Error
                                @endif
                            </span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Performance -->
        @if(isset($stats['avg_response_time']))
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Average Response Time</h3>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['avg_response_time'] }}ms</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Last 24 hours</p>
            </div>
        @endif
    </div>
</x-filament-panels::page>
