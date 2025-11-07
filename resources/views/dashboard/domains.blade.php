@extends('dashboard.layouts.app')

@section('title', 'Domain Management')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <p class="text-gray-600">Manage your domains and DNS settings</p>
    <a href="{{ route('dashboard.domains.register') }}" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors inline-flex items-center">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
        </svg>
        Register Domain
    </a>
</div>

<!-- Stats -->
<div class="grid md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Total Domains</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['total'] ?? 0 }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                </svg>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Active</p>
                <p class="text-2xl font-bold text-green-600 mt-1">{{ $stats['active'] ?? 0 }}</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Expiring Soon</p>
                <p class="text-2xl font-bold text-yellow-600 mt-1">{{ $stats['expiring'] ?? 0 }}</p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Transfer In</p>
                <p class="text-2xl font-bold text-purple-600 mt-1">{{ $stats['transfers'] ?? 0 }}</p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Domain Search -->
<div class="bg-gradient-to-br from-blue-600 to-indigo-700 text-white rounded-xl shadow-lg p-8 mb-8">
    <h2 class="text-2xl font-bold mb-4">Register a New Domain</h2>
    <p class="text-blue-100 mb-6">Search for available domains and register instantly</p>
    <form class="flex gap-3">
        <div class="flex-1 relative">
            <input type="text" placeholder="Enter domain name..."
                class="w-full px-4 py-3 rounded-lg text-gray-900 focus:ring-2 focus:ring-white focus:outline-none">
        </div>
        <select class="px-4 py-3 rounded-lg text-gray-900 focus:ring-2 focus:ring-white focus:outline-none">
            <option>.com</option>
            <option>.net</option>
            <option>.org</option>
            <option>.io</option>
            <option>.co</option>
        </select>
        <button type="submit" class="px-6 py-3 bg-white text-blue-600 font-semibold rounded-lg hover:bg-blue-50 transition-colors">
            Search
        </button>
    </form>
</div>

<!-- Domains List -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200">
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-900">Your Domains</h2>
        <div class="flex items-center space-x-3">
            <select class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option>All Domains</option>
                <option>Active</option>
                <option>Expiring Soon</option>
                <option>Expired</option>
            </select>
        </div>
    </div>

    <div class="divide-y divide-gray-200">
        @forelse($domains ?? [] as $domain)
        <div class="px-6 py-4 hover:bg-gray-50">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <div class="flex items-center space-x-3 mb-2">
                        <h3 class="text-lg font-semibold text-gray-900">{{ $domain->domain }}</h3>
                        @php
                            $statusColors = [
                                'active' => 'bg-green-100 text-green-800',
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'expired' => 'bg-red-100 text-red-800',
                                'cancelled' => 'bg-gray-100 text-gray-800',
                            ];
                        @endphp
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$domain->status] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ ucfirst($domain->status) }}
                        </span>
                    </div>
                    <div class="flex items-center space-x-6 text-sm text-gray-600">
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            Registered: {{ $domain->registration_date->format('M d, Y') }}
                        </span>
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Expires: {{ $domain->expiration_date->format('M d, Y') }}
                            @if($domain->expiration_date->diffInDays(now()) <= 30)
                            <span class="ml-2 text-red-600 font-medium">({{ $domain->expiration_date->diffInDays(now()) }} days)</span>
                            @endif
                        </span>
                        @if($domain->auto_renew)
                        <span class="flex items-center text-green-600">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Auto-renew enabled
                        </span>
                        @endif
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <a href="{{ route('dashboard.domains.manage', $domain) }}" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                        Manage DNS
                    </a>
                    <button class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Quick Info Grid -->
            <div class="grid grid-cols-4 gap-4 mt-4 pt-4 border-t border-gray-100">
                <div>
                    <p class="text-xs text-gray-500">Nameservers</p>
                    <p class="text-sm font-medium text-gray-900 mt-0.5">{{ $domain->nameserver_status ?? 'Default' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">DNS Records</p>
                    <p class="text-sm font-medium text-gray-900 mt-0.5">{{ $domain->dns_records_count ?? 0 }} records</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Privacy Protection</p>
                    <p class="text-sm font-medium {{ $domain->privacy_protection ? 'text-green-600' : 'text-gray-900' }} mt-0.5">
                        {{ $domain->privacy_protection ? 'Enabled' : 'Disabled' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Lock Status</p>
                    <p class="text-sm font-medium text-gray-900 mt-0.5">{{ $domain->is_locked ? 'Locked' : 'Unlocked' }}</p>
                </div>
            </div>
        </div>
        @empty
        <div class="px-6 py-12 text-center">
            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
            </svg>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">No Domains Yet</h3>
            <p class="text-gray-600 mb-6">Register your first domain to get started</p>
            <a href="{{ route('dashboard.domains.register') }}" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Register Domain
            </a>
        </div>
        @endforelse
    </div>

    @if(isset($domains) && $domains->hasPages())
    <div class="px-6 py-4 border-t border-gray-200">
        {{ $domains->links() }}
    </div>
    @endif
</div>

<!-- Domain Actions -->
<div class="grid md:grid-cols-3 gap-6 mt-8">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Transfer Domain</h3>
        <p class="text-sm text-gray-600 mb-4">Transfer your existing domain to us for better management</p>
        <a href="{{ route('dashboard.domains.transfer') }}" class="text-blue-600 hover:text-blue-700 font-medium text-sm inline-flex items-center">
            Start Transfer
            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Privacy Protection</h3>
        <p class="text-sm text-gray-600 mb-4">Hide your personal information from public WHOIS records</p>
        <a href="{{ route('dashboard.domains.privacy') }}" class="text-blue-600 hover:text-blue-700 font-medium text-sm inline-flex items-center">
            Learn More
            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-4">
            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2">SSL Certificates</h3>
        <p class="text-sm text-gray-600 mb-4">Secure your website with SSL/TLS certificates</p>
        <a href="{{ route('dashboard.ssl') }}" class="text-blue-600 hover:text-blue-700 font-medium text-sm inline-flex items-center">
            View SSL Options
            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>
</div>
@endsection
