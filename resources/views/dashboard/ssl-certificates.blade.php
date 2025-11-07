@extends('dashboard.layouts.app')

@section('title', 'SSL Certificates')

@section('content')
<div class="max-w-6xl mx-auto">
    <!-- Header -->
    <div class="flex items-start justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">SSL Certificates</h1>
            <p class="text-gray-600">Secure your websites with SSL/TLS certificates</p>
        </div>
        <button onclick="document.getElementById('installModal').classList.remove('hidden')" class="px-4 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-medium rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-all shadow-lg shadow-blue-500/30">
            <svg class="w-5 h-5 inline-block mr-2 -mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Install SSL Certificate
        </button>
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

    <!-- Free Let's Encrypt Banner -->
    <div class="mb-6 bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-lg p-6">
        <div class="flex items-start justify-between">
            <div class="flex items-start flex-1">
                <div class="flex-shrink-0">
                    <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <div class="ml-4 flex-1">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Free SSL Certificates with Let's Encrypt</h3>
                    <p class="text-sm text-gray-700 mb-3">Secure your domains with free, auto-renewing SSL certificates from Let's Encrypt. Installation takes just a few seconds!</p>
                    <button onclick="document.getElementById('letsencryptModal').classList.remove('hidden')" class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                        Get Free SSL Certificate
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- SSL Certificates Grid -->
    @php
        $certificates = $certificates ?? [];
    @endphp

    @if(count($certificates) > 0)
    <div class="grid md:grid-cols-2 gap-6 mb-8">
        @foreach($certificates as $cert)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
            <div class="bg-gradient-to-r from-{{ $cert->isExpired() ? 'red' : ($cert->isExpiringSoon() ? 'yellow' : 'green') }}-500 to-{{ $cert->isExpired() ? 'red' : ($cert->isExpiringSoon() ? 'orange' : 'emerald') }}-600 px-6 py-4 text-white">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold mb-1">{{ $cert->domain }}</h3>
                        <p class="text-sm text-white/90">{{ $cert->type === 'letsencrypt' ? 'Let\'s Encrypt' : 'Custom Certificate' }}</p>
                    </div>
                    <div class="flex items-center">
                        @if($cert->isExpired())
                        <span class="px-3 py-1 bg-white/20 rounded-full text-xs font-medium">Expired</span>
                        @elseif($cert->isExpiringSoon())
                        <span class="px-3 py-1 bg-white/20 rounded-full text-xs font-medium">Expiring Soon</span>
                        @else
                        <span class="px-3 py-1 bg-white/20 rounded-full text-xs font-medium">Active</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <div class="text-xs font-medium text-gray-500 mb-1">Issued</div>
                        <div class="text-sm font-semibold text-gray-900">{{ $cert->issued_at->format('M d, Y') }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-medium text-gray-500 mb-1">Expires</div>
                        <div class="text-sm font-semibold {{ $cert->isExpired() ? 'text-red-600' : ($cert->isExpiringSoon() ? 'text-yellow-600' : 'text-gray-900') }}">
                            {{ $cert->expires_at->format('M d, Y') }}
                        </div>
                    </div>
                </div>

                <div>
                    <div class="text-xs font-medium text-gray-500 mb-1">Issuer</div>
                    <div class="text-sm text-gray-900">{{ $cert->issuer ?? 'Let\'s Encrypt' }}</div>
                </div>

                @if($cert->san_domains && count($cert->san_domains) > 0)
                <div>
                    <div class="text-xs font-medium text-gray-500 mb-1">Additional Domains</div>
                    <div class="flex flex-wrap gap-1">
                        @foreach($cert->san_domains as $san)
                        <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded">{{ $san }}</span>
                        @endforeach
                    </div>
                </div>
                @endif

                <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                    <div class="flex items-center text-xs text-gray-500">
                        @if($cert->auto_renew)
                        <svg class="w-4 h-4 mr-1 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        Auto-renew enabled
                        @else
                        <svg class="w-4 h-4 mr-1 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        Manual renewal
                        @endif
                    </div>
                    <div class="flex gap-2">
                        <button onclick="viewCertificate({{ $cert->id }})" class="px-3 py-1.5 text-sm text-blue-600 hover:bg-blue-50 rounded transition-colors">
                            View Details
                        </button>
                        @if($cert->type === 'letsencrypt' && !$cert->isExpired())
                        <form action="{{ route('dashboard.ssl.renew', $cert->id) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 text-sm text-green-600 hover:bg-green-50 rounded transition-colors">
                                Renew Now
                            </button>
                        </form>
                        @endif
                        <form action="{{ route('dashboard.ssl.destroy', $cert->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to remove this SSL certificate?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-3 py-1.5 text-sm text-red-600 hover:bg-red-50 rounded transition-colors">
                                Remove
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <!-- Empty State -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-12 text-center">
            <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No SSL Certificates Installed</h3>
            <p class="text-sm text-gray-600 mb-6">Secure your website with a free SSL certificate from Let's Encrypt.</p>
            <button onclick="document.getElementById('letsencryptModal').classList.remove('hidden')" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                Get Free SSL Certificate
            </button>
        </div>
    </div>
    @endif

    <!-- Benefits Section -->
    <div class="mt-8 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-gradient-to-r from-purple-50 to-pink-50 px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Why Use SSL Certificates?</h2>
        </div>
        <div class="p-6">
            <div class="grid md:grid-cols-3 gap-6">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-semibold text-gray-900 mb-1">Data Encryption</h3>
                        <p class="text-sm text-gray-600">Protects sensitive information transmitted between your site and visitors.</p>
                    </div>
                </div>

                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-semibold text-gray-900 mb-1">Trust & Credibility</h3>
                        <p class="text-sm text-gray-600">Browser padlock icon builds visitor confidence and trust.</p>
                    </div>
                </div>

                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-semibold text-gray-900 mb-1">SEO Benefits</h3>
                        <p class="text-sm text-gray-600">Google ranks HTTPS sites higher in search results.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Let's Encrypt Modal -->
<div id="letsencryptModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full">
        <div class="bg-gradient-to-r from-green-600 to-emerald-600 text-white px-6 py-4 flex items-center justify-between">
            <h3 class="text-xl font-semibold">Get Free SSL Certificate</h3>
            <button onclick="document.getElementById('letsencryptModal').classList.add('hidden')" class="text-white hover:text-gray-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form action="{{ route('dashboard.ssl.letsencrypt') }}" method="POST" class="p-6">
            @csrf

            <div class="space-y-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Domain <span class="text-red-500">*</span></label>
                    <select name="domain" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500" required>
                        <option value="">Choose a domain...</option>
                        @foreach($availableDomains ?? [] as $domain)
                        <option value="{{ $domain->name }}">{{ $domain->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-start">
                    <input type="checkbox" name="include_www" value="1" class="mt-1 h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                    <label class="ml-2 text-sm text-gray-700">
                        Include www subdomain (e.g., www.example.com)
                    </label>
                </div>

                <div class="flex items-start">
                    <input type="checkbox" name="auto_renew" value="1" checked class="mt-1 h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                    <label class="ml-2 text-sm text-gray-700">
                        Automatically renew certificate before expiration
                    </label>
                </div>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-blue-600 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <h4 class="text-sm font-semibold text-blue-900 mb-1">About Let's Encrypt</h4>
                        <ul class="text-sm text-blue-800 space-y-1">
                            <li>• 100% free SSL certificates</li>
                            <li>• Valid for 90 days with automatic renewal</li>
                            <li>• Trusted by all major browsers</li>
                            <li>• Installation takes seconds</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
                <button type="button" onclick="document.getElementById('letsencryptModal').classList.add('hidden')" class="px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button type="submit" class="px-6 py-2 bg-gradient-to-r from-green-600 to-emerald-600 text-white font-medium rounded-lg hover:from-green-700 hover:to-emerald-700 transition-all">
                    Install SSL Certificate
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Custom SSL Install Modal -->
<div id="installModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-6 py-4 flex items-center justify-between">
            <h3 class="text-xl font-semibold">Install Custom SSL Certificate</h3>
            <button onclick="document.getElementById('installModal').classList.add('hidden')" class="text-white hover:text-gray-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form action="{{ route('dashboard.ssl.custom') }}" method="POST" class="p-6">
            @csrf

            <div class="space-y-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Domain <span class="text-red-500">*</span></label>
                    <input type="text" name="domain" placeholder="example.com" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Certificate (CRT) <span class="text-red-500">*</span></label>
                    <textarea name="certificate" rows="8" placeholder="-----BEGIN CERTIFICATE-----
...
-----END CERTIFICATE-----" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono text-sm" required></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Private Key <span class="text-red-500">*</span></label>
                    <textarea name="private_key" rows="8" placeholder="-----BEGIN PRIVATE KEY-----
...
-----END PRIVATE KEY-----" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono text-sm" required></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Certificate Chain (Optional)</label>
                    <textarea name="chain" rows="6" placeholder="-----BEGIN CERTIFICATE-----
...
-----END CERTIFICATE-----" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono text-sm"></textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
                <button type="button" onclick="document.getElementById('installModal').classList.add('hidden')" class="px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button type="submit" class="px-6 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-medium rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-all">
                    Install Certificate
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function viewCertificate(certId) {
    window.location.href = `/dashboard/ssl-certificates/${certId}`;
}
</script>
@endsection
