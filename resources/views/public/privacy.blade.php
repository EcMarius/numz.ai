<x-layouts.marketing>
    <x-slot name="seo">
        <title>Privacy Policy - {{ setting('site.title', 'Premium Hosting Solutions') }}</title>
        <meta name="description" content="Learn how we collect, use, and protect your personal data. GDPR compliant privacy policy.">
        <meta name="keywords" content="privacy policy, data protection, GDPR, privacy, data security">
        <meta property="og:title" content="Privacy Policy - {{ setting('site.title') }}">
        <meta property="og:description" content="How we protect your privacy and personal data">
        <meta property="og:type" content="website">
    </x-slot>

    <!-- Hero Section -->
    <section class="py-16 bg-gradient-to-br from-blue-50 via-white to-purple-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
        <x-container>
            <div class="max-w-3xl mx-auto text-center">
                <h1 class="text-4xl font-bold tracking-tight text-gray-900 dark:text-white md:text-5xl">
                    Privacy Policy
                </h1>
                <p class="mt-4 text-lg text-gray-600 dark:text-gray-300">
                    Last updated: {{ now()->format('F j, Y') }}
                </p>
                <div class="inline-flex items-center gap-2 px-4 py-2 mt-4 text-sm font-medium text-green-700 bg-green-100 rounded-full dark:bg-green-900/30 dark:text-green-300">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    GDPR Compliant
                </div>
            </div>
        </x-container>
    </section>

    <!-- Content -->
    <section class="py-16 bg-white dark:bg-gray-800">
        <x-container>
            <div class="max-w-4xl mx-auto">
                <div class="p-6 mb-8 border-l-4 border-blue-500 bg-blue-50 dark:bg-blue-900/20">
                    <p class="text-gray-700 dark:text-gray-300">
                        {{ setting('company_name', setting('site.title')) }} is committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our services.
                    </p>
                </div>

                <!-- Table of Contents -->
                <div class="p-8 mb-12 bg-gray-50 dark:bg-gray-900 rounded-2xl">
                    <h2 class="mb-4 text-xl font-semibold text-gray-900 dark:text-white">Table of Contents</h2>
                    <nav class="space-y-2">
                        <a href="#collection" class="block text-blue-600 hover:text-blue-700 dark:text-blue-400">1. Information We Collect</a>
                        <a href="#usage" class="block text-blue-600 hover:text-blue-700 dark:text-blue-400">2. How We Use Your Information</a>
                        <a href="#sharing" class="block text-blue-600 hover:text-blue-700 dark:text-blue-400">3. Information Sharing and Disclosure</a>
                        <a href="#cookies" class="block text-blue-600 hover:text-blue-700 dark:text-blue-400">4. Cookies and Tracking</a>
                        <a href="#security" class="block text-blue-600 hover:text-blue-700 dark:text-blue-400">5. Data Security</a>
                        <a href="#retention" class="block text-blue-600 hover:text-blue-700 dark:text-blue-400">6. Data Retention</a>
                        <a href="#rights" class="block text-blue-600 hover:text-blue-700 dark:text-blue-400">7. Your Data Rights (GDPR)</a>
                        <a href="#third-party" class="block text-blue-600 hover:text-blue-700 dark:text-blue-400">8. Third-Party Services</a>
                        <a href="#children" class="block text-blue-600 hover:text-blue-700 dark:text-blue-400">9. Children's Privacy</a>
                        <a href="#changes" class="block text-blue-600 hover:text-blue-700 dark:text-blue-400">10. Changes to Privacy Policy</a>
                    </nav>
                </div>

                <!-- Privacy Content -->
                <div class="space-y-12 prose prose-lg dark:prose-invert max-w-none">
                    <section id="collection">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">1. Information We Collect</h2>

                        <h3 class="mt-6 text-xl font-semibold text-gray-900 dark:text-white">Personal Information</h3>
                        <p class="mt-2 text-gray-600 dark:text-gray-300">
                            When you register for an account or use our services, we collect the following personal information:
                        </p>
                        <ul class="mt-4 space-y-2 text-gray-600 dark:text-gray-300">
                            <li>• Name and email address</li>
                            <li>• Billing address and payment information</li>
                            <li>• Phone number (optional)</li>
                            <li>• Company name and tax identification (for business accounts)</li>
                            <li>• Account credentials (username and encrypted password)</li>
                        </ul>

                        <h3 class="mt-6 text-xl font-semibold text-gray-900 dark:text-white">Usage Information</h3>
                        <p class="mt-2 text-gray-600 dark:text-gray-300">
                            We automatically collect certain information about your device and how you interact with our services:
                        </p>
                        <ul class="mt-4 space-y-2 text-gray-600 dark:text-gray-300">
                            <li>• IP address and geographic location</li>
                            <li>• Browser type and version</li>
                            <li>• Operating system</li>
                            <li>• Pages visited and features used</li>
                            <li>• Time and date of access</li>
                            <li>• Referring website addresses</li>
                        </ul>

                        <h3 class="mt-6 text-xl font-semibold text-gray-900 dark:text-white">Technical Information</h3>
                        <p class="mt-2 text-gray-600 dark:text-gray-300">
                            For the provision of hosting services, we collect:
                        </p>
                        <ul class="mt-4 space-y-2 text-gray-600 dark:text-gray-300">
                            <li>• Server logs and access logs</li>
                            <li>• Domain names and DNS records</li>
                            <li>• Resource usage statistics</li>
                            <li>• Email traffic logs (for spam prevention)</li>
                        </ul>
                    </section>

                    <section id="usage">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">2. How We Use Your Information</h2>
                        <p class="mt-4 text-gray-600 dark:text-gray-300">
                            We use the collected information for the following purposes:
                        </p>

                        <h3 class="mt-6 text-xl font-semibold text-gray-900 dark:text-white">Service Provision</h3>
                        <ul class="mt-4 space-y-2 text-gray-600 dark:text-gray-300">
                            <li>• To provide, maintain, and improve our hosting services</li>
                            <li>• To process transactions and send related information</li>
                            <li>• To provide customer support and respond to inquiries</li>
                            <li>• To monitor and analyze usage patterns and trends</li>
                        </ul>

                        <h3 class="mt-6 text-xl font-semibold text-gray-900 dark:text-white">Communication</h3>
                        <ul class="mt-4 space-y-2 text-gray-600 dark:text-gray-300">
                            <li>• To send service updates, security alerts, and administrative messages</li>
                            <li>• To respond to your comments and questions</li>
                            <li>• To send marketing communications (with your consent)</li>
                        </ul>

                        <h3 class="mt-6 text-xl font-semibold text-gray-900 dark:text-white">Security and Compliance</h3>
                        <ul class="mt-4 space-y-2 text-gray-600 dark:text-gray-300">
                            <li>• To detect, prevent, and address fraud and security issues</li>
                            <li>• To comply with legal obligations</li>
                            <li>• To enforce our Terms of Service</li>
                        </ul>
                    </section>

                    <section id="sharing">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">3. Information Sharing and Disclosure</h2>
                        <p class="mt-4 text-gray-600 dark:text-gray-300">
                            We do not sell your personal information. We may share your information in the following circumstances:
                        </p>

                        <h3 class="mt-6 text-xl font-semibold text-gray-900 dark:text-white">Service Providers</h3>
                        <p class="mt-2 text-gray-600 dark:text-gray-300">
                            We share information with third-party service providers who perform services on our behalf, such as:
                        </p>
                        <ul class="mt-4 space-y-2 text-gray-600 dark:text-gray-300">
                            <li>• Payment processors (Stripe, PayPal)</li>
                            <li>• Cloud infrastructure providers</li>
                            <li>• Email service providers</li>
                            <li>• Analytics providers</li>
                            <li>• Customer support tools</li>
                        </ul>

                        <h3 class="mt-6 text-xl font-semibold text-gray-900 dark:text-white">Legal Requirements</h3>
                        <p class="mt-2 text-gray-600 dark:text-gray-300">
                            We may disclose your information if required by law or in response to valid requests by public authorities.
                        </p>

                        <h3 class="mt-6 text-xl font-semibold text-gray-900 dark:text-white">Business Transfers</h3>
                        <p class="mt-2 text-gray-600 dark:text-gray-300">
                            In the event of a merger, acquisition, or sale of assets, your information may be transferred to the acquiring entity.
                        </p>
                    </section>

                    <section id="cookies">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">4. Cookies and Tracking Technologies</h2>
                        <p class="mt-4 text-gray-600 dark:text-gray-300">
                            We use cookies and similar tracking technologies to collect and track information about your activities on our services.
                        </p>

                        <h3 class="mt-6 text-xl font-semibold text-gray-900 dark:text-white">Types of Cookies We Use</h3>
                        <ul class="mt-4 space-y-2 text-gray-600 dark:text-gray-300">
                            <li>• <strong>Essential Cookies:</strong> Required for the website to function properly</li>
                            <li>• <strong>Analytics Cookies:</strong> Help us understand how visitors interact with our website</li>
                            <li>• <strong>Preference Cookies:</strong> Remember your settings and preferences</li>
                            <li>• <strong>Marketing Cookies:</strong> Track your browsing habits to show relevant ads (with consent)</li>
                        </ul>

                        <p class="mt-4 text-gray-600 dark:text-gray-300">
                            You can control cookies through your browser settings. However, disabling cookies may affect the functionality of our services.
                        </p>
                    </section>

                    <section id="security">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">5. Data Security</h2>
                        <p class="mt-4 text-gray-600 dark:text-gray-300">
                            We implement appropriate technical and organizational measures to protect your personal information:
                        </p>
                        <ul class="mt-4 space-y-2 text-gray-600 dark:text-gray-300">
                            <li>• SSL/TLS encryption for data in transit</li>
                            <li>• Encryption of sensitive data at rest</li>
                            <li>• Regular security audits and penetration testing</li>
                            <li>• Access controls and authentication measures</li>
                            <li>• Employee training on data protection</li>
                            <li>• Regular backup and disaster recovery procedures</li>
                        </ul>
                        <p class="mt-4 text-gray-600 dark:text-gray-300">
                            While we strive to protect your personal information, no method of transmission over the Internet is 100% secure. We cannot guarantee absolute security.
                        </p>
                    </section>

                    <section id="retention">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">6. Data Retention</h2>
                        <p class="mt-4 text-gray-600 dark:text-gray-300">
                            We retain your personal information for as long as necessary to provide our services and comply with legal obligations:
                        </p>
                        <ul class="mt-4 space-y-2 text-gray-600 dark:text-gray-300">
                            <li>• Account information: Retained while your account is active</li>
                            <li>• Billing records: Retained for 7 years for tax and accounting purposes</li>
                            <li>• Server logs: Retained for 90 days</li>
                            <li>• Backup data: Retained for 30 days after account termination</li>
                        </ul>
                        <p class="mt-4 text-gray-600 dark:text-gray-300">
                            After termination, we will delete or anonymize your personal information unless we are required to retain it by law.
                        </p>
                    </section>

                    <section id="rights">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">7. Your Data Rights (GDPR)</h2>
                        <p class="mt-4 text-gray-600 dark:text-gray-300">
                            Under the General Data Protection Regulation (GDPR), you have the following rights regarding your personal data:
                        </p>

                        <div class="mt-6 space-y-6">
                            <div>
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Right to Access</h3>
                                <p class="mt-2 text-gray-600 dark:text-gray-300">
                                    You have the right to request copies of your personal data.
                                </p>
                            </div>

                            <div>
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Right to Rectification</h3>
                                <p class="mt-2 text-gray-600 dark:text-gray-300">
                                    You have the right to request correction of inaccurate or incomplete personal data.
                                </p>
                            </div>

                            <div>
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Right to Erasure</h3>
                                <p class="mt-2 text-gray-600 dark:text-gray-300">
                                    You have the right to request deletion of your personal data under certain conditions.
                                </p>
                            </div>

                            <div>
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Right to Restrict Processing</h3>
                                <p class="mt-2 text-gray-600 dark:text-gray-300">
                                    You have the right to request restriction of processing of your personal data.
                                </p>
                            </div>

                            <div>
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Right to Data Portability</h3>
                                <p class="mt-2 text-gray-600 dark:text-gray-300">
                                    You have the right to request transfer of your data to another organization or directly to you.
                                </p>
                            </div>

                            <div>
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Right to Object</h3>
                                <p class="mt-2 text-gray-600 dark:text-gray-300">
                                    You have the right to object to processing of your personal data for direct marketing purposes.
                                </p>
                            </div>

                            <div>
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Right to Withdraw Consent</h3>
                                <p class="mt-2 text-gray-600 dark:text-gray-300">
                                    Where we rely on consent to process your personal data, you have the right to withdraw that consent at any time.
                                </p>
                            </div>
                        </div>

                        <div class="p-6 mt-8 border-l-4 border-green-500 bg-green-50 dark:bg-green-900/20">
                            <p class="font-medium text-green-900 dark:text-green-200">
                                To exercise any of these rights, please contact us at <a href="mailto:{{ setting('contact_email', 'privacy@example.com') }}" class="text-green-700 underline dark:text-green-400">{{ setting('contact_email', 'privacy@example.com') }}</a>
                            </p>
                            <p class="mt-2 text-sm text-green-700 dark:text-green-300">
                                We will respond to your request within 30 days.
                            </p>
                        </div>
                    </section>

                    <section id="third-party">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">8. Third-Party Services</h2>
                        <p class="mt-4 text-gray-600 dark:text-gray-300">
                            Our services may contain links to third-party websites and services. We are not responsible for the privacy practices of these third parties.
                        </p>

                        <h3 class="mt-6 text-xl font-semibold text-gray-900 dark:text-white">Third-Party Services We Use</h3>
                        <ul class="mt-4 space-y-2 text-gray-600 dark:text-gray-300">
                            <li>• <strong>Google Analytics:</strong> Website analytics and tracking</li>
                            <li>• <strong>Stripe/PayPal:</strong> Payment processing</li>
                            <li>• <strong>Intercom/Zendesk:</strong> Customer support</li>
                            <li>• <strong>Amazon Web Services:</strong> Cloud infrastructure</li>
                            <li>• <strong>Cloudflare:</strong> CDN and security services</li>
                        </ul>

                        <p class="mt-4 text-gray-600 dark:text-gray-300">
                            Each of these services has their own privacy policy. We encourage you to review their policies.
                        </p>
                    </section>

                    <section id="children">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">9. Children's Privacy</h2>
                        <p class="mt-4 text-gray-600 dark:text-gray-300">
                            Our services are not directed to individuals under the age of 18. We do not knowingly collect personal information from children under 18.
                        </p>
                        <p class="mt-4 text-gray-600 dark:text-gray-300">
                            If you become aware that a child has provided us with personal information, please contact us. If we become aware that we have collected personal information from a child under 18, we will take steps to delete such information.
                        </p>
                    </section>

                    <section id="changes">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">10. Changes to This Privacy Policy</h2>
                        <p class="mt-4 text-gray-600 dark:text-gray-300">
                            We may update this Privacy Policy from time to time. We will notify you of any material changes by:
                        </p>
                        <ul class="mt-4 space-y-2 text-gray-600 dark:text-gray-300">
                            <li>• Posting the new Privacy Policy on this page</li>
                            <li>• Updating the "Last updated" date at the top of this policy</li>
                            <li>• Sending you an email notification (for material changes)</li>
                        </ul>
                        <p class="mt-4 text-gray-600 dark:text-gray-300">
                            We encourage you to review this Privacy Policy periodically for any changes. Your continued use of our services after changes are posted constitutes your acceptance of the updated policy.
                        </p>
                    </section>

                    <!-- International Transfers -->
                    <section class="p-8 mt-12 border border-gray-200 dark:border-gray-700 rounded-2xl">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">International Data Transfers</h2>
                        <p class="mt-4 text-gray-600 dark:text-gray-300">
                            Your information may be transferred to and processed in countries other than your country of residence. These countries may have data protection laws that are different from your country.
                        </p>
                        <p class="mt-4 text-gray-600 dark:text-gray-300">
                            When we transfer personal data from the European Economic Area (EEA) to other countries, we use approved data transfer mechanisms such as Standard Contractual Clauses to ensure appropriate safeguards are in place.
                        </p>
                    </section>

                    <!-- Contact Section -->
                    <section class="p-8 mt-12 border-t border-gray-200 dark:border-gray-700">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Contact Us About Privacy</h2>
                        <p class="mt-4 text-gray-600 dark:text-gray-300">
                            If you have any questions, concerns, or requests regarding this Privacy Policy or our data practices, please contact:
                        </p>

                        <div class="mt-6 space-y-4">
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white">Data Protection Officer</h3>
                                <p class="mt-2 text-gray-600 dark:text-gray-300">
                                    Email: <a href="mailto:{{ setting('contact_email', 'privacy@example.com') }}" class="text-blue-600 hover:text-blue-700 dark:text-blue-400">{{ setting('contact_email', 'privacy@example.com') }}</a><br>
                                    Address: 123 Tech Street, San Francisco, CA 94102, USA<br>
                                    Phone: +1 (800) 555-1234
                                </p>
                            </div>

                            <div class="p-4 border-l-4 border-blue-500 bg-blue-50 dark:bg-blue-900/20">
                                <p class="text-sm text-blue-900 dark:text-blue-200">
                                    <strong>EU Representatives:</strong> If you are located in the European Economic Area, you also have the right to lodge a complaint with your local data protection authority.
                                </p>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Print Button -->
                <div class="flex justify-center mt-12">
                    <button onclick="window.print()" class="px-8 py-3 text-sm font-medium text-white transition bg-blue-600 rounded-lg hover:bg-blue-700">
                        Print Privacy Policy
                    </button>
                </div>
            </div>
        </x-container>
    </section>

</x-layouts.marketing>
