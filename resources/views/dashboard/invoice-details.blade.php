@extends('dashboard.layouts.app')

@section('title', 'Invoice Details')

@section('content')
<!-- Breadcrumb -->
<nav class="flex mb-6" aria-label="Breadcrumb">
    <ol class="inline-flex items-center space-x-1 md:space-x-3">
        <li class="inline-flex items-center">
            <a href="{{ route('dashboard') }}" class="text-gray-600 hover:text-gray-900 inline-flex items-center">
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                </svg>
                Dashboard
            </a>
        </li>
        <li>
            <div class="flex items-center">
                <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                </svg>
                <a href="{{ route('dashboard.billing') }}" class="text-gray-600 hover:text-gray-900 ml-1 md:ml-2">Invoices</a>
            </div>
        </li>
        <li aria-current="page">
            <div class="flex items-center">
                <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                </svg>
                <span class="text-gray-500 ml-1 md:ml-2">#{{ $invoice->invoice_number }}</span>
            </div>
        </li>
    </ol>
</nav>

<!-- Invoice Header -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8 mb-8">
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
        <div class="flex-1">
            <div class="flex items-center space-x-3 mb-4">
                <h1 class="text-3xl font-bold text-gray-900">Invoice #{{ $invoice->invoice_number }}</h1>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{
                    $invoice->status === 'paid' ? 'bg-green-100 text-green-800' :
                    ($invoice->status === 'unpaid' ? 'bg-yellow-100 text-yellow-800' :
                    ($invoice->status === 'cancelled' ? 'bg-gray-100 text-gray-800' : 'bg-blue-100 text-blue-800'))
                }}">
                    {{ ucfirst($invoice->status) }}
                </span>
            </div>

            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-gray-600 mb-1">Invoice Date</p>
                    <p class="font-medium text-gray-900">{{ $invoice->created_at->format('F d, Y') }}</p>
                </div>
                <div>
                    <p class="text-gray-600 mb-1">Due Date</p>
                    <p class="font-medium text-gray-900 {{ $invoice->status === 'unpaid' && $invoice->due_date && $invoice->due_date->isPast() ? 'text-red-600' : '' }}">
                        {{ $invoice->due_date ? $invoice->due_date->format('F d, Y') : 'N/A' }}
                        @if($invoice->status === 'unpaid' && $invoice->due_date && $invoice->due_date->isPast())
                        <span class="text-xs ml-1">(Overdue)</span>
                        @endif
                    </p>
                </div>
                @if($invoice->status === 'paid' && $invoice->paid_date)
                <div>
                    <p class="text-gray-600 mb-1">Paid Date</p>
                    <p class="font-medium text-green-600">{{ $invoice->paid_date->format('F d, Y') }}</p>
                </div>
                @endif
            </div>
        </div>

        <div class="lg:text-right">
            <p class="text-sm text-gray-600 mb-2">Total Amount</p>
            <p class="text-4xl font-bold text-gray-900">${{ number_format($invoice->total, 2) }}</p>
            @if($invoice->status === 'unpaid')
            <div class="mt-4 space-y-2">
                <form method="POST" action="{{ route('client.invoices.pay', $invoice->id) }}">
                    @csrf
                    <button type="submit" class="w-full lg:w-auto px-6 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition-colors shadow-sm">
                        Pay Now
                    </button>
                </form>
            </div>
            @endif
        </div>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-8">
    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Invoice Details -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Invoice Details</h2>
            </div>
            <div class="p-6">
                <!-- Bill To & From -->
                <div class="grid md:grid-cols-2 gap-8 mb-8">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">From</h3>
                        <div class="text-sm text-gray-600 space-y-1">
                            <p class="font-semibold text-gray-900">{{ config('app.name', 'Numz') }}</p>
                            <p>{{ setting('site.company_address', '123 Business St') }}</p>
                            <p>{{ setting('site.company_city', 'City') }}, {{ setting('site.company_state', 'State') }} {{ setting('site.company_zip', '12345') }}</p>
                            <p>{{ setting('site.company_country', 'Country') }}</p>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">Bill To</h3>
                        <div class="text-sm text-gray-600 space-y-1">
                            <p class="font-semibold text-gray-900">{{ $invoice->user->name }}</p>
                            <p>{{ $invoice->user->email }}</p>
                            @if($invoice->user->company_name)
                            <p>{{ $invoice->user->company_name }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-y border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($invoice->items as $item)
                            <tr>
                                <td class="px-4 py-4 text-sm text-gray-900">
                                    <div>
                                        <p class="font-medium">{{ $item->description }}</p>
                                        @if($item->details)
                                        <p class="text-xs text-gray-600 mt-1">{{ $item->details }}</p>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-900 text-center">{{ $item->quantity }}</td>
                                <td class="px-4 py-4 text-sm text-gray-900 text-right">${{ number_format($item->unit_price, 2) }}</td>
                                <td class="px-4 py-4 text-sm text-gray-900 text-right font-medium">${{ number_format($item->total, 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-gray-600">
                                    No items found
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Totals -->
                <div class="mt-8 flex justify-end">
                    <div class="w-full md:w-80 space-y-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600">Subtotal</span>
                            <span class="font-medium text-gray-900">${{ number_format($invoice->subtotal, 2) }}</span>
                        </div>
                        @if($invoice->discount > 0)
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600">Discount</span>
                            <span class="font-medium text-green-600">-${{ number_format($invoice->discount, 2) }}</span>
                        </div>
                        @endif
                        @if($invoice->tax > 0)
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600">Tax</span>
                            <span class="font-medium text-gray-900">${{ number_format($invoice->tax, 2) }}</span>
                        </div>
                        @endif
                        @if($invoice->late_fee > 0)
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600">Late Fee</span>
                            <span class="font-medium text-red-600">${{ number_format($invoice->late_fee, 2) }}</span>
                        </div>
                        @endif
                        <div class="pt-3 border-t-2 border-gray-200">
                            <div class="flex items-center justify-between">
                                <span class="text-lg font-semibold text-gray-900">Total</span>
                                <span class="text-2xl font-bold text-gray-900">${{ number_format($invoice->total, 2) }}</span>
                            </div>
                        </div>
                        @if($invoice->amount_paid > 0)
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600">Amount Paid</span>
                            <span class="font-medium text-green-600">${{ number_format($invoice->amount_paid, 2) }}</span>
                        </div>
                        @if($invoice->remaining_balance > 0)
                        <div class="flex items-center justify-between">
                            <span class="text-lg font-semibold text-gray-900">Balance Due</span>
                            <span class="text-xl font-bold text-red-600">${{ number_format($invoice->remaining_balance, 2) }}</span>
                        </div>
                        @endif
                        @endif
                    </div>
                </div>

                <!-- Notes -->
                @if($invoice->notes)
                <div class="mt-8 pt-8 border-t border-gray-200">
                    <h3 class="text-sm font-semibold text-gray-900 mb-2">Notes</h3>
                    <p class="text-sm text-gray-600">{{ $invoice->notes }}</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Transaction History -->
        @if($invoice->status === 'paid' || $invoice->amount_paid > 0)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Transaction History</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Transaction ID</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @if($invoice->status === 'paid')
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $invoice->paid_date ? $invoice->paid_date->format('M d, Y H:i') : 'N/A' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ ucfirst($invoice->payment_method ?? 'N/A') }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600 font-mono">{{ $invoice->transaction_id ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-green-600 font-semibold text-right">${{ number_format($invoice->total, 2) }}</td>
                        </tr>
                        @else
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-gray-600">
                                No transactions recorded
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Actions -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Actions</h2>
            <div class="space-y-3">
                @if($invoice->status === 'unpaid')
                <form method="POST" action="{{ route('client.invoices.pay', $invoice->id) }}">
                    @csrf
                    <button type="submit" class="w-full px-4 py-2.5 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                        Pay Invoice
                    </button>
                </form>
                @endif
                <a href="{{ route('client.invoices.pdf', $invoice->id) }}" target="_blank"
                   class="block w-full px-4 py-2.5 bg-blue-600 text-white text-center text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    View PDF
                </a>
                <a href="{{ route('client.invoices.download', $invoice->id) }}"
                   class="block w-full px-4 py-2.5 bg-white text-gray-700 text-center text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors">
                    Download PDF
                </a>
                <button onclick="window.print()"
                        class="w-full px-4 py-2.5 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors">
                    Print Invoice
                </button>
            </div>
        </div>

        <!-- Payment Methods -->
        @if($invoice->status === 'unpaid')
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
            <h3 class="text-sm font-semibold text-blue-900 mb-3 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
                Payment Methods
            </h3>
            <ul class="text-sm text-blue-800 space-y-2">
                <li class="flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    Credit/Debit Card
                </li>
                <li class="flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    PayPal
                </li>
                <li class="flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    Bank Transfer
                </li>
                <li class="flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    Cryptocurrency
                </li>
            </ul>
        </div>
        @endif

        <!-- Support -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-sm font-semibold text-gray-900 mb-3 flex items-center">
                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                Need Help?
            </h3>
            <p class="text-sm text-gray-600 mb-4">
                Have questions about this invoice? Our support team is here to help.
            </p>
            <a href="{{ route('dashboard.tickets.create') }}"
               class="block w-full px-4 py-2.5 bg-gray-100 text-gray-700 text-center text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                Contact Support
            </a>
        </div>

        <!-- Invoice Info -->
        <div class="bg-gray-50 rounded-xl border border-gray-200 p-6">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Invoice Information</h3>
            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="text-gray-600">Currency</dt>
                    <dd class="font-medium text-gray-900 mt-1">{{ strtoupper($invoice->currency ?? 'USD') }}</dd>
                </div>
                @if($invoice->payment_method)
                <div>
                    <dt class="text-gray-600">Payment Method</dt>
                    <dd class="font-medium text-gray-900 mt-1">{{ ucfirst($invoice->payment_method) }}</dd>
                </div>
                @endif
                <div>
                    <dt class="text-gray-600">Billing Cycle</dt>
                    <dd class="font-medium text-gray-900 mt-1">{{ ucfirst($invoice->billing_cycle ?? 'One-time') }}</dd>
                </div>
            </dl>
        </div>
    </div>
</div>
@endsection
