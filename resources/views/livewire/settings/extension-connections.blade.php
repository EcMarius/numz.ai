<div>
    @if(count($extensionTokens) > 0)
        <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
            <!-- Header -->
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-3">
                    <!-- Browser Extension Icon -->
                    <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center bg-purple-100 dark:bg-purple-900/30">
                        <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                        </svg>
                    </div>

                    <!-- Extension Name and Count -->
                    <div>
                        <h4 class="text-sm font-semibold text-zinc-900 dark:text-white">
                            Browser Extension
                            <span class="text-xs font-normal text-zinc-500 dark:text-zinc-400">({{ count($extensionTokens) }} {{ count($extensionTokens) === 1 ? 'connection' : 'connections' }})</span>
                        </h4>
                    </div>
                </div>

                <!-- Revoke All Button (only show if multiple tokens) -->
                @if(count($extensionTokens) > 1)
                    <button wire:click="revokeAll"
                            wire:confirm="Are you sure you want to revoke all extension connections? You will need to log in again in the extension."
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Revoke All
                    </button>
                @endif
            </div>

            <!-- Token List -->
            <div class="space-y-2">
                @foreach($extensionTokens as $token)
                    <div class="flex items-center justify-between p-2.5 rounded-lg border bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-600 transition-colors">
                        <div class="flex items-center gap-2 flex-1 min-w-0">
                            <!-- Token Icon -->
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                                <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                </svg>
                            </div>

                            <!-- Token Details -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="text-sm font-medium text-zinc-900 dark:text-white truncate">
                                        {{ ucfirst(str_replace('-', ' ', $token['name'])) }}
                                    </p>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">
                                        Connected
                                    </span>
                                </div>
                                <div class="flex items-center gap-3 mt-0.5">
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                        <span class="font-medium">Created:</span> {{ $token['created_at'] }}
                                    </p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                        <span class="font-medium">Last used:</span> {{ $token['last_used_at'] }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Revoke Button -->
                        <button wire:click="revokeAccess({{ $token['id'] }})"
                                wire:confirm="Are you sure you want to revoke this extension access? You will need to log in again in the extension."
                                class="flex-shrink-0 inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium text-red-700 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Revoke
                        </button>
                    </div>
                @endforeach
            </div>

            <!-- Info Message -->
            <div class="mt-3 p-2.5 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                <p class="text-xs text-blue-700 dark:text-blue-400 flex items-start gap-2">
                    <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Revoking extension access will immediately sign you out of the browser extension. You can reconnect at any time by logging in again through the extension.</span>
                </p>
            </div>
        </div>
    @else
        <!-- Not Connected State -->
        <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex items-center gap-3">
                <!-- Browser Extension Icon -->
                <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center bg-zinc-100 dark:bg-zinc-700">
                    <svg class="w-5 h-5 text-zinc-400 dark:text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                    </svg>
                </div>

                <!-- Not Connected Info -->
                <div class="flex-1">
                    <h4 class="text-sm font-semibold text-zinc-900 dark:text-white">
                        Browser Extension
                    </h4>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                        Not connected. Install the browser extension and log in to start syncing leads.
                    </p>
                </div>

                <!-- Status Badge -->
                <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-medium bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400">
                    Not Connected
                </span>
            </div>
        </div>
    @endif
</div>
