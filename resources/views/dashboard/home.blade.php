@extends('dashboard.layouts.app')

@section('title', 'Dashboard')

@section('content')
<!-- Welcome Banner -->
<div class="bg-gradient-to-r from-blue-600 to-indigo-600 rounded-2xl p-8 mb-8 text-white shadow-lg">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-3xl font-bold mb-2">Welcome back, {{ auth()->user()->name }}!</h2>
            <p class="text-blue-100">Here's what's happening with your services today.</p>
        </div>
        <div class="hidden lg:block">
            <svg class="w-24 h-24 text-blue-400 opacity-50" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"/>
            </svg>
        </div>
    </div>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Active Services -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                </svg>
            </div>
            <span class="text-xs font-medium text-blue-600 bg-blue-50 px-2 py-1 rounded-full">Active</span>
        </div>
        <h3 class="text-3xl font-bold text-gray-900 mb-1">{{ $stats['active_services'] ?? 0 }}</h3>
        <p class="text-sm text-gray-600">Active Services</p>
        <div class="mt-4 pt-4 border-t border-gray-100">
            <a href="{{ route('dashboard.services') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium inline-flex items-center">
                View all
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    </div>

    <!-- Unpaid Invoices -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <span class="text-xs font-medium text-yellow-600 bg-yellow-50 px-2 py-1 rounded-full">
                {{ $stats['unpaid_invoices'] ?? 0 }} unpaid
            </span>
        </div>
        <h3 class="text-3xl font-bold text-gray-900 mb-1">${{ number_format($stats['pending_amount'] ?? 0, 2) }}</h3>
        <p class="text-sm text-gray-600">Pending Invoices</p>
        <div class="mt-4 pt-4 border-t border-gray-100">
            <a href="{{ route('dashboard.billing') }}" class="text-sm text-yellow-600 hover:text-yellow-700 font-medium inline-flex items-center">
                Pay now
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    </div>

    <!-- Open Tickets -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </div>
            <span class="text-xs font-medium text-purple-600 bg-purple-50 px-2 py-1 rounded-full">Support</span>
        </div>
        <h3 class="text-3xl font-bold text-gray-900 mb-1">{{ $stats['open_tickets'] ?? 0 }}</h3>
        <p class="text-sm text-gray-600">Open Tickets</p>
        <div class="mt-4 pt-4 border-t border-gray-100">
            <a href="{{ route('dashboard.tickets') }}" class="text-sm text-purple-600 hover:text-purple-700 font-medium inline-flex items-center">
                View tickets
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    </div>

    <!-- Domains Expiring -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                </svg>
            </div>
            <span class="text-xs font-medium text-green-600 bg-green-50 px-2 py-1 rounded-full">30 days</span>
        </div>
        <h3 class="text-3xl font-bold text-gray-900 mb-1">{{ $stats['domains_expiring'] ?? 0 }}</h3>
        <p class="text-sm text-gray-600">Domains Expiring Soon</p>
        <div class="mt-4 pt-4 border-t border-gray-100">
            <a href="{{ route('dashboard.domains') }}" class="text-sm text-green-600 hover:text-green-700 font-medium inline-flex items-center">
                Manage domains
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-8">
    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-8">
        <!-- Spending Over Time Chart -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Spending Overview</h2>
                <select class="text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <option>Last 6 months</option>
                    <option>Last 12 months</option>
                    <option>This year</option>
                </select>
            </div>
            <div class="p-6">
                <div class="h-64 flex items-end justify-between space-x-2" x-data="{ spending: {{ json_encode($spending_data ?? [120, 150, 180, 160, 200, 220]) }} }">
                    <template x-for="(amount, index) in spending" :key="index">
                        <div class="flex-1 bg-gradient-to-t from-blue-500 to-blue-400 rounded-t-lg hover:from-blue-600 hover:to-blue-500 transition-all cursor-pointer relative group"
                             :style="`height: ${(amount / Math.max(...spending)) * 100}%`"
                             :title="`$${amount}`">
                            <div class="absolute -top-8 left-1/2 transform -translate-x-1/2 bg-gray-900 text-white px-2 py-1 rounded text-xs opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                                $<span x-text="amount"></span>
                            </div>
                        </div>
                    </template>
                </div>
                <div class="flex justify-between mt-4 text-xs text-gray-600">
                    <span>6 mo ago</span>
                    <span>5 mo ago</span>
                    <span>4 mo ago</span>
                    <span>3 mo ago</span>
                    <span>2 mo ago</span>
                    <span>This month</span>
                </div>
            </div>
        </div>

        <!-- Recent Services -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Active Services</h2>
                <a href="{{ route('dashboard.services') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">View All</a>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($recent_services ?? [] as $service)
                <div class="p-6 hover:bg-gray-50 transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4 flex-1">
                            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-gray-900">{{ $service->product->name ?? 'Service' }}</p>
                                <p class="text-sm text-gray-600 truncate">{{ $service->domain ?? 'No domain assigned' }}</p>
                                <p class="text-xs text-gray-500 mt-1">Next due: {{ $service->next_due_date ? $service->next_due_date->format('M d, Y') : 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="text-right ml-4">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $service->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($service->status) }}
                            </span>
                            <p class="text-sm font-semibold text-gray-900 mt-2">${{ number_format($service->price ?? 0, 2) }}</p>
                            <p class="text-xs text-gray-500">{{ ucfirst($service->billing_cycle ?? 'monthly') }}</p>
                        </div>
                    </div>
                </div>
                @empty
                <div class="p-12 text-center">
                    <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                    <p class="text-gray-600 mb-4">No active services yet</p>
                    <a href="/pricing" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                        Browse Plans
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Recent Activity Timeline -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Recent Activity</h2>
            </div>
            <div class="p-6">
                <div class="space-y-6">
                    @forelse($recent_activity ?? [] as $activity)
                    <div class="flex items-start space-x-4">
                        <div class="w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900">{{ $activity['title'] }}</p>
                            <p class="text-sm text-gray-600 mt-1">{{ $activity['description'] }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ $activity['time'] }}</p>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-8">
                        <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm text-gray-600">No recent activity</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Quick Actions -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h2>
            <div class="space-y-3">
                <a href="/pricing" class="block w-full px-4 py-3 bg-blue-600 text-white text-center font-medium rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                    Order New Service
                </a>
                <a href="{{ route('dashboard.tickets.create') }}" class="block w-full px-4 py-3 bg-white text-gray-700 text-center font-medium rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors">
                    Open Support Ticket
                </a>
                <a href="{{ route('dashboard.billing') }}" class="block w-full px-4 py-3 bg-white text-gray-700 text-center font-medium rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors">
                    View Invoices
                </a>
                <a href="{{ route('dashboard.domains') }}" class="block w-full px-4 py-3 bg-white text-gray-700 text-center font-medium rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors">
                    Manage Domains
                </a>
            </div>
        </div>

        <!-- System Status -->
        <div class="bg-gradient-to-br from-blue-600 to-indigo-700 text-white rounded-xl shadow-lg p-6">
            <h2 class="text-lg font-semibold mb-3 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                System Status
            </h2>
            <div class="space-y-3 mb-4">
                <div class="flex items-center">
                    <div class="w-2 h-2 bg-green-400 rounded-full mr-3 animate-pulse"></div>
                    <span class="text-sm text-blue-100">All Systems Operational</span>
                </div>
                <div class="flex items-center text-sm text-blue-100">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    99.9% uptime this month
                </div>
            </div>
            <a href="/status" class="text-white hover:text-blue-100 text-sm font-medium inline-flex items-center">
                View Full Status
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>

        <!-- Announcements -->
        @if(isset($announcements) && count($announcements) > 0)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                </svg>
                Announcements
            </h2>
            <div class="space-y-4">
                @foreach($announcements as $announcement)
                <div class="pb-4 border-b border-gray-100 last:border-0 last:pb-0">
                    <h3 class="font-medium text-gray-900 text-sm mb-1">{{ $announcement->title }}</h3>
                    <p class="text-xs text-gray-600 line-clamp-2">{{ $announcement->content }}</p>
                    <p class="text-xs text-gray-500 mt-2">{{ $announcement->created_at->diffForHumans() }}</p>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Recent Tickets -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Recent Tickets</h2>
            <div class="space-y-4">
                @forelse($recent_tickets ?? [] as $ticket)
                <div class="pb-4 border-b border-gray-100 last:border-0 last:pb-0">
                    <div class="flex items-start justify-between mb-2">
                        <p class="font-medium text-gray-900 text-sm flex-1 pr-2">{{ $ticket->subject }}</p>
                        <span class="text-xs px-2 py-1 rounded whitespace-nowrap {{ $ticket->status === 'open' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ ucfirst($ticket->status) }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-600 mb-2">{{ $ticket->department }} - Priority: {{ ucfirst($ticket->priority) }}</p>
                    <p class="text-xs text-gray-500">{{ $ticket->created_at->diffForHumans() }}</p>
                </div>
                @empty
                <div class="text-center py-6">
                    <svg class="w-10 h-10 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                    </svg>
                    <p class="text-sm text-gray-600">No recent tickets</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
