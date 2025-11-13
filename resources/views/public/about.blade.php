<x-layouts.marketing>
    <x-slot name="seo">
        <title>About Us - {{ setting('site.title', 'Premium Hosting Solutions') }}</title>
        <meta name="description" content="Learn about our mission to provide the best web hosting services. Meet our team and discover our company values.">
        <meta name="keywords" content="about us, company, team, mission, values">
        <meta property="og:title" content="About {{ setting('site.title') }}">
        <meta property="og:description" content="Meet the team behind your hosting success">
        <meta property="og:type" content="website">
    </x-slot>

    <!-- Hero Section -->
    <section class="py-24 bg-gradient-to-br from-blue-50 via-white to-purple-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
        <x-container>
            <div class="max-w-3xl mx-auto text-center">
                <h1 class="text-4xl font-bold tracking-tight text-gray-900 dark:text-white md:text-5xl lg:text-6xl">
                    Building the Future of Web Hosting
                </h1>
                <p class="mt-6 text-lg text-gray-600 dark:text-gray-300 md:text-xl">
                    We're on a mission to make web hosting simple, fast, and accessible for everyone. From developers to enterprises, we provide the tools you need to succeed online.
                </p>
            </div>
        </x-container>
    </section>

    <!-- Company Story -->
    <section class="py-24 bg-white dark:bg-gray-800">
        <x-container>
            <div class="grid gap-12 lg:grid-cols-2 lg:items-center">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white md:text-4xl">
                        Our Story
                    </h2>
                    <div class="mt-6 space-y-4 text-gray-600 dark:text-gray-300">
                        <p>
                            Founded in 2015, our company started with a simple vision: to provide reliable, high-performance web hosting that doesn't require a technical degree to use. We saw too many developers and businesses struggling with complex hosting panels, slow support, and unreliable infrastructure.
                        </p>
                        <p>
                            Today, we're proud to serve over 50,000 customers in more than 120 countries, hosting millions of websites on our infrastructure. Our platform has grown from a small startup to a trusted hosting provider, but we've never lost sight of what matters most: our customers' success.
                        </p>
                        <p>
                            Every feature we build, every server we deploy, and every support interaction we have is guided by one principle: make web hosting better. We're not just providing hosting; we're building the foundation for dreams, businesses, and ideas to thrive online.
                        </p>
                    </div>
                </div>

                <div class="grid gap-6 md:grid-cols-2">
                    <div class="p-6 bg-blue-50 dark:bg-gray-900 rounded-2xl">
                        <div class="text-4xl font-bold text-blue-600 dark:text-blue-400">50K+</div>
                        <div class="mt-2 text-gray-600 dark:text-gray-400">Happy Customers</div>
                    </div>
                    <div class="p-6 bg-purple-50 dark:bg-gray-900 rounded-2xl">
                        <div class="text-4xl font-bold text-purple-600 dark:text-purple-400">99.9%</div>
                        <div class="mt-2 text-gray-600 dark:text-gray-400">Uptime SLA</div>
                    </div>
                    <div class="p-6 bg-green-50 dark:bg-gray-900 rounded-2xl">
                        <div class="text-4xl font-bold text-green-600 dark:text-green-400">120+</div>
                        <div class="mt-2 text-gray-600 dark:text-gray-400">Countries</div>
                    </div>
                    <div class="p-6 bg-orange-50 dark:bg-gray-900 rounded-2xl">
                        <div class="text-4xl font-bold text-orange-600 dark:text-orange-400">24/7</div>
                        <div class="mt-2 text-gray-600 dark:text-gray-400">Expert Support</div>
                    </div>
                </div>
            </div>
        </x-container>
    </section>

    <!-- Mission & Values -->
    <section class="py-24 bg-gray-50 dark:bg-gray-900">
        <x-container>
            <div class="max-w-3xl mx-auto mb-16 text-center">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white md:text-4xl">
                    Our Mission & Values
                </h2>
                <p class="mt-4 text-lg text-gray-600 dark:text-gray-300">
                    The principles that guide everything we do
                </p>
            </div>

            <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                <div class="p-8 bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 rounded-2xl">
                    <div class="flex items-center justify-center w-12 h-12 mb-4 text-white bg-blue-600 rounded-xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Performance First</h3>
                    <p class="mt-3 text-gray-600 dark:text-gray-400">
                        We believe speed matters. Every millisecond counts, and we're obsessed with making your sites load faster.
                    </p>
                </div>

                <div class="p-8 bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 rounded-2xl">
                    <div class="flex items-center justify-center w-12 h-12 mb-4 text-white bg-green-600 rounded-xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Customer Success</h3>
                    <p class="mt-3 text-gray-600 dark:text-gray-400">
                        Your success is our success. We go above and beyond to ensure you have everything you need to thrive.
                    </p>
                </div>

                <div class="p-8 bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 rounded-2xl">
                    <div class="flex items-center justify-center w-12 h-12 mb-4 text-white bg-purple-600 rounded-xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Security & Trust</h3>
                    <p class="mt-3 text-gray-600 dark:text-gray-400">
                        We treat your data like our own. Security isn't an afterthought; it's built into everything we do.
                    </p>
                </div>

                <div class="p-8 bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 rounded-2xl">
                    <div class="flex items-center justify-center w-12 h-12 mb-4 text-white bg-orange-600 rounded-xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Simplicity</h3>
                    <p class="mt-3 text-gray-600 dark:text-gray-400">
                        Powerful doesn't mean complicated. We make advanced features accessible to everyone.
                    </p>
                </div>

                <div class="p-8 bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 rounded-2xl">
                    <div class="flex items-center justify-center w-12 h-12 mb-4 text-white bg-pink-600 rounded-xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Innovation</h3>
                    <p class="mt-3 text-gray-600 dark:text-gray-400">
                        We're constantly evolving, adopting new technologies and practices to stay ahead.
                    </p>
                </div>

                <div class="p-8 bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 rounded-2xl">
                    <div class="flex items-center justify-center w-12 h-12 mb-4 text-white bg-indigo-600 rounded-xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Transparency</h3>
                    <p class="mt-3 text-gray-600 dark:text-gray-400">
                        No hidden fees, no surprises. We believe in honest communication and fair pricing.
                    </p>
                </div>
            </div>
        </x-container>
    </section>

    <!-- Team Section -->
    <section class="py-24 bg-white dark:bg-gray-800">
        <x-container>
            <div class="max-w-3xl mx-auto mb-16 text-center">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white md:text-4xl">
                    Meet Our Team
                </h2>
                <p class="mt-4 text-lg text-gray-600 dark:text-gray-300">
                    The talented people behind your hosting success
                </p>
            </div>

            <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-4">
                <!-- Team Member 1 -->
                <div class="text-center">
                    <div class="relative inline-block">
                        <div class="w-32 h-32 mx-auto overflow-hidden bg-gradient-to-br from-blue-400 to-blue-600 rounded-full">
                            <div class="flex items-center justify-center w-full h-full text-3xl font-bold text-white">
                                JD
                            </div>
                        </div>
                    </div>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">John Doe</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">CEO & Founder</p>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-500">
                        Passionate about making hosting accessible to everyone
                    </p>
                </div>

                <!-- Team Member 2 -->
                <div class="text-center">
                    <div class="relative inline-block">
                        <div class="w-32 h-32 mx-auto overflow-hidden bg-gradient-to-br from-purple-400 to-purple-600 rounded-full">
                            <div class="flex items-center justify-center w-full h-full text-3xl font-bold text-white">
                                SM
                            </div>
                        </div>
                    </div>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">Sarah Mitchell</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">CTO</p>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-500">
                        Leading our infrastructure and engineering teams
                    </p>
                </div>

                <!-- Team Member 3 -->
                <div class="text-center">
                    <div class="relative inline-block">
                        <div class="w-32 h-32 mx-auto overflow-hidden bg-gradient-to-br from-green-400 to-green-600 rounded-full">
                            <div class="flex items-center justify-center w-full h-full text-3xl font-bold text-white">
                                MC
                            </div>
                        </div>
                    </div>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">Michael Chen</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Head of Support</p>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-500">
                        Ensuring world-class customer experience
                    </p>
                </div>

                <!-- Team Member 4 -->
                <div class="text-center">
                    <div class="relative inline-block">
                        <div class="w-32 h-32 mx-auto overflow-hidden bg-gradient-to-br from-orange-400 to-orange-600 rounded-full">
                            <div class="flex items-center justify-center w-full h-full text-3xl font-bold text-white">
                                EJ
                            </div>
                        </div>
                    </div>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">Emily Johnson</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Product Manager</p>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-500">
                        Building features that customers love
                    </p>
                </div>
            </div>
        </x-container>
    </section>

    <!-- Office Locations -->
    <section class="py-24 bg-gray-50 dark:bg-gray-900">
        <x-container>
            <div class="max-w-3xl mx-auto mb-16 text-center">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white md:text-4xl">
                    Our Offices
                </h2>
                <p class="mt-4 text-lg text-gray-600 dark:text-gray-300">
                    Visit us or reach out at any of our global locations
                </p>
            </div>

            <div class="grid gap-8 md:grid-cols-3">
                <div class="p-8 bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 rounded-2xl">
                    <div class="flex items-center justify-center w-12 h-12 mb-4 text-white bg-blue-600 rounded-xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">San Francisco, USA</h3>
                    <p class="mt-3 text-gray-600 dark:text-gray-400">
                        123 Tech Street<br>
                        San Francisco, CA 94102<br>
                        United States
                    </p>
                    <p class="mt-4 text-sm font-medium text-blue-600 dark:text-blue-400">
                        Headquarters
                    </p>
                </div>

                <div class="p-8 bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 rounded-2xl">
                    <div class="flex items-center justify-center w-12 h-12 mb-4 text-white bg-purple-600 rounded-xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">London, UK</h3>
                    <p class="mt-3 text-gray-600 dark:text-gray-400">
                        456 Innovation Ave<br>
                        London, EC1A 1BB<br>
                        United Kingdom
                    </p>
                    <p class="mt-4 text-sm font-medium text-purple-600 dark:text-purple-400">
                        European Office
                    </p>
                </div>

                <div class="p-8 bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 rounded-2xl">
                    <div class="flex items-center justify-center w-12 h-12 mb-4 text-white bg-green-600 rounded-xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Singapore</h3>
                    <p class="mt-3 text-gray-600 dark:text-gray-400">
                        789 Marina Boulevard<br>
                        Singapore 018956<br>
                        Singapore
                    </p>
                    <p class="mt-4 text-sm font-medium text-green-600 dark:text-green-400">
                        APAC Office
                    </p>
                </div>
            </div>
        </x-container>
    </section>

    <!-- Careers -->
    <section class="py-24 bg-gradient-to-br from-blue-600 to-purple-600">
        <x-container>
            <div class="max-w-3xl mx-auto text-center">
                <h2 class="text-3xl font-bold text-white md:text-4xl">
                    Join Our Team
                </h2>
                <p class="mt-4 text-lg text-blue-100">
                    We're always looking for talented, passionate people to join our mission. Check out our open positions and be part of something amazing.
                </p>

                <div class="flex flex-col items-center justify-center gap-4 mt-8 sm:flex-row">
                    <x-button href="/careers" tag="a" size="lg" color="secondary" class="px-8 py-4 text-lg">
                        View Open Positions
                    </x-button>
                </div>

                <div class="grid gap-6 mt-12 md:grid-cols-3">
                    <div class="p-6 text-center bg-white/10 backdrop-blur-sm rounded-xl">
                        <div class="text-2xl font-bold text-white">Remote First</div>
                        <div class="mt-2 text-sm text-blue-100">Work from anywhere</div>
                    </div>
                    <div class="p-6 text-center bg-white/10 backdrop-blur-sm rounded-xl">
                        <div class="text-2xl font-bold text-white">Health Benefits</div>
                        <div class="mt-2 text-sm text-blue-100">Comprehensive coverage</div>
                    </div>
                    <div class="p-6 text-center bg-white/10 backdrop-blur-sm rounded-xl">
                        <div class="text-2xl font-bold text-white">Growth</div>
                        <div class="mt-2 text-sm text-blue-100">Career development</div>
                    </div>
                </div>
            </div>
        </x-container>
    </section>

    <!-- Contact CTA -->
    <section class="py-24 bg-white dark:bg-gray-800">
        <x-container>
            <div class="max-w-3xl mx-auto text-center">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white md:text-4xl">
                    Get in Touch
                </h2>
                <p class="mt-4 text-lg text-gray-600 dark:text-gray-300">
                    Have questions? Want to learn more about our company? We'd love to hear from you.
                </p>

                <x-button href="{{ route('contact') }}" tag="a" size="lg" class="mt-8">
                    Contact Us
                </x-button>
            </div>
        </x-container>
    </section>

</x-layouts.marketing>
