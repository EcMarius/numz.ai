<x-layouts.marketing>
    <x-slot name="seo">
        <title>Contact Us - {{ setting('site.title', 'Premium Hosting Solutions') }}</title>
        <meta name="description" content="Get in touch with our team. We're here to help with sales questions, technical support, and general inquiries.">
        <meta name="keywords" content="contact, support, help, get in touch">
        <meta property="og:title" content="Contact {{ setting('site.title') }}">
        <meta property="og:description" content="Reach out to our team for assistance">
        <meta property="og:type" content="website">
    </x-slot>

    <!-- Hero Section -->
    <section class="py-24 bg-gradient-to-br from-blue-50 via-white to-purple-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
        <x-container>
            <div class="max-w-3xl mx-auto text-center">
                <h1 class="text-4xl font-bold tracking-tight text-gray-900 dark:text-white md:text-5xl lg:text-6xl">
                    Let's Talk
                </h1>
                <p class="mt-6 text-lg text-gray-600 dark:text-gray-300 md:text-xl">
                    Have a question or need help? Our team is here for you 24/7. Reach out and we'll get back to you as soon as possible.
                </p>
            </div>
        </x-container>
    </section>

    <!-- Contact Methods -->
    <section class="py-16 bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <x-container>
            <div class="grid gap-8 md:grid-cols-3">
                <div class="text-center">
                    <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 text-white bg-blue-600 rounded-full">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Email Us</h3>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        <a href="mailto:{{ setting('contact_email', 'support@example.com') }}" class="text-blue-600 hover:text-blue-700 dark:text-blue-400">
                            {{ setting('contact_email', 'support@example.com') }}
                        </a>
                    </p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-500">
                        We'll respond within 24 hours
                    </p>
                </div>

                <div class="text-center">
                    <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 text-white bg-green-600 rounded-full">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Call Us</h3>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        <a href="tel:+18005551234" class="text-blue-600 hover:text-blue-700 dark:text-blue-400">
                            +1 (800) 555-1234
                        </a>
                    </p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-500">
                        24/7 phone support
                    </p>
                </div>

                <div class="text-center">
                    <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 text-white bg-purple-600 rounded-full">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Live Chat</h3>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        <button class="text-blue-600 hover:text-blue-700 dark:text-blue-400">
                            Start chatting now
                        </button>
                    </p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-500">
                        Average response time: 2 min
                    </p>
                </div>
            </div>
        </x-container>
    </section>

    <!-- Contact Form & Info -->
    <section class="py-24 bg-white dark:bg-gray-800">
        <x-container>
            <div class="grid gap-12 lg:grid-cols-2">
                <!-- Contact Form -->
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white">
                        Send Us a Message
                    </h2>
                    <p class="mt-2 text-gray-600 dark:text-gray-300">
                        Fill out the form below and we'll get back to you as soon as possible.
                    </p>

                    <form class="mt-8 space-y-6" x-data="{ name: '', email: '', subject: '', message: '', submitting: false }">
                        <div class="grid gap-6 md:grid-cols-2">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Your Name <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="name"
                                    x-model="name"
                                    required
                                    class="block w-full px-4 py-3 mt-1 text-gray-900 border border-gray-300 rounded-lg dark:bg-gray-900 dark:text-white dark:border-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="John Doe"
                                >
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Email Address <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="email"
                                    id="email"
                                    x-model="email"
                                    required
                                    class="block w-full px-4 py-3 mt-1 text-gray-900 border border-gray-300 rounded-lg dark:bg-gray-900 dark:text-white dark:border-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="john@example.com"
                                >
                            </div>
                        </div>

                        <div>
                            <label for="subject" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Subject <span class="text-red-500">*</span>
                            </label>
                            <select
                                id="subject"
                                x-model="subject"
                                required
                                class="block w-full px-4 py-3 mt-1 text-gray-900 border border-gray-300 rounded-lg dark:bg-gray-900 dark:text-white dark:border-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                                <option value="">Select a subject</option>
                                <option value="sales">Sales Inquiry</option>
                                <option value="support">Technical Support</option>
                                <option value="billing">Billing Question</option>
                                <option value="partnership">Partnership Opportunity</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div>
                            <label for="message" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Message <span class="text-red-500">*</span>
                            </label>
                            <textarea
                                id="message"
                                x-model="message"
                                required
                                rows="6"
                                class="block w-full px-4 py-3 mt-1 text-gray-900 border border-gray-300 rounded-lg dark:bg-gray-900 dark:text-white dark:border-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Tell us how we can help..."
                            ></textarea>
                        </div>

                        <div>
                            <button
                                type="submit"
                                :disabled="submitting"
                                class="flex items-center justify-center w-full px-8 py-4 text-lg font-semibold text-white transition bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span x-show="!submitting">Send Message</span>
                                <span x-show="submitting" class="flex items-center">
                                    <svg class="w-5 h-5 mr-3 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Sending...
                                </span>
                            </button>
                        </div>

                        <p class="text-sm text-gray-500 dark:text-gray-500">
                            By submitting this form, you agree to our <a href="/privacy" class="text-blue-600 hover:text-blue-700">Privacy Policy</a>.
                        </p>
                    </form>
                </div>

                <!-- Contact Information -->
                <div>
                    <div class="sticky top-8 space-y-8">
                        <!-- Office Hours -->
                        <div class="p-8 bg-gray-50 dark:bg-gray-900 rounded-2xl">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Business Hours</h3>
                            <div class="mt-4 space-y-2 text-gray-600 dark:text-gray-400">
                                <div class="flex justify-between">
                                    <span>Monday - Friday:</span>
                                    <span class="font-medium">9:00 AM - 6:00 PM PST</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Saturday:</span>
                                    <span class="font-medium">10:00 AM - 4:00 PM PST</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Sunday:</span>
                                    <span class="font-medium">Closed</span>
                                </div>
                            </div>
                            <div class="p-4 mt-4 border-l-4 border-blue-500 bg-blue-50 dark:bg-blue-900/20">
                                <p class="text-sm font-medium text-blue-900 dark:text-blue-200">
                                    24/7 Support Available
                                </p>
                                <p class="mt-1 text-sm text-blue-700 dark:text-blue-300">
                                    Emergency support is available around the clock via phone and live chat.
                                </p>
                            </div>
                        </div>

                        <!-- Office Locations -->
                        <div class="p-8 bg-gray-50 dark:bg-gray-900 rounded-2xl">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Office Locations</h3>
                            <div class="mt-4 space-y-6 text-gray-600 dark:text-gray-400">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">San Francisco (HQ)</p>
                                    <p class="mt-1 text-sm">
                                        123 Tech Street<br>
                                        San Francisco, CA 94102<br>
                                        United States
                                    </p>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">London</p>
                                    <p class="mt-1 text-sm">
                                        456 Innovation Ave<br>
                                        London, EC1A 1BB<br>
                                        United Kingdom
                                    </p>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">Singapore</p>
                                    <p class="mt-1 text-sm">
                                        789 Marina Boulevard<br>
                                        Singapore 018956<br>
                                        Singapore
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Social Media -->
                        <div class="p-8 bg-gray-50 dark:bg-gray-900 rounded-2xl">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Follow Us</h3>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                Stay connected on social media for updates and tips
                            </p>
                            <div class="flex gap-4 mt-4">
                                @if(setting('social_twitter'))
                                <a href="{{ setting('social_twitter') }}" target="_blank" class="flex items-center justify-center w-10 h-10 text-white transition bg-blue-400 rounded-full hover:bg-blue-500">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84"></path>
                                    </svg>
                                </a>
                                @endif

                                @if(setting('social_facebook'))
                                <a href="{{ setting('social_facebook') }}" target="_blank" class="flex items-center justify-center w-10 h-10 text-white transition bg-blue-600 rounded-full hover:bg-blue-700">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd"></path>
                                    </svg>
                                </a>
                                @endif

                                @if(setting('social_linkedin'))
                                <a href="{{ setting('social_linkedin') }}" target="_blank" class="flex items-center justify-center w-10 h-10 text-white transition bg-blue-700 rounded-full hover:bg-blue-800">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path fill-rule="evenodd" d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z" clip-rule="evenodd"></path>
                                    </svg>
                                </a>
                                @endif

                                @if(setting('social_github'))
                                <a href="{{ setting('social_github') }}" target="_blank" class="flex items-center justify-center w-10 h-10 text-white transition bg-gray-800 rounded-full hover:bg-gray-900">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd"></path>
                                    </svg>
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </x-container>
    </section>

    <!-- FAQ Section -->
    <section class="py-24 bg-gray-50 dark:bg-gray-900">
        <x-container>
            <div class="max-w-3xl mx-auto">
                <h2 class="mb-4 text-3xl font-bold text-center text-gray-900 dark:text-white">
                    Quick Answers
                </h2>
                <p class="mb-12 text-center text-gray-600 dark:text-gray-300">
                    Common questions before contacting us
                </p>

                <div class="space-y-4" x-data="{ openFaq: null }">
                    <div class="overflow-hidden bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 rounded-xl">
                        <button
                            @click="openFaq = openFaq === 1 ? null : 1"
                            class="flex items-center justify-between w-full px-6 py-5 text-left"
                        >
                            <span class="font-semibold text-gray-900 dark:text-white">What's the best way to get technical support?</span>
                            <svg class="w-5 h-5 text-gray-500 transition-transform" :class="{ 'rotate-180': openFaq === 1 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div x-show="openFaq === 1" x-collapse x-cloak>
                            <div class="px-6 pb-5 text-gray-600 dark:text-gray-400">
                                For urgent technical issues, we recommend using our live chat for the fastest response. You can also call our 24/7 support line or create a support ticket from your dashboard.
                            </div>
                        </div>
                    </div>

                    <div class="overflow-hidden bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 rounded-xl">
                        <button
                            @click="openFaq = openFaq === 2 ? null : 2"
                            class="flex items-center justify-between w-full px-6 py-5 text-left"
                        >
                            <span class="font-semibold text-gray-900 dark:text-white">How quickly do you respond to inquiries?</span>
                            <svg class="w-5 h-5 text-gray-500 transition-transform" :class="{ 'rotate-180': openFaq === 2 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div x-show="openFaq === 2" x-collapse x-cloak>
                            <div class="px-6 pb-5 text-gray-600 dark:text-gray-400">
                                Our average response time is under 2 minutes for live chat, within 1 hour for email, and immediate for phone calls during business hours. We aim to respond to all inquiries within 24 hours maximum.
                            </div>
                        </div>
                    </div>

                    <div class="overflow-hidden bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 rounded-xl">
                        <button
                            @click="openFaq = openFaq === 3 ? null : 3"
                            class="flex items-center justify-between w-full px-6 py-5 text-left"
                        >
                            <span class="font-semibold text-gray-900 dark:text-white">Can I schedule a demo or consultation?</span>
                            <svg class="w-5 h-5 text-gray-500 transition-transform" :class="{ 'rotate-180': openFaq === 3 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div x-show="openFaq === 3" x-collapse x-cloak>
                            <div class="px-6 pb-5 text-gray-600 dark:text-gray-400">
                                Absolutely! Contact our sales team through this form or call us directly to schedule a personalized demo. We're happy to walk you through our platform and answer any questions.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </x-container>
    </section>

</x-layouts.marketing>
