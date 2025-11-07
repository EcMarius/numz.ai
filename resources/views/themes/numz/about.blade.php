@extends('theme::layouts.app')

@section('content')
<!-- Hero Section -->
<section class="relative bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800 text-white overflow-hidden">
    <!-- Background Pattern -->
    <div class="absolute inset-0 opacity-10">
        <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'1\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
    </div>

    <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative">
        <div class="py-20 text-center">
            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold mb-6">About Us</h1>
            <p class="text-xl text-blue-100 max-w-3xl mx-auto">
                Empowering businesses and individuals with reliable, fast, and secure web hosting solutions since 2010.
            </p>
        </div>
    </div>
</section>

<!-- Our Story Section -->
<section class="py-20 bg-white">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto">
            <div class="text-center mb-12">
                <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">Our Story</h2>
                <div class="w-20 h-1 bg-gradient-to-r from-blue-600 to-indigo-600 mx-auto"></div>
            </div>

            <div class="prose prose-lg max-w-none">
                <p class="text-gray-700 leading-relaxed mb-6">
                    Founded in 2010, we started with a simple mission: to provide reliable and affordable web hosting that anyone could use, regardless of technical expertise. What began as a small team of passionate technologists has grown into a trusted hosting provider serving over 10,000 customers worldwide.
                </p>
                <p class="text-gray-700 leading-relaxed mb-6">
                    We believe that everyone deserves access to fast, secure, and reliable web hosting. Whether you're launching your first blog, building an online store, or running a mission-critical business application, we're here to provide the infrastructure and support you need to succeed.
                </p>
                <p class="text-gray-700 leading-relaxed">
                    Today, we continue to invest in cutting-edge technology, world-class infrastructure, and exceptional customer support to deliver the best hosting experience possible.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Values Section -->
<section class="py-20 bg-gray-50">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">Our Values</h2>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                The principles that guide everything we do
            </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-6xl mx-auto">
            <!-- Value 1 -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 hover:shadow-lg transition-shadow">
                <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3">Performance</h3>
                <p class="text-gray-600 leading-relaxed">
                    We invest in premium infrastructure and optimization to ensure your websites load fast and perform reliably under any load.
                </p>
            </div>

            <!-- Value 2 -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 hover:shadow-lg transition-shadow">
                <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3">Security</h3>
                <p class="text-gray-600 leading-relaxed">
                    Your data security is our top priority. We implement industry-leading security measures to keep your websites and data safe.
                </p>
            </div>

            <!-- Value 3 -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 hover:shadow-lg transition-shadow">
                <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-pink-600 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3">Support</h3>
                <p class="text-gray-600 leading-relaxed">
                    Our expert support team is available 24/7 to help you with any questions or issues. We're always here when you need us.
                </p>
            </div>

            <!-- Value 4 -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 hover:shadow-lg transition-shadow">
                <div class="w-14 h-14 bg-gradient-to-br from-orange-500 to-red-600 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3">Transparency</h3>
                <p class="text-gray-600 leading-relaxed">
                    No hidden fees, no surprises. We believe in honest pricing and clear communication about our services and policies.
                </p>
            </div>

            <!-- Value 5 -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 hover:shadow-lg transition-shadow">
                <div class="w-14 h-14 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3">Innovation</h3>
                <p class="text-gray-600 leading-relaxed">
                    We constantly explore new technologies and improve our services to give you the best hosting experience possible.
                </p>
            </div>

            <!-- Value 6 -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 hover:shadow-lg transition-shadow">
                <div class="w-14 h-14 bg-gradient-to-br from-yellow-500 to-orange-600 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3">Community</h3>
                <p class="text-gray-600 leading-relaxed">
                    We're committed to giving back to the web development community through open-source contributions and education.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Team Section -->
<section class="py-20 bg-white">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">Meet Our Team</h2>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                The people behind your hosting experience
            </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8 max-w-6xl mx-auto">
            <!-- Team Member 1 -->
            <div class="text-center">
                <div class="w-32 h-32 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full mx-auto mb-4 flex items-center justify-center">
                    <span class="text-4xl font-bold text-white">JD</span>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-1">John Doe</h3>
                <p class="text-sm text-blue-600 mb-2">CEO & Founder</p>
                <p class="text-sm text-gray-600">Leading the vision and strategy</p>
            </div>

            <!-- Team Member 2 -->
            <div class="text-center">
                <div class="w-32 h-32 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full mx-auto mb-4 flex items-center justify-center">
                    <span class="text-4xl font-bold text-white">JS</span>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-1">Jane Smith</h3>
                <p class="text-sm text-blue-600 mb-2">CTO</p>
                <p class="text-sm text-gray-600">Overseeing technical operations</p>
            </div>

            <!-- Team Member 3 -->
            <div class="text-center">
                <div class="w-32 h-32 bg-gradient-to-br from-purple-500 to-pink-600 rounded-full mx-auto mb-4 flex items-center justify-center">
                    <span class="text-4xl font-bold text-white">MB</span>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-1">Mike Brown</h3>
                <p class="text-sm text-blue-600 mb-2">Head of Support</p>
                <p class="text-sm text-gray-600">Ensuring customer satisfaction</p>
            </div>

            <!-- Team Member 4 -->
            <div class="text-center">
                <div class="w-32 h-32 bg-gradient-to-br from-orange-500 to-red-600 rounded-full mx-auto mb-4 flex items-center justify-center">
                    <span class="text-4xl font-bold text-white">SD</span>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-1">Sarah Davis</h3>
                <p class="text-sm text-blue-600 mb-2">Director of Operations</p>
                <p class="text-sm text-gray-600">Managing daily operations</p>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="py-20 bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800 text-white">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl sm:text-4xl font-bold mb-4">By The Numbers</h2>
            <p class="text-xl text-blue-100 max-w-3xl mx-auto">
                Our commitment to excellence, measured
            </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8 max-w-6xl mx-auto">
            <!-- Stat 1 -->
            <div class="text-center">
                <div class="text-5xl font-bold mb-2">10K+</div>
                <div class="text-blue-200 text-lg">Happy Customers</div>
            </div>

            <!-- Stat 2 -->
            <div class="text-center">
                <div class="text-5xl font-bold mb-2">99.9%</div>
                <div class="text-blue-200 text-lg">Uptime Guarantee</div>
            </div>

            <!-- Stat 3 -->
            <div class="text-center">
                <div class="text-5xl font-bold mb-2">24/7</div>
                <div class="text-blue-200 text-lg">Expert Support</div>
            </div>

            <!-- Stat 4 -->
            <div class="text-center">
                <div class="text-5xl font-bold mb-2">15+</div>
                <div class="text-blue-200 text-lg">Years Experience</div>
            </div>
        </div>
    </div>
</section>

<!-- Infrastructure Section -->
<section class="py-20 bg-gray-50">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto">
            <div class="text-center mb-12">
                <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">Our Infrastructure</h2>
                <p class="text-xl text-gray-600">
                    Enterprise-grade technology powering your websites
                </p>
            </div>

            <div class="grid md:grid-cols-2 gap-8">
                <!-- Infrastructure Item 1 -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 mb-2">Premium Servers</h3>
                            <p class="text-gray-600">Latest generation hardware with NVMe SSD storage for blazing fast performance.</p>
                        </div>
                    </div>
                </div>

                <!-- Infrastructure Item 2 -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 mb-2">Global CDN</h3>
                            <p class="text-gray-600">Content delivery network with 50+ locations worldwide for optimal speed.</p>
                        </div>
                    </div>
                </div>

                <!-- Infrastructure Item 3 -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 mb-2">DDoS Protection</h3>
                            <p class="text-gray-600">Advanced security measures to protect your websites from attacks.</p>
                        </div>
                    </div>
                </div>

                <!-- Infrastructure Item 4 -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 mb-2">Automated Backups</h3>
                            <p class="text-gray-600">Daily automated backups to keep your data safe and recoverable.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-20 bg-white">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto text-center">
            <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-6">Ready to Get Started?</h2>
            <p class="text-xl text-gray-600 mb-8">
                Join thousands of satisfied customers and experience the difference of premium hosting.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-8 py-4 text-lg font-semibold text-white bg-gradient-to-r from-blue-600 to-indigo-600 rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-all shadow-lg hover:shadow-xl">
                    Get Started Free
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </a>
                <a href="{{ route('contact') }}" class="inline-flex items-center justify-center px-8 py-4 text-lg font-semibold text-gray-700 bg-white border-2 border-gray-300 rounded-lg hover:bg-gray-50 transition-all">
                    Contact Sales
                </a>
            </div>
        </div>
    </div>
</section>
@endsection
