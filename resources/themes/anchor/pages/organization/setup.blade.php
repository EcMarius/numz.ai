<?php
    use function Laravel\Folio\{middleware, name};
    name('organization.setup');
    middleware('auth');

    // Check if user already has an organization
    $organization = auth()->user()?->organization ?? null;
?>

<x-layouts.app>
    <x-app.container x-data="{
        submitting: false
    }" class="space-y-6" x-cloak>
        <div class="w-full max-w-2xl mx-auto">
            @if($organization)
                <x-app.heading
                    title="Your Organization"
                    description="Organization details and settings."
                />
            @else
                <x-app.heading
                    title="Welcome to Your Business Plan! 🎉"
                    description="Let's set up your organization to get started with team collaboration."
                />
            @endif

            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                    <p class="text-sm text-green-800 dark:text-green-200">{{ session('success') }}</p>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                    <p class="text-sm text-red-800 dark:text-red-200">{{ session('error') }}</p>
                </div>
            @endif

            @if($organization)
                <!-- Organization Details (when organization exists) -->
                <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm p-6">
                    <h2 class="text-xl font-bold text-zinc-900 dark:text-white mb-6">Organization Details</h2>

                    <div class="space-y-5">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                Organization Name
                            </label>
                            <p class="text-base text-zinc-900 dark:text-white font-semibold">{{ $organization->name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                Organization Domain
                            </label>
                            <p class="text-base text-zinc-900 dark:text-white font-mono">{{ $organization->domain }}</p>
                        </div>

                        @if($organization->address)
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                    Address
                                </label>
                                <p class="text-base text-zinc-900 dark:text-white">{{ $organization->address }}</p>
                            </div>
                        @endif
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-700 flex gap-3">
                        <a href="/dashboard" class="inline-flex items-center justify-center px-6 py-3 bg-zinc-900 hover:bg-black dark:bg-white dark:hover:bg-zinc-100 text-white dark:text-zinc-900 font-medium rounded-lg transition-colors duration-150">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                            </svg>
                            Go to Dashboard
                        </a>
                        @if(auth()->user()->isOrganizationOwner())
                            <a href="/team" class="inline-flex items-center justify-center px-6 py-3 bg-white hover:bg-zinc-50 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-900 dark:text-white border border-zinc-200 dark:border-zinc-600 font-medium rounded-lg transition-colors duration-150">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                                Manage Team
                            </a>
                        @endif
                    </div>
                </div>
            @else
                <!-- Info Card (when no organization) -->
                <div class="p-6 bg-zinc-50 dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-700 mb-6">
                    <div class="flex items-start gap-4">
                        <svg class="w-8 h-8 text-zinc-600 dark:text-zinc-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                        <div>
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">One Quick Step</h3>
                            <p class="text-sm text-zinc-700 dark:text-zinc-300">
                                You're subscribed to a team plan! Please complete your organization setup to access your dashboard and start inviting team members.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Organization Setup Form -->
                <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm p-6">
                    <h2 class="text-xl font-bold text-zinc-900 dark:text-white mb-6">Organization Details</h2>

                    <form action="{{ route('organization.store') }}" method="POST" @submit="submitting = true">
                    @csrf

                    @if($errors->any())
                        <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                            <h3 class="text-sm font-semibold text-red-900 dark:text-red-200 mb-2">Please fix the following errors:</h3>
                            <ul class="list-disc list-inside text-sm text-red-800 dark:text-red-200 space-y-1">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="space-y-5">
                        <!-- Organization Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                Organization Name <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                name="name"
                                id="name"
                                required
                                value="{{ old('name') }}"
                                placeholder="e.g., Acme Corporation"
                                class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 focus:ring-2 focus:ring-zinc-500 focus:border-zinc-500 dark:focus:ring-zinc-400 dark:focus:border-zinc-400 transition-colors">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Domain -->
                        <div>
                            <label for="domain" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                Organization Domain <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    type="text"
                                    name="domain"
                                    id="domain"
                                    required
                                    value="{{ old('domain') }}"
                                    placeholder="acme-corp"
                                    class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 focus:ring-2 focus:ring-zinc-500 focus:border-zinc-500 dark:focus:ring-zinc-400 dark:focus:border-zinc-400 transition-colors">
                            </div>
                            <p class="mt-1.5 text-xs text-zinc-500 dark:text-zinc-400">
                                This will be used to identify your organization. Only lowercase letters, numbers, and hyphens.
                            </p>
                            @error('domain')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Address (Optional) -->
                        <div>
                            <label for="address" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                Address (Optional)
                            </label>
                            <textarea
                                name="address"
                                id="address"
                                rows="3"
                                placeholder="Full organization address..."
                                class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 focus:ring-2 focus:ring-zinc-500 focus:border-zinc-500 dark:focus:ring-zinc-400 dark:focus:border-zinc-400 transition-colors">{{ old('address') }}</textarea>
                            @error('address')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-700">
                        <button
                            type="submit"
                            :disabled="submitting"
                            class="w-full inline-flex items-center justify-center px-6 py-3 bg-zinc-900 hover:bg-black dark:bg-white dark:hover:bg-zinc-100 text-white dark:text-zinc-900 disabled:opacity-50 disabled:cursor-not-allowed font-medium rounded-lg transition-colors duration-150">
                            <svg x-show="!submitting" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <svg x-show="submitting" class="animate-spin w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-show="!submitting">Complete Setup & Continue</span>
                            <span x-show="submitting">Setting up...</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Help Text -->
            <div class="text-center">
                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                    Need help? <a href="mailto:support@evenleads.com" class="text-zinc-900 dark:text-white hover:underline font-medium">Contact Support</a>
                </p>
            </div>
            @endif
        </div>
    </x-app.container>
</x-layouts.app>
