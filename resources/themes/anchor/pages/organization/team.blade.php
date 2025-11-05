<?php
    use function Laravel\Folio\{middleware, name};
    name('organization.team');
    middleware('auth');
?>

<x-layouts.app>
    <x-app.container class="space-y-6" x-cloak>
        <div class="w-full">
            <x-app.heading
                title="Team Management"
                description="Manage your team members and seat allocations"
            />

            <!-- Seats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
                    <div class="text-sm text-zinc-600 dark:text-zinc-400 mb-1">Total Seats</div>
                    <div class="text-3xl font-bold text-zinc-900 dark:text-white">{{ $organization->total_seats }}</div>
                </div>
                <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
                    <div class="text-sm text-zinc-600 dark:text-zinc-400 mb-1">Seats Used</div>
                    <div class="text-3xl font-bold text-zinc-900 dark:text-white">{{ $organization->used_seats }}</div>
                </div>
                <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
                    <div class="text-sm text-zinc-600 dark:text-zinc-400 mb-1">Available Seats</div>
                    <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $organization->available_seats }}</div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-wrap gap-3 mb-6">
                <a href="{{ route('organization.add-member') }}" class="inline-flex items-center px-6 py-3 bg-zinc-900 hover:bg-black dark:bg-white dark:hover:bg-zinc-100 text-white dark:text-zinc-900 font-medium rounded-lg transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Add Team Member
                </a>
                <a href="/settings/subscription" class="inline-flex items-center px-6 py-3 bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700 text-zinc-900 dark:text-white border border-zinc-200 dark:border-zinc-700 font-medium rounded-lg transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Add Seats
                </a>
            </div>

            <!-- Team Members List -->
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
                <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Team Members</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-zinc-50 dark:bg-zinc-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Role</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @forelse($teamMembers as $member)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <x-avatar src="{{ $member->avatar() }}" alt="{{ $member->name }}" size="xs" class="mr-3" />
                                            <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ $member->name }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-600 dark:text-zinc-400">{{ $member->email }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $member->team_role === 'owner' ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' : 'bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200' }}">
                                            {{ ucfirst($member->team_role) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('organization.show-member', $member) }}" class="inline-flex items-center px-3 py-1.5 bg-zinc-900 hover:bg-black dark:bg-white dark:hover:bg-zinc-100 text-white dark:text-zinc-900 text-xs font-medium rounded-lg transition-colors">
                                                View
                                            </a>
                                            @if($member->team_role !== 'owner')
                                                <form action="{{ route('organization.destroy-member', $member) }}" method="POST" onsubmit="return confirm('Are you sure you want to remove this team member?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-medium rounded-lg transition-colors">
                                                        Remove
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center text-zinc-500 dark:text-zinc-400">
                                        <svg class="mx-auto h-12 w-12 text-zinc-400 dark:text-zinc-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                        </svg>
                                        <p class="text-sm">No team members yet. Add your first team member to get started!</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if(session('success'))
                <div class="mt-6 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                    <p class="text-sm text-green-800 dark:text-green-200">{{ session('success') }}</p>
                </div>
            @endif

            @if(session('error'))
                <div class="mt-6 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                    <p class="text-sm text-red-800 dark:text-red-200">{{ session('error') }}</p>
                </div>
            @endif
        </div>
    </x-app.container>
</x-layouts.app>
