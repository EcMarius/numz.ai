@extends('dashboard.layouts.app')

@section('title', 'Profile Settings')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-2">Profile Settings</h1>
    <p class="text-gray-600">Manage your account settings and preferences</p>
</div>

<div class="grid lg:grid-cols-4 gap-8">
    <!-- Sidebar Navigation -->
    <div class="lg:col-span-1">
        <nav class="space-y-1" x-data="{ active: '{{ request('tab', 'profile') }}' }">
            <a href="?tab=profile"
               :class="active === 'profile' ? 'bg-blue-50 border-blue-500 text-blue-700' : 'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
               class="group border-l-4 px-3 py-2 flex items-center text-sm font-medium">
                <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                Personal Information
            </a>
            <a href="?tab=password"
               :class="active === 'password' ? 'bg-blue-50 border-blue-500 text-blue-700' : 'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
               class="group border-l-4 px-3 py-2 flex items-center text-sm font-medium">
                <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
                Password & Security
            </a>
            <a href="?tab=2fa"
               :class="active === '2fa' ? 'bg-blue-50 border-blue-500 text-blue-700' : 'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
               class="group border-l-4 px-3 py-2 flex items-center text-sm font-medium">
                <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                Two-Factor Authentication
            </a>
            <a href="?tab=api"
               :class="active === 'api' ? 'bg-blue-50 border-blue-500 text-blue-700' : 'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
               class="group border-l-4 px-3 py-2 flex items-center text-sm font-medium">
                <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                </svg>
                API Keys
            </a>
            <a href="?tab=notifications"
               :class="active === 'notifications' ? 'bg-blue-50 border-blue-500 text-blue-700' : 'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
               class="group border-l-4 px-3 py-2 flex items-center text-sm font-medium">
                <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                Notifications
            </a>
            <a href="?tab=activity"
               :class="active === 'activity' ? 'bg-blue-50 border-blue-500 text-blue-700' : 'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
               class="group border-l-4 px-3 py-2 flex items-center text-sm font-medium">
                <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Activity Log
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="lg:col-span-3">
        @if(request('tab', 'profile') === 'profile')
        <!-- Personal Information -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Personal Information</h2>
            </div>
            <form class="p-6 space-y-6">
                @csrf
                <!-- Avatar -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Profile Photo</label>
                    <div class="flex items-center space-x-6">
                        <div class="w-24 h-24 bg-blue-600 rounded-full flex items-center justify-center text-white text-3xl font-bold">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </div>
                        <div>
                            <button type="button" class="px-4 py-2 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors">
                                Change Photo
                            </button>
                            <p class="text-xs text-gray-500 mt-2">JPG, GIF or PNG. Max size of 2MB</p>
                        </div>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                        <input type="text" value="{{ auth()->user()->name }}"
                               class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                        <input type="email" value="{{ auth()->user()->email }}"
                               class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Company Name</label>
                        <input type="text" value="{{ auth()->user()->company_name }}"
                               class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Country</label>
                        <select class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            <option>Select country</option>
                            <option selected>United States</option>
                            <option>Canada</option>
                            <option>United Kingdom</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                    <button type="button" class="px-4 py-2 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>

        @elseif(request('tab') === 'password')
        <!-- Password & Security -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Change Password</h2>
            </div>
            <form class="p-6 space-y-6">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                    <input type="password"
                           class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                    <input type="password"
                           class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Must be at least 8 characters with letters, numbers and symbols</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                    <input type="password"
                           class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                    <button type="button" class="px-4 py-2 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                        Update Password
                    </button>
                </div>
            </form>
        </div>

        @elseif(request('tab') === '2fa')
        <!-- Two-Factor Authentication -->
        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Two-Factor Authentication</h2>
                </div>
                <div class="p-6">
                    <div class="flex items-start justify-between mb-6">
                        <div class="flex-1">
                            <h3 class="text-sm font-medium text-gray-900 mb-1">Authentication App (Recommended)</h3>
                            <p class="text-sm text-gray-600">Use an authentication app to generate verification codes</p>
                        </div>
                        <button type="button" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                            Enable 2FA
                        </button>
                    </div>

                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex">
                            <svg class="h-5 w-5 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="ml-3">
                                <h4 class="text-sm font-medium text-blue-900">Why enable 2FA?</h4>
                                <p class="text-sm text-blue-800 mt-1">
                                    Two-factor authentication adds an extra layer of security to your account by requiring a verification code in addition to your password.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Backup Codes -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Backup Codes</h2>
                </div>
                <div class="p-6">
                    <p class="text-sm text-gray-600 mb-4">
                        Recovery codes can be used to access your account in the event you lose access to your device and cannot receive two-factor authentication codes.
                    </p>
                    <button type="button" class="px-4 py-2 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors">
                        Generate Backup Codes
                    </button>
                </div>
            </div>
        </div>

        @elseif(request('tab') === 'api')
        <!-- API Keys -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">API Keys</h2>
                    <p class="text-sm text-gray-600 mt-1">Manage your API keys for integrations</p>
                </div>
                <button type="button" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    Create New Key
                </button>
            </div>
            <div class="divide-y divide-gray-200">
                @forelse($api_keys ?? [] as $key)
                <div class="p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3 mb-2">
                                <h3 class="text-sm font-medium text-gray-900">{{ $key['name'] }}</h3>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                    Active
                                </span>
                            </div>
                            <p class="text-sm text-gray-600 mb-3">Created {{ $key['created_at'] }}</p>
                            <div class="flex items-center space-x-2">
                                <code class="text-sm bg-gray-100 px-3 py-1.5 rounded font-mono">{{ $key['key'] }}</code>
                                <button onclick="copyApiKey('{{ $key['key'] }}')"
                                        class="p-1.5 text-gray-600 hover:text-gray-900 rounded hover:bg-gray-100">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <button type="button" class="text-red-600 hover:text-red-700 text-sm font-medium">
                            Revoke
                        </button>
                    </div>
                </div>
                @empty
                <div class="p-12 text-center">
                    <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                    </svg>
                    <h3 class="text-sm font-medium text-gray-900 mb-1">No API Keys</h3>
                    <p class="text-sm text-gray-600">Create your first API key to get started</p>
                </div>
                @endforelse
            </div>
        </div>

        @elseif(request('tab') === 'notifications')
        <!-- Notification Preferences -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Email Notifications</h2>
            </div>
            <div class="divide-y divide-gray-200">
                <div class="p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h3 class="text-sm font-medium text-gray-900">Service Updates</h3>
                            <p class="text-sm text-gray-600 mt-1">Get notified when services are created, suspended, or terminated</p>
                        </div>
                        <button type="button" class="relative inline-flex h-6 w-11 items-center rounded-full bg-blue-600">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white translate-x-6"></span>
                        </button>
                    </div>
                </div>
                <div class="p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h3 class="text-sm font-medium text-gray-900">Invoice Notifications</h3>
                            <p class="text-sm text-gray-600 mt-1">Receive alerts about new invoices and payment due dates</p>
                        </div>
                        <button type="button" class="relative inline-flex h-6 w-11 items-center rounded-full bg-blue-600">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white translate-x-6"></span>
                        </button>
                    </div>
                </div>
                <div class="p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h3 class="text-sm font-medium text-gray-900">Support Ticket Updates</h3>
                            <p class="text-sm text-gray-600 mt-1">Get notified when support staff replies to your tickets</p>
                        </div>
                        <button type="button" class="relative inline-flex h-6 w-11 items-center rounded-full bg-blue-600">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white translate-x-6"></span>
                        </button>
                    </div>
                </div>
                <div class="p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h3 class="text-sm font-medium text-gray-900">Domain Expiration</h3>
                            <p class="text-sm text-gray-600 mt-1">Receive reminders before your domains expire</p>
                        </div>
                        <button type="button" class="relative inline-flex h-6 w-11 items-center rounded-full bg-blue-600">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white translate-x-6"></span>
                        </button>
                    </div>
                </div>
                <div class="p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h3 class="text-sm font-medium text-gray-900">Marketing Emails</h3>
                            <p class="text-sm text-gray-600 mt-1">Receive promotional offers and product updates</p>
                        </div>
                        <button type="button" class="relative inline-flex h-6 w-11 items-center rounded-full bg-gray-200">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white translate-x-1"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        @elseif(request('tab') === 'activity')
        <!-- Activity Log -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Recent Activity</h2>
            </div>
            <div class="divide-y divide-gray-200">
                @forelse($activity_log ?? [] as $log)
                <div class="p-6">
                    <div class="flex items-start space-x-4">
                        <div class="w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900">{{ $log['action'] }}</p>
                            <p class="text-sm text-gray-600 mt-1">{{ $log['description'] }}</p>
                            <p class="text-xs text-gray-500 mt-2">{{ $log['timestamp'] }}</p>
                        </div>
                    </div>
                </div>
                @empty
                <div class="p-12 text-center">
                    <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm text-gray-600">No recent activity</p>
                </div>
                @endforelse
            </div>
        </div>
        @endif
    </div>
</div>

<script>
function copyApiKey(key) {
    navigator.clipboard.writeText(key).then(function() {
        alert('API key copied to clipboard!');
    });
}
</script>
@endsection
