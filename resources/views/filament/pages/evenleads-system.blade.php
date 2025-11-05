<x-filament-panels::page>
    <div class="space-y-6">
        <!-- System Status Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Syncing Campaigns -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Syncing Campaigns
                        </h3>
                        <p class="mt-2 text-3xl font-semibold {{ $syncingCampaignsCount > 0 ? 'text-blue-600 dark:text-blue-400' : 'text-gray-900 dark:text-white' }}">
                            {{ $syncingCampaignsCount }}
                        </p>
                    </div>
                    <div class="p-3 bg-blue-100 dark:bg-blue-900/20 rounded-full">
                        <svg class="w-8 h-8 text-blue-600 dark:text-blue-400 {{ $syncingCampaignsCount > 0 ? 'animate-spin' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </div>
                </div>
                @if($syncingCampaignsCount > 0)
                    <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                        Currently running campaign syncs
                    </p>
                @else
                    <p class="mt-3 text-sm text-green-600 dark:text-green-400">
                        ✓ No campaigns syncing
                    </p>
                @endif
            </div>

            <!-- Pending Jobs -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Pending Sync Jobs
                        </h3>
                        <p class="mt-2 text-3xl font-semibold {{ $pendingJobsCount > 0 ? 'text-yellow-600 dark:text-yellow-400' : 'text-gray-900 dark:text-white' }}">
                            {{ $pendingJobsCount }}
                        </p>
                    </div>
                    <div class="p-3 bg-yellow-100 dark:bg-yellow-900/20 rounded-full">
                        <svg class="w-8 h-8 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                @if($pendingJobsCount > 0)
                    <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                        Jobs waiting in queue
                    </p>
                @else
                    <p class="mt-3 text-sm text-green-600 dark:text-green-400">
                        ✓ Queue is clear
                    </p>
                @endif
            </div>

            <!-- Pending Follow-Ups -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Pending Follow-Ups
                        </h3>
                        <p class="mt-2 text-3xl font-semibold {{ $pendingFollowUpsCount > 0 ? 'text-purple-600 dark:text-purple-400' : 'text-gray-900 dark:text-white' }}">
                            {{ $pendingFollowUpsCount }}
                        </p>
                    </div>
                    <div class="p-3 bg-purple-100 dark:bg-purple-900/20 rounded-full">
                        <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                        </svg>
                    </div>
                </div>
                @if($pendingFollowUpsCount > 0)
                    <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                        Scheduled follow-ups waiting
                    </p>
                @else
                    <p class="mt-3 text-sm text-green-600 dark:text-green-400">
                        ✓ No pending follow-ups
                    </p>
                @endif
            </div>
        </div>

        <!-- Follow-Up System Control & Testing -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Follow-Up System Control
                    </h3>
                    <button wire:click="toggleFollowUpSystem" type="button"
                            class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ $followUpSystemEnabled ? 'bg-green-100 text-green-800 hover:bg-green-200 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 hover:bg-red-200 dark:bg-red-900 dark:text-red-200' }}">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            @if($followUpSystemEnabled)
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            @else
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            @endif
                        </svg>
                        System {{ $followUpSystemEnabled ? 'Enabled' : 'Disabled' }}
                    </button>
                </div>
            </div>
            <div class="p-6 space-y-6">
                <!-- Follow-Up Testing Tool -->
                <div>
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">
                        Test Follow-Up Message
                    </h4>
                    <div class="flex gap-2 mb-4">
                        <input type="number" wire:model="selectedLeadId" placeholder="Lead ID (optional)"
                               class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm">
                        <button wire:click="testFollowUpMessage(selectedLeadId)" type="button"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors">
                            Run Test
                        </button>
                    </div>

                    @if($followUpTestOutput)
                        <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                            <pre class="text-xs text-gray-900 dark:text-white font-mono whitespace-pre-wrap">{{ $followUpTestOutput }}</pre>
                        </div>
                    @endif
                </div>

                <!-- Pending Follow-Ups List -->
                @if($pendingFollowUpsCount > 0)
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">
                            Next {{ min($pendingFollowUpsCount, 20) }} Pending Follow-Ups
                        </h4>
                        <div class="bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-100 dark:bg-gray-800">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Lead ID</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Title</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Platform</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Scheduled</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Message</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($this->getPendingFollowUps() as $followUp)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">{{ $followUp['lead_id'] }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-white truncate max-w-xs">{{ $followUp['lead_title'] }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">{{ ucfirst($followUp['platform']) }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">{{ $followUp['scheduled_diff'] }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">{{ $followUp['message_preview'] }}</td>
                                            <td class="px-4 py-2">
                                                <button wire:click="testFollowUpMessage({{ $followUp['lead_id'] }})" type="button"
                                                        class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200">
                                                    Test
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Commands Reference -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Available Commands
                </h3>
            </div>
            <div class="p-6 space-y-4">
                <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h4 class="font-mono text-sm font-semibold text-gray-900 dark:text-white">
                                php artisan evenleads:stop-all-syncs
                            </h4>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Stop all running campaign syncs and remove pending jobs from queue
                            </p>
                        </div>
                        <span class="ml-4 px-2 py-1 text-xs font-medium text-red-700 bg-red-100 dark:text-red-400 dark:bg-red-900/20 rounded">
                            STOP
                        </span>
                    </div>
                    <div class="mt-3">
                        <p class="text-xs text-gray-500 dark:text-gray-500">Options:</p>
                        <ul class="mt-1 text-xs text-gray-600 dark:text-gray-400 space-y-1">
                            <li><code class="px-1 py-0.5 bg-gray-200 dark:bg-gray-800 rounded">--force</code> Skip confirmation prompt</li>
                        </ul>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h4 class="font-mono text-sm font-semibold text-gray-900 dark:text-white">
                                php artisan evenleads:cleanup-sync-logs
                            </h4>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Delete sync debug logs older than 30 days
                            </p>
                        </div>
                        <span class="ml-4 px-2 py-1 text-xs font-medium text-blue-700 bg-blue-100 dark:text-blue-400 dark:bg-blue-900/20 rounded">
                            CLEANUP
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Warning Message -->
        @if($syncingCampaignsCount > 0 || $pendingJobsCount > 0)
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 dark:border-yellow-600 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400 dark:text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700 dark:text-yellow-400">
                            <strong>Warning:</strong> Use the "Stop All Syncs" button above to safely stop all running syncs and clear the queue.
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
