@extends('theme::layouts.app')

@section('content')
<!-- Hero Section -->
<section class="bg-gradient-to-br from-blue-600 to-indigo-700 text-white py-20">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto">
            <h1 class="text-4xl sm:text-5xl font-bold mb-6">
                Get in Touch
            </h1>
            <p class="text-xl text-blue-100">
                Have questions? We're here to help. Reach out to our team anytime.
            </p>
        </div>
    </div>
</section>

<!-- Contact Section -->
<section class="py-20 bg-gray-50">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-3 gap-12 max-w-7xl mx-auto">
            <!-- Contact Form -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-lg p-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Send us a Message</h2>
                    <form action="/contact" method="POST" class="space-y-6">
                        @csrf
                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                                <input type="text" id="name" name="name" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors">
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                                <input type="email" id="email" name="email" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors">
                            </div>
                        </div>

                        <div>
                            <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">Subject *</label>
                            <input type="text" id="subject" name="subject" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors">
                        </div>

                        <div>
                            <label for="department" class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                            <select id="department" name="department"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors">
                                <option value="general">General Inquiry</option>
                                <option value="sales">Sales</option>
                                <option value="support">Technical Support</option>
                                <option value="billing">Billing</option>
                            </select>
                        </div>

                        <div>
                            <label for="message" class="block text-sm font-medium text-gray-700 mb-2">Message *</label>
                            <textarea id="message" name="message" rows="6" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"></textarea>
                        </div>

                        <div class="flex items-start">
                            <input type="checkbox" id="agree" name="agree" required
                                class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="agree" class="ml-3 text-sm text-gray-600">
                                I agree to the <a href="/privacy" class="text-blue-600 hover:text-blue-700">Privacy Policy</a> and <a href="/terms" class="text-blue-600 hover:text-blue-700">Terms of Service</a>
                            </label>
                        </div>

                        <button type="submit"
                            class="w-full px-8 py-4 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all">
                            Send Message
                        </button>
                    </form>
                </div>
            </div>

            <!-- Contact Info -->
            <div class="space-y-6">
                <!-- Support -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">24/7 Support</h3>
                    <p class="text-gray-600 text-sm mb-4">Get help anytime, anywhere</p>
                    <a href="/support" class="text-blue-600 hover:text-blue-700 font-medium text-sm inline-flex items-center">
                        Open Ticket
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>

                <!-- Email -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Email Us</h3>
                    <p class="text-gray-600 text-sm mb-4">We'll respond within 24 hours</p>
                    <a href="mailto:support@numz.ai" class="text-blue-600 hover:text-blue-700 font-medium text-sm">
                        support@numz.ai
                    </a>
                </div>

                <!-- Live Chat -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Live Chat</h3>
                    <p class="text-gray-600 text-sm mb-4">Chat with our team now</p>
                    <button onclick="openChat()" class="text-blue-600 hover:text-blue-700 font-medium text-sm inline-flex items-center">
                        Start Chat
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </div>

                <!-- Knowledge Base -->
                <div class="bg-gradient-to-br from-blue-600 to-indigo-700 text-white rounded-2xl shadow-lg p-6">
                    <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold mb-2">Knowledge Base</h3>
                    <p class="text-blue-100 text-sm mb-4">Find answers instantly</p>
                    <a href="/docs" class="text-white hover:text-blue-100 font-medium text-sm inline-flex items-center">
                        Browse Articles
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="py-20 bg-white">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto">
            <h2 class="text-3xl font-bold text-center text-gray-900 mb-4">
                Quick Answers
            </h2>
            <p class="text-center text-gray-600 mb-12">
                Common questions we receive from our customers
            </p>
            <div class="space-y-4">
                <details class="group bg-gray-50 rounded-lg p-6">
                    <summary class="flex items-center justify-between cursor-pointer font-semibold text-gray-900">
                        How quickly will I get a response?
                        <svg class="w-5 h-5 text-gray-500 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </summary>
                    <p class="mt-4 text-gray-600">
                        We aim to respond to all inquiries within 24 hours. Priority support customers typically receive responses within 2-4 hours.
                    </p>
                </details>

                <details class="group bg-gray-50 rounded-lg p-6">
                    <summary class="flex items-center justify-between cursor-pointer font-semibold text-gray-900">
                        Do you offer phone support?
                        <svg class="w-5 h-5 text-gray-500 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </summary>
                    <p class="mt-4 text-gray-600">
                        Phone support is available for Business and Enterprise plan customers. Contact us to get your dedicated support line.
                    </p>
                </details>

                <details class="group bg-gray-50 rounded-lg p-6">
                    <summary class="flex items-center justify-between cursor-pointer font-semibold text-gray-900">
                        Can I schedule a demo or consultation?
                        <svg class="w-5 h-5 text-gray-500 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </summary>
                    <p class="mt-4 text-gray-600">
                        Absolutely! Select "Sales" from the department dropdown above and mention your preferred time in the message.
                    </p>
                </details>
            </div>
        </div>
    </div>
</section>
@endsection
