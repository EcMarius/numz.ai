@extends('theme::layouts.app')

@section('content')
<!-- Hero Section -->
<section class="relative bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800 text-white">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="py-16 text-center">
            <h1 class="text-4xl sm:text-5xl font-bold mb-4">Terms of Service</h1>
            <p class="text-xl text-blue-100 max-w-3xl mx-auto">
                Please read these terms carefully before using our services
            </p>
            <p class="text-sm text-blue-200 mt-4">Last Updated: {{ date('F d, Y') }}</p>
        </div>
    </div>
</section>

<!-- Content Section -->
<section class="py-16 bg-gray-50">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto">
            <!-- Table of Contents -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Table of Contents</h2>
                <nav class="space-y-2">
                    <a href="#acceptance" class="block text-blue-600 hover:text-blue-700 hover:underline">1. Acceptance of Terms</a>
                    <a href="#services" class="block text-blue-600 hover:text-blue-700 hover:underline">2. Description of Services</a>
                    <a href="#account" class="block text-blue-600 hover:text-blue-700 hover:underline">3. Account Registration</a>
                    <a href="#use" class="block text-blue-600 hover:text-blue-700 hover:underline">4. Acceptable Use Policy</a>
                    <a href="#payment" class="block text-blue-600 hover:text-blue-700 hover:underline">5. Payment and Billing</a>
                    <a href="#uptime" class="block text-blue-600 hover:text-blue-700 hover:underline">6. Service Availability</a>
                    <a href="#data" class="block text-blue-600 hover:text-blue-700 hover:underline">7. Data and Backup</a>
                    <a href="#intellectual" class="block text-blue-600 hover:text-blue-700 hover:underline">8. Intellectual Property</a>
                    <a href="#termination" class="block text-blue-600 hover:text-blue-700 hover:underline">9. Termination</a>
                    <a href="#limitation" class="block text-blue-600 hover:text-blue-700 hover:underline">10. Limitation of Liability</a>
                    <a href="#changes" class="block text-blue-600 hover:text-blue-700 hover:underline">11. Changes to Terms</a>
                    <a href="#contact" class="block text-blue-600 hover:text-blue-700 hover:underline">12. Contact Information</a>
                </nav>
            </div>

            <!-- Terms Content -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-8 space-y-8">
                    <!-- Section 1 -->
                    <section id="acceptance">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">1. Acceptance of Terms</h2>
                        <div class="prose prose-blue max-w-none">
                            <p class="text-gray-700 leading-relaxed mb-4">
                                By accessing or using our hosting services, you agree to be bound by these Terms of Service and all applicable laws and regulations. If you do not agree with any part of these terms, you may not use our services.
                            </p>
                            <p class="text-gray-700 leading-relaxed">
                                These terms apply to all users of the service, including without limitation users who are browsers, vendors, customers, merchants, and/or contributors of content.
                            </p>
                        </div>
                    </section>

                    <div class="border-t border-gray-200"></div>

                    <!-- Section 2 -->
                    <section id="services">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">2. Description of Services</h2>
                        <div class="prose prose-blue max-w-none">
                            <p class="text-gray-700 leading-relaxed mb-4">
                                We provide web hosting services including, but not limited to:
                            </p>
                            <ul class="list-disc list-inside space-y-2 text-gray-700 ml-4">
                                <li>Shared hosting services</li>
                                <li>VPS (Virtual Private Server) hosting</li>
                                <li>Dedicated server hosting</li>
                                <li>Domain registration and management</li>
                                <li>Email hosting services</li>
                                <li>SSL certificates</li>
                                <li>Technical support</li>
                            </ul>
                            <p class="text-gray-700 leading-relaxed mt-4">
                                The specific features and resources available to you depend on your selected hosting plan. We reserve the right to modify, suspend, or discontinue any aspect of our services at any time.
                            </p>
                        </div>
                    </section>

                    <div class="border-t border-gray-200"></div>

                    <!-- Section 3 -->
                    <section id="account">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">3. Account Registration</h2>
                        <div class="prose prose-blue max-w-none">
                            <p class="text-gray-700 leading-relaxed mb-4">
                                To use our services, you must register for an account by providing accurate and complete information. You are responsible for:
                            </p>
                            <ul class="list-disc list-inside space-y-2 text-gray-700 ml-4">
                                <li>Maintaining the confidentiality of your account credentials</li>
                                <li>All activities that occur under your account</li>
                                <li>Notifying us immediately of any unauthorized use</li>
                                <li>Ensuring your contact information is current and accurate</li>
                            </ul>
                            <p class="text-gray-700 leading-relaxed mt-4">
                                You must be at least 18 years old to create an account. Accounts registered by automated methods or bots are not permitted.
                            </p>
                        </div>
                    </section>

                    <div class="border-t border-gray-200"></div>

                    <!-- Section 4 -->
                    <section id="use">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">4. Acceptable Use Policy</h2>
                        <div class="prose prose-blue max-w-none">
                            <p class="text-gray-700 leading-relaxed mb-4">
                                You agree not to use our services for any unlawful purpose or in any way that could damage, disable, overburden, or impair our servers or networks. Prohibited activities include, but are not limited to:
                            </p>
                            <ul class="list-disc list-inside space-y-2 text-gray-700 ml-4">
                                <li>Hosting or distributing malware, viruses, or malicious code</li>
                                <li>Engaging in spamming or sending unsolicited bulk emails</li>
                                <li>Hosting adult content, illegal materials, or copyrighted content without authorization</li>
                                <li>Running cryptocurrency mining operations</li>
                                <li>Conducting DDoS attacks or port scanning</li>
                                <li>Hosting phishing or fraudulent websites</li>
                                <li>Consuming excessive server resources that impact other users</li>
                                <li>Reselling services without explicit authorization</li>
                            </ul>
                            <p class="text-gray-700 leading-relaxed mt-4">
                                Violation of this policy may result in immediate suspension or termination of your account without refund.
                            </p>
                        </div>
                    </section>

                    <div class="border-t border-gray-200"></div>

                    <!-- Section 5 -->
                    <section id="payment">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">5. Payment and Billing</h2>
                        <div class="prose prose-blue max-w-none">
                            <p class="text-gray-700 leading-relaxed mb-4">
                                All fees are stated in US dollars unless otherwise specified. You agree to pay all applicable fees for the services you select.
                            </p>
                            <h3 class="text-lg font-semibold text-gray-900 mt-6 mb-3">Billing Cycles</h3>
                            <p class="text-gray-700 leading-relaxed mb-4">
                                Services are billed on a recurring basis according to your selected billing cycle (monthly, quarterly, annually). Payments are due at the beginning of each billing cycle.
                            </p>
                            <h3 class="text-lg font-semibold text-gray-900 mt-6 mb-3">Refund Policy</h3>
                            <p class="text-gray-700 leading-relaxed mb-4">
                                We offer a 30-day money-back guarantee for first-time customers on shared hosting plans. Refunds are not available for:
                            </p>
                            <ul class="list-disc list-inside space-y-2 text-gray-700 ml-4">
                                <li>Domain registrations or transfers</li>
                                <li>Setup fees or administrative charges</li>
                                <li>Renewals after the initial term</li>
                                <li>VPS or dedicated server services</li>
                                <li>Accounts terminated for policy violations</li>
                            </ul>
                            <h3 class="text-lg font-semibold text-gray-900 mt-6 mb-3">Late Payments</h3>
                            <p class="text-gray-700 leading-relaxed">
                                Failure to pay by the due date may result in service suspension. Accounts suspended for non-payment for more than 30 days may be terminated, and all data may be permanently deleted.
                            </p>
                        </div>
                    </section>

                    <div class="border-t border-gray-200"></div>

                    <!-- Section 6 -->
                    <section id="uptime">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">6. Service Availability</h2>
                        <div class="prose prose-blue max-w-none">
                            <p class="text-gray-700 leading-relaxed mb-4">
                                We strive to maintain 99.9% uptime for our services. However, we do not guarantee uninterrupted or error-free service. Scheduled maintenance will be announced in advance when possible.
                            </p>
                            <p class="text-gray-700 leading-relaxed">
                                Downtime caused by factors beyond our reasonable control, including DDoS attacks, network failures, or force majeure events, are excluded from uptime calculations.
                            </p>
                        </div>
                    </section>

                    <div class="border-t border-gray-200"></div>

                    <!-- Section 7 -->
                    <section id="data">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">7. Data and Backup</h2>
                        <div class="prose prose-blue max-w-none">
                            <p class="text-gray-700 leading-relaxed mb-4">
                                While we perform regular backups of our systems, you are solely responsible for maintaining your own backups of all data and content. We are not responsible for any data loss or corruption.
                            </p>
                            <p class="text-gray-700 leading-relaxed">
                                Backup services, if offered, are provided as a courtesy and do not guarantee data recovery in all circumstances.
                            </p>
                        </div>
                    </section>

                    <div class="border-t border-gray-200"></div>

                    <!-- Section 8 -->
                    <section id="intellectual">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">8. Intellectual Property</h2>
                        <div class="prose prose-blue max-w-none">
                            <p class="text-gray-700 leading-relaxed mb-4">
                                You retain all rights to content you upload to our servers. However, you grant us a license to store, display, and transmit your content as necessary to provide our services.
                            </p>
                            <p class="text-gray-700 leading-relaxed">
                                Our service, including all software, design elements, and branding, is protected by intellectual property laws. You may not copy, modify, or reverse engineer any part of our service.
                            </p>
                        </div>
                    </section>

                    <div class="border-t border-gray-200"></div>

                    <!-- Section 9 -->
                    <section id="termination">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">9. Termination</h2>
                        <div class="prose prose-blue max-w-none">
                            <p class="text-gray-700 leading-relaxed mb-4">
                                Either party may terminate the service relationship at any time:
                            </p>
                            <ul class="list-disc list-inside space-y-2 text-gray-700 ml-4">
                                <li><strong>By You:</strong> You may cancel your services at any time through your account panel</li>
                                <li><strong>By Us:</strong> We may suspend or terminate your account for violation of these terms, non-payment, or other reasonable cause</li>
                            </ul>
                            <p class="text-gray-700 leading-relaxed mt-4">
                                Upon termination, you must immediately cease using our services. We will retain your data for 30 days after termination, after which it will be permanently deleted.
                            </p>
                        </div>
                    </section>

                    <div class="border-t border-gray-200"></div>

                    <!-- Section 10 -->
                    <section id="limitation">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">10. Limitation of Liability</h2>
                        <div class="prose prose-blue max-w-none">
                            <p class="text-gray-700 leading-relaxed mb-4">
                                TO THE MAXIMUM EXTENT PERMITTED BY LAW, WE SHALL NOT BE LIABLE FOR ANY INDIRECT, INCIDENTAL, SPECIAL, CONSEQUENTIAL, OR PUNITIVE DAMAGES, OR ANY LOSS OF PROFITS OR REVENUES, WHETHER INCURRED DIRECTLY OR INDIRECTLY, OR ANY LOSS OF DATA, USE, GOODWILL, OR OTHER INTANGIBLE LOSSES.
                            </p>
                            <p class="text-gray-700 leading-relaxed">
                                Our total liability to you for any claims arising from or related to our services shall not exceed the amount you paid us in the 12 months preceding the claim.
                            </p>
                        </div>
                    </section>

                    <div class="border-t border-gray-200"></div>

                    <!-- Section 11 -->
                    <section id="changes">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">11. Changes to Terms</h2>
                        <div class="prose prose-blue max-w-none">
                            <p class="text-gray-700 leading-relaxed mb-4">
                                We reserve the right to modify these terms at any time. We will notify you of significant changes via email or through your account dashboard.
                            </p>
                            <p class="text-gray-700 leading-relaxed">
                                Your continued use of our services after changes take effect constitutes acceptance of the revised terms.
                            </p>
                        </div>
                    </section>

                    <div class="border-t border-gray-200"></div>

                    <!-- Section 12 -->
                    <section id="contact">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">12. Contact Information</h2>
                        <div class="prose prose-blue max-w-none">
                            <p class="text-gray-700 leading-relaxed mb-4">
                                If you have any questions about these Terms of Service, please contact us:
                            </p>
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mt-4">
                                <div class="space-y-2 text-gray-700">
                                    <p><strong>Email:</strong> legal@yourhosting.com</p>
                                    <p><strong>Phone:</strong> +1 (555) 123-4567</p>
                                    <p><strong>Address:</strong> 123 Hosting Street, Tech City, TC 12345</p>
                                    <p><strong>Business Hours:</strong> Monday - Friday, 9:00 AM - 6:00 PM EST</p>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <!-- Additional Information -->
            <div class="mt-8 bg-blue-50 border border-blue-200 rounded-xl p-6">
                <div class="flex items-start">
                    <svg class="w-6 h-6 text-blue-600 mt-1 mr-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Need Help Understanding These Terms?</h3>
                        <p class="text-gray-700 mb-4">
                            Our support team is here to help answer any questions you may have about our Terms of Service.
                        </p>
                        <a href="{{ route('contact') }}" class="inline-flex items-center text-blue-600 hover:text-blue-700 font-medium">
                            Contact Support
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
