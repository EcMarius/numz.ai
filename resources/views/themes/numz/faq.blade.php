@extends('theme::layouts.app')

@section('content')
<!-- Hero Section -->
<section class="relative bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800 text-white">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="py-16 text-center">
            <h1 class="text-4xl sm:text-5xl font-bold mb-4">Frequently Asked Questions</h1>
            <p class="text-xl text-blue-100 max-w-3xl mx-auto">
                Find answers to common questions about our hosting services
            </p>
        </div>
    </div>
</section>

<!-- Search Section -->
<section class="py-8 bg-white border-b border-gray-200">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl mx-auto">
            <div class="relative">
                <input type="text" id="faqSearch" placeholder="Search FAQ..." class="w-full px-6 py-4 pr-14 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <svg class="absolute right-4 top-1/2 transform -translate-y-1/2 w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Content -->
<section class="py-16 bg-gray-50">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto">
            <!-- General Questions -->
            <div class="mb-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                    <svg class="w-7 h-7 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    General Questions
                </h2>
                <div class="space-y-4">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden" x-data="{ open: false }">
                        <button @click="open = !open" class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors">
                            <span class="font-semibold text-gray-900">What types of hosting do you offer?</span>
                            <svg class="w-5 h-5 text-gray-500 transform transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="open" x-collapse class="px-6 pb-4">
                            <p class="text-gray-700 leading-relaxed">We offer shared hosting, VPS hosting, and dedicated servers. Shared hosting is perfect for small websites and blogs, VPS provides more resources and control, while dedicated servers offer maximum performance for high-traffic sites.</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden" x-data="{ open: false }">
                        <button @click="open = !open" class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors">
                            <span class="font-semibold text-gray-900">Do you offer a money-back guarantee?</span>
                            <svg class="w-5 h-5 text-gray-500 transform transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="open" x-collapse class="px-6 pb-4">
                            <p class="text-gray-700 leading-relaxed">Yes! We offer a 30-day money-back guarantee on all shared hosting plans. If you're not satisfied within the first 30 days, we'll refund your hosting fee (domain registration fees are non-refundable).</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden" x-data="{ open: false }">
                        <button @click="open = !open" class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors">
                            <span class="font-semibold text-gray-900">Can I upgrade my plan later?</span>
                            <svg class="w-5 h-5 text-gray-500 transform transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="open" x-collapse class="px-6 pb-4">
                            <p class="text-gray-700 leading-relaxed">Absolutely! You can upgrade your hosting plan at any time from your dashboard. You'll only pay the prorated difference for the remaining billing period.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Technical Questions -->
            <div class="mb-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                    <svg class="w-7 h-7 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Technical Questions
                </h2>
                <div class="space-y-4">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden" x-data="{ open: false }">
                        <button @click="open = !open" class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors">
                            <span class="font-semibold text-gray-900">What PHP version do you support?</span>
                            <svg class="w-5 h-5 text-gray-500 transform transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="open" x-collapse class="px-6 pb-4">
                            <p class="text-gray-700 leading-relaxed">We support PHP 7.4, 8.0, 8.1, 8.2, and 8.3. You can switch between PHP versions instantly from your control panel.</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden" x-data="{ open: false }">
                        <button @click="open = !open" class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors">
                            <span class="font-semibold text-gray-900">Do you provide SSH access?</span>
                            <svg class="w-5 h-5 text-gray-500 transform transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="open" x-collapse class="px-6 pb-4">
                            <p class="text-gray-700 leading-relaxed">SSH access is available on VPS and Dedicated Server plans. Shared hosting customers can request SSH access by opening a support ticket.</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden" x-data="{ open: false }">
                        <button @click="open = !open" class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors">
                            <span class="font-semibold text-gray-900">Can I install WordPress or other applications?</span>
                            <svg class="w-5 h-5 text-gray-500 transform transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="open" x-collapse class="px-6 pb-4">
                            <p class="text-gray-700 leading-relaxed">Yes! We offer one-click installation for WordPress, Joomla, Drupal, and 100+ other popular applications through Softaculous in your control panel.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Billing & Account -->
            <div class="mb-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                    <svg class="w-7 h-7 text-purple-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                    Billing & Account
                </h2>
                <div class="space-y-4">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden" x-data="{ open: false }">
                        <button @click="open = !open" class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors">
                            <span class="font-semibold text-gray-900">What payment methods do you accept?</span>
                            <svg class="w-5 h-5 text-gray-500 transform transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="open" x-collapse class="px-6 pb-4">
                            <p class="text-gray-700 leading-relaxed">We accept all major credit cards (Visa, MasterCard, American Express), PayPal, and bank transfers for annual payments.</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden" x-data="{ open: false }">
                        <button @click="open = !open" class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors">
                            <span class="font-semibold text-gray-900">Can I cancel my hosting anytime?</span>
                            <svg class="w-5 h-5 text-gray-500 transform transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="open" x-collapse class="px-6 pb-4">
                            <p class="text-gray-700 leading-relaxed">Yes, you can cancel your services at any time. For monthly plans, you can cancel without penalty. Annual plans are refundable within 30 days.</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden" x-data="{ open: false }">
                        <button @click="open = !open" class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors">
                            <span class="font-semibold text-gray-900">Do you offer discounts for annual billing?</span>
                            <svg class="w-5 h-5 text-gray-500 transform transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="open" x-collapse class="px-6 pb-4">
                            <p class="text-gray-700 leading-relaxed">Yes! Annual billing saves you approximately 20% compared to monthly payments. You'll see the discounted price when selecting annual billing during checkout.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Support -->
            <div>
                <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                    <svg class="w-7 h-7 text-orange-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    Support
                </h2>
                <div class="space-y-4">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden" x-data="{ open: false }">
                        <button @click="open = !open" class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors">
                            <span class="font-semibold text-gray-900">What support do you provide?</span>
                            <svg class="w-5 h-5 text-gray-500 transform transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="open" x-collapse class="px-6 pb-4">
                            <p class="text-gray-700 leading-relaxed">We provide 24/7 support via live chat, email, and ticket system. Our expert team is available around the clock to help with hosting-related issues.</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden" x-data="{ open: false }">
                        <button @click="open = !open" class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors">
                            <span class="font-semibold text-gray-900">How quickly do you respond to support requests?</span>
                            <svg class="w-5 h-5 text-gray-500 transform transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="open" x-collapse class="px-6 pb-4">
                            <p class="text-gray-700 leading-relaxed">Our average response time is under 15 minutes for live chat and under 2 hours for support tickets. Emergency issues are prioritized and handled immediately.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Still Have Questions CTA -->
<section class="py-16 bg-white">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl shadow-xl p-8 md:p-12 text-white text-center">
            <svg class="w-16 h-16 mx-auto mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
            <h2 class="text-3xl font-bold mb-4">Still Have Questions?</h2>
            <p class="text-xl text-blue-100 mb-8">
                Our support team is here to help 24/7. Don't hesitate to reach out!
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

@push('scripts')
<script>
// Simple FAQ search
document.getElementById('faqSearch').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const faqItems = document.querySelectorAll('[x-data]');

    faqItems.forEach(item => {
        const text = item.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
});
</script>
@endpush
@endsection
