<div class="space-y-6">
    <div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Connected Platforms</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400">Manage your connected social media accounts. You can connect up to {{ $maxAccountsPerPlatform }} accounts per platform.</p>
    </div>

    @if(empty($platforms))
        <div class="text-center py-12 bg-gray-50 dark:bg-gray-800 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No connected accounts</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by connecting your first account.</p>
        </div>
    @else
        <div class="space-y-6">
            @foreach($platforms as $platform)
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <!-- Platform Header -->
                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-600 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-white dark:bg-gray-700 flex items-center justify-center shadow-sm">
                                @if($platform['name'] === 'reddit')
                                    <svg class="w-6 h-6 text-orange-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0zm5.01 4.744c.688 0 1.25.561 1.25 1.249a1.25 1.25 0 0 1-2.498.056l-2.597-.547-.8 3.747c1.824.07 3.48.632 4.674 1.488.308-.309.73-.491 1.207-.491.968 0 1.754.786 1.754 1.754 0 .716-.435 1.333-1.01 1.614a3.111 3.111 0 0 1 .042.52c0 2.694-3.13 4.87-7.004 4.87-3.874 0-7.004-2.176-7.004-4.87 0-.183.015-.366.043-.534A1.748 1.748 0 0 1 4.028 12c0-.968.786-1.754 1.754-1.754.463 0 .898.196 1.207.49 1.207-.883 2.878-1.43 4.744-1.487l.885-4.182a.342.342 0 0 1 .14-.197.35.35 0 0 1 .238-.042l2.906.617a1.214 1.214 0 0 1 1.108-.701zM9.25 12C8.561 12 8 12.562 8 13.25c0 .687.561 1.248 1.25 1.248.687 0 1.248-.561 1.248-1.249 0-.688-.561-1.249-1.249-1.249zm5.5 0c-.687 0-1.248.561-1.248 1.25 0 .687.561 1.248 1.249 1.248.688 0 1.249-.561 1.249-1.249 0-.687-.562-1.249-1.25-1.249zm-5.466 3.99a.327.327 0 0 0-.231.094.33.33 0 0 0 0 .463c.842.842 2.484.913 2.961.913.477 0 2.105-.056 2.961-.913a.361.361 0 0 0 .029-.463.33.33 0 0 0-.464 0c-.547.533-1.684.73-2.512.73-.828 0-1.979-.196-2.512-.73a.326.326 0 0 0-.232-.095z"/></svg>
                                @elseif($platform['name'] === 'facebook')
                                    <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                @elseif($platform['name'] === 'twitter' || $platform['name'] === 'x')
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                                @elseif($platform['name'] === 'linkedin')
                                    <svg class="w-6 h-6 text-blue-700" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                                @endif
                            </div>
                            <div>
                                <h4 class="text-base font-semibold text-gray-900 dark:text-white">{{ $platform['display_name'] }}</h4>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $platform['count'] }} {{ Str::plural('account', $platform['count']) }} connected</p>
                            </div>
                        </div>
                        @if($platform['count'] < $maxAccountsPerPlatform)
                            <button wire:click="connectAnother('{{ $platform['name'] }}')"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Connect Another
                            </button>
                        @else
                            <span class="text-sm text-gray-500 dark:text-gray-400">Limit reached ({{ $maxAccountsPerPlatform }})</span>
                        @endif
                    </div>

                    <!-- Accounts List -->
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($platform['accounts'] as $account)
                            <div class="px-6 py-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                                <div class="flex items-center gap-4 flex-1">
                                    @if($account['avatar'])
                                        <img src="{{ $account['avatar'] }}" alt="{{ $account['name'] }}" class="w-10 h-10 rounded-full">
                                    @else
                                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-emerald-400 to-blue-500 flex items-center justify-center text-white font-semibold">
                                            {{ strtoupper(substr($account['name'], 0, 1)) }}
                                        </div>
                                    @endif
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium text-gray-900 dark:text-white">{{ $account['name'] }}</span>
                                            @if($account['is_primary'])
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">
                                                    Primary
                                                </span>
                                            @endif
                                            @if($account['token_expired'])
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                                    Token Expired
                                                </span>
                                            @endif
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $account['email'] ?? 'No email' }} • Connected {{ $account['connected_at'] }}
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if(!$account['is_primary'])
                                        <button wire:click="setPrimary({{ $account['id'] }})"
                                                class="px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg transition">
                                            Set as Primary
                                        </button>
                                    @endif
                                    <button wire:click="disconnect({{ $account['id'] }})"
                                            wire:confirm="Are you sure you want to disconnect this account?"
                                            class="px-3 py-1.5 text-sm font-medium text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/30 rounded-lg transition">
                                        Disconnect
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
