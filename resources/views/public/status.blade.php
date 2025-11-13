<x-layouts.marketing>
    <x-slot name="seo">
        <title>System Status - {{ setting('site.title', 'Premium Hosting Solutions') }}</title>
        <meta name="description" content="Check the current status of our services, view historical uptime data, and subscribe to status updates.">
        <meta name="keywords" content="status, uptime, system status, service status">
        <meta property="og:title" content="System Status - {{ setting('site.title') }}">
        <meta property="og:description" content="Real-time system status and uptime information">
        <meta property="og:type" content="website">
    </x-slot>

    <!-- Hero Section -->
    <section class="py-16 bg-gradient-to-br from-blue-50 via-white to-purple-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
        <x-container>
            <div class="max-w-3xl mx-auto text-center">
                <h1 class="text-4xl font-bold tracking-tight text-gray-900 dark:text-white md:text-5xl">
                    System Status
                </h1>
                <p class="mt-4 text-lg text-gray-600 dark:text-gray-300">
                    Real-time information about our services and infrastructure
                </p>
            </div>
        </x-container>
    </section>

    <!-- Current Status -->
    <section class="py-12 bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <x-container>
            <div class="p-8 border border-green-200 bg-green-50 dark:bg-green-900/20 dark:border-green-800 rounded-2xl">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center justify-center w-12 h-12 bg-green-600 rounded-full">
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">All Systems Operational</h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Last updated: {{ now()->format('F j, Y \a\t g:i A') }} UTC</p>
                        </div>
                    </div>
                    <button class="px-6 py-3 text-sm font-medium text-white transition bg-blue-600 rounded-lg hover:bg-blue-700">
                        Subscribe to Updates
                    </button>
                </div>
            </div>
        </x-container>
    </section>

    <!-- Service Status -->
    <section class="py-16 bg-white dark:bg-gray-800">
        <x-container>
            <h2 class="mb-8 text-2xl font-bold text-gray-900 dark:text-white">Service Status</h2>

            <div class="space-y-4">
                <!-- Web Hosting -->
                <div class="flex items-center justify-between p-6 bg-gray-50 dark:bg-gray-900 rounded-xl">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center justify-center w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">Web Hosting</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Shared and VPS hosting infrastructure</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></span>
                        <span class="text-sm font-medium text-green-600 dark:text-green-400">Operational</span>
                    </div>
                </div>

                <!-- API -->
                <div class="flex items-center justify-between p-6 bg-gray-50 dark:bg-gray-900 rounded-xl">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center justify-center w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">API</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">RESTful API and webhooks</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></span>
                        <span class="text-sm font-medium text-green-600 dark:text-green-400">Operational</span>
                    </div>
                </div>

                <!-- Control Panel -->
                <div class="flex items-center justify-between p-6 bg-gray-50 dark:bg-gray-900 rounded-xl">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center justify-center w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">Control Panel</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Customer dashboard and management tools</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></span>
                        <span class="text-sm font-medium text-green-600 dark:text-green-400">Operational</span>
                    </div>
                </div>

                <!-- Email Service -->
                <div class="flex items-center justify-between p-6 bg-gray-50 dark:bg-gray-900 rounded-xl">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center justify-center w-10 h-10 bg-orange-100 dark:bg-orange-900/30 rounded-lg">
                            <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">Email Service</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Email hosting and delivery</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></span>
                        <span class="text-sm font-medium text-green-600 dark:text-green-400">Operational</span>
                    </div>
                </div>

                <!-- DNS Service -->
                <div class="flex items-center justify-between p-6 bg-gray-50 dark:bg-gray-900 rounded-xl">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center justify-center w-10 h-10 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">DNS Service</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Domain name resolution</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></span>
                        <span class="text-sm font-medium text-green-600 dark:text-green-400">Operational</span>
                    </div>
                </div>

                <!-- CDN -->
                <div class="flex items-center justify-between p-6 bg-gray-50 dark:bg-gray-900 rounded-xl">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center justify-center w-10 h-10 bg-pink-100 dark:bg-pink-900/30 rounded-lg">
                            <svg class="w-5 h-5 text-pink-600 dark:text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">CDN</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Content delivery network</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></span>
                        <span class="text-sm font-medium text-green-600 dark:text-green-400">Operational</span>
                    </div>
                </div>
            </div>
        </x-container>
    </section>

    <!-- Uptime Statistics -->
    <section class="py-16 bg-gray-50 dark:bg-gray-900">
        <x-container>
            <h2 class="mb-8 text-2xl font-bold text-gray-900 dark:text-white">Uptime Statistics</h2>

            <div class="grid gap-8 md:grid-cols-3">
                <div class="p-8 text-center bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 rounded-2xl">
                    <div class="text-5xl font-bold text-green-600 dark:text-green-400">99.98%</div>
                    <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">Last 30 Days</div>
                    <div class="h-2 mt-4 bg-gray-200 rounded-full dark:bg-gray-700">
                        <div class="h-2 bg-gradient-to-r from-green-400 to-green-600 rounded-full" style="width: 99.98%"></div>
                    </div>
                </div>

                <div class="p-8 text-center bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 rounded-2xl">
                    <div class="text-5xl font-bold text-blue-600 dark:text-blue-400">99.97%</div>
                    <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">Last 90 Days</div>
                    <div class="h-2 mt-4 bg-gray-200 rounded-full dark:bg-gray-700">
                        <div class="h-2 bg-gradient-to-r from-blue-400 to-blue-600 rounded-full" style="width: 99.97%"></div>
                    </div>
                </div>

                <div class="p-8 text-center bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 rounded-2xl">
                    <div class="text-5xl font-bold text-purple-600 dark:text-purple-400">99.96%</div>
                    <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">Last 12 Months</div>
                    <div class="h-2 mt-4 bg-gray-200 rounded-full dark:bg-gray-700">
                        <div class="h-2 bg-gradient-to-r from-purple-400 to-purple-600 rounded-full" style="width: 99.96%"></div>
                    </div>
                </div>
            </div>

            <!-- Uptime History -->
            <div class="p-8 mt-8 bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 rounded-2xl">
                <h3 class="mb-6 text-lg font-semibold text-gray-900 dark:text-white">90-Day Uptime History</h3>
                <div class="grid grid-cols-90 gap-1">
                    @for($i = 0; $i < 90; $i++)
                        <div class="h-8 bg-green-500 rounded" title="100% uptime"></div>
                    @endfor
                </div>
                <div class="flex items-center justify-between mt-4 text-sm text-gray-600 dark:text-gray-400">
                    <span>{{ now()->subDays(90)->format('M j') }}</span>
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-green-500 rounded"></div>
                            <span>Operational</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-yellow-500 rounded"></div>
                            <span>Degraded</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-red-500 rounded"></div>
                            <span>Outage</span>
                        </div>
                    </div>
                    <span>Today</span>
                </div>
            </div>
        </x-container>
    </section>

    <!-- Incident History -->
    <section class="py-16 bg-white dark:bg-gray-800">
        <x-container>
            <h2 class="mb-8 text-2xl font-bold text-gray-900 dark:text-white">Recent Incidents</h2>

            <div class="space-y-4">
                <div class="p-6 border border-gray-200 dark:border-gray-700 rounded-xl">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start gap-4">
                            <div class="flex items-center justify-center flex-shrink-0 w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white">Scheduled Maintenance Complete</h3>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    Successfully completed scheduled maintenance on our database infrastructure. All services are now fully operational with improved performance.
                                </p>
                                <div class="flex items-center gap-4 mt-3 text-xs text-gray-500 dark:text-gray-500">
                                    <span>November 10, 2025</span>
                                    <span>•</span>
                                    <span class="px-2 py-1 text-green-700 bg-green-100 rounded dark:bg-green-900/30 dark:text-green-400">Resolved</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-6 bg-green-50 border border-green-200 dark:bg-green-900/10 dark:border-green-800 rounded-xl">
                    <div class="flex items-center gap-2 mb-4">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-sm font-medium text-green-800 dark:text-green-300">No incidents in the last 30 days</span>
                    </div>
                    <p class="text-sm text-green-700 dark:text-green-400">
                        We're proud to maintain excellent uptime and service reliability. Our infrastructure is continuously monitored to ensure the best experience for our customers.
                    </p>
                </div>
            </div>
        </x-container>
    </section>

    <!-- Scheduled Maintenance -->
    <section class="py-16 bg-gray-50 dark:bg-gray-900">
        <x-container>
            <h2 class="mb-8 text-2xl font-bold text-gray-900 dark:text-white">Scheduled Maintenance</h2>

            <div class="p-8 bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 rounded-2xl">
                <div class="flex items-center gap-3 mb-4">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">No Scheduled Maintenance</h3>
                </div>
                <p class="text-gray-600 dark:text-gray-400">
                    There is currently no scheduled maintenance planned. We'll notify you at least 48 hours in advance of any planned maintenance windows.
                </p>
            </div>
        </x-container>
    </section>

    <!-- Subscribe -->
    <section class="py-16 bg-gradient-to-br from-blue-600 to-purple-600">
        <x-container>
            <div class="max-w-3xl mx-auto text-center">
                <h2 class="text-3xl font-bold text-white md:text-4xl">
                    Get Status Updates
                </h2>
                <p class="mt-4 text-lg text-blue-100">
                    Subscribe to receive email notifications about scheduled maintenance and service incidents.
                </p>

                <form class="flex flex-col max-w-md gap-4 mx-auto mt-8 sm:flex-row" x-data="{ email: '' }">
                    <input
                        type="email"
                        x-model="email"
                        placeholder="Enter your email"
                        class="flex-1 px-6 py-4 text-gray-900 bg-white border-0 rounded-lg focus:ring-2 focus:ring-white focus:outline-none"
                        required
                    >
                    <button
                        type="submit"
                        class="px-8 py-4 font-semibold text-blue-600 transition bg-white rounded-lg hover:bg-gray-100"
                    >
                        Subscribe
                    </button>
                </form>
            </div>
        </x-container>
    </section>

</x-layouts.marketing>
