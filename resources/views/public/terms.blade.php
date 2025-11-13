<x-layouts.marketing>
    <x-slot name="seo">
        <title>Terms of Service - {{ setting('site.title', 'Premium Hosting Solutions') }}</title>
        <meta name="description" content="Read our terms of service to understand your rights and responsibilities when using our hosting services.">
        <meta name="keywords" content="terms of service, terms and conditions, legal, agreement">
        <meta property="og:title" content="Terms of Service - {{ setting('site.title') }}">
        <meta property="og:description" content="Terms and conditions for using our services">
        <meta property="og:type" content="website">
    </x-slot>

    <!-- Hero Section -->
    <section class="py-16 bg-gradient-to-br from-blue-50 via-white to-purple-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
        <x-container>
            <div class="max-w-3xl mx-auto text-center">
                <h1 class="text-4xl font-bold tracking-tight text-gray-900 dark:text-white md:text-5xl">
                    Terms of Service
                </h1>
                <p class="mt-4 text-lg text-gray-600 dark:text-gray-300">
                    Last updated: {{ now()->format('F j, Y') }}
                </p>
            </div>
        </x-container>
    </section>

    <!-- Content -->
    <section class="py-16 bg-white dark:bg-gray-800">
        <x-container>
            <div class="max-w-4xl mx-auto">
                <div class="p-6 mb-8 border-l-4 border-blue-500 bg-blue-50 dark:bg-blue-900/20">
                    <p class="text-gray-700 dark:text-gray-300">
                        Please read these Terms of Service carefully before using our services. By accessing or using our services, you agree to be bound by these terms.
                    </p>
                </div>

                <!-- Table of Contents -->
                <div class="p-8 mb-12 bg-gray-50 dark:bg-gray-900 rounded-2xl">
                    <h2 class="mb-4 text-xl font-semibold text-gray-900 dark:text-white">Table of Contents</h2>
                    <nav class="space-y-2">
                        <a href="#agreement" class="block text-blue-600 hover:text-blue-700 dark:text-blue-400">1. Agreement to Terms</a>
                        <a href="#services" class="block text-blue-600 hover:text-blue-700 dark:text-blue-400">2. Use of Services</a>
                        <a href="#accounts" class="block text-blue-600 hover:text-blue-700 dark:text-blue-400">3. User Accounts</a>
                        <a href="#payments" class="block text-blue-600 hover:text-blue-700 dark:text-blue-400">4. Payments and Billing</a>
                        <a href="#refunds" class="block text-blue-600 hover:text-blue-700 dark:text-blue-400">5. Refund Policy</a>
                        <a href="#prohibited" class="block text-blue-600 hover:text-blue-700 dark:text-blue-400">6. Prohibited Activities</a>
                        <a href="#content" class="block text-blue-600 hover:text-blue-700 dark:text-blue-400">7. User Content</a>
                        <a href="#termination" class="block text-blue-600 hover:text-blue-700 dark:text-blue-400">8. Termination</a>
                        <a href="#liability" class="block text-blue-600 hover:text-blue-700 dark:text-blue-400">9. Limitation of Liability</a>
                        <a href="#changes" class="block text-blue-600 hover:text-blue-700 dark:text-blue-400">10. Changes to Terms</a>
                    </nav>
                </div>

                <!-- Terms Content -->
                <div class="space-y-12 prose prose-lg dark:prose-invert max-w-none">
                    <section id="agreement">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">1. Agreement to Terms</h2>
                        <p class="mt-4 text-gray-600 dark:text-gray-300">
                            By accessing and using {{ setting('site.title', 'our services') }}, you accept and agree to be bound by the terms and provision of this agreement. If you do not agree to abide by the above, please do not use this service.
                        </p>
                        <p class="mt-4 text-gray-600 dark:text-gray-300">
                            These Terms of Service constitute a legally binding agreement between you and {{ setting('company_name', setting('site.title')) }} regarding your use of our hosting services, websites, and applications.
                        </p>
                    </section>

                    <section id="services">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">2. Use of Services</h2>
                        <p class="mt-4 text-gray-600 dark:text-gray-300">
                            Our services are provided for lawful purposes only. You agree to use our services in compliance with all applicable local, state, national, and international laws and regulations.
                        </p>
                        <p class="mt-4 text-gray-600 dark:text-gray-300">
                            We reserve the right to modify, suspend, or discontinue any aspect of our services at any time, with or without notice.
                        </p>
                    </section>

                    <section id="accounts">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">3. User Accounts</h2>
                        <p class="mt-4 text-gray-600 dark:text-gray-300">
                            When you create an account with us, you must provide accurate, complete, and current information. Failure to do so constitutes a breach of these Terms.
                        </p>
                        <p class="mt-4 text-gray-600 dark:text-gray-300">
                            You are responsible for safeguarding the password and for all activities that occur under your account. You agree to notify us immediately of any unauthorized use of your account.
                        </p>
                        <ul class="mt-4 space-y-2 text-gray-600 dark:text-gray-300">
                            <li>• You must be at least 18 years old to create an account</li>
                            <li>• One person or entity may maintain only one account</li>
                            <li>• You must not share your account credentials with others</li>
                            <li>• You are responsible for all activities under your account</li>
                        </ul>
                    </section>

                    <section id="payments">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">4. Payments and Billing</h2>
                        <p class="mt-4 text-gray-600 dark:text-gray-300">
                            All fees are exclusive of applicable taxes unless stated otherwise. You are responsible for payment of all applicable taxes. We will charge tax when required to do so.
                        </p>
                        <h3 class="mt-6 text-xl font-semibold text-gray-900 dark:text-white">Billing Cycles</h3>
                        <p class="mt-2 text-gray-600 dark:text-gray-300">
                            Services are billed on a subscription basis (monthly or annually). You will be charged in advance for each billing cycle on the calendar day corresponding to the commencement of your subscription.
                        </p>
                        <h3 class="mt-6 text-xl font-semibold text-gray-900 dark:text-white">Automatic Renewal</h3>
                        <p class="mt-2 text-gray-600 dark:text-gray-300">
                            Your subscription will automatically renew at the end of each billing cycle unless you cancel before the renewal date. We will charge your payment method on file for the renewal.
                        </p>
                        <h3 class="mt-6 text-xl font-semibold text-gray-900 dark:text-white">Failed Payments</h3>
                        <p class="mt-2 text-gray-600 dark:text-gray-300">
                            If a payment fails, we will attempt to charge your payment method up to three times. If payment continues to fail, your service may be suspended or terminated.
                        </p>
                    </section>

                    <section id="refunds">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">5. Refund Policy</h2>
                        <p class="mt-4 text-gray-600 dark:text-gray-300">
                            We offer a 30-day money-back guarantee on all hosting plans. If you're not satisfied with our service, you may request a full refund within 30 days of your initial purchase.
                        </p>
                        <h3 class="mt-6 text-xl font-semibold text-gray-900 dark:text-white">Refund Conditions</h3>
                        <ul class="mt-4 space-y-2 text-gray-600 dark:text-gray-300">
                            <li>• Refunds are only available for the initial purchase of hosting plans</li>
                            <li>• Renewals and upgrades are not eligible for refunds</li>
                            <li>• Domain registrations are non-refundable</li>
                            <li>• Add-on services purchased separately are non-refundable</li>
                            <li>• Refunds will be processed within 5-10 business days</li>
                        </ul>
                    </section>

                    <section id="prohibited">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">6. Prohibited Activities</h2>
                        <p class="mt-4 text-gray-600 dark:text-gray-300">
                            You may not use our services to engage in any of the following prohibited activities:
                        </p>
                        <ul class="mt-4 space-y-2 text-gray-600 dark:text-gray-300">
                            <li>• Violating laws or regulations</li>
                            <li>• Distributing malware, viruses, or malicious code</li>
                            <li>• Engaging in fraudulent activities</li>
                            <li>• Hosting illegal content or pirated materials</li>
                            <li>• Sending spam or unsolicited emails</li>
                            <li>• Mining cryptocurrency without prior written approval</li>
                            <li>• Using excessive server resources that impact other users</li>
                            <li>• Attempting to gain unauthorized access to other accounts or systems</li>
                            <li>• Hosting adult content without proper age verification</li>
                            <li>• Engaging in phishing or identity theft</li>
                        </ul>
                        <p class="mt-4 text-gray-600 dark:text-gray-300">
                            Violation of these terms may result in immediate suspension or termination of your account without refund.
                        </p>
                    </section>

                    <section id="content">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">7. User Content</h2>
                        <p class="mt-4 text-gray-600 dark:text-gray-300">
                            You retain all rights to the content you upload, post, or display on or through our services ("User Content"). By uploading User Content, you grant us a worldwide, non-exclusive, royalty-free license to host, store, and display your content solely for the purpose of providing our services.
                        </p>
                        <h3 class="mt-6 text-xl font-semibold text-gray-900 dark:text-white">Content Responsibility</h3>
                        <p class="mt-2 text-gray-600 dark:text-gray-300">
                            You are solely responsible for your User Content and the consequences of posting or publishing it. We do not endorse any User Content or any opinion, recommendation, or advice expressed therein.
                        </p>
                        <h3 class="mt-6 text-xl font-semibold text-gray-900 dark:text-white">Backups</h3>
                        <p class="mt-2 text-gray-600 dark:text-gray-300">
                            While we provide automated backup services, you are responsible for maintaining your own backups of your User Content. We are not responsible for any loss or corruption of your data.
                        </p>
                    </section>

                    <section id="termination">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">8. Termination</h2>
                        <p class="mt-4 text-gray-600 dark:text-gray-300">
                            Either party may terminate this agreement at any time. You may cancel your account at any time through your account settings or by contacting our support team.
                        </p>
                        <h3 class="mt-6 text-xl font-semibold text-gray-900 dark:text-white">Termination by Us</h3>
                        <p class="mt-2 text-gray-600 dark:text-gray-300">
                            We may terminate or suspend your account immediately, without prior notice, if you breach these Terms. Upon termination, your right to use the services will immediately cease.
                        </p>
                        <h3 class="mt-6 text-xl font-semibold text-gray-900 dark:text-white">Data After Termination</h3>
                        <p class="mt-2 text-gray-600 dark:text-gray-300">
                            Upon termination, we will retain your data for 30 days, after which it will be permanently deleted. We recommend downloading all your data before canceling your account.
                        </p>
                    </section>

                    <section id="liability">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">9. Limitation of Liability</h2>
                        <p class="mt-4 text-gray-600 dark:text-gray-300">
                            To the maximum extent permitted by applicable law, {{ setting('company_name', setting('site.title')) }} shall not be liable for any indirect, incidental, special, consequential, or punitive damages, or any loss of profits or revenues, whether incurred directly or indirectly, or any loss of data, use, goodwill, or other intangible losses.
                        </p>
                        <h3 class="mt-6 text-xl font-semibold text-gray-900 dark:text-white">Service Availability</h3>
                        <p class="mt-2 text-gray-600 dark:text-gray-300">
                            While we strive to provide 99.9% uptime, we do not guarantee uninterrupted access to our services. We are not liable for any downtime, data loss, or damages resulting from service interruptions.
                        </p>
                    </section>

                    <section id="changes">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">10. Changes to Terms</h2>
                        <p class="mt-4 text-gray-600 dark:text-gray-300">
                            We reserve the right to modify these Terms at any time. If we make material changes, we will notify you by email or through a notice on our website prior to the changes taking effect.
                        </p>
                        <p class="mt-4 text-gray-600 dark:text-gray-300">
                            Your continued use of our services after such modifications constitutes your acceptance of the updated Terms.
                        </p>
                    </section>

                    <!-- Contact Section -->
                    <section class="p-8 mt-12 border-t border-gray-200 dark:border-gray-700">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Contact Us</h2>
                        <p class="mt-4 text-gray-600 dark:text-gray-300">
                            If you have any questions about these Terms of Service, please contact us:
                        </p>
                        <ul class="mt-4 space-y-2 text-gray-600 dark:text-gray-300">
                            <li>• Email: <a href="mailto:{{ setting('contact_email', 'legal@example.com') }}" class="text-blue-600 hover:text-blue-700 dark:text-blue-400">{{ setting('contact_email', 'legal@example.com') }}</a></li>
                            <li>• Address: 123 Tech Street, San Francisco, CA 94102</li>
                            <li>• Phone: +1 (800) 555-1234</li>
                        </ul>
                    </section>
                </div>

                <!-- Print Button -->
                <div class="flex justify-center mt-12">
                    <button onclick="window.print()" class="px-8 py-3 text-sm font-medium text-white transition bg-blue-600 rounded-lg hover:bg-blue-700">
                        Print Terms
                    </button>
                </div>
            </div>
        </x-container>
    </section>

</x-layouts.marketing>
