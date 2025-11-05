<x-filament-panels::page>
    <div class="space-y-6">
        <!-- BYOAPI Requirement Toggle Section -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                        Require Users to Bring Their Own API Keys
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        When enabled, users must configure their own Reddit and X (Twitter) API credentials before creating campaigns or syncing.
                        This prevents platform API rate limit issues by distributing API usage across user-owned applications.
                    </p>
                    @if($byoapiRequired && $stats['users_without_required_keys'] > 0)
                        <div class="p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                            <p class="text-sm text-amber-800 dark:text-amber-200">
                                ⚠️ <strong>{{ $stats['users_without_required_keys'] }} users</strong> have connected platforms but haven't added their API keys yet. They will see prompts to add credentials.
                            </p>
                        </div>
                    @endif
                </div>
                <label class="relative inline-flex items-center cursor-pointer ml-6">
                    <input type="checkbox"
                           wire:click="toggleBYOAPIRequirement"
                           {{ $byoapiRequired ? 'checked' : '' }}
                           class="sr-only peer">
                    <div class="w-14 h-7 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600 dark:peer-checked:bg-blue-500"></div>
                </label>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Reddit API Users -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border-l-4 border-orange-500">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Reddit API Users</p>
                    <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0zm5.01 4.744c.688 0 1.25.561 1.25 1.249a1.25 1.25 0 0 1-2.498.056l-2.597-.547-.8 3.747c1.824.07 3.48.632 4.674 1.488.308-.309.73-.491 1.207-.491.968 0 1.754.786 1.754 1.754 0 .716-.435 1.333-1.01 1.614a3.111 3.111 0 0 1 .042.52c0 2.694-3.13 4.87-7.004 4.87-3.874 0-7.004-2.176-7.004-4.87 0-.183.015-.366.043-.534A1.748 1.748 0 0 1 4.028 12c0-.968.786-1.754 1.754-1.754.463 0 .898.196 1.207.49 1.207-.883 2.878-1.43 4.744-1.487l.885-4.182a.342.342 0 0 1 .14-.197.35.35 0 0 1 .238-.042l2.906.617a1.214 1.214 0 0 1 1.108-.701zM9.25 12C8.561 12 8 12.562 8 13.25c0 .687.561 1.248 1.25 1.248.687 0 1.248-.561 1.248-1.249 0-.688-.561-1.249-1.249-1.249zm5.5 0c-.687 0-1.248.561-1.248 1.25 0 .687.561 1.248 1.249 1.248.688 0 1.249-.561 1.249-1.249 0-.687-.562-1.249-1.25-1.249zm-5.466 3.99a.327.327 0 0 0-.231.094.33.33 0 0 0 0 .463c.842.842 2.484.913 2.961.913.477 0 2.105-.056 2.961-.913a.361.361 0 0 0 .029-.463.33.33 0 0 0-.464 0c-.547.533-1.684.73-2.512.73-.828 0-1.979-.196-2.512-.73a.326.326 0 0 0-.232-.095z"/>
                    </svg>
                </div>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['reddit_api_users'] }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ $stats['reddit_api_percentage'] }}% of all users
                </p>
            </div>

            <!-- X API Users -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border-l-4 border-zinc-900 dark:border-white">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">X (Twitter) API Users</p>
                    <svg class="w-5 h-5 text-zinc-900 dark:text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                    </svg>
                </div>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['x_api_users'] }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ $stats['x_api_percentage'] }}% of all users
                </p>
            </div>

            <!-- Total BYOAPI Users -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border-l-4 border-blue-500">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total BYOAPI Users</p>
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['any_custom_api_users'] }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ $stats['any_custom_api_percentage'] }}% of all users
                </p>
            </div>

            <!-- Users Without Required Keys -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border-l-4 {{ $byoapiRequired && $stats['users_without_required_keys'] > 0 ? 'border-red-500' : 'border-gray-300' }}">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Missing API Keys</p>
                    @if($byoapiRequired && $stats['users_without_required_keys'] > 0)
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    @else
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    @endif
                </div>
                <p class="text-3xl font-bold {{ $byoapiRequired && $stats['users_without_required_keys'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">
                    {{ $stats['users_without_required_keys'] }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ $byoapiRequired ? 'Users affected by enforcement' : 'Enforcement disabled' }}
                </p>
            </div>
        </div>

        <!-- User List -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Users with Connected Platforms
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Showing users who have connected Reddit or X accounts
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                User
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Email
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Reddit API
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                X API
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                        @forelse($this->getAllUsersWithConnections() as $user)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $user->name }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ $user->email }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @if($user->reddit_use_custom_api)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            ✓ Configured
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                            Not set
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @if($user->x_use_custom_api)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            ✓ Configured
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                            Not set
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No users with connected platforms found</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Help Section -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="flex-1">
                    <h3 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-2">
                        About BYOAPI (Bring Your Own API)
                    </h3>
                    <div class="text-sm text-blue-800 dark:text-blue-200 space-y-1">
                        <p><strong>Benefits:</strong> Higher rate limits, isolated quotas, better reliability, full control</p>
                        <p><strong>User Setup:</strong> Users create their own Reddit/X developer apps and configure credentials in Settings → Profile</p>
                        <p><strong>Enforcement:</strong> When enabled, users are prompted to add API keys when connecting platforms, creating campaigns, or syncing</p>
                        <p><strong>Security:</strong> All API credentials are encrypted in the database and never displayed in admin panel</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
