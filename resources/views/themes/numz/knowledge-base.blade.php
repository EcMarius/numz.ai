@extends('theme::layouts.app')

@section('content')
<!-- Hero Section -->
<section class="relative bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800 text-white">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="py-16 text-center">
            <h1 class="text-4xl sm:text-5xl font-bold mb-6">Knowledge Base</h1>
            <p class="text-xl text-blue-100 max-w-3xl mx-auto mb-8">
                Find answers, guides, and tutorials to help you get the most out of your hosting
            </p>

            <!-- Search Box -->
            <div class="max-w-2xl mx-auto">
                <form action="{{ route('knowledge-base.search') }}" method="GET" class="relative">
                    <input type="text" name="q" placeholder="Search for articles, guides, and tutorials..." class="w-full px-6 py-4 pr-14 rounded-lg text-gray-900 focus:outline-none focus:ring-4 focus:ring-blue-300" value="{{ request('q') }}">
                    <button type="submit" class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Quick Links Section -->
<section class="py-12 bg-white border-b border-gray-200">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-6xl mx-auto">
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Quick Link 1 -->
                <a href="#getting-started" class="flex items-center p-4 border border-gray-200 rounded-lg hover:border-blue-300 hover:bg-blue-50 transition-all">
                    <svg class="w-8 h-8 text-blue-600 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <div>
                        <div class="font-semibold text-gray-900">Quick Start</div>
                        <div class="text-sm text-gray-600">Get started fast</div>
                    </div>
                </a>

                <!-- Quick Link 2 -->
                <a href="#hosting" class="flex items-center p-4 border border-gray-200 rounded-lg hover:border-blue-300 hover:bg-blue-50 transition-all">
                    <svg class="w-8 h-8 text-green-600 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                    </svg>
                    <div>
                        <div class="font-semibold text-gray-900">Hosting</div>
                        <div class="text-sm text-gray-600">Manage your site</div>
                    </div>
                </a>

                <!-- Quick Link 3 -->
                <a href="#domains" class="flex items-center p-4 border border-gray-200 rounded-lg hover:border-blue-300 hover:bg-blue-50 transition-all">
                    <svg class="w-8 h-8 text-purple-600 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                    </svg>
                    <div>
                        <div class="font-semibold text-gray-900">Domains</div>
                        <div class="text-sm text-gray-600">Domain setup</div>
                    </div>
                </a>

                <!-- Quick Link 4 -->
                <a href="#troubleshooting" class="flex items-center p-4 border border-gray-200 rounded-lg hover:border-blue-300 hover:bg-blue-50 transition-all">
                    <svg class="w-8 h-8 text-orange-600 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <div>
                        <div class="font-semibold text-gray-900">Help</div>
                        <div class="text-sm text-gray-600">Fix issues</div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Main Content Section -->
<section class="py-16 bg-gray-50">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-6xl mx-auto">
            <!-- Getting Started -->
            <div id="getting-started" class="mb-16">
                <h2 class="text-3xl font-bold text-gray-900 mb-8 flex items-center">
                    <svg class="w-8 h-8 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    Getting Started
                </h2>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Article Card -->
                    <a href="#" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md hover:border-blue-300 transition-all">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Creating Your First Website</h3>
                        <p class="text-sm text-gray-600 mb-4">Learn how to set up your first website in minutes with our easy-to-follow guide.</p>
                        <span class="text-sm text-blue-600 font-medium flex items-center">
                            Read article
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </span>
                    </a>

                    <a href="#" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md hover:border-blue-300 transition-all">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Control Panel Overview</h3>
                        <p class="text-sm text-gray-600 mb-4">Understand the features and tools available in your hosting control panel.</p>
                        <span class="text-sm text-blue-600 font-medium flex items-center">
                            Read article
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </span>
                    </a>

                    <a href="#" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md hover:border-blue-300 transition-all">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Uploading Your Files</h3>
                        <p class="text-sm text-gray-600 mb-4">Multiple ways to upload and manage your website files securely.</p>
                        <span class="text-sm text-blue-600 font-medium flex items-center">
                            Read article
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </span>
                    </a>

                    <a href="#" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md hover:border-blue-300 transition-all">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Setting Up Email Accounts</h3>
                        <p class="text-sm text-gray-600 mb-4">Create professional email addresses for your domain in just a few clicks.</p>
                        <span class="text-sm text-blue-600 font-medium flex items-center">
                            Read article
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </span>
                    </a>

                    <a href="#" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md hover:border-blue-300 transition-all">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Installing WordPress</h3>
                        <p class="text-sm text-gray-600 mb-4">One-click WordPress installation and setup guide for beginners.</p>
                        <span class="text-sm text-blue-600 font-medium flex items-center">
                            Read article
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </span>
                    </a>

                    <a href="#" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md hover:border-blue-300 transition-all">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">SSL Certificate Setup</h3>
                        <p class="text-sm text-gray-600 mb-4">Enable free SSL certificates to secure your website with HTTPS.</p>
                        <span class="text-sm text-blue-600 font-medium flex items-center">
                            Read article
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </span>
                    </a>
                </div>
            </div>

            <!-- Hosting Management -->
            <div id="hosting" class="mb-16">
                <h2 class="text-3xl font-bold text-gray-900 mb-8 flex items-center">
                    <svg class="w-8 h-8 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                    </svg>
                    Hosting Management
                </h2>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <a href="#" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md hover:border-blue-300 transition-all">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Managing Databases</h3>
                        <p class="text-sm text-gray-600 mb-4">Create and manage MySQL/PostgreSQL databases for your applications.</p>
                        <span class="text-sm text-blue-600 font-medium flex items-center">
                            Read article
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </span>
                    </a>

                    <a href="#" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md hover:border-blue-300 transition-all">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">FTP/SFTP Access</h3>
                        <p class="text-sm text-gray-600 mb-4">Configure secure file transfer protocols for your hosting account.</p>
                        <span class="text-sm text-blue-600 font-medium flex items-center">
                            Read article
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </span>
                    </a>

                    <a href="#" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md hover:border-blue-300 transition-all">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Backup & Restore</h3>
                        <p class="text-sm text-gray-600 mb-4">Learn how to backup and restore your website data effectively.</p>
                        <span class="text-sm text-blue-600 font-medium flex items-center">
                            Read article
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </span>
                    </a>

                    <a href="#" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md hover:border-blue-300 transition-all">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Cron Jobs Setup</h3>
                        <p class="text-sm text-gray-600 mb-4">Schedule automated tasks to run at specific times or intervals.</p>
                        <span class="text-sm text-blue-600 font-medium flex items-center">
                            Read article
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </span>
                    </a>

                    <a href="#" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md hover:border-blue-300 transition-all">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">PHP Configuration</h3>
                        <p class="text-sm text-gray-600 mb-4">Customize PHP settings and version for your applications.</p>
                        <span class="text-sm text-blue-600 font-medium flex items-center">
                            Read article
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </span>
                    </a>

                    <a href="#" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md hover:border-blue-300 transition-all">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Resource Usage</h3>
                        <p class="text-sm text-gray-600 mb-4">Monitor and optimize your hosting resource consumption.</p>
                        <span class="text-sm text-blue-600 font-medium flex items-center">
                            Read article
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </span>
                    </a>
                </div>
            </div>

            <!-- Domain Management -->
            <div id="domains" class="mb-16">
                <h2 class="text-3xl font-bold text-gray-900 mb-8 flex items-center">
                    <svg class="w-8 h-8 text-purple-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                    </svg>
                    Domain Management
                </h2>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <a href="#" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md hover:border-blue-300 transition-all">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Registering a Domain</h3>
                        <p class="text-sm text-gray-600 mb-4">Step-by-step guide to registering your perfect domain name.</p>
                        <span class="text-sm text-blue-600 font-medium flex items-center">
                            Read article
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </span>
                    </a>

                    <a href="#" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md hover:border-blue-300 transition-all">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">DNS Management</h3>
                        <p class="text-sm text-gray-600 mb-4">Configure DNS records for your domain names effectively.</p>
                        <span class="text-sm text-blue-600 font-medium flex items-center">
                            Read article
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </span>
                    </a>

                    <a href="#" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md hover:border-blue-300 transition-all">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Domain Transfer</h3>
                        <p class="text-sm text-gray-600 mb-4">Transfer your existing domains to our platform easily.</p>
                        <span class="text-sm text-blue-600 font-medium flex items-center">
                            Read article
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </span>
                    </a>

                    <a href="#" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md hover:border-blue-300 transition-all">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Domain Privacy</h3>
                        <p class="text-sm text-gray-600 mb-4">Protect your personal information with WHOIS privacy.</p>
                        <span class="text-sm text-blue-600 font-medium flex items-center">
                            Read article
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </span>
                    </a>
                </div>
            </div>

            <!-- Troubleshooting -->
            <div id="troubleshooting" class="mb-16">
                <h2 class="text-3xl font-bold text-gray-900 mb-8 flex items-center">
                    <svg class="w-8 h-8 text-orange-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Troubleshooting
                </h2>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <a href="#" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md hover:border-blue-300 transition-all">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Website Not Loading</h3>
                        <p class="text-sm text-gray-600 mb-4">Common causes and solutions for website loading issues.</p>
                        <span class="text-sm text-blue-600 font-medium flex items-center">
                            Read article
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </span>
                    </a>

                    <a href="#" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md hover:border-blue-300 transition-all">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Email Not Working</h3>
                        <p class="text-sm text-gray-600 mb-4">Troubleshoot email delivery and receiving problems.</p>
                        <span class="text-sm text-blue-600 font-medium flex items-center">
                            Read article
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </span>
                    </a>

                    <a href="#" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md hover:border-blue-300 transition-all">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Database Connection Errors</h3>
                        <p class="text-sm text-gray-600 mb-4">Fix common database connection and configuration issues.</p>
                        <span class="text-sm text-blue-600 font-medium flex items-center">
                            Read article
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </span>
                    </a>

                    <a href="#" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md hover:border-blue-300 transition-all">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">SSL Certificate Issues</h3>
                        <p class="text-sm text-gray-600 mb-4">Resolve SSL certificate warnings and HTTPS problems.</p>
                        <span class="text-sm text-blue-600 font-medium flex items-center">
                            Read article
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </span>
                    </a>

                    <a href="#" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md hover:border-blue-300 transition-all">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Slow Website Performance</h3>
                        <p class="text-sm text-gray-600 mb-4">Identify and fix website speed and performance issues.</p>
                        <span class="text-sm text-blue-600 font-medium flex items-center">
                            Read article
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </span>
                    </a>

                    <a href="#" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md hover:border-blue-300 transition-all">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">File Permission Errors</h3>
                        <p class="text-sm text-gray-600 mb-4">Understanding and fixing file permission issues.</p>
                        <span class="text-sm text-blue-600 font-medium flex items-center">
                            Read article
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Contact Support CTA -->
<section class="py-16 bg-white">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl shadow-xl p-8 md:p-12 text-white text-center">
            <svg class="w-16 h-16 mx-auto mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            <h2 class="text-3xl font-bold mb-4">Can't Find What You're Looking For?</h2>
            <p class="text-xl text-blue-100 mb-8">
                Our support team is available 24/7 to help you with any questions or issues.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('contact') }}" class="inline-flex items-center justify-center px-8 py-4 text-lg font-semibold text-blue-600 bg-white rounded-lg hover:bg-blue-50 transition-all">
                    Contact Support
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </a>
                <a href="{{ route('dashboard.tickets.create') }}" class="inline-flex items-center justify-center px-8 py-4 text-lg font-semibold text-white border-2 border-white rounded-lg hover:bg-white hover:text-blue-600 transition-all">
                    Open a Ticket
                </a>
            </div>
        </div>
    </div>
</section>
@endsection
