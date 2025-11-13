<x-layouts.marketing>
    <x-slot name="seo">
        <title>Features - {{ setting('site.title', 'Premium Hosting Solutions') }}</title>
        <meta name="description" content="Explore our comprehensive hosting features including performance optimization, security, developer tools, and 24/7 support.">
        <meta name="keywords" content="hosting features, web hosting tools, cdn, ssl, backup, security">
        <meta property="og:title" content="Powerful Hosting Features - {{ setting('site.title') }}">
        <meta property="og:description" content="Everything you need to build, deploy, and scale your websites">
        <meta property="og:type" content="website">
    </x-slot>

    <!-- Hero Section -->
    <section class="py-24 bg-gradient-to-br from-blue-50 via-white to-purple-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
        <x-container>
            <div class="max-w-3xl mx-auto text-center">
                <h1 class="text-4xl font-bold tracking-tight text-gray-900 dark:text-white md:text-5xl lg:text-6xl">
                    Powerful Features for Modern Websites
                </h1>
                <p class="mt-6 text-lg text-gray-600 dark:text-gray-300 md:text-xl">
                    Everything you need to build, deploy, and scale your websites with confidence. Enterprise-grade infrastructure meets developer-friendly tools.
                </p>
            </div>
        </x-container>
    </section>

    <!-- Performance Features -->
    <section class="py-24 bg-white dark:bg-gray-800">
        <x-container>
            <div class="flex flex-col gap-12 lg:flex-row lg:items-center">
                <div class="lg:w-1/2">
                    <div class="inline-flex items-center px-4 py-2 mb-4 text-sm font-medium text-blue-700 bg-blue-100 rounded-full dark:bg-blue-900/30 dark:text-blue-300">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        Performance
                    </div>
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white md:text-4xl">
                        Blazing Fast Performance
                    </h2>
                    <p class="mt-4 text-lg text-gray-600 dark:text-gray-300">
                        Experience lightning-fast load times with our optimized infrastructure and global CDN.
                    </p>

                    <div class="mt-8 space-y-6">
                        <div class="flex gap-4">
                            <div class="flex-shrink-0">
                                <div class="flex items-center justify-center w-12 h-12 text-white bg-blue-600 rounded-xl">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">NVMe SSD Storage</h3>
                                <p class="mt-1 text-gray-600 dark:text-gray-400">Up to 35x faster than traditional HDDs with enterprise-grade NVMe SSDs.</p>
                            </div>
                        </div>

                        <div class="flex gap-4">
                            <div class="flex-shrink-0">
                                <div class="flex items-center justify-center w-12 h-12 text-white bg-blue-600 rounded-xl">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Global CDN</h3>
                                <p class="mt-1 text-gray-600 dark:text-gray-400">200+ edge locations worldwide ensure fast content delivery to your visitors.</p>
                            </div>
                        </div>

                        <div class="flex gap-4">
                            <div class="flex-shrink-0">
                                <div class="flex items-center justify-center w-12 h-12 text-white bg-blue-600 rounded-xl">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">HTTP/3 & QUIC</h3>
                                <p class="mt-1 text-gray-600 dark:text-gray-400">Next-generation protocols for faster, more reliable connections.</p>
                            </div>
                        </div>

                        <div class="flex gap-4">
                            <div class="flex-shrink-0">
                                <div class="flex items-center justify-center w-12 h-12 text-white bg-blue-600 rounded-xl">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Smart Caching</h3>
                                <p class="mt-1 text-gray-600 dark:text-gray-400">Advanced caching mechanisms reduce server load and improve response times.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:w-1/2">
                    <div class="p-8 bg-gradient-to-br from-blue-100 to-purple-100 dark:from-gray-700 dark:to-gray-800 rounded-2xl">
                        <div class="space-y-6">
                            <div class="p-6 bg-white rounded-xl dark:bg-gray-900">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Page Load Time</span>
                                    <span class="text-2xl font-bold text-green-600 dark:text-green-400">0.3s</span>
                                </div>
                                <div class="w-full h-2 bg-gray-200 rounded-full dark:bg-gray-700">
                                    <div class="h-2 bg-gradient-to-r from-green-400 to-green-600 rounded-full" style="width: 95%"></div>
                                </div>
                            </div>

                            <div class="p-6 bg-white rounded-xl dark:bg-gray-900">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Time to First Byte</span>
                                    <span class="text-2xl font-bold text-blue-600 dark:text-blue-400">50ms</span>
                                </div>
                                <div class="w-full h-2 bg-gray-200 rounded-full dark:bg-gray-700">
                                    <div class="h-2 bg-gradient-to-r from-blue-400 to-blue-600 rounded-full" style="width: 98%"></div>
                                </div>
                            </div>

                            <div class="p-6 bg-white rounded-xl dark:bg-gray-900">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Uptime</span>
                                    <span class="text-2xl font-bold text-purple-600 dark:text-purple-400">99.9%</span>
                                </div>
                                <div class="w-full h-2 bg-gray-200 rounded-full dark:bg-gray-700">
                                    <div class="h-2 bg-gradient-to-r from-purple-400 to-purple-600 rounded-full" style="width: 99.9%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </x-container>
    </section>

    <!-- Security Features -->
    <section class="py-24 bg-gray-50 dark:bg-gray-900">
        <x-container>
            <div class="flex flex-col-reverse gap-12 lg:flex-row lg:items-center">
                <div class="lg:w-1/2">
                    <div class="grid gap-6 md:grid-cols-2">
                        <div class="p-6 bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 rounded-xl">
                            <div class="flex items-center justify-center w-12 h-12 mb-4 text-white bg-green-600 rounded-xl">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Free SSL Certificates</h3>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Automatic SSL installation and renewal with Let's Encrypt</p>
                        </div>

                        <div class="p-6 bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 rounded-xl">
                            <div class="flex items-center justify-center w-12 h-12 mb-4 text-white bg-green-600 rounded-xl">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">DDoS Protection</h3>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Enterprise-grade protection against attacks</p>
                        </div>

                        <div class="p-6 bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 rounded-xl">
                            <div class="flex items-center justify-center w-12 h-12 mb-4 text-white bg-green-600 rounded-xl">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Web Application Firewall</h3>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Protects against OWASP Top 10 vulnerabilities</p>
                        </div>

                        <div class="p-6 bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 rounded-xl">
                            <div class="flex items-center justify-center w-12 h-12 mb-4 text-white bg-green-600 rounded-xl">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Daily Backups</h3>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Automated daily backups with easy restoration</p>
                        </div>

                        <div class="p-6 bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 rounded-xl">
                            <div class="flex items-center justify-center w-12 h-12 mb-4 text-white bg-green-600 rounded-xl">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Malware Scanning</h3>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Real-time malware detection and removal</p>
                        </div>

                        <div class="p-6 bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 rounded-xl">
                            <div class="flex items-center justify-center w-12 h-12 mb-4 text-white bg-green-600 rounded-xl">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">2FA Authentication</h3>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Two-factor authentication for enhanced security</p>
                        </div>
                    </div>
                </div>

                <div class="lg:w-1/2">
                    <div class="inline-flex items-center px-4 py-2 mb-4 text-sm font-medium text-green-700 bg-green-100 rounded-full dark:bg-green-900/30 dark:text-green-300">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                        Security
                    </div>
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white md:text-4xl">
                        Enterprise-Grade Security
                    </h2>
                    <p class="mt-4 text-lg text-gray-600 dark:text-gray-300">
                        Your website's security is our top priority. We employ multiple layers of protection to keep your data safe and secure.
                    </p>

                    <div class="p-6 mt-8 border-l-4 border-green-500 bg-green-50 dark:bg-green-900/20">
                        <h3 class="font-semibold text-gray-900 dark:text-white">Security Compliance</h3>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            We maintain compliance with industry standards including PCI DSS, GDPR, and SOC 2 Type II to ensure your data is handled with the highest level of security.
                        </p>
                    </div>

                    <div class="mt-6 space-y-4">
                        <div class="flex items-center gap-3">
                            <svg class="w-6 h-6 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-gray-700 dark:text-gray-300">Automated security updates</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <svg class="w-6 h-6 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-gray-700 dark:text-gray-300">24/7 security monitoring</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <svg class="w-6 h-6 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-gray-700 dark:text-gray-300">Isolated hosting environments</span>
                        </div>
                    </div>
                </div>
            </div>
        </x-container>
    </section>

    <!-- Developer Tools -->
    <section class="py-24 bg-white dark:bg-gray-800">
        <x-container>
            <div class="max-w-3xl mx-auto mb-16 text-center">
                <div class="inline-flex items-center px-4 py-2 mb-4 text-sm font-medium text-purple-700 bg-purple-100 rounded-full dark:bg-purple-900/30 dark:text-purple-300">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                    </svg>
                    Developer Tools
                </div>
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white md:text-4xl">
                    Built for Developers
                </h2>
                <p class="mt-4 text-lg text-gray-600 dark:text-gray-300">
                    All the tools and flexibility developers need to build amazing websites
                </p>
            </div>

            <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                <div class="p-6 bg-gray-50 dark:bg-gray-900 rounded-xl">
                    <div class="flex items-center justify-center w-12 h-12 mb-4 text-white bg-purple-600 rounded-xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">SSH Access</h3>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">Full SSH access for complete control over your hosting environment.</p>
                </div>

                <div class="p-6 bg-gray-50 dark:bg-gray-900 rounded-xl">
                    <div class="flex items-center justify-center w-12 h-12 mb-4 text-white bg-purple-600 rounded-xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Git Integration</h3>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">Deploy directly from GitHub, GitLab, or Bitbucket with automatic builds.</p>
                </div>

                <div class="p-6 bg-gray-50 dark:bg-gray-900 rounded-xl">
                    <div class="flex items-center justify-center w-12 h-12 mb-4 text-white bg-purple-600 rounded-xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Database Management</h3>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">MySQL, PostgreSQL, and MongoDB support with phpMyAdmin access.</p>
                </div>

                <div class="p-6 bg-gray-50 dark:bg-gray-900 rounded-xl">
                    <div class="flex items-center justify-center w-12 h-12 mb-4 text-white bg-purple-600 rounded-xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Staging Environment</h3>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">Test changes safely before pushing to production.</p>
                </div>

                <div class="p-6 bg-gray-50 dark:bg-gray-900 rounded-xl">
                    <div class="flex items-center justify-center w-12 h-12 mb-4 text-white bg-purple-600 rounded-xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">WP-CLI & Composer</h3>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">Command-line tools for WordPress and PHP dependency management.</p>
                </div>

                <div class="p-6 bg-gray-50 dark:bg-gray-900 rounded-xl">
                    <div class="flex items-center justify-center w-12 h-12 mb-4 text-white bg-purple-600 rounded-xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">RESTful API</h3>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">Comprehensive API for automation and custom integrations.</p>
                </div>
            </div>
        </x-container>
    </section>

    <!-- Support Features -->
    <section class="py-24 bg-gradient-to-br from-blue-600 to-purple-600">
        <x-container>
            <div class="max-w-3xl mx-auto text-center">
                <h2 class="text-3xl font-bold text-white md:text-4xl">
                    World-Class Support
                </h2>
                <p class="mt-4 text-lg text-blue-100">
                    Our expert support team is available 24/7 to help you succeed
                </p>
            </div>

            <div class="grid gap-8 mt-16 md:grid-cols-3">
                <div class="p-8 text-center bg-white/10 backdrop-blur-sm rounded-2xl">
                    <div class="flex items-center justify-center w-16 h-16 mx-auto mb-6 bg-white rounded-full">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-white">24/7 Availability</h3>
                    <p class="mt-3 text-blue-100">
                        Get help whenever you need it, day or night, 365 days a year
                    </p>
                </div>

                <div class="p-8 text-center bg-white/10 backdrop-blur-sm rounded-2xl">
                    <div class="flex items-center justify-center w-16 h-16 mx-auto mb-6 bg-white rounded-full">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-white">Multiple Channels</h3>
                    <p class="mt-3 text-blue-100">
                        Live chat, phone, email, and ticketing system support
                    </p>
                </div>

                <div class="p-8 text-center bg-white/10 backdrop-blur-sm rounded-2xl">
                    <div class="flex items-center justify-center w-16 h-16 mx-auto mb-6 bg-white rounded-full">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-white">Expert Team</h3>
                    <p class="mt-3 text-blue-100">
                        Knowledgeable support staff with years of hosting experience
                    </p>
                </div>
            </div>
        </x-container>
    </section>

    <!-- Technical Specifications -->
    <section class="py-24 bg-gray-50 dark:bg-gray-900">
        <x-container>
            <div class="max-w-3xl mx-auto mb-16 text-center">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white md:text-4xl">
                    Technical Specifications
                </h2>
                <p class="mt-4 text-lg text-gray-600 dark:text-gray-300">
                    Built on cutting-edge technology for optimal performance
                </p>
            </div>

            <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-4">
                <div class="p-6 text-center bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 rounded-xl">
                    <div class="text-4xl font-bold text-blue-600 dark:text-blue-400">PHP 8.4</div>
                    <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">Latest PHP Version</div>
                </div>

                <div class="p-6 text-center bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 rounded-xl">
                    <div class="text-4xl font-bold text-green-600 dark:text-green-400">MySQL 8.0</div>
                    <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">Database Server</div>
                </div>

                <div class="p-6 text-center bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 rounded-xl">
                    <div class="text-4xl font-bold text-purple-600 dark:text-purple-400">Node.js</div>
                    <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">JavaScript Runtime</div>
                </div>

                <div class="p-6 text-center bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 rounded-xl">
                    <div class="text-4xl font-bold text-orange-600 dark:text-orange-400">Redis</div>
                    <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">Object Caching</div>
                </div>
            </div>
        </x-container>
    </section>

    <!-- CTA -->
    <section class="py-24 bg-white dark:bg-gray-800">
        <x-container>
            <div class="max-w-3xl mx-auto text-center">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white md:text-4xl">
                    Ready to Experience These Features?
                </h2>
                <p class="mt-4 text-lg text-gray-600 dark:text-gray-300">
                    Start your free trial today and see why thousands of developers choose our platform
                </p>

                <div class="flex flex-col items-center justify-center gap-4 mt-8 sm:flex-row">
                    <x-button href="{{ route('register') }}" tag="a" size="lg" class="px-8 py-4 text-lg">
                        Start Free Trial
                    </x-button>
                    <x-button href="{{ route('pricing') }}" tag="a" size="lg" color="secondary" class="px-8 py-4 text-lg">
                        View Pricing
                    </x-button>
                </div>

                <p class="mt-6 text-sm text-gray-600 dark:text-gray-400">
                    No credit card required • 30-day money-back guarantee
                </p>
            </div>
        </x-container>
    </section>

</x-layouts.marketing>
