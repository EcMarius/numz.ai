<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-4">
            <!-- Current Version -->
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        System Version
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Current version: <span class="font-mono font-semibold">{{ $currentVersion }}</span>
                    </p>
                </div>

                <!-- Check for Updates Button -->
                <x-filament::button
                    wire:click="checkForUpdates"
                    size="sm"
                    color="gray"
                    :disabled="$updateInProgress"
                >
                    <x-filament::icon
                        icon="heroicon-o-arrow-path"
                        class="w-4 h-4 mr-2"
                    />
                    Check for Updates
                </x-filament::button>
            </div>

            <!-- Update Available Banner -->
            @if($updateAvailable && !$updateInProgress)
                <x-filament::section
                    :heading="'Update Available: Version ' . ($latestCheck?->latest_version ?? 'Unknown')"
                    :description="$latestCheck?->changelog ? 'View changelog below' : 'A new version is available for installation'"
                    icon="heroicon-o-arrow-up-circle"
                    icon-color="success"
                >
                    <div class="space-y-4">
                        <!-- Changelog Preview -->
                        @if($latestCheck?->changelog)
                            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 max-h-48 overflow-y-auto">
                                <h4 class="font-semibold text-sm text-gray-900 dark:text-white mb-2">
                                    What's New:
                                </h4>
                                <div class="prose prose-sm dark:prose-invert max-w-none">
                                    {!! \Illuminate\Support\Str::markdown($latestCheck->changelog) !!}
                                </div>
                            </div>
                        @endif

                        <!-- Update Actions -->
                        <div class="flex items-center justify-between pt-2">
                            <p class="text-xs text-gray-600 dark:text-gray-400">
                                <x-filament::icon
                                    icon="heroicon-o-shield-check"
                                    class="w-4 h-4 inline"
                                />
                                Automatic backup will be created before updating
                            </p>

                            <x-filament::button
                                wire:click="startUpdate"
                                color="success"
                                size="lg"
                            >
                                <x-filament::icon
                                    icon="heroicon-o-arrow-down-tray"
                                    class="w-5 h-5 mr-2"
                                />
                                Update Now
                            </x-filament::button>
                        </div>
                    </div>
                </x-filament::section>
            @endif

            <!-- Update In Progress -->
            @if($updateInProgress && $currentUpdate)
                <x-filament::section
                    heading="Update In Progress"
                    description="Please do not close this window or refresh the page"
                    icon="heroicon-o-arrow-path"
                    icon-color="warning"
                >
                    <div class="space-y-4">
                        <!-- Progress Bar -->
                        <div>
                            <div class="flex justify-between text-sm mb-2">
                                <span class="font-medium text-gray-700 dark:text-gray-300">
                                    {{ $currentUpdate->status }}
                                </span>
                                <span class="font-semibold text-gray-900 dark:text-white">
                                    {{ $currentUpdate->progress_percentage }}%
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4">
                                <div
                                    class="bg-warning-500 h-4 rounded-full transition-all duration-300 flex items-center justify-end px-2"
                                    style="width: {{ $currentUpdate->progress_percentage }}%"
                                >
                                    @if($currentUpdate->progress_percentage > 15)
                                        <span class="text-xs font-semibold text-white">
                                            {{ $currentUpdate->progress_percentage }}%
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Update Steps -->
                        @if($currentUpdate->update_steps && count($currentUpdate->update_steps) > 0)
                            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 max-h-40 overflow-y-auto">
                                <h4 class="font-semibold text-sm text-gray-900 dark:text-white mb-2">
                                    Progress:
                                </h4>
                                <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                    @foreach($currentUpdate->update_steps as $step)
                                        <li class="flex items-center">
                                            <x-filament::icon
                                                icon="heroicon-o-check-circle"
                                                class="w-4 h-4 mr-2 text-success-500"
                                            />
                                            {{ $step['step'] ?? 'Unknown step' }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <!-- Auto-refresh indicator -->
                        <div class="text-xs text-gray-500 dark:text-gray-500 text-center">
                            <x-filament::icon
                                icon="heroicon-o-arrow-path"
                                class="w-3 h-3 inline animate-spin"
                            />
                            This page will auto-refresh every 5 seconds
                        </div>
                    </div>
                </x-filament::section>

                <!-- Auto-refresh when update is in progress -->
                <script>
                    setTimeout(() => {
                        window.location.reload();
                    }, 5000);
                </script>
            @endif

            <!-- No Updates Available -->
            @if(!$updateAvailable && !$updateInProgress && $latestCheck)
                <div class="bg-success-50 dark:bg-success-900/20 rounded-lg p-4 text-center">
                    <x-filament::icon
                        icon="heroicon-o-check-circle"
                        class="w-12 h-12 mx-auto text-success-500 mb-2"
                    />
                    <p class="text-sm font-medium text-success-900 dark:text-success-100">
                        Your system is up to date!
                    </p>
                    <p class="text-xs text-success-700 dark:text-success-300 mt-1">
                        Last checked: {{ $latestCheck->checked_at->diffForHumans() }}
                    </p>
                </div>
            @endif

            <!-- Recent Updates -->
            @php
                $recentUpdates = \App\Models\SystemUpdate::where('status', 'completed')
                    ->orderBy('completed_at', 'desc')
                    ->limit(3)
                    ->get();
            @endphp

            @if($recentUpdates->count() > 0)
                <div class="border-t dark:border-gray-700 pt-4 mt-4">
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                        Recent Updates
                    </h4>
                    <div class="space-y-2">
                        @foreach($recentUpdates as $update)
                            <div class="flex items-center justify-between text-sm bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                                <div class="flex items-center space-x-3">
                                    <x-filament::icon
                                        icon="heroicon-o-check-circle"
                                        class="w-5 h-5 text-success-500"
                                    />
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-white">
                                            Version {{ $update->version }}
                                        </div>
                                        <div class="text-xs text-gray-600 dark:text-gray-400">
                                            {{ $update->completed_at->format('M d, Y \a\t g:i A') }}
                                        </div>
                                    </div>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-500">
                                    {{ $update->update_type }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </x-filament::section>

    <!-- Notification Scripts -->
    <script>
        window.addEventListener('update-available', event => {
            new FilamentNotification()
                .title('Update Available')
                .success()
                .body('Version ' + event.detail.version + ' is now available!')
                .send();
        });

        window.addEventListener('no-update-available', event => {
            new FilamentNotification()
                .title('Up to Date')
                .success()
                .body('Your system is running the latest version.')
                .send();
        });

        window.addEventListener('update-check-failed', event => {
            new FilamentNotification()
                .title('Check Failed')
                .danger()
                .body('Failed to check for updates: ' + event.detail.error)
                .send();
        });

        window.addEventListener('update-started', event => {
            new FilamentNotification()
                .title('Update Started')
                .warning()
                .body('System update has begun. Please wait...')
                .duration(5000)
                .send();

            // Reload after 2 seconds to show progress
            setTimeout(() => window.location.reload(), 2000);
        });

        window.addEventListener('update-start-failed', event => {
            new FilamentNotification()
                .title('Update Failed')
                .danger()
                .body('Failed to start update: ' + event.detail.error)
                .send();
        });
    </script>
</x-filament-widgets::widget>
