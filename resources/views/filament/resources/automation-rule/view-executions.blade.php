<x-filament-panels::page>
    <div class="space-y-4">
        {{-- Rule Summary Card --}}
        <x-filament::card>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total Executions</div>
                    <div class="text-2xl font-bold">{{ number_format($record->execution_count) }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Success Rate</div>
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                        {{ $record->success_rate }}%
                    </div>
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Avg Execution Time</div>
                    <div class="text-2xl font-bold">
                        {{ number_format($record->average_execution_time, 3) }}s
                    </div>
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Last Executed</div>
                    <div class="text-lg font-semibold">
                        {{ $record->last_executed_at?->diffForHumans() ?? 'Never' }}
                    </div>
                </div>
            </div>
        </x-filament::card>

        {{-- Execution Log Table --}}
        <x-filament::card>
            {{ $this->table }}
        </x-filament::card>
    </div>
</x-filament-panels::page>
