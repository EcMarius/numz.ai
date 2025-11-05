<?php
    use function Laravel\Folio\{middleware, name};
    name('organization.show-member');
    middleware('auth');
?>

<x-layouts.app>
    <x-app.container class="space-y-6" x-cloak>
        <div class="w-full">
            <!-- Back Button -->
            <div class="mb-6">
                <a href="{{ route('organization.team') }}" class="inline-flex items-center text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to Team
                </a>
            </div>

            <!-- Member Info -->
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm p-6 mb-6">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-4">
                        <x-avatar src="{{ $member->avatar() }}" alt="{{ $member->name }}" size="lg" />
                        <div>
                            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $member->name }}</h1>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $member->email }}</p>
                            <div class="mt-2">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $member->team_role === 'owner' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' }}">
                                    {{ ucfirst($member->team_role) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    @if($member->team_role !== 'owner')
                        <form action="{{ route('organization.destroy-member', $member) }}" method="POST" onsubmit="return confirm('Are you sure you want to remove this team member? This action cannot be undone.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                Remove Member
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <!-- Campaigns Section -->
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
                <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Campaigns ({{ $campaigns->count() }})</h2>
                </div>

                <div class="p-6">
                    @forelse($campaigns as $campaign)
                        <div class="mb-6 last:mb-0 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700">
                            <!-- Campaign Header -->
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $campaign->name }}</h3>
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">
                                        {{ $campaign->leads->count() }} leads • Created {{ $campaign->created_at->diffForHumans() }}
                                    </p>
                                </div>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $campaign->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200' }}">
                                    {{ ucfirst($campaign->status ?? 'active') }}
                                </span>
                            </div>

                            <!-- Leads Table -->
                            @if($campaign->leads->count() > 0)
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm">
                                        <thead class="bg-zinc-100 dark:bg-zinc-800">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Lead</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Platform</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Date</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                            @foreach($campaign->leads->take(10) as $lead)
                                                <tr class="hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                                                    <td class="px-4 py-3">
                                                        <div class="text-sm font-medium text-zinc-900 dark:text-white truncate max-w-xs">
                                                            {{ $lead->title ?? $lead->post_title ?? 'Untitled' }}
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <span class="text-xs px-2 py-1 rounded bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                            {{ ucfirst($lead->platform ?? 'reddit') }}
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                                                        {{ $lead->created_at->format('M d, Y') }}
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <span class="text-xs px-2 py-1 rounded {{ $lead->is_archived ? 'bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' }}">
                                                            {{ $lead->is_archived ? 'Archived' : 'Active' }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    @if($campaign->leads->count() > 10)
                                        <div class="px-4 py-3 bg-zinc-50 dark:bg-zinc-900/50 text-sm text-zinc-600 dark:text-zinc-400 text-center">
                                            Showing 10 of {{ $campaign->leads->count() }} leads
                                        </div>
                                    @endif
                                </div>
                            @else
                                <div class="py-8 text-center text-zinc-500 dark:text-zinc-400">
                                    <svg class="mx-auto h-10 w-10 text-zinc-400 dark:text-zinc-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                    </svg>
                                    <p class="text-sm">No leads yet for this campaign</p>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="py-12 text-center text-zinc-500 dark:text-zinc-400">
                            <svg class="mx-auto h-12 w-12 text-zinc-400 dark:text-zinc-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <p class="text-sm">This member hasn't created any campaigns yet.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </x-app.container>
</x-layouts.app>
