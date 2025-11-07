@extends('dashboard.layouts.app')

@section('title', 'Billing & Invoices')

@section('content')
<div class="grid lg:grid-cols-3 gap-8 mb-8">
    <!-- Account Balance -->
    <div class="bg-gradient-to-br from-blue-600 to-indigo-700 text-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">Account Balance</h3>
            <svg class="w-8 h-8 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
            </svg>
        </div>
        <div class="text-3xl font-bold mb-2">${{ number_format($balance ?? 0, 2) }}</div>
        <p class="text-blue-100 text-sm">Available credit</p>
    </div>

    <!-- Pending Invoices -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Pending</h3>
            <svg class="w-8 h-8 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div class="text-3xl font-bold text-gray-900 mb-2">${{ number_format($pending ?? 0, 2) }}</div>
        <p class="text-gray-600 text-sm">{{ $pending_count ?? 0 }} invoice(s)</p>
    </div>

    <!-- Total Spent -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Total Spent</h3>
            <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
            </svg>
        </div>
        <div class="text-3xl font-bold text-gray-900 mb-2">${{ number_format($total_spent ?? 0, 2) }}</div>
        <p class="text-gray-600 text-sm">All time</p>
    </div>
</div>

<!-- Invoices Table -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200">
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-900">Invoices</h2>
        <div class="flex items-center space-x-3">
            <!-- Filter -->
            <select class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option>All Invoices</option>
                <option>Paid</option>
                <option>Pending</option>
                <option>Overdue</option>
                <option>Cancelled</option>
            </select>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice #</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($invoices ?? [] as $invoice)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm font-medium text-gray-900">#{{ $invoice->invoice_number }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                        {{ $invoice->created_at->format('M d, Y') }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        {{ $invoice->description ?? ($invoice->order ? $invoice->order->product->name : 'N/A') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm font-semibold text-gray-900">${{ number_format($invoice->total, 2) }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                        {{ $invoice->due_date->format('M d, Y') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @php
                            $statusColors = [
                                'paid' => 'bg-green-100 text-green-800',
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'overdue' => 'bg-red-100 text-red-800',
                                'cancelled' => 'bg-gray-100 text-gray-800',
                            ];
                        @endphp
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$invoice->status] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ ucfirst($invoice->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex items-center justify-end space-x-2">
                            <a href="{{ route('dashboard.invoices.show', $invoice) }}" class="text-blue-600 hover:text-blue-900">View</a>
                            <a href="{{ route('dashboard.invoices.download', $invoice) }}" class="text-gray-600 hover:text-gray-900">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </a>
                            @if($invoice->status === 'pending' || $invoice->status === 'overdue')
                            <a href="{{ route('dashboard.invoices.pay', $invoice) }}" class="px-3 py-1 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                                Pay Now
                            </a>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center">
                        <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p class="text-gray-600">No invoices found</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(isset($invoices) && $invoices->hasPages())
    <div class="px-6 py-4 border-t border-gray-200">
        {{ $invoices->links() }}
    </div>
    @endif
</div>

<!-- Payment Methods -->
<div class="grid md:grid-cols-2 gap-8 mt-8">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Payment Methods</h2>
        <div class="space-y-4">
            @forelse($payment_methods ?? [] as $method)
            <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-8 bg-gradient-to-r from-blue-600 to-purple-600 rounded flex items-center justify-center text-white text-xs font-bold">
                        {{ $method->brand }}
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">•••• {{ $method->last4 }}</p>
                        <p class="text-sm text-gray-600">Expires {{ $method->exp_month }}/{{ $method->exp_year }}</p>
                    </div>
                </div>
                @if($method->is_default)
                <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded">Default</span>
                @endif
            </div>
            @empty
            <div class="text-center py-8 text-gray-600">
                <p class="mb-4">No payment methods on file</p>
                <button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    Add Payment Method
                </button>
            </div>
            @endforelse
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Billing Address</h2>
        @if(isset($billing_address))
        <div class="text-sm text-gray-600 space-y-1">
            <p class="font-medium text-gray-900">{{ $billing_address->name }}</p>
            <p>{{ $billing_address->address1 }}</p>
            @if($billing_address->address2)
            <p>{{ $billing_address->address2 }}</p>
            @endif
            <p>{{ $billing_address->city }}, {{ $billing_address->state }} {{ $billing_address->zip }}</p>
            <p>{{ $billing_address->country }}</p>
        </div>
        <button class="mt-4 text-blue-600 hover:text-blue-700 text-sm font-medium">
            Update Address
        </button>
        @else
        <div class="text-center py-8 text-gray-600">
            <p class="mb-4">No billing address on file</p>
            <button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                Add Billing Address
            </button>
        </div>
        @endif
    </div>
</div>
@endsection
