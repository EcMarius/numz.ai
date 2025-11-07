@extends('dashboard.layouts.app')

@section('title', 'Dashboard')

@section('content')
<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Active Services -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                </svg>
            </div>
        </div>
        <h3 class="text-2xl font-bold text-gray-900">{{ $stats['active_services'] ?? 0 }}</h3>
        <p class="text-sm text-gray-600 mt-1">Active Services</p>
    </div>

    <!-- Domains -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                </svg>
            </div>
        </div>
        <h3 class="text-2xl font-bold text-gray-900">{{ $stats['domains'] ?? 0 }}</h3>
        <p class="text-sm text-gray-600 mt-1">Domains</p>
    </div>

    <!-- Pending Invoices -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <h3 class="text-2xl font-bold text-gray-900">${{ number_format($stats['pending_amount'] ?? 0, 2) }}</h3>
        <p class="text-sm text-gray-600 mt-1">Pending Invoices</p>
    </div>

    <!-- Open Tickets -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </div>
        </div>
        <h3 class="text-2xl font-bold text-gray-900">{{ $stats['open_tickets'] ?? 0 }}</h3>
        <p class="text-sm text-gray-600 mt-1">Open Tickets</p>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-8">
    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-8">
        <!-- Recent Services -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Recent Services</h2>
                <a href="{{ route('dashboard.services') }}" class="text-sm text-blue-600 hover:text-blue-700">View All</a>
            </div>
            <div class="p-6">
                @forelse($recent_services ?? [] as $service)
                <div class="flex items-center justify-between py-4 border-b border-gray-100 last:border-0">
                    <div class="flex items-center space-x-4">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">{{ $service->product->name }}</p>
                            <p class="text-sm text-gray-600">{{ $service->domain ?? 'No domain' }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $service->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ ucfirst($service->status) }}
                        </span>
                        <p class="text-xs text-gray-600 mt-1">${{ number_format($service->total, 2) }}/mo</p>
                    </div>
                </div>
                @empty
                <div class="text-center py-12">
                    <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                    <p class="text-gray-600">No services yet</p>
                    <a href="/pricing" class="inline-flex items-center mt-4 text-blue-600 hover:text-blue-700 font-medium">
                        Browse Plans
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Recent Invoices -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Recent Invoices</h2>
                <a href="{{ route('dashboard.billing') }}" class="text-sm text-blue-600 hover:text-blue-700">View All</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoice</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($recent_invoices ?? [] as $invoice)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">#{{ $invoice->invoice_number }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $invoice->created_at->format('M d, Y') }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">${{ number_format($invoice->total, 2) }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $invoice->status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ ucfirst($invoice->status) }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-gray-600">
                                No invoices yet
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Quick Actions -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h2>
            <div class="space-y-3">
                <a href="/pricing" class="block w-full px-4 py-3 bg-blue-600 text-white text-center font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    Order New Service
                </a>
                <a href="{{ route('dashboard.tickets.create') }}" class="block w-full px-4 py-3 bg-white text-gray-700 text-center font-medium rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors">
                    Open Support Ticket
                </a>
                <a href="{{ route('dashboard.billing') }}" class="block w-full px-4 py-3 bg-white text-gray-700 text-center font-medium rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors">
                    View Invoices
                </a>
            </div>
        </div>

        <!-- Announcements -->
        <div class="bg-gradient-to-br from-blue-600 to-indigo-700 text-white rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold mb-3">System Status</h2>
            <div class="flex items-center mb-4">
                <div class="w-2 h-2 bg-green-400 rounded-full mr-2 animate-pulse"></div>
                <span class="text-sm">All Systems Operational</span>
            </div>
            <p class="text-blue-100 text-sm mb-4">
                99.9% uptime this month. View full status report.
            </p>
            <a href="/status" class="text-white hover:text-blue-100 text-sm font-medium inline-flex items-center">
                View Status
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>

        <!-- Recent Tickets -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Recent Tickets</h2>
            <div class="space-y-4">
                @forelse($recent_tickets ?? [] as $ticket)
                <div class="pb-4 border-b border-gray-100 last:border-0 last:pb-0">
                    <div class="flex items-start justify-between mb-2">
                        <p class="font-medium text-gray-900 text-sm">{{ $ticket->subject }}</p>
                        <span class="text-xs px-2 py-1 rounded {{ $ticket->status === 'open' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ ucfirst($ticket->status) }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-600">{{ $ticket->created_at->diffForHumans() }}</p>
                </div>
                @empty
                <p class="text-sm text-gray-600 text-center py-4">No recent tickets</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
