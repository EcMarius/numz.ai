<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign in to EvenLeads</title>
    @vite(['resources/css/app.css'])
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica', 'Arial', sans-serif;
        }
    </style>
</head>
<body class="bg-white dark:bg-black min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-gray-900 to-gray-700 dark:from-white dark:to-gray-300 rounded-2xl mb-4">
                <span class="text-white dark:text-black font-bold text-2xl">1L</span>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Sign in to EvenLeads</h1>
            <p class="text-gray-600 dark:text-gray-400">Connect your browser extension</p>
        </div>

        <!-- Login Form -->
        <div class="bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg p-6 shadow-lg">
            @if ($errors->any())
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                    <p class="text-sm text-red-800 dark:text-red-200">{{ $errors->first() }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('extension.login') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                        Email Address
                    </label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition"
                        placeholder="your@email.com"
                    />
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                        Password
                    </label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition"
                        placeholder="••••••••"
                    />
                </div>

                <div class="flex items-center justify-between text-sm">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            type="checkbox"
                            name="remember"
                            class="w-4 h-4 rounded border-gray-300 dark:border-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-gray-900 dark:focus:ring-white"
                        />
                        <span class="text-gray-700 dark:text-gray-300">Remember me</span>
                    </label>
                    <a href="{{ route('auth.password.request') }}" class="text-gray-900 dark:text-white hover:underline">
                        Forgot password?
                    </a>
                </div>

                <button
                    type="submit"
                    class="w-full py-3 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg font-medium hover:bg-gray-800 dark:hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition"
                >
                    Sign In
                </button>
            </form>

            <div class="mt-6 text-center text-sm text-gray-600 dark:text-gray-400">
                <p>Don't have an account?
                    <a href="{{ route('auth.register') }}" class="text-gray-900 dark:text-white font-medium hover:underline">
                        Sign up
                    </a>
                </p>
            </div>
        </div>

        <!-- Security Notice -->
        <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-800 rounded-lg">
            <p class="text-xs text-gray-600 dark:text-gray-400 text-center">
                <svg class="inline-block w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                This page is secure and only accessible from the EvenLeads browser extension.
            </p>
        </div>
    </div>

    @vite(['resources/js/app.js'])
</body>
</html>
