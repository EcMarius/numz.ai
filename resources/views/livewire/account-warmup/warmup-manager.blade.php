<div class="space-y-6">
    <!-- Header -->
    <x-app.heading
        title="Account Warmup"
        description="Build trust with new social media accounts by automating authentic engagement that follows platform rules."
        :border="true"
    />

    <!-- Accounts List -->
    @if(empty($accounts))
        <div class="text-center py-12 bg-gray-50 dark:bg-gray-800 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No accounts available</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Connect social media accounts first to enable warmup.</p>
        </div>
    @else
        <div class="grid gap-4">
            @foreach($accounts as $account)
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-emerald-400 to-blue-500 flex items-center justify-center text-white font-semibold">
                                    {{ strtoupper(substr($account['platform'], 0, 1)) }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium text-gray-900 dark:text-white">{{ $account['name'] }}</span>
                                        @if($account['is_primary'])
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">
                                                Primary
                                            </span>
                                        @endif
                                    </div>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ ucfirst($account['platform']) }}</span>
                                </div>
                            </div>

                            @if(!$account['has_warmup'])
                                <button wire:click="openConfigModal({{ $account['id'] }})"
                                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition">
                                    Start Warmup
                                </button>
                            @endif
                        </div>

                        @if($account['has_warmup'])
                            @php $warmup = $account['warmup']; @endphp

                            <!-- Progress Bar -->
                            <div class="mb-4">
                                <div class="flex justify-between text-sm mb-2">
                                    <span class="text-gray-600 dark:text-gray-400">Day {{ $warmup['current_day'] }} of {{ $warmup['total_days'] }}</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $warmup['progress'] }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                                    <div class="bg-emerald-600 h-2.5 rounded-full transition-all" style="width: {{ $warmup['progress'] }}%"></div>
                                </div>
                            </div>

                            <!-- Status and Phase -->
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Status</div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if($warmup['status'] === 'active') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400
                                        @elseif($warmup['status'] === 'paused') bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400
                                        @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400
                                        @endif">
                                        {{ ucfirst($warmup['status']) }}
                                    </span>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Phase</div>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ ucfirst($warmup['phase']) }}</span>
                                </div>
                            </div>

                            <!-- Stats -->
                            <div class="grid grid-cols-2 gap-4 mb-4 p-3 bg-gray-50 dark:bg-gray-700/30 rounded-lg">
                                <div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Comments</div>
                                    <div class="text-lg font-bold text-gray-900 dark:text-white">{{ $warmup['stats']['total_comments'] ?? 0 }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Posts</div>
                                    <div class="text-lg font-bold text-gray-900 dark:text-white">{{ $warmup['stats']['total_posts'] ?? 0 }}</div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex gap-2">
                                @if($warmup['status'] === 'active')
                                    <button wire:click="pauseWarmup({{ $warmup['id'] }})"
                                            class="flex-1 px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white text-sm font-medium rounded-lg transition">
                                        Pause
                                    </button>
                                @elseif($warmup['status'] === 'paused')
                                    <button wire:click="resumeWarmup({{ $warmup['id'] }})"
                                            class="flex-1 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition">
                                        Resume
                                    </button>
                                @endif
                                <button wire:click="deleteWarmup({{ $warmup['id'] }})"
                                        wire:confirm="Are you sure you want to stop and delete this warmup?"
                                        class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition">
                                    Delete
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Configuration Modal -->
    @if($showConfigModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" wire:click.self="showConfigModal = false">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full mx-4">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Configure Warmup</h3>
                    <button wire:click="showConfigModal = false" class="text-gray-400 hover:text-gray-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form wire:submit.prevent="startWarmup" class="p-6 space-y-4">
                    <!-- Scheduled Days -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Duration (days)</label>
                        <input type="number" wire:model="warmupSettings.scheduled_days" min="7" max="30"
                               class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Recommended: 14 days</p>
                    </div>

                    <!-- Target Subreddits/Groups -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Target Subreddits</label>
                        <textarea wire:model="warmupSettings.targets" rows="3"
                                  placeholder="AskReddit, CasualConversation, news"
                                  class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Comma-separated list of subreddits to engage with</p>
                    </div>

                    <!-- Info Box -->
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                        <div class="flex gap-3">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            <div class="text-sm text-blue-700 dark:text-blue-300">
                                <p class="font-medium mb-1">How warmup works:</p>
                                <ul class="list-disc list-inside space-y-1 text-xs">
                                    <li>Days 1-3: Introduction phase (1-2 comments/day)</li>
                                    <li>Days 4-7: Engagement phase (2-3 comments, occasional posts)</li>
                                    <li>Days 8-14: Reputation phase (3-4 comments, 1 post/day)</li>
                                </ul>
                                @if(auth()->user()->subscription('default')?->plan?->getCustomProperty('evenleads.ai_post_management'))
                                    <p class="mt-2 text-xs">AI will generate natural, authentic content for your posts and comments.</p>
                                @else
                                    <p class="mt-2 text-xs">Using default templates. Upgrade to use AI-generated content.</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-3 pt-4">
                        <button type="button" wire:click="showConfigModal = false"
                                class="flex-1 px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg transition">
                            Cancel
                        </button>
                        <button type="submit"
                                class="flex-1 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition">
                            Start Warmup
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Flash Messages -->
    @if(session()->has('success'))
        <div class="rounded-lg bg-green-50 dark:bg-green-900/20 p-4 border border-green-200 dark:border-green-800">
            <p class="text-sm text-green-800 dark:text-green-400">{{ session('success') }}</p>
        </div>
    @endif

    @if(session()->has('error'))
        <div class="rounded-lg bg-red-50 dark:bg-red-900/20 p-4 border border-red-200 dark:border-red-800">
            <p class="text-sm text-red-800 dark:text-red-400">{{ session('error') }}</p>
        </div>
    @endif
</div>
