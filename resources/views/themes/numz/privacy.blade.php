@extends('theme::layouts.app')

@section('content')
<!-- Hero Section -->
<section class="relative bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800 text-white">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="py-16 text-center">
            <h1 class="text-4xl sm:text-5xl font-bold mb-4">Privacy Policy</h1>
            <p class="text-xl text-blue-100 max-w-3xl mx-auto">
                Your privacy is important to us. Learn how we collect, use, and protect your information.
            </p>
            <p class="text-sm text-blue-200 mt-4">Last Updated: {{ date('F d, Y') }}</p>
        </div>
    </div>
</section>

<!-- Content Section -->
<section class="py-16 bg-gray-50">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto">
            <!-- Quick Summary -->
            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-6 mb-8">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Privacy at a Glance</h2>
                <div class="grid md:grid-cols-3 gap-6">
                    <div class="flex items-start">
                        <svg class="w-6 h-6 text-blue-600 mr-3 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <div>
                            <h3 class="font-semibold text-gray-900 mb-1">We Protect</h3>
                            <p class="text-sm text-gray-700">Your data with industry-standard encryption</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <svg class="w-6 h-6 text-blue-600 mr-3 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <div>
                            <h3 class="font-semibold text-gray-900 mb-1">We Don't Sell</h3>
                            <p class="text-sm text-gray-700">Your personal information to third parties</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <svg class="w-6 h-6 text-blue-600 mr-3 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        <div>
                            <h3 class="font-semibold text-gray-900 mb-1">You Control</h3>
                            <p class="text-sm text-gray-700">Your data with full access and deletion rights</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table of Contents -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Table of Contents</h2>
                <nav class="grid md:grid-cols-2 gap-2">
                    <a href="#information" class="text-blue-600 hover:text-blue-700 hover:underline">1. Information We Collect</a>
                    <a href="#use" class="text-blue-600 hover:text-blue-700 hover:underline">2. How We Use Information</a>
                    <a href="#sharing" class="text-blue-600 hover:text-blue-700 hover:underline">3. Information Sharing</a>
                    <a href="#cookies" class="text-blue-600 hover:text-blue-700 hover:underline">4. Cookies and Tracking</a>
                    <a href="#security" class="text-blue-600 hover:text-blue-700 hover:underline">5. Data Security</a>
                    <a href="#retention" class="text-blue-600 hover:text-blue-700 hover:underline">6. Data Retention</a>
                    <a href="#rights" class="text-blue-600 hover:text-blue-700 hover:underline">7. Your Rights</a>
                    <a href="#children" class="text-blue-600 hover:text-blue-700 hover:underline">8. Children's Privacy</a>
                    <a href="#international" class="text-blue-600 hover:text-blue-700 hover:underline">9. International Transfers</a>
                    <a href="#changes" class="text-blue-600 hover:text-blue-700 hover:underline">10. Policy Changes</a>
                    <a href="#contact" class="text-blue-600 hover:text-blue-700 hover:underline">11. Contact Us</a>
                </nav>
            </div>

            <!-- Privacy Policy Content -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-8 space-y-8">
                    <!-- Section 1 -->
                    <section id="information">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">1. Information We Collect</h2>
                        <div class="prose prose-blue max-w-none">
                            <h3 class="text-lg font-semibold text-gray-900 mt-6 mb-3">Information You Provide</h3>
                            <ul class="list-disc list-inside space-y-2 text-gray-700 ml-4">
                                <li><strong>Account Information:</strong> Name, email address, phone number, billing address</li>
                                <li><strong>Payment Information:</strong> Credit card details, billing information (processed securely through our payment providers)</li>
                                <li><strong>Communication Data:</strong> Support tickets, emails, and other correspondence with us</li>
                                <li><strong>Service Data:</strong> Domain names, website content, database information, email accounts</li>
                            </ul>

                            <h3 class="text-lg font-semibold text-gray-900 mt-6 mb-3">Information Automatically Collected</h3>
                            <ul class="list-disc list-inside space-y-2 text-gray-700 ml-4">
                                <li><strong>Usage Data:</strong> IP address, browser type, device information, operating system</li>
                                <li><strong>Log Data:</strong> Server logs, access times, pages viewed, time spent on pages</li>
                                <li><strong>Cookies:</strong> Session cookies, preference cookies, analytics cookies</li>
                                <li><strong>Performance Data:</strong> Website performance metrics, resource usage statistics</li>
                            </ul>
                        </div>
                    </section>

                    <div class="border-t border-gray-200"></div>

                    <!-- Section 2 -->
                    <section id="use">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">2. How We Use Your Information</h2>
                        <div class="prose prose-blue max-w-none">
                            <p class="text-gray-700 leading-relaxed mb-4">
                                We use the collected information for the following purposes:
                            </p>
                            <ul class="list-disc list-inside space-y-2 text-gray-700 ml-4">
                                <li><strong>Service Provision:</strong> To provide, maintain, and improve our hosting services</li>
                                <li><strong>Account Management:</strong> To create and manage your account, process payments, and send service-related communications</li>
                                <li><strong>Customer Support:</strong> To respond to your inquiries and provide technical support</li>
                                <li><strong>Security:</strong> To detect, prevent, and address fraud, abuse, and security issues</li>
                                <li><strong>Analytics:</strong> To understand how our services are used and improve user experience</li>
                                <li><strong>Communication:</strong> To send important updates, security alerts, and administrative messages</li>
                                <li><strong>Marketing:</strong> To send promotional materials (with your consent, where required)</li>
                                <li><strong>Legal Compliance:</strong> To comply with legal obligations and enforce our terms</li>
                            </ul>
                        </div>
                    </section>

                    <div class="border-t border-gray-200"></div>

                    <!-- Section 3 -->
                    <section id="sharing">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">3. Information Sharing and Disclosure</h2>
                        <div class="prose prose-blue max-w-none">
                            <p class="text-gray-700 leading-relaxed mb-4">
                                We do not sell your personal information. We may share your information in the following circumstances:
                            </p>
                            <h3 class="text-lg font-semibold text-gray-900 mt-6 mb-3">Service Providers</h3>
                            <p class="text-gray-700 leading-relaxed mb-4">
                                We work with third-party service providers who help us operate our business:
                            </p>
                            <ul class="list-disc list-inside space-y-2 text-gray-700 ml-4">
                                <li>Payment processors (Stripe, PayPal) for billing</li>
                                <li>Data center providers for server hosting</li>
                                <li>Email service providers for transactional emails</li>
                                <li>Analytics providers for usage statistics</li>
                                <li>Support software providers for customer service</li>
                            </ul>

                            <h3 class="text-lg font-semibold text-gray-900 mt-6 mb-3">Legal Requirements</h3>
                            <p class="text-gray-700 leading-relaxed mb-4">
                                We may disclose your information if required by law or in response to:
                            </p>
                            <ul class="list-disc list-inside space-y-2 text-gray-700 ml-4">
                                <li>Valid legal processes (subpoenas, court orders)</li>
                                <li>Requests from law enforcement or government agencies</li>
                                <li>Protection of our rights, property, or safety</li>
                                <li>Investigation of fraud or security issues</li>
                            </ul>

                            <h3 class="text-lg font-semibold text-gray-900 mt-6 mb-3">Business Transfers</h3>
                            <p class="text-gray-700 leading-relaxed">
                                If we are involved in a merger, acquisition, or sale of assets, your information may be transferred as part of that transaction.
                            </p>
                        </div>
                    </section>

                    <div class="border-t border-gray-200"></div>

                    <!-- Section 4 -->
                    <section id="cookies">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">4. Cookies and Tracking Technologies</h2>
                        <div class="prose prose-blue max-w-none">
                            <p class="text-gray-700 leading-relaxed mb-4">
                                We use cookies and similar tracking technologies to improve your experience on our website.
                            </p>
                            <h3 class="text-lg font-semibold text-gray-900 mt-6 mb-3">Types of Cookies We Use</h3>
                            <ul class="list-disc list-inside space-y-2 text-gray-700 ml-4">
                                <li><strong>Essential Cookies:</strong> Required for website functionality (login, shopping cart)</li>
                                <li><strong>Preference Cookies:</strong> Remember your settings and preferences</li>
                                <li><strong>Analytics Cookies:</strong> Help us understand how you use our website</li>
                                <li><strong>Marketing Cookies:</strong> Track advertising effectiveness (used only with consent)</li>
                            </ul>
                            <p class="text-gray-700 leading-relaxed mt-4">
                                You can control cookies through your browser settings. Note that disabling cookies may affect website functionality.
                            </p>
                        </div>
                    </section>

                    <div class="border-t border-gray-200"></div>

                    <!-- Section 5 -->
                    <section id="security">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">5. Data Security</h2>
                        <div class="prose prose-blue max-w-none">
                            <p class="text-gray-700 leading-relaxed mb-4">
                                We implement industry-standard security measures to protect your information:
                            </p>
                            <ul class="list-disc list-inside space-y-2 text-gray-700 ml-4">
                                <li><strong>Encryption:</strong> SSL/TLS encryption for data in transit</li>
                                <li><strong>Access Controls:</strong> Restricted access to personal information</li>
                                <li><strong>Secure Infrastructure:</strong> Firewalls, intrusion detection, and monitoring</li>
                                <li><strong>Regular Audits:</strong> Security assessments and vulnerability testing</li>
                                <li><strong>Employee Training:</strong> Staff trained on data protection practices</li>
                            </ul>
                            <p class="text-gray-700 leading-relaxed mt-4">
                                While we strive to protect your information, no method of transmission over the internet is 100% secure. You are responsible for maintaining the confidentiality of your account credentials.
                            </p>
                        </div>
                    </section>

                    <div class="border-t border-gray-200"></div>

                    <!-- Section 6 -->
                    <section id="retention">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">6. Data Retention</h2>
                        <div class="prose prose-blue max-w-none">
                            <p class="text-gray-700 leading-relaxed mb-4">
                                We retain your information for as long as necessary to provide our services and comply with legal obligations:
                            </p>
                            <ul class="list-disc list-inside space-y-2 text-gray-700 ml-4">
                                <li><strong>Account Data:</strong> Retained while your account is active</li>
                                <li><strong>Billing Records:</strong> Kept for 7 years for tax and accounting purposes</li>
                                <li><strong>Support Records:</strong> Retained for 3 years for reference and quality assurance</li>
                                <li><strong>Server Logs:</strong> Typically retained for 90 days</li>
                            </ul>
                            <p class="text-gray-700 leading-relaxed mt-4">
                                After account termination, we will delete or anonymize your data within 30 days unless required to retain it by law.
                            </p>
                        </div>
                    </section>

                    <div class="border-t border-gray-200"></div>

                    <!-- Section 7 -->
                    <section id="rights">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">7. Your Privacy Rights</h2>
                        <div class="prose prose-blue max-w-none">
                            <p class="text-gray-700 leading-relaxed mb-4">
                                Depending on your location, you may have the following rights:
                            </p>
                            <ul class="list-disc list-inside space-y-2 text-gray-700 ml-4">
                                <li><strong>Access:</strong> Request a copy of your personal information</li>
                                <li><strong>Correction:</strong> Request correction of inaccurate data</li>
                                <li><strong>Deletion:</strong> Request deletion of your personal information</li>
                                <li><strong>Portability:</strong> Request your data in a portable format</li>
                                <li><strong>Restriction:</strong> Request limitation of processing</li>
                                <li><strong>Objection:</strong> Object to processing of your data</li>
                                <li><strong>Withdraw Consent:</strong> Withdraw consent for marketing communications</li>
                            </ul>
                            <p class="text-gray-700 leading-relaxed mt-4">
                                To exercise these rights, please contact us at privacy@yourhosting.com. We will respond within 30 days.
                            </p>
                        </div>
                    </section>

                    <div class="border-t border-gray-200"></div>

                    <!-- Section 8 -->
                    <section id="children">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">8. Children's Privacy</h2>
                        <div class="prose prose-blue max-w-none">
                            <p class="text-gray-700 leading-relaxed">
                                Our services are not intended for children under 18. We do not knowingly collect information from children. If you believe we have collected information from a child, please contact us immediately so we can delete it.
                            </p>
                        </div>
                    </section>

                    <div class="border-t border-gray-200"></div>

                    <!-- Section 9 -->
                    <section id="international">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">9. International Data Transfers</h2>
                        <div class="prose prose-blue max-w-none">
                            <p class="text-gray-700 leading-relaxed mb-4">
                                Your information may be transferred to and processed in countries other than your own. We ensure appropriate safeguards are in place:
                            </p>
                            <ul class="list-disc list-inside space-y-2 text-gray-700 ml-4">
                                <li>Standard contractual clauses approved by relevant authorities</li>
                                <li>Data processing agreements with service providers</li>
                                <li>Compliance with applicable data protection laws</li>
                            </ul>
                        </div>
                    </section>

                    <div class="border-t border-gray-200"></div>

                    <!-- Section 10 -->
                    <section id="changes">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">10. Changes to This Policy</h2>
                        <div class="prose prose-blue max-w-none">
                            <p class="text-gray-700 leading-relaxed">
                                We may update this Privacy Policy from time to time. We will notify you of significant changes by email or through a prominent notice on our website. The "Last Updated" date at the top of this page indicates when the policy was last revised.
                            </p>
                        </div>
                    </section>

                    <div class="border-t border-gray-200"></div>

                    <!-- Section 11 -->
                    <section id="contact">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">11. Contact Us</h2>
                        <div class="prose prose-blue max-w-none">
                            <p class="text-gray-700 leading-relaxed mb-4">
                                If you have questions or concerns about this Privacy Policy or our data practices:
                            </p>
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mt-4">
                                <div class="space-y-2 text-gray-700">
                                    <p><strong>Data Protection Officer:</strong> privacy@yourhosting.com</p>
                                    <p><strong>General Inquiries:</strong> support@yourhosting.com</p>
                                    <p><strong>Phone:</strong> +1 (555) 123-4567</p>
                                    <p><strong>Mail:</strong> Privacy Department, 123 Hosting Street, Tech City, TC 12345</p>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <!-- GDPR Notice -->
            <div class="mt-8 bg-gradient-to-r from-purple-50 to-pink-50 border border-purple-200 rounded-xl p-6">
                <div class="flex items-start">
                    <svg class="w-6 h-6 text-purple-600 mt-1 mr-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">GDPR & CCPA Compliance</h3>
                        <p class="text-gray-700 mb-4">
                            We are committed to protecting your privacy rights under GDPR (for EU residents) and CCPA (for California residents). You have the right to access, correct, delete, and export your personal data.
                        </p>
                        <a href="{{ route('dashboard.settings') }}" class="inline-flex items-center text-purple-600 hover:text-purple-700 font-medium">
                            Manage Your Data
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
