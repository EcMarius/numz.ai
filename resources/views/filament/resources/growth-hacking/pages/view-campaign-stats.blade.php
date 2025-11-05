<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Emails Sent</div>
                        <div class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ $campaign->emails_sent }}</div>
                    </div>
                    <x-heroicon-o-paper-airplane class="w-12 h-12 text-gray-400" />
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Open Rate</div>
                        <div class="text-3xl font-bold text-blue-600 mt-2">{{ $campaign->open_rate }}%</div>
                    </div>
                    <x-heroicon-o-eye class="w-12 h-12 text-blue-400" />
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Click Rate</div>
                        <div class="text-3xl font-bold text-green-600 mt-2">{{ $campaign->click_rate }}%</div>
                    </div>
                    <x-heroicon-o-cursor-arrow-rays class="w-12 h-12 text-green-400" />
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Conversions</div>
                        <div class="text-3xl font-bold text-purple-600 mt-2">{{ $campaign->logged_in_count }}</div>
                        <div class="text-xs text-gray-500 mt-1">{{ $campaign->conversion_rate }}% rate</div>
                    </div>
                    <x-heroicon-o-user-plus class="w-12 h-12 text-purple-400" />
                </div>
            </div>
        </div>

        <!-- Campaign Details -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Campaign Details</h3>
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Campaign Name</dt>
                    <dd class="text-sm text-gray-900 dark:text-white mt-1">{{ $campaign->name }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                    <dd class="text-sm text-gray-900 dark:text-white mt-1">
                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300">
                            {{ ucfirst($campaign->status) }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Accounts Created</dt>
                    <dd class="text-sm text-gray-900 dark:text-white mt-1">{{ $campaign->accounts_created }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Created At</dt>
                    <dd class="text-sm text-gray-900 dark:text-white mt-1">{{ $campaign->created_at->format('M d, Y H:i') }}</dd>
                </div>
            </dl>
        </div>
    </div>
</x-filament-panels::page>
