<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Search Form --}}
        <x-filament::card>
            <form wire:submit="search">
                <div class="space-y-6">
                    {{-- Search Query --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-2">Search Queries *</label>
                        <textarea wire:model="search_query" rows="4"
                               placeholder="Enter search queries (one per line or comma-separated)&#10;Examples:&#10;need web developer, looking for app developer&#10;or&#10;need web developer&#10;looking for app developer&#10;wordpress help"
                               class="block w-full px-4 py-3 rounded-lg border-2 border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white text-sm transition-colors"></textarea>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">💡 Enter multiple search queries separated by commas or line breaks</p>
                        @error('search_query') <span class="block text-sm text-danger-600 mt-1">{{ $message }}</span> @enderror
                    </div>

                    {{-- Search Type --}}
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                        <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-3">Search Type *</label>
                        <div class="flex gap-4">
                            <label class="flex items-center px-4 py-3 border-2 rounded-lg cursor-pointer transition-colors"
                                   :class="$wire.search_type === 'global' ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/30' : 'border-gray-300 dark:border-gray-600 hover:border-primary-400 dark:hover:border-primary-500'">
                                <input type="radio" wire:model.live="search_type" value="global" class="w-4 h-4 text-primary-600 focus:ring-primary-500 mr-3">
                                <span class="text-sm font-medium" :style="$wire.search_type === 'global' ? 'color: #111827 !important;' : ''">Global Search (All of Reddit)</span>
                            </label>
                            <label class="flex items-center px-4 py-3 border-2 rounded-lg cursor-pointer transition-colors"
                                   :class="$wire.search_type === 'subreddit' ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/30' : 'border-gray-300 dark:border-gray-600 hover:border-primary-400 dark:hover:border-primary-500'">
                                <input type="radio" wire:model.live="search_type" value="subreddit" class="w-4 h-4 text-primary-600 focus:ring-primary-500 mr-3">
                                <span class="text-sm font-medium" :style="$wire.search_type === 'subreddit' ? 'color: #111827 !important;' : ''">Subreddit Search</span>
                            </label>
                        </div>
                    </div>

                    {{-- Subreddit (conditional) --}}
                    @if($search_type === 'subreddit')
                        <div class="bg-blue-50 dark:bg-blue-900/20 border-2 border-blue-200 dark:border-blue-800 rounded-lg p-4">
                            <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-2">Subreddit Names *</label>
                            <textarea wire:model="subreddit" rows="4"
                                   placeholder="Enter subreddit names (one per line or comma-separated)&#10;Examples:&#10;entrepreneurship, hiring, forhire&#10;or&#10;r/entrepreneurship&#10;r/hiring&#10;r/forhire"
                                   class="block w-full px-4 py-3 rounded-lg border-2 border-blue-300 dark:border-blue-700 shadow-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white text-sm transition-colors"></textarea>
                            <p class="mt-2 text-xs text-blue-700 dark:text-blue-300">💡 r/ prefix is optional. "hiring" and "r/hiring" both work</p>
                            @error('subreddit') <span class="block text-sm text-danger-600 mt-1">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    {{-- Sort By --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-2">Sort By *</label>
                        <select wire:model="sort_by"
                                class="block w-full px-4 py-3 rounded-lg border-2 border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white text-sm transition-colors">
                            <option value="relevance">Relevance</option>
                            <option value="hot">Hot</option>
                            <option value="new">New</option>
                            <option value="top">Top</option>
                            <option value="comments">Most Comments</option>
                        </select>
                    </div>

                    {{-- Limit --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-2">Max Results *</label>
                        <input type="number" wire:model="limit" min="1" max="100"
                               class="block w-full px-4 py-3 rounded-lg border-2 border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white text-sm transition-colors">

                        @if($search_type === 'subreddit')
                            <div class="mt-3 flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-gray-200 dark:border-gray-700">
                                <input type="checkbox" wire:model.live="limit_per_subreddit" id="limit_per_sub"
                                       class="mt-0.5 w-4 h-4 text-primary-600 focus:ring-primary-500 rounded">
                                <label for="limit_per_sub" class="flex-1 cursor-pointer">
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">Limit per subreddit</span>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">
                                        When checked: Each subreddit returns up to {{ $limit }} results<br>
                                        When unchecked: Total results across all subreddits limited to {{ $limit }}
                                    </p>
                                </label>
                            </div>
                        @endif

                        @error('limit') <span class="block text-sm text-danger-600 mt-1">{{ $message }}</span> @enderror
                    </div>

                    {{-- AI Testing --}}
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                        <div class="bg-purple-50 dark:bg-purple-900/20 border-2 border-purple-200 dark:border-purple-800 rounded-lg p-4">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" wire:model.live="test_ai_scoring" class="mt-0.5 w-5 h-5 text-purple-600 focus:ring-purple-500 rounded">
                                <div class="flex-1">
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">Test AI Relevance Scoring</span>
                                    <p class="text-xs text-purple-700 dark:text-purple-300 mt-1">🤖 Run AI scoring on results to see how they would be filtered in real campaigns</p>
                                </div>
                            </label>

                            @if($test_ai_scoring)
                                <div class="mt-4 pl-8">
                                    <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-2">Test Against Campaign</label>
                                    <select wire:model="test_campaign_id"
                                            class="block w-full px-4 py-3 rounded-lg border-2 border-purple-300 dark:border-purple-700 shadow-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white text-sm transition-colors">
                                        <option value="">Select a campaign</option>
                                        @foreach($this->getCampaigns() as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                    <p class="text-xs text-purple-600 dark:text-purple-400 mt-2">AI will score posts based on this campaign's "what are you looking for" criteria</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex gap-3">
                    <button type="submit"
                            wire:loading.attr="disabled"
                            wire:target="search"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg wire:loading.remove wire:target="search" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <svg wire:loading wire:target="search" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="search">Search Reddit</span>
                        <span wire:loading wire:target="search">Searching...</span>
                    </button>

                    @if($results !== null)
                        <x-filament::button color="gray" wire:click="clearResults" type="button">
                            Clear Results
                        </x-filament::button>
                    @endif
                </div>
            </form>
        </x-filament::card>

        {{-- Live Progress Tracker --}}
        @if($isLoading && $progressTotal > 0)
            <x-filament::card>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Search in Progress</h3>
                        <div class="flex items-center gap-2">
                            <svg class="animate-spin w-5 h-5 text-primary-600" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-sm font-medium text-primary-600 dark:text-primary-400">{{ $progressCurrent }}/{{ $progressTotal }}</span>
                        </div>
                    </div>

                    {{-- Progress Bar --}}
                    <div class="relative">
                        <div class="w-full h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-primary-500 to-primary-600 transition-all duration-300 ease-out"
                                 style="width: {{ $progressTotal > 0 ? ($progressCurrent / $progressTotal * 100) : 0 }}%"></div>
                        </div>
                    </div>

                    {{-- Status and ETA --}}
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-700 dark:text-gray-300 font-medium">{{ $progressStatus }}</span>
                        @if($progressEta > 0)
                            <span class="text-gray-600 dark:text-gray-400">ETA: ~{{ $progressEta }}s</span>
                        @endif
                    </div>

                    {{-- Live Results Preview --}}
                    @if(count($liveResults) > 0)
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                            <p class="text-sm font-medium text-gray-900 dark:text-white mb-2">
                                Found {{ count($liveResults) }} result(s) so far
                                @if($errorCount > 0)
                                    <span class="text-warning-600 dark:text-warning-400">({{ $errorCount }} error(s))</span>
                                @endif
                            </p>
                            <div class="max-h-48 overflow-y-auto space-y-1">
                                @foreach(array_slice($liveResults, -5) as $preview)
                                    <div class="text-xs text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 rounded px-3 py-2">
                                        <span class="font-medium text-primary-600 dark:text-primary-400">r/{{ $preview['subreddit'] }}</span>
                                        → {{ \Illuminate\Support\Str::limit($preview['title'], 80) }}
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </x-filament::card>
        @endif

        {{-- Error Message --}}
        @if($errorMessage)
            <x-filament::card>
                <div class="flex items-start gap-3 p-4 bg-danger-50 dark:bg-danger-500/10 rounded-lg border border-danger-200 dark:border-danger-500/20">
                    <svg class="w-6 h-6 text-danger-600 dark:text-danger-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <div>
                        <h3 class="font-semibold text-danger-900 dark:text-danger-100">Search Error</h3>
                        <p class="text-sm text-danger-700 dark:text-danger-300 mt-1">{{ $errorMessage }}</p>
                    </div>
                </div>
            </x-filament::card>
        @endif

        {{-- Results --}}
        @if($results !== null)
            <x-filament::card>
                <div class="space-y-4">
                    <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                        <div class="flex items-center justify-between mb-3">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                                Search Results
                            </h2>
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 px-3 py-1 rounded-full">
                                {{ count($results) }} {{ count($results) === 1 ? 'result' : 'results' }}
                            </span>
                        </div>

                        {{-- Timing Stats --}}
                        @if($totalTime)
                            <div class="grid grid-cols-3 gap-3 text-sm">
                                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-3">
                                    <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">Total Time</div>
                                    <div class="font-semibold text-gray-900 dark:text-white">{{ $totalTime }}s</div>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-3">
                                    <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">Avg per Result</div>
                                    <div class="font-semibold text-gray-900 dark:text-white">{{ $avgTimePerResult }}s</div>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-3">
                                    <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">Avg per {{ $searchCount > 1 ? 'Subreddit' : 'Search' }}</div>
                                    <div class="font-semibold text-gray-900 dark:text-white">{{ $avgTimePerSearch }}s ({{ $searchCount }} {{ $searchCount === 1 ? 'search' : 'searches' }})</div>
                                </div>
                            </div>
                        @endif
                    </div>

                    @if(empty($results))
                        <div class="text-center py-12">
                            <svg class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No results found</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Try adjusting your search query or parameters</p>
                            <div class="text-xs text-gray-500 dark:text-gray-500 space-y-1">
                                <p>Suggestions:</p>
                                <ul class="list-disc list-inside">
                                    <li>Try different search terms or keywords</li>
                                    <li>Check if subreddit names are correct (with or without r/ prefix)</li>
                                    <li>Try a broader time range (currently: past week)</li>
                                    <li>Verify Reddit connection is active</li>
                                </ul>
                            </div>
                        </div>
                    @else
                        <div class="space-y-4">
                            @foreach($results as $index => $post)
                                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-5 hover:border-gray-300 dark:hover:border-gray-600 transition-colors">
                                    {{-- Post Header --}}
                                    <div class="flex items-start justify-between gap-4 mb-3">
                                        <div class="flex-1 min-w-0">
                                            <a href="{{ $post['url'] }}" target="_blank" class="block group">
                                                <h3 class="text-base font-semibold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">
                                                    {{ $post['title'] }}
                                                </h3>
                                            </a>
                                            <div class="flex items-center gap-3 mt-2 text-sm text-gray-600 dark:text-gray-400">
                                                <span>u/{{ $post['author'] }}</span>
                                                <span>r/{{ $post['subreddit'] }}</span>
                                                <span>{{ \Carbon\Carbon::createFromTimestamp($post['created_utc'])->diffForHumans() }}</span>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-3 text-sm text-gray-600 dark:text-gray-400">
                                            <span>↑ {{ $post['score'] }}</span>
                                            <span>💬 {{ $post['num_comments'] }}</span>
                                        </div>
                                    </div>

                                    {{-- Post Content --}}
                                    @if(!empty($post['selftext']))
                                        <div class="mt-3 text-sm text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4">
                                            <p class="line-clamp-3">{{ $post['selftext'] }}</p>
                                        </div>
                                    @endif

                                    {{-- AI Scoring Results --}}
                                    @if(isset($post['ai_score']))
                                        <div class="mt-4 border-t border-gray-200 dark:border-gray-700 pt-4">
                                            <div class="flex items-start gap-4">
                                                <div class="flex-shrink-0">
                                                    @if($post['ai_relevant'])
                                                        <div class="flex items-center justify-center w-16 h-16 rounded-lg bg-success-100 dark:bg-success-500/20">
                                                            <span class="text-2xl font-bold text-success-700 dark:text-success-400">
                                                                {{ $post['ai_score'] }}
                                                            </span>
                                                        </div>
                                                    @else
                                                        <div class="flex items-center justify-center w-16 h-16 rounded-lg bg-gray-100 dark:bg-gray-800">
                                                            <span class="text-2xl font-bold text-gray-500 dark:text-gray-400">
                                                                {{ $post['ai_score'] }}
                                                            </span>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="flex-1">
                                                    <div class="flex items-center gap-2 mb-2">
                                                        <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                                            AI Relevance Score
                                                        </span>
                                                        @if($post['ai_relevant'])
                                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-success-100 dark:bg-success-500/20 text-success-700 dark:text-success-400">
                                                                ✓ Relevant
                                                            </span>
                                                        @else
                                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-400">
                                                                ✗ Not Relevant
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <p class="text-sm text-gray-600 dark:text-gray-400 italic">
                                                        "{{ $post['ai_reason'] }}"
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="mt-3 text-xs text-gray-500">
                                        Post ID: {{ $post['id'] }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </x-filament::card>
        @endif
    </div>
</x-filament-panels::page>
