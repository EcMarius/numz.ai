<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Current Version & Update Status -->
        <x-filament::section>
            <x-slot name="heading">
                System Version Information
            </x-slot>

            <div class="space-y-4">
                <!-- Current Version -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                        <div class="text-sm text-gray-600 dark:text-gray-400">Current Version</div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ $currentVersion }}
                        </div>
                    </div>

                    @if($latestCheck)
                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Latest Version</div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                                {{ $latestCheck->latest_version }}
                            </div>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Last Checked</div>
                            <div class="text-lg font-semibold text-gray-900 dark:text-white mt-1">
                                {{ $latestCheck->checked_at->diffForHumans() }}
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Update Available Alert -->
                @if($updateAvailable && !$updateInProgress)
                    <div class="bg-success-50 dark:bg-success-900/20 border border-success-200 dark:border-success-800 rounded-lg p-6">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center">
                                    <x-filament::icon
                                        icon="heroicon-o-arrow-up-circle"
                                        class="w-6 h-6 text-success-500 mr-3"
                                    />
                                    <h3 class="text-lg font-semibold text-success-900 dark:text-success-100">
                                        Update Available: Version {{ $latestCheck?->latest_version }}
                                    </h3>
                                </div>

                                @if($latestCheck?->changelog)
                                    <div class="mt-4 bg-white dark:bg-gray-900 rounded-lg p-4 max-h-64 overflow-y-auto">
                                        <h4 class="font-semibold text-sm text-gray-900 dark:text-white mb-3">
                                            What's New:
                                        </h4>
                                        <div class="prose prose-sm dark:prose-invert max-w-none">
                                            {!! \Illuminate\Support\Str::markdown($latestCheck->changelog) !!}
                                        </div>
                                    </div>
                                @endif

                                <div class="mt-4 flex items-center text-sm text-success-700 dark:text-success-300">
                                    <x-filament::icon
                                        icon="heroicon-o-shield-check"
                                        class="w-4 h-4 mr-2"
                                    />
                                    Automatic backup will be created before updating
                                </div>
                            </div>

                            <div class="ml-4">
                                <x-filament::button
                                    wire:click="startUpdate"
                                    color="success"
                                    size="xl"
                                >
                                    <x-filament::icon
                                        icon="heroicon-o-arrow-down-tray"
                                        class="w-5 h-5 mr-2"
                                    />
                                    Update Now
                                </x-filament::button>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Update In Progress Alert -->
                @if($updateInProgress && $currentUpdate)
                    <div class="bg-warning-50 dark:bg-warning-900/20 border border-warning-200 dark:border-warning-800 rounded-lg p-6">
                        <div class="flex items-center mb-4">
                            <x-filament::icon
                                icon="heroicon-o-arrow-path"
                                class="w-6 h-6 text-warning-500 mr-3 animate-spin"
                            />
                            <h3 class="text-lg font-semibold text-warning-900 dark:text-warning-100">
                                Update In Progress
                            </h3>
                        </div>

                        <!-- Progress Bar -->
                        <div class="mb-4">
                            <div class="flex justify-between text-sm mb-2">
                                <span class="font-medium text-gray-700 dark:text-gray-300">
                                    Status: {{ ucfirst($currentUpdate->status) }}
                                </span>
                                <span class="font-semibold text-gray-900 dark:text-white">
                                    {{ $currentUpdate->progress_percentage }}%
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-6">
                                <div
                                    class="bg-warning-500 h-6 rounded-full transition-all duration-300 flex items-center justify-end px-3"
                                    style="width: {{ $currentUpdate->progress_percentage }}%"
                                >
                                    @if($currentUpdate->progress_percentage > 10)
                                        <span class="text-sm font-semibold text-white">
                                            {{ $currentUpdate->progress_percentage }}%
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Update Steps -->
                        @if($currentUpdate->update_steps && count($currentUpdate->update_steps) > 0)
                            <div class="bg-white dark:bg-gray-900 rounded-lg p-4">
                                <h4 class="font-semibold text-sm text-gray-900 dark:text-white mb-3">
                                    Progress Steps:
                                </h4>
                                <ul class="space-y-2">
                                    @foreach($currentUpdate->update_steps as $step)
                                        <li class="flex items-center text-sm text-gray-600 dark:text-gray-400">
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

                        <div class="mt-4 text-sm text-warning-700 dark:text-warning-300 text-center">
                            <x-filament::icon
                                icon="heroicon-o-information-circle"
                                class="w-4 h-4 inline mr-1"
                            />
                            Please do not close this window. Page will auto-refresh every 5 seconds.
                        </div>
                    </div>

                    <!-- Auto-refresh when update is in progress -->
                    <script>
                        setTimeout(() => {
                            window.location.reload();
                        }, 5000);
                    </script>
                @endif

                <!-- Up to Date Status -->
                @if(!$updateAvailable && !$updateInProgress && $latestCheck)
                    <div class="bg-success-50 dark:bg-success-900/20 border border-success-200 dark:border-success-800 rounded-lg p-4 text-center">
                        <x-filament::icon
                            icon="heroicon-o-check-circle"
                            class="w-12 h-12 mx-auto text-success-500 mb-2"
                        />
                        <p class="text-base font-medium text-success-900 dark:text-success-100">
                            Your system is up to date!
                        </p>
                        <p class="text-sm text-success-700 dark:text-success-300 mt-1">
                            Last checked: {{ $latestCheck->checked_at->diffForHumans() }}
                        </p>
                    </div>
                @endif
            </div>
        </x-filament::section>

        <!-- Update History Table -->
        <x-filament::section>
            <x-slot name="heading">
                Update History
            </x-slot>

            <x-slot name="description">
                View all system updates, their status, and manage rollbacks
            </x-slot>

            {{ $this->table }}
        </x-filament::section>

        <!-- Backups Section -->
        <x-filament::section>
            <x-slot name="heading">
                System Backups
            </x-slot>

            <x-slot name="description">
                Manage system backups created before updates
            </x-slot>

            @php
                $backups = $this->getBackups();
            @endphp

            @if(count($backups) > 0)
                <div class="space-y-2">
                    @foreach($backups as $backup)
                        <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3">
                                    <x-filament::icon
                                        icon="heroicon-o-shield-check"
                                        class="w-5 h-5 {{ $backup['is_restorable'] && $backup['files_exist'] ? 'text-success-500' : 'text-gray-400' }}"
                                    />
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-white">
                                            Version {{ $backup['version'] }}
                                        </div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                            Created: {{ $backup['created_at'] }} • Size: {{ $backup['total_size'] }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center space-x-2">
                                @if($backup['is_restorable'] && $backup['files_exist'])
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-success-100 text-success-800 dark:bg-success-900 dark:text-success-200">
                                        Restorable
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200">
                                        Not Restorable
                                    </span>
                                @endif

                                <x-filament::button
                                    wire:click="deleteBackup({{ $backup['id'] }})"
                                    color="danger"
                                    size="sm"
                                    outlined
                                >
                                    Delete
                                </x-filament::button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                    <x-filament::icon
                        icon="heroicon-o-archive-box"
                        class="w-12 h-12 mx-auto mb-4 text-gray-400"
                    />
                    <p>No backups found</p>
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
