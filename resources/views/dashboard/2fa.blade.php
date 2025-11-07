@extends('dashboard.layouts.app')

@section('title', 'Two-Factor Authentication')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Two-Factor Authentication</h1>
        <p class="text-gray-600">Add an extra layer of security to your account</p>
    </div>

    <!-- Success/Error Messages -->
    @if(session('success'))
    <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-start">
        <svg class="w-5 h-5 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    @if(session('error'))
    <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex items-start">
        <svg class="w-5 h-5 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
        </svg>
        <span>{{ session('error') }}</span>
    </div>
    @endif

    @php
        $is2faEnabled = auth()->user()->two_factor_enabled ?? false;
    @endphp

    @if(!$is2faEnabled)
    <!-- Enable 2FA Section -->
    <div class="space-y-6">
        <!-- Information Card -->
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-semibold text-blue-900 mb-1">What is Two-Factor Authentication?</h3>
                    <p class="text-sm text-blue-800">Two-factor authentication (2FA) adds an extra layer of security to your account by requiring both your password and a verification code from your mobile device to log in.</p>
                </div>
            </div>
        </div>

        <!-- Setup Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Enable Two-Factor Authentication</h2>
            </div>
            <div class="p-6">
                <!-- Step 1 -->
                <div class="mb-8">
                    <div class="flex items-start mb-4">
                        <div class="flex-shrink-0 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-semibold">1</div>
                        <div class="ml-4 flex-1">
                            <h3 class="text-base font-semibold text-gray-900 mb-2">Download an Authenticator App</h3>
                            <p class="text-sm text-gray-600 mb-4">Download and install one of these authenticator apps on your mobile device:</p>
                            <div class="grid sm:grid-cols-2 gap-3">
                                <div class="flex items-center p-3 border border-gray-200 rounded-lg hover:border-blue-300 transition-colors">
                                    <svg class="w-10 h-10 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                    </svg>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900">Google Authenticator</div>
                                        <div class="text-xs text-gray-500">iOS & Android</div>
                                    </div>
                                </div>
                                <div class="flex items-center p-3 border border-gray-200 rounded-lg hover:border-blue-300 transition-colors">
                                    <svg class="w-10 h-10 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                    </svg>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900">Authy</div>
                                        <div class="text-xs text-gray-500">iOS & Android</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="mb-8">
                    <div class="flex items-start mb-4">
                        <div class="flex-shrink-0 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-semibold">2</div>
                        <div class="ml-4 flex-1">
                            <h3 class="text-base font-semibold text-gray-900 mb-2">Scan the QR Code</h3>
                            <p class="text-sm text-gray-600 mb-4">Open your authenticator app and scan this QR code:</p>

                            @if(session('qr_code'))
                            <div class="bg-white p-6 border-2 border-gray-200 rounded-lg inline-block mb-4">
                                {!! session('qr_code') !!}
                            </div>
                            @else
                            <div class="bg-gray-100 p-12 border-2 border-dashed border-gray-300 rounded-lg inline-flex items-center justify-center mb-4">
                                <div class="text-center">
                                    <svg class="w-16 h-16 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                                    </svg>
                                    <p class="text-sm text-gray-500">Click "Generate QR Code" below</p>
                                </div>
                            </div>
                            @endif

                            @if(session('secret_key'))
                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                <p class="text-xs text-gray-600 mb-2">Can't scan? Enter this code manually:</p>
                                <code class="text-sm font-mono font-semibold text-gray-900">{{ session('secret_key') }}</code>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="mb-6">
                    <div class="flex items-start mb-4">
                        <div class="flex-shrink-0 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-semibold">3</div>
                        <div class="ml-4 flex-1">
                            <h3 class="text-base font-semibold text-gray-900 mb-2">Verify Your Code</h3>
                            <p class="text-sm text-gray-600 mb-4">Enter the 6-digit code from your authenticator app to complete setup:</p>

                            <form action="{{ route('dashboard.2fa.enable') }}" method="POST" class="space-y-4">
                                @csrf
                                <div>
                                    <input type="text" name="code" maxlength="6" pattern="[0-9]{6}" placeholder="000000" class="text-center text-2xl tracking-widest font-mono w-48 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required autocomplete="off">
                                    @error('code')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="flex gap-3">
                                    @if(!session('qr_code'))
                                    <button type="button" onclick="window.location.href='{{ route('dashboard.2fa.generate') }}'" class="px-6 py-2.5 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                                        Generate QR Code
                                    </button>
                                    @endif

                                    <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-medium rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-all shadow-lg shadow-blue-500/30" {{ !session('qr_code') ? 'disabled' : '' }}>
                                        <svg class="w-5 h-5 inline-block mr-2 -mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                        </svg>
                                        Enable 2FA
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @else
    <!-- 2FA Enabled Section -->
    <div class="space-y-6">
        <!-- Status Card -->
        <div class="bg-green-50 border border-green-200 rounded-xl p-6">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <div class="ml-4 flex-1">
                    <h3 class="text-sm font-semibold text-green-900 mb-1">Two-Factor Authentication is Active</h3>
                    <p class="text-sm text-green-800">Your account is protected with two-factor authentication. You'll need your password and a verification code to log in.</p>
                </div>
            </div>
        </div>

        <!-- Backup Codes Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-purple-50 to-pink-50 px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Backup Codes</h2>
            </div>
            <div class="p-6">
                <p class="text-sm text-gray-600 mb-4">Save these backup codes in a safe place. Each code can be used once if you lose access to your authenticator app.</p>

                @if(session('backup_codes'))
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 mb-4">
                    <div class="grid grid-cols-2 gap-2 font-mono text-sm">
                        @foreach(session('backup_codes') as $code)
                        <div class="bg-white px-3 py-2 rounded border border-gray-200">{{ $code }}</div>
                        @endforeach
                    </div>
                </div>

                <div class="flex gap-3">
                    <button onclick="navigator.clipboard.writeText('{{ implode(\"\n\", session('backup_codes')) }}')" class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                        <svg class="w-4 h-4 inline-block mr-1 -mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        Copy Codes
                    </button>
                    <button onclick="window.print()" class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                        <svg class="w-4 h-4 inline-block mr-1 -mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        Print Codes
                    </button>
                </div>
                @else
                <form action="{{ route('dashboard.2fa.regenerate-codes') }}" method="POST">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors">
                        Generate New Backup Codes
                    </button>
                    <p class="text-xs text-gray-500 mt-2">Generating new codes will invalidate all previous codes.</p>
                </form>
                @endif
            </div>
        </div>

        <!-- Recovery Information -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-yellow-50 to-orange-50 px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Recovery Information</h2>
            </div>
            <div class="p-6">
                <div class="flex items-start mb-4">
                    <svg class="w-5 h-5 text-yellow-600 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div class="flex-1">
                        <h3 class="text-sm font-semibold text-gray-900 mb-2">Lost Access to Your Device?</h3>
                        <p class="text-sm text-gray-600 mb-3">If you lose access to your authenticator app:</p>
                        <ul class="text-sm text-gray-600 space-y-1 list-disc list-inside">
                            <li>Use one of your backup codes to log in</li>
                            <li>Contact our support team for assistance</li>
                            <li>Keep your backup codes in a secure location</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Disable 2FA -->
        <div class="bg-white rounded-xl shadow-sm border border-red-200 overflow-hidden">
            <div class="bg-gradient-to-r from-red-50 to-pink-50 px-6 py-4 border-b border-red-200">
                <h2 class="text-lg font-semibold text-gray-900">Disable Two-Factor Authentication</h2>
            </div>
            <div class="p-6">
                <p class="text-sm text-gray-600 mb-4">This will remove the extra layer of security from your account. We recommend keeping 2FA enabled for maximum security.</p>

                <form action="{{ route('dashboard.2fa.disable') }}" method="POST" onsubmit="return confirm('Are you sure you want to disable two-factor authentication? This will make your account less secure.')">
                    @csrf
                    @method('DELETE')

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Confirm your password</label>
                        <input type="password" name="password" placeholder="Enter your password" class="w-full max-w-md px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500" required>
                        @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="px-6 py-2.5 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition-colors">
                        Disable 2FA
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
