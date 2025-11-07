@extends('dashboard.layouts.app')

@section('title', 'Account Settings')

@section('content')
<div class="max-w-5xl mx-auto">
    <!-- Settings Navigation -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
        <nav class="flex overflow-x-auto">
            <a href="#profile" class="px-6 py-4 text-sm font-medium text-blue-600 border-b-2 border-blue-600 whitespace-nowrap">
                Profile
            </a>
            <a href="#security" class="px-6 py-4 text-sm font-medium text-gray-600 hover:text-gray-900 border-b-2 border-transparent hover:border-gray-300 whitespace-nowrap">
                Security
            </a>
            <a href="#notifications" class="px-6 py-4 text-sm font-medium text-gray-600 hover:text-gray-900 border-b-2 border-transparent hover:border-gray-300 whitespace-nowrap">
                Notifications
            </a>
            <a href="#api" class="px-6 py-4 text-sm font-medium text-gray-600 hover:text-gray-900 border-b-2 border-transparent hover:border-gray-300 whitespace-nowrap">
                API Keys
            </a>
        </nav>
    </div>

    <!-- Profile Section -->
    <div id="profile" class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Profile Information</h2>
            <p class="text-sm text-gray-600 mt-1">Update your account profile information and email address.</p>
        </div>
        <form class="p-6 space-y-6">
            @csrf
            <!-- Avatar -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Profile Photo</label>
                <div class="flex items-center space-x-6">
                    <div class="w-20 h-20 bg-blue-600 rounded-full flex items-center justify-center text-white text-2xl font-bold">
                        {{ substr(auth()->user()->name, 0, 1) }}
                    </div>
                    <div>
                        <button type="button" class="px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm font-medium">
                            Change Photo
                        </button>
                        <button type="button" class="ml-3 px-4 py-2 bg-white text-red-600 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm font-medium">
                            Remove
                        </button>
                        <p class="text-xs text-gray-500 mt-2">JPG, GIF or PNG. Max size of 2MB.</p>
                    </div>
                </div>
            </div>

            <!-- Name -->
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                    <input type="text" id="first_name" name="first_name" value="{{ auth()->user()->first_name ?? '' }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                    <input type="text" id="last_name" name="last_name" value="{{ auth()->user()->last_name ?? '' }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                <input type="email" id="email" name="email" value="{{ auth()->user()->email }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <p class="text-xs text-gray-500 mt-1">This email will be used for notifications and account recovery.</p>
            </div>

            <!-- Company -->
            <div>
                <label for="company" class="block text-sm font-medium text-gray-700 mb-2">Company Name (Optional)</label>
                <input type="text" id="company" name="company" value="{{ auth()->user()->company ?? '' }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <!-- Phone -->
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                <input type="tel" id="phone" name="phone" value="{{ auth()->user()->phone ?? '' }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <!-- Address -->
            <div class="space-y-4">
                <div>
                    <label for="address" class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                    <input type="text" id="address" name="address" value="{{ auth()->user()->address ?? '' }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label for="city" class="block text-sm font-medium text-gray-700 mb-2">City</label>
                        <input type="text" id="city" name="city" value="{{ auth()->user()->city ?? '' }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label for="state" class="block text-sm font-medium text-gray-700 mb-2">State / Province</label>
                        <input type="text" id="state" name="state" value="{{ auth()->user()->state ?? '' }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label for="zip" class="block text-sm font-medium text-gray-700 mb-2">ZIP / Postal Code</label>
                        <input type="text" id="zip" name="zip" value="{{ auth()->user()->zip ?? '' }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label for="country" class="block text-sm font-medium text-gray-700 mb-2">Country</label>
                        <select id="country" name="country"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option>United States</option>
                            <option>Canada</option>
                            <option>United Kingdom</option>
                            <option>Australia</option>
                            <option>Other</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="flex justify-end space-x-3">
                <button type="button" class="px-4 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Save Changes
                </button>
            </div>
        </form>
    </div>

    <!-- Security Section -->
    <div id="security" class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Security Settings</h2>
            <p class="text-sm text-gray-600 mt-1">Manage your password and security preferences.</p>
        </div>
        <div class="p-6 space-y-6">
            <!-- Change Password -->
            <div class="pb-6 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-900 mb-4">Change Password</h3>
                <form class="space-y-4">
                    @csrf
                    <div>
                        <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                        <input type="password" id="current_password" name="current_password"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                        <input type="password" id="new_password" name="new_password"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Update Password
                    </button>
                </form>
            </div>

            <!-- Two-Factor Authentication -->
            <div class="pb-6 border-b border-gray-200">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">Two-Factor Authentication</h3>
                        <p class="text-sm text-gray-600 mt-1">Add an extra layer of security to your account.</p>
                    </div>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Disabled</span>
                </div>
                <a href="{{ route('dashboard.settings.2fa') }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    Enable 2FA
                </a>
            </div>

            <!-- Active Sessions -->
            <div>
                <h3 class="text-base font-semibold text-gray-900 mb-4">Active Sessions</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">Current Session</p>
                                <p class="text-sm text-gray-600">Chrome on Windows • {{ request()->ip() }}</p>
                                <p class="text-xs text-gray-500">Active now</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Account -->
    <div class="bg-white rounded-xl shadow-sm border border-red-200">
        <div class="px-6 py-4 border-b border-red-200 bg-red-50">
            <h2 class="text-lg font-semibold text-red-900">Danger Zone</h2>
        </div>
        <div class="p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-2">Delete Account</h3>
            <p class="text-sm text-gray-600 mb-4">Once you delete your account, there is no going back. Please be certain.</p>
            <button type="button" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                Delete Account
            </button>
        </div>
    </div>
</div>
@endsection
