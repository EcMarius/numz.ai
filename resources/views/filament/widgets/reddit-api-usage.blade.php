<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold">Reddit API Usage</h3>
                <a href="{{ \App\Filament\Pages\ApiUsageMonitor::getUrl() }}"
                   class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                    View Details →
                </a>
            </div>

            @php $stats = $this->getStats(); @endphp

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Today</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['today'] }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">This Hour</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['this_hour'] }}</p>
                </div>
            </div>

            @if($stats['rate_limit'])
                <div>
                    <div class="flex justify-between text-sm mb-2">
                        <span class="text-gray-600 dark:text-gray-400">Rate Limit</span>
                        <span class="font-medium
                            @if($stats['rate_limit']['percentage_used'] >= 95) text-red-600
                            @elseif($stats['rate_limit']['percentage_used'] >= 80) text-yellow-600
                            @else text-green-600
                            @endif">
                            {{ $stats['rate_limit']['remaining'] }} / {{ $stats['rate_limit']['limit'] }}
                        </span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                        <div class="h-2 rounded-full
                            @if($stats['rate_limit']['percentage_used'] >= 95) bg-red-600
                            @elseif($stats['rate_limit']['percentage_used'] >= 80) bg-yellow-500
                            @else bg-green-600
                            @endif"
                             style="width: {{ min(100, $stats['rate_limit']['percentage_used']) }}%"></div>
                    </div>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
