@extends('dashboard.layouts.app')

@section('title', 'Invoices')

@section('content')
<div class="mb-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Invoices</h1>
            <p class="text-gray-600">View and manage your billing history</p>
        </div>
        @if(isset($total_unpaid) && $total_unpaid > 0)
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg px-4 py-3">
            <p class="text-sm text-yellow-800">
                <span class="font-semibold">Outstanding Balance:</span> ${{ number_format($total_unpaid, 2) }}
            </p>
        </div>
        @endif
    </div>
</div>

<!-- Stats Overview -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
        </div>
        <h3 class="text-2xl font-bold text-gray-900">{{ $stats['total'] ?? 0 }}</h3>
        <p class="text-sm text-gray-600">Total Invoices</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <h3 class="text-2xl font-bold text-gray-900">{{ $stats['paid'] ?? 0 }}</h3>
        <p class="text-sm text-gray-600">Paid</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <h3 class="text-2xl font-bold text-gray-900">{{ $stats['unpaid'] ?? 0 }}</h3>
        <p class="text-sm text-gray-600">Unpaid</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <h3 class="text-2xl font-bold text-gray-900">${{ number_format($stats['total_amount'] ?? 0, 2) }}</h3>
        <p class="text-sm text-gray-600">Total Spent</p>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6">
    <div class="p-6">
        <form method="GET" action="{{ route('dashboard.billing') }}" class="flex flex-col lg:flex-row gap-4">
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
                           placeholder="Search by invoice number...">
                </div>
            </div>

            <!-- Status Filter -->
            <div class="sm:w-48">
                <select name="status" class="block w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All Status</option>
                    <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="unpaid" {{ request('status') == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    <option value="refunded" {{ request('status') == 'refunded' ? 'selected' : '' }}>Refunded</option>
                </select>
            </div>

            <!-- Date Range -->
            <div class="sm:w-48">
                <select name="date_range" class="block w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <option value="all" {{ request('date_range') == 'all' ? 'selected' : '' }}>All Time</option>
                    <option value="this_month" {{ request('date_range') == 'this_month' ? 'selected' : '' }}>This Month</option>
                    <option value="last_month" {{ request('date_range') == 'last_month' ? 'selected' : '' }}>Last Month</option>
                    <option value="this_year" {{ request('date_range') == 'this_year' ? 'selected' : '' }}>This Year</option>
                    <option value="last_year" {{ request('date_range') == 'last_year' ? 'selected' : '' }}>Last Year</option>
                </select>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="px-6 py-2.5 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition-colors">
                Filter
            </button>
        </form>
    </div>

    <!-- Quick Filter Tabs -->
    <div class="border-t border-gray-200">
        <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
            <a href="?status=all" class="{{ request('status', 'all') == 'all' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                All ({{ $stats['total'] ?? 0 }})
            </a>
            <a href="?status=unpaid" class="{{ request('status') == 'unpaid' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Unpaid ({{ $stats['unpaid'] ?? 0 }})
            </a>
            <a href="?status=paid" class="{{ request('status') == 'paid' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Paid ({{ $stats['paid'] ?? 0 }})
            </a>
        </nav>
    </div>
</div>

<!-- Invoices Table -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Invoice #
                    </th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Date
                    </th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Due Date
                    </th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Amount
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
                @forelse($invoices ?? [] as $invoice)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="text-sm font-medium text-gray-900">#{{ $invoice->invoice_number }}</div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-600">{{ $invoice->created_at->format('M d, Y') }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-600">
                            {{ $invoice->due_date ? $invoice->due_date->format('M d, Y') : 'N/A' }}
                        </div>
                        @if($invoice->status === 'unpaid' && $invoice->due_date && $invoice->due_date->isPast())
                        <span class="text-xs text-red-600 font-medium">Overdue</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-semibold text-gray-900">${{ number_format($invoice->total, 2) }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{
                            $invoice->status === 'paid' ? 'bg-green-100 text-green-800' :
                            ($invoice->status === 'unpaid' ? 'bg-yellow-100 text-yellow-800' :
                            ($invoice->status === 'cancelled' ? 'bg-gray-100 text-gray-800' : 'bg-blue-100 text-blue-800'))
                        }}">
                            {{ ucfirst($invoice->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm space-x-2">
                        <a href="{{ route('dashboard.invoice-details', $invoice->id) }}"
                           class="text-blue-600 hover:text-blue-700 font-medium">
                            View
                        </a>
                        @if($invoice->status === 'unpaid')
                        <a href="{{ route('client.invoices.pay', $invoice->id) }}"
                           class="text-green-600 hover:text-green-700 font-medium">
                            Pay
                        </a>
                        @endif
                        <a href="{{ route('client.invoices.pdf', $invoice->id) }}" target="_blank"
                           class="text-gray-600 hover:text-gray-700 font-medium">
                            PDF
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12">
                        <div class="text-center">
                            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">No Invoices Found</h3>
                            <p class="text-gray-600">
                                @if(request('search') || request('status') != 'all')
                                    No invoices match your search criteria.
                                @else
                                    You don't have any invoices yet.
                                @endif
                            </p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
@if(isset($invoices) && $invoices->hasPages())
<div class="mt-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 px-6 py-4">
        {{ $invoices->links() }}
    </div>
</div>
@endif

<!-- Payment Methods Info -->
@if(isset($invoices) && $invoices->where('status', 'unpaid')->count() > 0)
<div class="mt-8 bg-blue-50 border border-blue-200 rounded-xl p-6">
    <div class="flex items-start">
        <div class="flex-shrink-0">
            <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div class="ml-3 flex-1">
            <h3 class="text-sm font-medium text-blue-900">Payment Information</h3>
            <div class="mt-2 text-sm text-blue-800">
                <p class="mb-2">We accept the following payment methods:</p>
                <ul class="list-disc list-inside space-y-1">
                    <li>Credit/Debit Cards (Visa, MasterCard, American Express)</li>
                    <li>PayPal</li>
                    <li>Bank Transfer</li>
                    <li>Cryptocurrency (Bitcoin, Ethereum)</li>
                </ul>
                <p class="mt-3">
                    <a href="#" class="font-medium text-blue-700 hover:text-blue-600">
                        Learn more about payment options →
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>
@endif
@endsection
