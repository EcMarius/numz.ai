@extends('dashboard.layouts.app')

@section('title', 'My Services')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <p class="text-gray-600">Manage your hosting services and subscriptions</p>
    </div>
    <a href="/pricing" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors inline-flex items-center">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
        </svg>
        Order New Service
    </a>
</div>

<!-- Filter Tabs -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
            <a href="?status=all" class="border-blue-500 text-blue-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                All Services ({{ $counts['all'] ?? 0 }})
            </a>
            <a href="?status=active" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Active ({{ $counts['active'] ?? 0 }})
            </a>
            <a href="?status=pending" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Pending ({{ $counts['pending'] ?? 0 }})
            </a>
            <a href="?status=suspended" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Suspended ({{ $counts['suspended'] ?? 0 }})
            </a>
        </nav>
    </div>
</div>

<!-- Services Grid -->
<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
    @forelse($services ?? [] as $service)
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4">
            <div class="flex items-center justify-between">
                <h3 class="text-white font-semibold">{{ $service->product->name }}</h3>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $service->status === 'active' ? 'bg-green-400 text-green-900' : 'bg-yellow-400 text-yellow-900' }}">
                    {{ ucfirst($service->status) }}
                </span>
            </div>
            @if($service->domain)
            <p class="text-blue-100 text-sm mt-1">{{ $service->domain }}</p>
            @endif
        </div>

        <!-- Body -->
        <div class="p-6">
            <!-- Details -->
            <div class="space-y-3 mb-6">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600">Billing Cycle</span>
                    <span class="font-medium text-gray-900">{{ ucfirst($service->billing_cycle) }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600">Price</span>
                    <span class="font-medium text-gray-900">${{ number_format($service->total, 2) }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600">Next Due Date</span>
                    <span class="font-medium text-gray-900">{{ $service->next_due_date->format('M d, Y') }}</span>
                </div>
                @if($service->status === 'active')
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600">Auto Renew</span>
                    <span class="font-medium {{ $service->auto_renew ? 'text-green-600' : 'text-gray-900' }}">
                        {{ $service->auto_renew ? 'Enabled' : 'Disabled' }}
                    </span>
                </div>
                @endif
            </div>

            <!-- Actions -->
            <div class="flex gap-2">
                <a href="{{ route('dashboard.services.show', $service) }}" class="flex-1 px-4 py-2 bg-blue-600 text-white text-center text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    Manage
                </a>
                @if($service->status === 'active')
                <a href="{{ route('dashboard.services.renew', $service) }}" class="px-4 py-2 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors">
                    Renew
                </a>
                @endif
            </div>
        </div>
    </div>
    @empty
    <!-- Empty State -->
    <div class="col-span-full">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
            </svg>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">No Services Yet</h3>
            <p class="text-gray-600 mb-6">Get started by ordering your first hosting service</p>
            <a href="/pricing" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Browse Plans
            </a>
        </div>
    </div>
    @endforelse
</div>

@if(isset($services) && $services->hasPages())
<div class="mt-8">
    {{ $services->links() }}
</div>
@endif
@endsection
