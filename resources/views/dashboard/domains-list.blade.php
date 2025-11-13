@extends('dashboard.layouts.app')

@section('title', 'Domains')

@section('content')
<div class="mb-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">My Domains</h1>
            <p class="text-gray-600">Manage your domain registrations and settings</p>
        </div>
        <a href="/domains/register" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Register Domain
        </a>
    </div>
</div>

<!-- Stats Overview -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <h3 class="text-2xl font-bold text-gray-900">{{ $stats['active'] ?? 0 }}</h3>
        <p class="text-sm text-gray-600">Active Domains</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <h3 class="text-2xl font-bold text-gray-900">{{ $stats['expiring_soon'] ?? 0 }}</h3>
        <p class="text-sm text-gray-600">Expiring Soon (30d)</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </div>
        </div>
        <h3 class="text-2xl font-bold text-gray-900">{{ $stats['auto_renew'] ?? 0 }}</h3>
        <p class="text-sm text-gray-600">Auto-Renew Enabled</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
        </div>
        <h3 class="text-2xl font-bold text-gray-900">{{ $stats['expired'] ?? 0 }}</h3>
        <p class="text-sm text-gray-600">Expired</p>
    </div>
</div>

<!-- Search and Filters -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6">
    <div class="p-6">
        <form method="GET" action="{{ route('dashboard.domains') }}" class="flex flex-col lg:flex-row gap-4">
            <!-- Search -->
            <div class="flex-1">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}"
                           class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Search domains...">
                </div>
            </div>

            <!-- Status Filter -->
            <div class="sm:w-48">
                <select name="status" class="block w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All Status</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                    <option value="transferred" {{ request('status') == 'transferred' ? 'selected' : '' }}>Transferred</option>
                </select>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="px-6 py-2.5 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition-colors">
                Filter
            </button>
        </form>
    </div>

    <!-- Filter Tabs -->
    <div class="border-t border-gray-200">
        <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
            <a href="?status=all" class="{{ request('status', 'all') == 'all' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                All Domains ({{ $stats['total'] ?? 0 }})
            </a>
            <a href="?status=active" class="{{ request('status') == 'active' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Active ({{ $stats['active'] ?? 0 }})
            </a>
            <a href="?expiring=true" class="{{ request('expiring') == 'true' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Expiring Soon ({{ $stats['expiring_soon'] ?? 0 }})
            </a>
        </nav>
    </div>
</div>

<!-- Domains List -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Domain Name
                    </th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Registration Date
                    </th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Expiry Date
                    </th>
                    <th class="px-6 py-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Auto-Renew
                    </th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
                    </th>
                    <th class="px-6 py-4 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @forelse($domains ?? [] as $domain)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                                </svg>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-900">{{ $domain->domain_name }}</div>
                                @if($domain->whois_privacy)
                                <div class="text-xs text-green-600 mt-1 flex items-center">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                                    </svg>
                                    WHOIS Privacy
                                </div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-600">
                            {{ $domain->registered_at ? $domain->registered_at->format('M d, Y') : 'N/A' }}
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-600">
                            {{ $domain->expires_at ? $domain->expires_at->format('M d, Y') : 'N/A' }}
                        </div>
                        @if($domain->expires_at && $domain->isExpiringSoon(30))
                        <div class="text-xs text-yellow-600 font-medium mt-1">
                            Expires in {{ $domain->days_until_expiration }} days
                        </div>
                        @elseif($domain->isExpired())
                        <div class="text-xs text-red-600 font-medium mt-1">
                            Expired
                        </div>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <button onclick="toggleAutoRenew({{ $domain->id }})"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $domain->auto_renew ? 'bg-blue-600' : 'bg-gray-200' }}">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $domain->auto_renew ? 'translate-x-6' : 'translate-x-1' }}"></span>
                        </button>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{
                            $domain->status === 'active' ? 'bg-green-100 text-green-800' :
                            ($domain->status === 'expired' ? 'bg-red-100 text-red-800' :
                            ($domain->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800'))
                        }}">
                            {{ ucfirst($domain->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm space-x-2">
                        <a href="{{ route('dashboard.domains.show', $domain->id) }}"
                           class="text-blue-600 hover:text-blue-700 font-medium">
                            Manage
                        </a>
                        @if($domain->status === 'active')
                        <a href="#"
                           class="text-green-600 hover:text-green-700 font-medium">
                            Renew
                        </a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12">
                        <div class="text-center">
                            <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
                                <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">No Domains Found</h3>
                            <p class="text-gray-600 mb-6">
                                @if(request('search'))
                                    No domains match your search criteria.
                                @else
                                    You don't have any domains yet. Register your first domain to get started.
                                @endif
                            </p>
                            @if(!request('search'))
                            <a href="/domains/register" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                Register Domain
                            </a>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
@if(isset($domains) && $domains->hasPages())
<div class="mt-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 px-6 py-4">
        {{ $domains->links() }}
    </div>
</div>
@endif

<!-- Domain Search Widget -->
<div class="mt-8 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-2xl p-8 text-white shadow-lg">
    <div class="max-w-3xl mx-auto">
        <h2 class="text-2xl font-bold mb-2 text-center">Register a New Domain</h2>
        <p class="text-blue-100 mb-6 text-center">Find and register your perfect domain name</p>
        <form class="flex gap-3">
            <div class="flex-1 relative">
                <input type="text" placeholder="Search for your domain..."
                       class="w-full px-6 py-4 rounded-lg text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-white">
            </div>
            <button type="submit"
                    class="px-8 py-4 bg-white text-blue-600 font-semibold rounded-lg hover:bg-blue-50 transition-colors">
                Search
            </button>
        </form>
        <div class="mt-4 flex flex-wrap gap-2 justify-center text-sm">
            <span class="text-blue-100">.com $12.99</span>
            <span class="text-blue-100">•</span>
            <span class="text-blue-100">.net $14.99</span>
            <span class="text-blue-100">•</span>
            <span class="text-blue-100">.org $13.99</span>
            <span class="text-blue-100">•</span>
            <span class="text-blue-100">.io $39.99</span>
        </div>
    </div>
</div>

<script>
function toggleAutoRenew(domainId) {
    if (confirm('Are you sure you want to toggle auto-renew for this domain?')) {
        fetch(`/client/domains/${domainId}/toggle-autorenew`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to update auto-renew setting');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred');
        });
    }
}
</script>
@endsection
