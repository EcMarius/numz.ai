<?php
    use function Laravel\Folio\{name};

    name('documentation');
?>

<x-layouts.marketing>
    <div class="flex flex-col w-full">
        <!-- Hero Section -->
        <section class="relative py-12 bg-gradient-to-b from-blue-50 to-white dark:from-zinc-900 dark:to-zinc-950">
            <div class="container relative z-10 px-8 mx-auto">
                <div class="flex flex-col items-center max-w-4xl mx-auto text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 mb-6 rounded-2xl bg-blue-600 dark:bg-blue-700">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <h1 class="text-4xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 sm:text-5xl md:text-6xl">
                        API Documentation
                    </h1>
                    <p class="mt-4 text-xl text-zinc-600 dark:text-zinc-400 max-w-2xl">
                        Integrate EvenLeads into your workflow with our comprehensive REST API. Manage campaigns, leads, and sync operations programmatically.
                    </p>

                    <div class="flex flex-wrap justify-center gap-4 mt-8">
                        @auth
                            <a href="{{ route('settings.api') }}" class="inline-flex items-center px-6 py-3 text-base font-medium text-white bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-800 rounded-lg transition-colors duration-150 shadow-lg hover:shadow-xl">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                </svg>
                                Get Your API Key
                            </a>
                        @else
                            <a href="{{ route('register') }}" class="inline-flex items-center px-6 py-3 text-base font-medium text-white bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-800 rounded-lg transition-colors duration-150 shadow-lg hover:shadow-xl">
                                Get Started
                                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                </svg>
                            </a>
                        @endauth
                        <a href="/docs.postman" download class="inline-flex items-center px-6 py-3 text-base font-medium text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700 border border-zinc-300 dark:border-zinc-700 rounded-lg transition-colors duration-150 shadow hover:shadow-lg">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                            Postman Collection
                        </a>
                        <a href="/docs.openapi" download class="inline-flex items-center px-6 py-3 text-base font-medium text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700 border border-zinc-300 dark:border-zinc-700 rounded-lg transition-colors duration-150 shadow hover:shadow-lg">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                            OpenAPI Spec
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Quick Stats Section -->
        <section class="py-8 bg-white dark:bg-zinc-950 border-y border-zinc-200 dark:border-zinc-800">
            <div class="container px-8 mx-auto">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 max-w-5xl mx-auto">
                    <div class="flex flex-col items-center text-center">
                        <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">14</div>
                        <div class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Endpoints</div>
                    </div>
                    <div class="flex flex-col items-center text-center">
                        <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">4</div>
                        <div class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Resource Groups</div>
                    </div>
                    <div class="flex flex-col items-center text-center">
                        <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">REST</div>
                        <div class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">API Architecture</div>
                    </div>
                    <div class="flex flex-col items-center text-center">
                        <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">JSON</div>
                        <div class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Response Format</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- API Documentation Embed -->
        <section class="py-12 bg-zinc-50 dark:bg-zinc-900">
            <div class="container px-4 mx-auto">
                <div class="max-w-7xl mx-auto">
                    <div class="bg-white dark:bg-zinc-950 rounded-2xl shadow-xl overflow-hidden border border-zinc-200 dark:border-zinc-800">
                        <iframe
                            src="/docs"
                            class="w-full border-0"
                            style="height: calc(100vh - 100px); min-height: 800px;"
                            title="API Documentation"
                        ></iframe>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="py-16 bg-gradient-to-r from-blue-600 to-blue-700 dark:from-blue-700 dark:to-blue-900">
            <div class="container px-8 mx-auto">
                <div class="max-w-3xl mx-auto text-center">
                    <h2 class="text-3xl font-bold text-white mb-4">
                        Ready to Get Started?
                    </h2>
                    <p class="text-xl text-blue-100 mb-8">
                        @auth
                            Create your first API key and start building with EvenLeads today.
                        @else
                            Sign up for free and get access to our powerful API.
                        @endauth
                    </p>
                    @auth
                        <a href="{{ route('settings.api') }}" class="inline-flex items-center px-8 py-4 text-lg font-medium text-blue-600 bg-white hover:bg-blue-50 rounded-lg transition-colors duration-150 shadow-xl hover:shadow-2xl">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                            </svg>
                            Create API Key
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="inline-flex items-center px-8 py-4 text-lg font-medium text-blue-600 bg-white hover:bg-blue-50 rounded-lg transition-colors duration-150 shadow-xl hover:shadow-2xl">
                            Get Started Free
                            <svg class="w-6 h-6 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                        </a>
                    @endauth
                </div>
            </div>
        </section>
    </div>
</x-layouts.marketing>
