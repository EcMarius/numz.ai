<x-layouts.marketing>
    <x-slot name="seo">
        <title>Pricing - {{ setting('site.title', 'Premium Hosting Solutions') }}</title>
        <meta name="description" content="Choose the perfect hosting plan for your needs. Transparent pricing with no hidden fees. 30-day money-back guarantee on all plans.">
        <meta name="keywords" content="hosting pricing, web hosting plans, cheap hosting, vps pricing">
        <meta property="og:title" content="Affordable Hosting Plans - {{ setting('site.title') }}">
        <meta property="og:description" content="Transparent pricing for all your hosting needs">
        <meta property="og:type" content="website">
    </x-slot>

    <!-- Hero Section -->
    <section class="py-24 bg-gradient-to-br from-blue-50 via-white to-purple-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
        <x-container>
            <div class="max-w-3xl mx-auto text-center">
                <h1 class="text-4xl font-bold tracking-tight text-gray-900 dark:text-white md:text-5xl lg:text-6xl">
                    Simple, Transparent Pricing
                </h1>
                <p class="mt-6 text-lg text-gray-600 dark:text-gray-300 md:text-xl">
                    Choose the perfect plan for your needs. Scale up or down anytime. No hidden fees, no surprises.
                </p>

                <!-- Billing Toggle -->
                <div class="flex items-center justify-center gap-4 mt-8" x-data="{ billingCycle: 'monthly' }">
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400" :class="{ 'text-gray-900 dark:text-white': billingCycle === 'monthly' }">
                        Monthly
                    </span>
                    <button
                        @click="billingCycle = billingCycle === 'monthly' ? 'annually' : 'monthly'"
                        type="button"
                        class="relative inline-flex items-center h-6 transition-colors bg-gray-200 rounded-full w-11 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:bg-gray-700"
                        :class="{ 'bg-blue-600': billingCycle === 'annually' }"
                    >
                        <span
                            class="inline-block w-4 h-4 transition-transform transform bg-white rounded-full"
                            :class="{ 'translate-x-6': billingCycle === 'annually', 'translate-x-1': billingCycle === 'monthly' }"
                        ></span>
                    </button>
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400" :class="{ 'text-gray-900 dark:text-white': billingCycle === 'annually' }">
                        Annually
                        <span class="ml-1.5 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                            Save 20%
                        </span>
                    </span>
                </div>
            </div>
        </x-container>
    </section>

    <!-- Pricing Plans -->
    <section class="py-24 bg-white dark:bg-gray-800" x-data="{ billingCycle: 'monthly' }">
        <x-container>
            <div class="grid gap-8 lg:grid-cols-4">
                <!-- Starter Plan -->
                <div class="flex flex-col p-8 border border-gray-200 rounded-2xl dark:border-gray-700 hover:border-blue-500 hover:shadow-xl transition-all">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Starter</h3>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Perfect for personal websites and blogs</p>

                        <div class="mt-6">
                            <div x-show="billingCycle === 'monthly'">
                                <span class="text-4xl font-bold text-gray-900 dark:text-white">$9.99</span>
                                <span class="text-gray-600 dark:text-gray-400">/month</span>
                            </div>
                            <div x-show="billingCycle === 'annually'" x-cloak>
                                <span class="text-4xl font-bold text-gray-900 dark:text-white">$7.99</span>
                                <span class="text-gray-600 dark:text-gray-400">/month</span>
                                <div class="text-sm text-green-600 dark:text-green-400">Billed $95.88/year</div>
                            </div>
                        </div>
                    </div>

                    <ul class="flex-1 mt-8 space-y-4">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-sm text-gray-600 dark:text-gray-400">10 GB SSD Storage</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-sm text-gray-600 dark:text-gray-400">100 GB Bandwidth</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-sm text-gray-600 dark:text-gray-400">1 Website</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-sm text-gray-600 dark:text-gray-400">Free SSL Certificate</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-sm text-gray-600 dark:text-gray-400">Daily Backups</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-sm text-gray-600 dark:text-gray-400">Email Support</span>
                        </li>
                    </ul>

                    <x-button href="{{ route('register') }}" tag="a" color="secondary" class="w-full mt-8">
                        Get Started
                    </x-button>
                </div>

                <!-- Professional Plan -->
                <div class="relative flex flex-col p-8 border-2 border-blue-500 shadow-2xl rounded-2xl bg-gradient-to-br from-blue-50 to-purple-50 dark:from-gray-700 dark:to-gray-800">
                    <div class="absolute top-0 right-6 -translate-y-1/2">
                        <span class="inline-flex px-4 py-1 text-xs font-semibold text-white bg-gradient-to-r from-blue-600 to-purple-600 rounded-full">
                            Most Popular
                        </span>
                    </div>

                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Professional</h3>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Ideal for small to medium businesses</p>

                        <div class="mt-6">
                            <div x-show="billingCycle === 'monthly'">
                                <span class="text-4xl font-bold text-gray-900 dark:text-white">$29.99</span>
                                <span class="text-gray-600 dark:text-gray-400">/month</span>
                            </div>
                            <div x-show="billingCycle === 'annually'" x-cloak>
                                <span class="text-4xl font-bold text-gray-900 dark:text-white">$23.99</span>
                                <span class="text-gray-600 dark:text-gray-400">/month</span>
                                <div class="text-sm text-green-600 dark:text-green-400">Billed $287.88/year</div>
                            </div>
                        </div>
                    </div>

                    <ul class="flex-1 mt-8 space-y-4">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-sm text-gray-600 dark:text-gray-400">50 GB SSD Storage</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-sm text-gray-600 dark:text-gray-400">Unlimited Bandwidth</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-sm text-gray-600 dark:text-gray-400">5 Websites</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-sm text-gray-600 dark:text-gray-400">Free SSL & CDN</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-sm text-gray-600 dark:text-gray-400">Staging Environment</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-sm text-gray-600 dark:text-gray-400">Priority Support</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-sm text-gray-600 dark:text-gray-400">Git Integration</span>
                        </li>
                    </ul>

                    <x-button href="{{ route('register') }}" tag="a" class="w-full mt-8">
                        Get Started
                    </x-button>
                </div>

                <!-- Business Plan -->
                <div class="flex flex-col p-8 border border-gray-200 rounded-2xl dark:border-gray-700 hover:border-blue-500 hover:shadow-xl transition-all">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Business</h3>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">For growing businesses and agencies</p>

                        <div class="mt-6">
                            <div x-show="billingCycle === 'monthly'">
                                <span class="text-4xl font-bold text-gray-900 dark:text-white">$59.99</span>
                                <span class="text-gray-600 dark:text-gray-400">/month</span>
                            </div>
                            <div x-show="billingCycle === 'annually'" x-cloak>
                                <span class="text-4xl font-bold text-gray-900 dark:text-white">$47.99</span>
                                <span class="text-gray-600 dark:text-gray-400">/month</span>
                                <div class="text-sm text-green-600 dark:text-green-400">Billed $575.88/year</div>
                            </div>
                        </div>
                    </div>

                    <ul class="flex-1 mt-8 space-y-4">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-sm text-gray-600 dark:text-gray-400">100 GB SSD Storage</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-sm text-gray-600 dark:text-gray-400">Unlimited Everything</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-sm text-gray-600 dark:text-gray-400">Unlimited Websites</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-sm text-gray-600 dark:text-gray-400">Advanced Security</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-sm text-gray-600 dark:text-gray-400">White-Label Options</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-sm text-gray-600 dark:text-gray-400">Dedicated IP Address</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-sm text-gray-600 dark:text-gray-400">24/7 Phone Support</span>
                        </li>
                    </ul>

                    <x-button href="{{ route('register') }}" tag="a" color="secondary" class="w-full mt-8">
                        Get Started
                    </x-button>
                </div>

                <!-- Enterprise Plan -->
                <div class="flex flex-col p-8 border border-gray-200 rounded-2xl dark:border-gray-700 hover:border-blue-500 hover:shadow-xl transition-all">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Enterprise</h3>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Custom solutions for large organizations</p>

                        <div class="mt-6">
                            <div x-show="billingCycle === 'monthly'">
                                <span class="text-4xl font-bold text-gray-900 dark:text-white">$99.99</span>
                                <span class="text-gray-600 dark:text-gray-400">/month</span>
                            </div>
                            <div x-show="billingCycle === 'annually'" x-cloak>
                                <span class="text-4xl font-bold text-gray-900 dark:text-white">$79.99</span>
                                <span class="text-gray-600 dark:text-gray-400">/month</span>
                                <div class="text-sm text-green-600 dark:text-green-400">Billed $959.88/year</div>
                            </div>
                        </div>
                    </div>

                    <ul class="flex-1 mt-8 space-y-4">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-sm text-gray-600 dark:text-gray-400">200 GB SSD Storage</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-sm text-gray-600 dark:text-gray-400">Dedicated Resources</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-sm text-gray-600 dark:text-gray-400">Unlimited Everything</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-sm text-gray-600 dark:text-gray-400">Custom Infrastructure</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-sm text-gray-600 dark:text-gray-400">SLA Guarantee</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-sm text-gray-600 dark:text-gray-400">Dedicated Account Manager</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-sm text-gray-600 dark:text-gray-400">White-Glove Onboarding</span>
                        </li>
                    </ul>

                    <x-button href="{{ route('contact') }}" tag="a" color="secondary" class="w-full mt-8">
                        Contact Sales
                    </x-button>
                </div>
            </div>
        </x-container>
    </section>

    <!-- Feature Comparison Table -->
    <section class="py-24 bg-gray-50 dark:bg-gray-900">
        <x-container>
            <div class="max-w-3xl mx-auto mb-16 text-center">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white md:text-4xl">
                    Compare All Features
                </h2>
                <p class="mt-4 text-lg text-gray-600 dark:text-gray-300">
                    See what's included in each plan
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 rounded-xl">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="px-6 py-4 text-sm font-semibold text-left text-gray-900 dark:text-white">Feature</th>
                            <th class="px-6 py-4 text-sm font-semibold text-center text-gray-900 dark:text-white">Starter</th>
                            <th class="px-6 py-4 text-sm font-semibold text-center text-gray-900 dark:text-white">Professional</th>
                            <th class="px-6 py-4 text-sm font-semibold text-center text-gray-900 dark:text-white">Business</th>
                            <th class="px-6 py-4 text-sm font-semibold text-center text-gray-900 dark:text-white">Enterprise</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">SSD Storage</td>
                            <td class="px-6 py-4 text-sm text-center text-gray-900 dark:text-white">10 GB</td>
                            <td class="px-6 py-4 text-sm text-center text-gray-900 dark:text-white">50 GB</td>
                            <td class="px-6 py-4 text-sm text-center text-gray-900 dark:text-white">100 GB</td>
                            <td class="px-6 py-4 text-sm text-center text-gray-900 dark:text-white">200 GB</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">Bandwidth</td>
                            <td class="px-6 py-4 text-sm text-center text-gray-900 dark:text-white">100 GB</td>
                            <td class="px-6 py-4 text-sm text-center text-gray-900 dark:text-white">Unlimited</td>
                            <td class="px-6 py-4 text-sm text-center text-gray-900 dark:text-white">Unlimited</td>
                            <td class="px-6 py-4 text-sm text-center text-gray-900 dark:text-white">Unlimited</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">Websites</td>
                            <td class="px-6 py-4 text-sm text-center text-gray-900 dark:text-white">1</td>
                            <td class="px-6 py-4 text-sm text-center text-gray-900 dark:text-white">5</td>
                            <td class="px-6 py-4 text-sm text-center text-gray-900 dark:text-white">Unlimited</td>
                            <td class="px-6 py-4 text-sm text-center text-gray-900 dark:text-white">Unlimited</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">Free SSL</td>
                            <td class="px-6 py-4 text-center"><svg class="w-5 h-5 mx-auto text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg></td>
                            <td class="px-6 py-4 text-center"><svg class="w-5 h-5 mx-auto text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg></td>
                            <td class="px-6 py-4 text-center"><svg class="w-5 h-5 mx-auto text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg></td>
                            <td class="px-6 py-4 text-center"><svg class="w-5 h-5 mx-auto text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg></td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">CDN</td>
                            <td class="px-6 py-4 text-center"><svg class="w-5 h-5 mx-auto text-gray-300" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg></td>
                            <td class="px-6 py-4 text-center"><svg class="w-5 h-5 mx-auto text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg></td>
                            <td class="px-6 py-4 text-center"><svg class="w-5 h-5 mx-auto text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg></td>
                            <td class="px-6 py-4 text-center"><svg class="w-5 h-5 mx-auto text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg></td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">Staging Environment</td>
                            <td class="px-6 py-4 text-center"><svg class="w-5 h-5 mx-auto text-gray-300" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg></td>
                            <td class="px-6 py-4 text-center"><svg class="w-5 h-5 mx-auto text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg></td>
                            <td class="px-6 py-4 text-center"><svg class="w-5 h-5 mx-auto text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg></td>
                            <td class="px-6 py-4 text-center"><svg class="w-5 h-5 mx-auto text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg></td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">Git Integration</td>
                            <td class="px-6 py-4 text-center"><svg class="w-5 h-5 mx-auto text-gray-300" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg></td>
                            <td class="px-6 py-4 text-center"><svg class="w-5 h-5 mx-auto text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg></td>
                            <td class="px-6 py-4 text-center"><svg class="w-5 h-5 mx-auto text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg></td>
                            <td class="px-6 py-4 text-center"><svg class="w-5 h-5 mx-auto text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg></td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">White-Label</td>
                            <td class="px-6 py-4 text-center"><svg class="w-5 h-5 mx-auto text-gray-300" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg></td>
                            <td class="px-6 py-4 text-center"><svg class="w-5 h-5 mx-auto text-gray-300" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg></td>
                            <td class="px-6 py-4 text-center"><svg class="w-5 h-5 mx-auto text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg></td>
                            <td class="px-6 py-4 text-center"><svg class="w-5 h-5 mx-auto text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg></td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">Dedicated IP</td>
                            <td class="px-6 py-4 text-center"><svg class="w-5 h-5 mx-auto text-gray-300" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg></td>
                            <td class="px-6 py-4 text-center"><svg class="w-5 h-5 mx-auto text-gray-300" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg></td>
                            <td class="px-6 py-4 text-center"><svg class="w-5 h-5 mx-auto text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg></td>
                            <td class="px-6 py-4 text-center"><svg class="w-5 h-5 mx-auto text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg></td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">Support Level</td>
                            <td class="px-6 py-4 text-sm text-center text-gray-900 dark:text-white">Email</td>
                            <td class="px-6 py-4 text-sm text-center text-gray-900 dark:text-white">Priority</td>
                            <td class="px-6 py-4 text-sm text-center text-gray-900 dark:text-white">24/7 Phone</td>
                            <td class="px-6 py-4 text-sm text-center text-gray-900 dark:text-white">White-Glove</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </x-container>
    </section>

    <!-- Add-ons Section -->
    <section class="py-24 bg-white dark:bg-gray-800">
        <x-container>
            <div class="max-w-3xl mx-auto mb-16 text-center">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white md:text-4xl">
                    Enhance Your Plan with Add-ons
                </h2>
                <p class="mt-4 text-lg text-gray-600 dark:text-gray-300">
                    Take your hosting to the next level with these optional add-ons
                </p>
            </div>

            <div class="grid gap-8 md:grid-cols-3">
                <div class="p-6 border border-gray-200 rounded-xl dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Extra Storage</h3>
                    <div class="mt-2">
                        <span class="text-2xl font-bold text-gray-900 dark:text-white">$5</span>
                        <span class="text-gray-600 dark:text-gray-400">/50GB/mo</span>
                    </div>
                    <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                        Need more space? Add additional SSD storage in 50GB increments.
                    </p>
                </div>

                <div class="p-6 border border-gray-200 rounded-xl dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Advanced Security</h3>
                    <div class="mt-2">
                        <span class="text-2xl font-bold text-gray-900 dark:text-white">$15</span>
                        <span class="text-gray-600 dark:text-gray-400">/month</span>
                    </div>
                    <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                        Enhanced firewall, malware scanning, and intrusion detection.
                    </p>
                </div>

                <div class="p-6 border border-gray-200 rounded-xl dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Premium CDN</h3>
                    <div class="mt-2">
                        <span class="text-2xl font-bold text-gray-900 dark:text-white">$10</span>
                        <span class="text-gray-600 dark:text-gray-400">/month</span>
                    </div>
                    <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                        Global content delivery with 200+ PoPs for ultra-fast loading.
                    </p>
                </div>
            </div>
        </x-container>
    </section>

    <!-- FAQ -->
    <section class="py-24 bg-gray-50 dark:bg-gray-900">
        <x-container>
            <div class="max-w-3xl mx-auto">
                <h2 class="mb-12 text-3xl font-bold text-center text-gray-900 dark:text-white md:text-4xl">
                    Frequently Asked Questions
                </h2>

                <div class="space-y-4" x-data="{ openFaq: null }">
                    <div class="overflow-hidden bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 rounded-xl">
                        <button
                            @click="openFaq = openFaq === 1 ? null : 1"
                            class="flex items-center justify-between w-full px-6 py-5 text-left"
                        >
                            <span class="font-semibold text-gray-900 dark:text-white">Can I upgrade or downgrade my plan?</span>
                            <svg class="w-5 h-5 text-gray-500 transition-transform" :class="{ 'rotate-180': openFaq === 1 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div x-show="openFaq === 1" x-collapse x-cloak>
                            <div class="px-6 pb-5 text-gray-600 dark:text-gray-400">
                                Yes! You can upgrade or downgrade your plan at any time. Changes take effect immediately, and we'll prorate the billing accordingly.
                            </div>
                        </div>
                    </div>

                    <div class="overflow-hidden bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 rounded-xl">
                        <button
                            @click="openFaq = openFaq === 2 ? null : 2"
                            class="flex items-center justify-between w-full px-6 py-5 text-left"
                        >
                            <span class="font-semibold text-gray-900 dark:text-white">What's your refund policy?</span>
                            <svg class="w-5 h-5 text-gray-500 transition-transform" :class="{ 'rotate-180': openFaq === 2 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div x-show="openFaq === 2" x-collapse x-cloak>
                            <div class="px-6 pb-5 text-gray-600 dark:text-gray-400">
                                We offer a 30-day money-back guarantee on all plans. If you're not satisfied, contact us within 30 days for a full refund, no questions asked.
                            </div>
                        </div>
                    </div>

                    <div class="overflow-hidden bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 rounded-xl">
                        <button
                            @click="openFaq = openFaq === 3 ? null : 3"
                            class="flex items-center justify-between w-full px-6 py-5 text-left"
                        >
                            <span class="font-semibold text-gray-900 dark:text-white">Do you offer discounts for annual billing?</span>
                            <svg class="w-5 h-5 text-gray-500 transition-transform" :class="{ 'rotate-180': openFaq === 3 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div x-show="openFaq === 3" x-collapse x-cloak>
                            <div class="px-6 pb-5 text-gray-600 dark:text-gray-400">
                                Yes! You save 20% when you choose annual billing. Plus, you'll never have to worry about monthly renewals.
                            </div>
                        </div>
                    </div>

                    <div class="overflow-hidden bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 rounded-xl">
                        <button
                            @click="openFaq = openFaq === 4 ? null : 4"
                            class="flex items-center justify-between w-full px-6 py-5 text-left"
                        >
                            <span class="font-semibold text-gray-900 dark:text-white">What payment methods do you accept?</span>
                            <svg class="w-5 h-5 text-gray-500 transition-transform" :class="{ 'rotate-180': openFaq === 4 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div x-show="openFaq === 4" x-collapse x-cloak>
                            <div class="px-6 pb-5 text-gray-600 dark:text-gray-400">
                                We accept all major credit cards (Visa, MasterCard, American Express), PayPal, and bank transfers for Enterprise plans.
                            </div>
                        </div>
                    </div>

                    <div class="overflow-hidden bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 rounded-xl">
                        <button
                            @click="openFaq = openFaq === 5 ? null : 5"
                            class="flex items-center justify-between w-full px-6 py-5 text-left"
                        >
                            <span class="font-semibold text-gray-900 dark:text-white">Is my data backed up?</span>
                            <svg class="w-5 h-5 text-gray-500 transition-transform" :class="{ 'rotate-180': openFaq === 5 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div x-show="openFaq === 5" x-collapse x-cloak>
                            <div class="px-6 pb-5 text-gray-600 dark:text-gray-400">
                                Yes! We perform daily automated backups of all your data. You can restore your site to any backup point with just one click from your dashboard.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </x-container>
    </section>

    <!-- Money-Back Guarantee -->
    <section class="py-16 bg-gradient-to-br from-blue-600 to-purple-600">
        <x-container>
            <div class="flex flex-col items-center text-center">
                <div class="flex items-center justify-center w-20 h-20 mb-6 bg-white rounded-full">
                    <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                </div>
                <h2 class="text-3xl font-bold text-white md:text-4xl">30-Day Money-Back Guarantee</h2>
                <p class="max-w-2xl mt-4 text-lg text-blue-100">
                    Try our hosting risk-free. If you're not completely satisfied within the first 30 days, we'll refund your money. No questions asked.
                </p>
                <x-button href="{{ route('register') }}" tag="a" size="lg" color="secondary" class="mt-8">
                    Start Your Free Trial
                </x-button>
            </div>
        </x-container>
    </section>

</x-layouts.marketing>
