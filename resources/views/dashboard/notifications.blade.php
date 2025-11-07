@extends('dashboard.layouts.app')

@section('title', 'Email & Notifications')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Email & Notifications</h1>
        <p class="text-gray-600">Manage your email preferences and notification settings</p>
    </div>

    <!-- Success Message -->
    @if(session('success'))
    <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-start">
        <svg class="w-5 h-5 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    <form action="{{ route('dashboard.notifications.update') }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Service Notifications -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-gray-200">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                    </svg>
                    <h2 class="text-lg font-semibold text-gray-900">Service Notifications</h2>
                </div>
            </div>
            <div class="p-6 space-y-4">
                <div class="flex items-start justify-between py-3 border-b border-gray-100 last:border-0">
                    <div class="flex-1">
                        <h3 class="text-sm font-medium text-gray-900 mb-1">Service Status Updates</h3>
                        <p class="text-sm text-gray-600">Receive notifications about service status changes, maintenance, and upgrades</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer ml-4">
                        <input type="checkbox" name="notifications[service_status]" value="1" class="sr-only peer" {{ ($settings['service_status'] ?? true) ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>

                <div class="flex items-start justify-between py-3 border-b border-gray-100 last:border-0">
                    <div class="flex-1">
                        <h3 class="text-sm font-medium text-gray-900 mb-1">Service Expiration Reminders</h3>
                        <p class="text-sm text-gray-600">Get reminded 7 days and 1 day before your services expire</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer ml-4">
                        <input type="checkbox" name="notifications[service_expiration]" value="1" class="sr-only peer" {{ ($settings['service_expiration'] ?? true) ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>

                <div class="flex items-start justify-between py-3">
                    <div class="flex-1">
                        <h3 class="text-sm font-medium text-gray-900 mb-1">Renewal Confirmations</h3>
                        <p class="text-sm text-gray-600">Receive confirmation emails when your services are renewed</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer ml-4">
                        <input type="checkbox" name="notifications[service_renewal]" value="1" class="sr-only peer" {{ ($settings['service_renewal'] ?? true) ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>
            </div>
        </div>

        <!-- Billing Notifications -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-green-50 to-emerald-50 px-6 py-4 border-b border-gray-200">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                    <h2 class="text-lg font-semibold text-gray-900">Billing Notifications</h2>
                </div>
            </div>
            <div class="p-6 space-y-4">
                <div class="flex items-start justify-between py-3 border-b border-gray-100 last:border-0">
                    <div class="flex-1">
                        <h3 class="text-sm font-medium text-gray-900 mb-1">New Invoices</h3>
                        <p class="text-sm text-gray-600">Get notified when new invoices are generated</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer ml-4">
                        <input type="checkbox" name="notifications[invoice_new]" value="1" class="sr-only peer" {{ ($settings['invoice_new'] ?? true) ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>

                <div class="flex items-start justify-between py-3 border-b border-gray-100 last:border-0">
                    <div class="flex-1">
                        <h3 class="text-sm font-medium text-gray-900 mb-1">Payment Confirmations</h3>
                        <p class="text-sm text-gray-600">Receive confirmation when payments are processed successfully</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer ml-4">
                        <input type="checkbox" name="notifications[payment_success]" value="1" class="sr-only peer" {{ ($settings['payment_success'] ?? true) ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>

                <div class="flex items-start justify-between py-3 border-b border-gray-100 last:border-0">
                    <div class="flex-1">
                        <h3 class="text-sm font-medium text-gray-900 mb-1">Payment Failed Alerts</h3>
                        <p class="text-sm text-gray-600">Get alerted when a payment attempt fails</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer ml-4">
                        <input type="checkbox" name="notifications[payment_failed]" value="1" class="sr-only peer" {{ ($settings['payment_failed'] ?? true) ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>

                <div class="flex items-start justify-between py-3">
                    <div class="flex-1">
                        <h3 class="text-sm font-medium text-gray-900 mb-1">Credit Balance Updates</h3>
                        <p class="text-sm text-gray-600">Notifications when your credit balance changes</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer ml-4">
                        <input type="checkbox" name="notifications[credit_balance]" value="1" class="sr-only peer" {{ ($settings['credit_balance'] ?? false) ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>
            </div>
        </div>

        <!-- Support Notifications -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-purple-50 to-pink-50 px-6 py-4 border-b border-gray-200">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-purple-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <h2 class="text-lg font-semibold text-gray-900">Support Notifications</h2>
                </div>
            </div>
            <div class="p-6 space-y-4">
                <div class="flex items-start justify-between py-3 border-b border-gray-100 last:border-0">
                    <div class="flex-1">
                        <h3 class="text-sm font-medium text-gray-900 mb-1">Ticket Replies</h3>
                        <p class="text-sm text-gray-600">Get notified when support staff replies to your tickets</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer ml-4">
                        <input type="checkbox" name="notifications[ticket_reply]" value="1" class="sr-only peer" {{ ($settings['ticket_reply'] ?? true) ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>

                <div class="flex items-start justify-between py-3 border-b border-gray-100 last:border-0">
                    <div class="flex-1">
                        <h3 class="text-sm font-medium text-gray-900 mb-1">Ticket Status Changes</h3>
                        <p class="text-sm text-gray-600">Notifications when your ticket status changes</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer ml-4">
                        <input type="checkbox" name="notifications[ticket_status]" value="1" class="sr-only peer" {{ ($settings['ticket_status'] ?? true) ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>

                <div class="flex items-start justify-between py-3">
                    <div class="flex-1">
                        <h3 class="text-sm font-medium text-gray-900 mb-1">Support Announcements</h3>
                        <p class="text-sm text-gray-600">Important announcements from our support team</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer ml-4">
                        <input type="checkbox" name="notifications[announcements]" value="1" class="sr-only peer" {{ ($settings['announcements'] ?? true) ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>
            </div>
        </div>

        <!-- Marketing & Updates -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-orange-50 to-amber-50 px-6 py-4 border-b border-gray-200">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-orange-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                    </svg>
                    <h2 class="text-lg font-semibold text-gray-900">Marketing & Updates</h2>
                </div>
            </div>
            <div class="p-6 space-y-4">
                <div class="flex items-start justify-between py-3 border-b border-gray-100 last:border-0">
                    <div class="flex-1">
                        <h3 class="text-sm font-medium text-gray-900 mb-1">Product Updates & News</h3>
                        <p class="text-sm text-gray-600">Stay updated with new features and product announcements</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer ml-4">
                        <input type="checkbox" name="notifications[product_news]" value="1" class="sr-only peer" {{ ($settings['product_news'] ?? false) ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>

                <div class="flex items-start justify-between py-3 border-b border-gray-100 last:border-0">
                    <div class="flex-1">
                        <h3 class="text-sm font-medium text-gray-900 mb-1">Special Offers & Promotions</h3>
                        <p class="text-sm text-gray-600">Receive emails about exclusive deals and promotions</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer ml-4">
                        <input type="checkbox" name="notifications[promotions]" value="1" class="sr-only peer" {{ ($settings['promotions'] ?? false) ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>

                <div class="flex items-start justify-between py-3">
                    <div class="flex-1">
                        <h3 class="text-sm font-medium text-gray-900 mb-1">Educational Content & Tips</h3>
                        <p class="text-sm text-gray-600">Get helpful tips and tutorials about our services</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer ml-4">
                        <input type="checkbox" name="notifications[educational]" value="1" class="sr-only peer" {{ ($settings['educational'] ?? false) ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>
            </div>
        </div>

        <!-- Email Digest Settings -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-50 to-blue-50 px-6 py-4 border-b border-gray-200">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-indigo-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <h2 class="text-lg font-semibold text-gray-900">Email Digest Settings</h2>
                </div>
            </div>
            <div class="p-6 space-y-4">
                <div class="flex items-start justify-between py-3 border-b border-gray-100 last:border-0">
                    <div class="flex-1">
                        <h3 class="text-sm font-medium text-gray-900 mb-1">Daily Digest</h3>
                        <p class="text-sm text-gray-600">Receive a daily summary of your account activity</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer ml-4">
                        <input type="checkbox" name="notifications[digest_daily]" value="1" class="sr-only peer" {{ ($settings['digest_daily'] ?? false) ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>

                <div class="flex items-start justify-between py-3">
                    <div class="flex-1">
                        <h3 class="text-sm font-medium text-gray-900 mb-1">Weekly Summary</h3>
                        <p class="text-sm text-gray-600">Get a weekly overview of your services and billing</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer ml-4">
                        <input type="checkbox" name="notifications[digest_weekly]" value="1" class="sr-only peer" {{ ($settings['digest_weekly'] ?? false) ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-between pt-6 border-t border-gray-200">
            <button type="button" onclick="window.history.back()" class="px-6 py-2.5 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                Cancel
            </button>
            <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-medium rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-all shadow-lg shadow-blue-500/30">
                Save Preferences
            </button>
        </div>
    </form>
</div>
@endsection
