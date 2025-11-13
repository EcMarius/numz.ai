@extends('dashboard.layouts.app')

@section('title', 'My Services')

@section('content')
<div class="mb-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">My Services</h1>
            <p class="text-gray-600">Manage your hosting services and subscriptions</p>
        </div>
        <a href="/pricing" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Order New Service
        </a>
    </div>
</div>

<!-- Search and Filters -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6">
    <div class="p-6">
        <form method="GET" action="{{ route('dashboard.services') }}" class="flex flex-col lg:flex-row gap-4">
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
                           placeholder="Search services by domain or product...">
                </div>
            </div>

            <!-- Status Filter -->
            <div class="sm:w-48">
                <select name="status" class="block w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All Status</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
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
                All Services ({{ $counts['all'] ?? 0 }})
            </a>
            <a href="?status=active" class="{{ request('status') == 'active' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Active ({{ $counts['active'] ?? 0 }})
            </a>
            <a href="?status=pending" class="{{ request('status') == 'pending' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Pending ({{ $counts['pending'] ?? 0 }})
            </a>
            <a href="?status=suspended" class="{{ request('status') == 'suspended' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Suspended ({{ $counts['suspended'] ?? 0 }})
            </a>
        </nav>
    </div>
</div>

<!-- Services List -->
<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
    @forelse($services ?? [] as $service)
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-all duration-200 hover:-translate-y-1">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex-1 min-w-0 pr-4">
                    <h3 class="text-white font-semibold truncate">{{ $service->product->name ?? 'Service' }}</h3>
                    @if($service->domain)
                    <p class="text-blue-100 text-sm mt-1 truncate">{{ $service->domain }}</p>
                    @endif
                </div>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium whitespace-nowrap {{
                    $service->status === 'active' ? 'bg-green-400 text-green-900' :
                    ($service->status === 'suspended' ? 'bg-red-400 text-red-900' :
                    ($service->status === 'pending' ? 'bg-yellow-400 text-yellow-900' : 'bg-gray-400 text-gray-900'))
                }}">
                    {{ ucfirst($service->status) }}
                </span>
            </div>
        </div>

        <!-- Body -->
        <div class="p-6">
            <!-- Service Details -->
            <div class="space-y-3 mb-6">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600 flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Billing Cycle
                    </span>
                    <span class="font-medium text-gray-900">{{ ucfirst($service->billing_cycle ?? 'monthly') }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600 flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Price
                    </span>
                    <span class="font-medium text-gray-900">${{ number_format($service->price ?? 0, 2) }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600 flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        Next Due
                    </span>
                    <span class="font-medium text-gray-900">
                        {{ $service->next_due_date ? $service->next_due_date->format('M d, Y') : 'N/A' }}
                    </span>
                </div>
                @if($service->status === 'active' && $service->activated_at)
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600 flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Active Since
                    </span>
                    <span class="font-medium text-gray-900">{{ $service->activated_at->format('M d, Y') }}</span>
                </div>
                @endif
            </div>

            <!-- Server Info -->
            @if($service->server)
            <div class="mb-6 p-3 bg-gray-50 rounded-lg">
                <p class="text-xs text-gray-600 mb-1">Server</p>
                <p class="text-sm font-medium text-gray-900">{{ $service->server->name ?? 'N/A' }}</p>
                @if($service->server->ip_address)
                <p class="text-xs text-gray-600 mt-1">{{ $service->server->ip_address }}</p>
                @endif
            </div>
            @endif

            <!-- Actions -->
            <div class="flex gap-2">
                <a href="{{ route('dashboard.service-details', $service->id) }}"
                   class="flex-1 px-4 py-2.5 bg-blue-600 text-white text-center text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    Manage
                </a>
                @if($service->status === 'active')
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open"
                            class="px-4 py-2.5 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                        </svg>
                    </button>
                    <div x-show="open" @click.away="open = false"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-10">
                        <a href="{{ route('dashboard.service-details', $service->id) }}"
                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            View Details
                        </a>
                        <a href="#"
                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            Upgrade/Downgrade
                        </a>
                        <a href="#"
                           class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                            Request Cancellation
                        </a>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
    @empty
    <!-- Empty State -->
    <div class="col-span-full">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-16 text-center">
            <div class="max-w-sm mx-auto">
                <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">No Services Found</h3>
                <p class="text-gray-600 mb-8">
                    @if(request('search'))
                        No services match your search criteria. Try adjusting your filters.
                    @else
                        You don't have any services yet. Get started by ordering your first hosting service.
                    @endif
                </p>
                @if(!request('search'))
                <a href="/pricing" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Browse Plans
                </a>
                @else
                <a href="{{ route('dashboard.services') }}" class="inline-flex items-center px-6 py-3 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition-colors">
                    Clear Filters
                </a>
                @endif
            </div>
        </div>
    </div>
    @endforelse
</div>

<!-- Pagination -->
@if(isset($services) && $services->hasPages())
<div class="mt-8">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 px-6 py-4">
        {{ $services->links() }}
    </div>
</div>
@endif

<!-- Bulk Actions Help Text -->
@if(isset($services) && $services->count() > 0)
<div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
    <div class="flex">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div class="ml-3">
            <p class="text-sm text-blue-800">
                <strong>Pro Tip:</strong> Click on any service to view detailed information, manage settings, and access advanced features.
            </p>
        </div>
    </div>
</div>
@endif
@endsection
