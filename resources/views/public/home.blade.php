<x-layouts.marketing>
    <x-slot name="seo">
        <title>{{ setting('site.title', 'Premium Hosting Solutions') }} - Fast, Secure & Reliable Web Hosting</title>
        <meta name="description" content="Get blazing fast web hosting with 99.9% uptime guarantee. Perfect for businesses, developers, and agencies. Start your 30-day free trial today.">
        <meta name="keywords" content="web hosting, cloud hosting, managed hosting, wordpress hosting, vps hosting">
        <meta property="og:title" content="{{ setting('site.title') }} - Premium Web Hosting">
        <meta property="og:description" content="Fast, secure & reliable web hosting solutions for your business">
        <meta property="og:type" content="website">
        <meta name="twitter:card" content="summary_large_image">
    </x-slot>

    <!-- Hero Section -->
    <section class="relative overflow-hidden bg-gradient-to-br from-blue-50 via-white to-purple-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
        <div class="absolute inset-0 bg-grid-slate-100 [mask-image:linear-gradient(0deg,white,rgba(255,255,255,0.6))] dark:bg-grid-slate-700/25"></div>
        <x-container class="relative">
            <div class="py-24 md:py-32 lg:py-40">
                <div class="max-w-4xl mx-auto text-center">
                    <div class="inline-flex items-center px-4 py-2 mb-8 space-x-2 text-sm font-medium text-blue-700 bg-blue-100 rounded-full dark:bg-blue-900/30 dark:text-blue-300">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        <span>99.9% Uptime Guarantee</span>
                    </div>

                    <h1 class="text-5xl font-bold tracking-tight text-gray-900 dark:text-white md:text-6xl lg:text-7xl">
                        Lightning-Fast Web Hosting
                        <span class="block mt-2 text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600">
                            Built for Performance
                        </span>
                    </h1>

                    <p class="max-w-2xl mx-auto mt-6 text-lg leading-relaxed text-gray-600 dark:text-gray-300 md:text-xl">
                        Experience enterprise-grade hosting infrastructure with blazing-fast speeds, rock-solid security, and 24/7 expert support. Perfect for businesses that demand excellence.
                    </p>

                    <div class="flex flex-col items-center justify-center gap-4 mt-10 sm:flex-row">
                        <x-button href="{{ route('register') }}" tag="a" size="lg" class="px-8 py-4 text-lg">
                            Start Free Trial
                        </x-button>
                        <x-button href="{{ route('pricing') }}" tag="a" size="lg" color="secondary" class="px-8 py-4 text-lg">
                            View Pricing
                        </x-button>
                    </div>

                    <div class="flex items-center justify-center gap-8 mt-12 text-sm text-gray-600 dark:text-gray-400">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span>No credit card required</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span>30-day money back</span>
                        </div>
                    </div>
                </div>
            </div>
        </x-container>
    </section>

    <!-- Trust Indicators -->
    <section class="py-12 bg-white border-y border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <x-container>
            <div class="grid grid-cols-2 gap-8 md:grid-cols-4">
                <div class="text-center">
                    <div class="text-4xl font-bold text-gray-900 dark:text-white">99.9%</div>
                    <div class="mt-1 text-sm text-gray-600 dark:text-gray-400">Uptime</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold text-gray-900 dark:text-white">50K+</div>
                    <div class="mt-1 text-sm text-gray-600 dark:text-gray-400">Customers</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold text-gray-900 dark:text-white">120+</div>
                    <div class="mt-1 text-sm text-gray-600 dark:text-gray-400">Countries</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold text-gray-900 dark:text-white">24/7</div>
                    <div class="mt-1 text-sm text-gray-600 dark:text-gray-400">Support</div>
                </div>
            </div>
        </x-container>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-24 bg-gray-50 dark:bg-gray-900">
        <x-container>
            <div class="max-w-3xl mx-auto text-center">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white md:text-4xl">
                    Everything You Need to Succeed
                </h2>
                <p class="mt-4 text-lg text-gray-600 dark:text-gray-300">
                    Powerful features and tools to help you build, deploy, and scale your websites with confidence.
                </p>
            </div>

            <div class="grid gap-8 mt-16 md:grid-cols-2 lg:grid-cols-3">
                <!-- Feature 1 -->
                <div class="p-8 bg-white border border-gray-200 rounded-2xl dark:bg-gray-800 dark:border-gray-700 hover:shadow-lg transition-shadow">
                    <div class="flex items-center justify-center w-12 h-12 text-white bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <h3 class="mt-4 text-xl font-semibold text-gray-900 dark:text-white">Lightning Fast</h3>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        SSD storage, HTTP/3, and global CDN ensure your site loads in milliseconds.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="p-8 bg-white border border-gray-200 rounded-2xl dark:bg-gray-800 dark:border-gray-700 hover:shadow-lg transition-shadow">
                    <div class="flex items-center justify-center w-12 h-12 text-white bg-gradient-to-br from-green-500 to-green-600 rounded-xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </div>
                    <h3 class="mt-4 text-xl font-semibold text-gray-900 dark:text-white">Advanced Security</h3>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        Free SSL certificates, DDoS protection, and daily backups keep your data safe.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="p-8 bg-white border border-gray-200 rounded-2xl dark:bg-gray-800 dark:border-gray-700 hover:shadow-lg transition-shadow">
                    <div class="flex items-center justify-center w-12 h-12 text-white bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                    </div>
                    <h3 class="mt-4 text-xl font-semibold text-gray-900 dark:text-white">Easy Scalability</h3>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        Scale resources instantly as your traffic grows with just a few clicks.
                    </p>
                </div>

                <!-- Feature 4 -->
                <div class="p-8 bg-white border border-gray-200 rounded-2xl dark:bg-gray-800 dark:border-gray-700 hover:shadow-lg transition-shadow">
                    <div class="flex items-center justify-center w-12 h-12 text-white bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </div>
                    <h3 class="mt-4 text-xl font-semibold text-gray-900 dark:text-white">24/7 Expert Support</h3>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        Our team of experts is available round the clock to help you succeed.
                    </p>
                </div>

                <!-- Feature 5 -->
                <div class="p-8 bg-white border border-gray-200 rounded-2xl dark:bg-gray-800 dark:border-gray-700 hover:shadow-lg transition-shadow">
                    <div class="flex items-center justify-center w-12 h-12 text-white bg-gradient-to-br from-pink-500 to-pink-600 rounded-xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </div>
                    <h3 class="mt-4 text-xl font-semibold text-gray-900 dark:text-white">Automated Backups</h3>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        Daily automated backups with one-click restore ensure your data is never lost.
                    </p>
                </div>

                <!-- Feature 6 -->
                <div class="p-8 bg-white border border-gray-200 rounded-2xl dark:bg-gray-800 dark:border-gray-700 hover:shadow-lg transition-shadow">
                    <div class="flex items-center justify-center w-12 h-12 text-white bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                        </svg>
                    </div>
                    <h3 class="mt-4 text-xl font-semibold text-gray-900 dark:text-white">Developer Friendly</h3>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        Git integration, SSH access, WP-CLI, and API for complete control.
                    </p>
                </div>
            </div>
        </x-container>
    </section>

    <!-- Pricing Preview -->
    <section class="py-24 bg-white dark:bg-gray-800">
        <x-container>
            <div class="max-w-3xl mx-auto text-center">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white md:text-4xl">
                    Simple, Transparent Pricing
                </h2>
                <p class="mt-4 text-lg text-gray-600 dark:text-gray-300">
                    Choose the perfect plan for your needs. Upgrade or downgrade anytime.
                </p>
            </div>

            <div class="grid gap-8 mt-16 md:grid-cols-3">
                <!-- Starter Plan -->
                <div class="p-8 border border-gray-200 rounded-2xl dark:border-gray-700 hover:border-blue-500 transition-colors">
                    <div class="text-sm font-semibold text-blue-600 uppercase dark:text-blue-400">Starter</div>
                    <div class="mt-4">
                        <span class="text-4xl font-bold text-gray-900 dark:text-white">$9.99</span>
                        <span class="text-gray-600 dark:text-gray-400">/month</span>
                    </div>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Perfect for small websites and blogs</p>

                    <ul class="mt-6 space-y-4">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-gray-600 dark:text-gray-400">10 GB SSD Storage</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-gray-600 dark:text-gray-400">100 GB Bandwidth</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-gray-600 dark:text-gray-400">Free SSL Certificate</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-gray-600 dark:text-gray-400">Daily Backups</span>
                        </li>
                    </ul>

                    <x-button href="{{ route('register') }}" tag="a" color="secondary" class="w-full mt-8">
                        Get Started
                    </x-button>
                </div>

                <!-- Professional Plan -->
                <div class="relative p-8 border-2 border-blue-500 shadow-xl rounded-2xl bg-gradient-to-br from-blue-50 to-purple-50 dark:from-gray-700 dark:to-gray-800">
                    <div class="absolute top-0 right-6 -translate-y-1/2">
                        <span class="inline-flex px-4 py-1 text-xs font-semibold text-white bg-gradient-to-r from-blue-600 to-purple-600 rounded-full">
                            Most Popular
                        </span>
                    </div>

                    <div class="text-sm font-semibold text-blue-600 uppercase dark:text-blue-400">Professional</div>
                    <div class="mt-4">
                        <span class="text-4xl font-bold text-gray-900 dark:text-white">$29.99</span>
                        <span class="text-gray-600 dark:text-gray-400">/month</span>
                    </div>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">For growing businesses</p>

                    <ul class="mt-6 space-y-4">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-gray-600 dark:text-gray-400">50 GB SSD Storage</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-gray-600 dark:text-gray-400">Unlimited Bandwidth</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-gray-600 dark:text-gray-400">Free SSL & CDN</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-gray-600 dark:text-gray-400">Priority Support</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-gray-600 dark:text-gray-400">Staging Environment</span>
                        </li>
                    </ul>

                    <x-button href="{{ route('register') }}" tag="a" class="w-full mt-8">
                        Get Started
                    </x-button>
                </div>

                <!-- Enterprise Plan -->
                <div class="p-8 border border-gray-200 rounded-2xl dark:border-gray-700 hover:border-blue-500 transition-colors">
                    <div class="text-sm font-semibold text-blue-600 uppercase dark:text-blue-400">Enterprise</div>
                    <div class="mt-4">
                        <span class="text-4xl font-bold text-gray-900 dark:text-white">$99.99</span>
                        <span class="text-gray-600 dark:text-gray-400">/month</span>
                    </div>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">For large-scale applications</p>

                    <ul class="mt-6 space-y-4">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-gray-600 dark:text-gray-400">200 GB SSD Storage</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-gray-600 dark:text-gray-400">Unlimited Everything</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-gray-600 dark:text-gray-400">Dedicated Resources</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-gray-600 dark:text-gray-400">White-Glove Support</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-gray-600 dark:text-gray-400">Custom Solutions</span>
                        </li>
                    </ul>

                    <x-button href="{{ route('register') }}" tag="a" color="secondary" class="w-full mt-8">
                        Get Started
                    </x-button>
                </div>
            </div>

            <div class="mt-12 text-center">
                <x-button href="{{ route('pricing') }}" tag="a" color="secondary" size="lg">
                    View All Plans & Features
                </x-button>
            </div>
        </x-container>
    </section>

    <!-- Testimonials -->
    <section class="py-24 bg-gray-50 dark:bg-gray-900">
        <x-container>
            <div class="max-w-3xl mx-auto text-center">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white md:text-4xl">
                    Loved by Thousands of Customers
                </h2>
                <p class="mt-4 text-lg text-gray-600 dark:text-gray-300">
                    Don't just take our word for it. Here's what our customers have to say.
                </p>
            </div>

            <div class="grid gap-8 mt-16 md:grid-cols-3">
                <div class="p-8 bg-white border border-gray-200 rounded-2xl dark:bg-gray-800 dark:border-gray-700">
                    <div class="flex items-center gap-1">
                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                    </div>
                    <p class="mt-4 text-gray-600 dark:text-gray-400">
                        "Switched to this hosting after years of dealing with slow loading times. The difference is night and day. My site has never been faster, and the support team is incredibly helpful."
                    </p>
                    <div class="flex items-center mt-6">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center w-10 h-10 text-white bg-gradient-to-br from-blue-500 to-purple-500 rounded-full">
                                <span class="text-sm font-bold">SC</span>
                            </div>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">Sarah Chen</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">E-commerce Store Owner</p>
                        </div>
                    </div>
                </div>

                <div class="p-8 bg-white border border-gray-200 rounded-2xl dark:bg-gray-800 dark:border-gray-700">
                    <div class="flex items-center gap-1">
                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                    </div>
                    <p class="mt-4 text-gray-600 dark:text-gray-400">
                        "As a developer, I need SSH access, Git integration, and flexibility. This platform gives me everything I need with great performance. The staging environment feature is a game-changer."
                    </p>
                    <div class="flex items-center mt-6">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center w-10 h-10 text-white bg-gradient-to-br from-green-500 to-teal-500 rounded-full">
                                <span class="text-sm font-bold">MR</span>
                            </div>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">Michael Rodriguez</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Full-Stack Developer</p>
                        </div>
                    </div>
                </div>

                <div class="p-8 bg-white border border-gray-200 rounded-2xl dark:bg-gray-800 dark:border-gray-700">
                    <div class="flex items-center gap-1">
                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                    </div>
                    <p class="mt-4 text-gray-600 dark:text-gray-400">
                        "We manage over 50 client websites, and the white-label options and agency features make our job so much easier. The reliability and speed are exactly what we needed."
                    </p>
                    <div class="flex items-center mt-6">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center w-10 h-10 text-white bg-gradient-to-br from-orange-500 to-pink-500 rounded-full">
                                <span class="text-sm font-bold">EP</span>
                            </div>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">Emily Parker</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Digital Agency Owner</p>
                        </div>
                    </div>
                </div>
            </div>
        </x-container>
    </section>

    <!-- Newsletter -->
    <section class="py-24 bg-gradient-to-br from-blue-600 to-purple-600">
        <x-container>
            <div class="max-w-3xl mx-auto text-center">
                <h2 class="text-3xl font-bold text-white md:text-4xl">
                    Stay Updated with Latest Features
                </h2>
                <p class="mt-4 text-lg text-blue-100">
                    Get hosting tips, security updates, and exclusive offers delivered to your inbox.
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

                <p class="mt-4 text-sm text-blue-100">
                    We respect your privacy. Unsubscribe at any time.
                </p>
            </div>
        </x-container>
    </section>

    <!-- Final CTA -->
    <section class="py-24 bg-white dark:bg-gray-800">
        <x-container>
            <div class="max-w-4xl mx-auto text-center">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white md:text-4xl">
                    Ready to Get Started?
                </h2>
                <p class="mt-4 text-lg text-gray-600 dark:text-gray-300">
                    Join thousands of satisfied customers and experience the difference today.
                </p>

                <div class="flex flex-col items-center justify-center gap-4 mt-8 sm:flex-row">
                    <x-button href="{{ route('register') }}" tag="a" size="lg" class="px-8 py-4 text-lg">
                        Start Your Free Trial
                    </x-button>
                    <x-button href="{{ route('contact') }}" tag="a" size="lg" color="secondary" class="px-8 py-4 text-lg">
                        Talk to Sales
                    </x-button>
                </div>

                <p class="mt-6 text-sm text-gray-600 dark:text-gray-400">
                    30-day money-back guarantee. No credit card required.
                </p>
            </div>
        </x-container>
    </section>

</x-layouts.marketing>
