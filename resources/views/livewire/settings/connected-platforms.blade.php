<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">Connected Platforms</h1>
        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
            Manage your connected social media platforms and integrations
        </p>
    </div>

    @if(empty($connections))
        <!-- Empty State -->
        <div class="text-center py-12 bg-white dark:bg-zinc-800 rounded-lg border-2 border-dashed border-zinc-300 dark:border-zinc-600">
            <svg class="mx-auto h-12 w-12 text-zinc-400 dark:text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-zinc-900 dark:text-white">No platforms connected</h3>
            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400 max-w-sm mx-auto">
                Connect your social media accounts to start managing posts and engaging with your audience
            </p>
            <div class="mt-6">
                <a href="/settings" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600 transition-colors">
                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Connect Platform
                </a>
            </div>
        </div>
    @else
        <!-- Connections Grid -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            @foreach($connections as $connection)
                <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden hover:shadow-md transition-shadow">
                    <div class="p-6">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center space-x-4">
                                <!-- Platform Icon -->
                                <div class="flex-shrink-0">
                                    <div @class([
                                        'flex items-center justify-center w-12 h-12 rounded-full',
                                        'bg-orange-100 dark:bg-orange-900/30' => $connection['platform'] === 'reddit',
                                        'bg-blue-100 dark:bg-blue-900/30' => $connection['platform'] === 'facebook',
                                        'bg-zinc-100 dark:bg-zinc-700' => $connection['platform'] === 'x' || $connection['platform'] === 'twitter',
                                        'bg-indigo-100 dark:bg-indigo-900/30' => $connection['platform'] === 'linkedin',
                                    ])>
                                        <svg class="w-6 h-6 @if($connection['platform'] === 'reddit') text-orange-600 dark:text-orange-400 @elseif($connection['platform'] === 'facebook') text-blue-600 dark:text-blue-400 @elseif($connection['platform'] === 'x' || $connection['platform'] === 'twitter') text-zinc-900 dark:text-zinc-100 @elseif($connection['platform'] === 'linkedin') text-indigo-600 dark:text-indigo-400 @else text-zinc-600 dark:text-zinc-400 @endif" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0zm5.01 4.744c.688 0 1.25.561 1.25 1.249a1.25 1.25 0 0 1-2.498.056l-2.597-.547-.8 3.747c1.824.07 3.48.632 4.674 1.488.308-.309.73-.491 1.207-.491.968 0 1.754.786 1.754 1.754 0 .716-.435 1.333-1.01 1.614a3.111 3.111 0 0 1 .042.52c0 2.694-3.13 4.87-7.004 4.87-3.874 0-7.004-2.176-7.004-4.87 0-.183.015-.366.043-.534A1.748 1.748 0 0 1 4.028 12c0-.968.786-1.754 1.754-1.754.463 0 .898.196 1.207.49 1.207-.883 2.878-1.43 4.744-1.487l.885-4.182a.342.342 0 0 1 .14-.197.35.35 0 0 1 .238-.042l2.906.617a1.214 1.214 0 0 1 1.108-.701zM9.25 12C8.561 12 8 12.562 8 13.25c0 .687.561 1.248 1.25 1.248.687 0 1.248-.561 1.248-1.249 0-.688-.561-1.249-1.249-1.249zm5.5 0c-.687 0-1.248.561-1.248 1.25 0 .687.561 1.248 1.249 1.248.688 0 1.249-.561 1.249-1.249 0-.687-.562-1.249-1.25-1.249zm-5.466 3.99a.327.327 0 0 0-.231.094.33.33 0 0 0 0 .463c.842.842 2.484.913 2.961.913.477 0 2.105-.056 2.961-.913a.361.361 0 0 0 .029-.463.33.33 0 0 0-.464 0c-.547.533-1.684.73-2.512.73-.828 0-1.979-.196-2.512-.73a.326.326 0 0 0-.232-.095z"/>
                                        </svg>
                                    </div>
                                </div>

                                <!-- Platform Info -->
                                <div>
                                    <h3 class="text-sm font-medium text-zinc-900 dark:text-white">
                                        {{ $connection['platform_name'] }}
                                    </h3>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                        @{{ $connection['username'] }}
                                    </p>
                                    <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1">
                                        Connected {{ $connection['connected_at'] }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="mt-4 flex items-center justify-end">
                            <button
                                wire:click="confirmDisconnect({{ $connection['id'] }})"
                                class="inline-flex items-center px-3 py-2 border border-red-300 dark:border-red-600 shadow-sm text-sm leading-4 font-medium rounded-md text-red-700 dark:text-red-300 bg-white dark:bg-zinc-800 hover:bg-red-50 dark:hover:bg-red-900/20 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors"
                            >
                                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                </svg>
                                Disconnect
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Connect More Platforms -->
        <div class="mt-6 text-center">
            <a href="/settings" class="inline-flex items-center text-sm text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300">
                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Connect another platform
            </a>
        </div>
    @endif

    <!-- Disconnect Confirmation Modal -->
    @if($confirmingDisconnect)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 bg-zinc-500 dark:bg-zinc-900 bg-opacity-75 dark:bg-opacity-75 transition-opacity" wire:click="cancelDisconnect"></div>

                <!-- Modal panel -->
                <div class="inline-block align-bottom bg-white dark:bg-zinc-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/30 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-zinc-900 dark:text-white" id="modal-title">
                                Disconnect Platform
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                    Are you sure you want to disconnect this platform? You will no longer be able to manage posts or sync data from this account until you reconnect.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                        <button
                            wire:click="disconnect"
                            type="button"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm transition-colors"
                        >
                            Disconnect
                        </button>
                        <button
                            wire:click="cancelDisconnect"
                            type="button"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-zinc-300 dark:border-zinc-600 shadow-sm px-4 py-2 bg-white dark:bg-zinc-700 text-base font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 sm:mt-0 sm:w-auto sm:text-sm transition-colors"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
