<?php
    use function Laravel\Folio\{middleware, name};
    name('organization.add-member');
    middleware('auth');
?>

<x-layouts.app>
    <x-app.container x-data="{ submitting: false }" class="space-y-6" x-cloak>
        <div class="w-full max-w-2xl mx-auto">
            <x-app.heading
                title="Add Team Member"
                description="Invite a new team member to your organization"
            />

            <!-- Available Seats Info -->
            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800 mb-6">
                <div class="flex items-center gap-3">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <div class="text-sm font-medium text-blue-900 dark:text-blue-100">Available Seats: {{ $organization->available_seats }}</div>
                        <div class="text-xs text-blue-700 dark:text-blue-300">{{ $organization->used_seats }} of {{ $organization->total_seats }} seats used</div>
                    </div>
                </div>
            </div>

            <!-- Add Member Form -->
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm p-6">
                <h2 class="text-xl font-bold text-zinc-900 dark:text-white mb-6">Team Member Details</h2>

                <form action="{{ route('organization.store-member') }}" method="POST" @submit="submitting = true">
                    @csrf

                    <div class="space-y-5">
                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                Full Name <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                name="name"
                                id="name"
                                required
                                value="{{ old('name') }}"
                                placeholder="John Doe"
                                class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-600 transition-colors">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                Email Address <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="email"
                                name="email"
                                id="email"
                                required
                                value="{{ old('email') }}"
                                placeholder="john@example.com"
                                class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-600 transition-colors">
                            @error('email')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div>
                            <label for="password" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                Password <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="password"
                                name="password"
                                id="password"
                                required
                                placeholder="Minimum 8 characters"
                                class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-600 transition-colors">
                            @error('password')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Password Confirmation -->
                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                Confirm Password <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="password"
                                name="password_confirmation"
                                id="password_confirmation"
                                required
                                placeholder="Re-enter password"
                                class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-600 transition-colors">
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-700 flex gap-3">
                        <button
                            type="submit"
                            :disabled="submitting"
                            class="flex-1 inline-flex items-center justify-center px-6 py-3 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 disabled:cursor-not-allowed text-white font-medium rounded-lg transition-colors duration-150">
                            <svg x-show="!submitting" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                            </svg>
                            <svg x-show="submitting" x-cloak class="animate-spin w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-show="!submitting">Add Team Member</span>
                            <span x-show="submitting" x-cloak>Adding...</span>
                        </button>
                        <a href="{{ route('organization.team') }}" class="inline-flex items-center justify-center px-6 py-3 bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700 text-zinc-900 dark:text-white border border-zinc-200 dark:border-zinc-700 font-medium rounded-lg transition-colors">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </x-app.container>
</x-layouts.app>
