@php
    $isInvalidSignature = isset($exception) &&
        (str_contains($exception->getMessage(), 'Invalid signature') ||
         str_contains($exception->getMessage(), 'signature'));
@endphp

<x-auth::layouts.app title="{{ $isInvalidSignature ? 'Verification Link Expired' : 'Access Forbidden' }}">

    <x-auth::elements.container>

        @if($isInvalidSignature)
            <!-- Heading for Expired Verification Link -->
            <div class="text-center mb-6">
                <h1 class="text-3xl font-bold text-zinc-900 dark:text-white mb-2">
                    Verification Link Expired
                </h1>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                    This link is no longer valid
                </p>
            </div>

            <!-- Informative Message -->
            <div class="mb-6 p-5 bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-400 mt-0.5 mr-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <h3 class="text-sm font-semibold text-zinc-900 mb-2">What happened?</h3>
                        <p class="text-sm text-zinc-700 dark:text-zinc-300 mb-4">
                            Email verification links are time-sensitive for your security. This link may have expired or already been used.
                        </p>
                        <h3 class="text-sm font-semibold text-zinc-900 mb-2">What should you do?</h3>
                        <ul class="text-sm text-zinc-700 dark:text-zinc-300 space-y-2">
                            <li class="flex items-start gap-2">
                                <span class="text-zinc-500 dark:text-zinc-400 mt-0.5">•</span>
                                <span>Log in to your account and request a new verification email</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-zinc-500 dark:text-zinc-400 mt-0.5">•</span>
                                <span>Check your inbox for the latest verification email from EvenLeads</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-zinc-500 dark:text-zinc-400 mt-0.5">•</span>
                                <span>Click the verification link as soon as you receive it</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="space-y-3">
                <a href="{{ route('auth.login') }}" class="block w-full px-6 py-3 text-center text-base font-medium text-white bg-zinc-900 rounded-md hover:bg-zinc-800 transition-colors">
                    Go to Login
                </a>

                <div class="text-center">
                    <x-auth::elements.text-link href="/">
                        Back to Home
                    </x-auth::elements.text-link>
                </div>
            </div>
        @else
            <!-- Heading for General 403 -->
            <div class="text-center mb-6">
                <h1 class="text-3xl font-bold text-zinc-900 dark:text-white mb-2">
                    Access Denied
                </h1>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                    You don't have permission to access this resource
                </p>
            </div>

            <!-- Informative Message -->
            <div class="mb-6 p-5 bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-400 mt-0.5 mr-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <p class="text-sm text-zinc-700 dark:text-zinc-300">
                        If you believe this is an error, please contact support or try logging in with an authorized account.
                    </p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="space-y-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="block w-full px-6 py-3 text-center text-base font-medium text-white bg-zinc-900 rounded-md hover:bg-zinc-800 transition-colors">
                        Go to Dashboard
                    </a>
                @else
                    <a href="{{ route('auth.login') }}" class="block w-full px-6 py-3 text-center text-base font-medium text-white bg-zinc-900 rounded-md hover:bg-zinc-800 transition-colors">
                        Go to Login
                    </a>
                @endauth

                <div class="text-center">
                    <x-auth::elements.text-link href="/">
                        Back to Home
                    </x-auth::elements.text-link>
                </div>
            </div>
        @endif

    </x-auth::elements.container>

</x-auth::layouts.app>
