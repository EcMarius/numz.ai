@extends('dashboard.layouts.app')

@section('title', 'Service Details')

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
                <a href="{{ route('dashboard.services') }}" class="text-gray-600 hover:text-gray-900 ml-1 md:ml-2">Services</a>
            </div>
        </li>
        <li aria-current="page">
            <div class="flex items-center">
                <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                </svg>
                <span class="text-gray-500 ml-1 md:ml-2">Service Details</span>
            </div>
        </li>
    </ol>
</nav>

<!-- Service Header -->
<div class="bg-gradient-to-r from-blue-600 to-indigo-600 rounded-2xl p-8 mb-8 text-white shadow-lg">
    <div class="flex items-start justify-between">
        <div class="flex-1">
            <div class="flex items-center space-x-3 mb-3">
                <h1 class="text-3xl font-bold">{{ $service->product->name ?? 'Service' }}</h1>
                <span class="px-3 py-1 rounded-full text-sm font-medium {{
                    $service->status === 'active' ? 'bg-green-400 text-green-900' :
                    ($service->status === 'suspended' ? 'bg-red-400 text-red-900' :
                    ($service->status === 'pending' ? 'bg-yellow-400 text-yellow-900' : 'bg-gray-400 text-gray-900'))
                }}">
                    {{ ucfirst($service->status) }}
                </span>
            </div>
            @if($service->domain)
            <p class="text-blue-100 text-lg mb-4">{{ $service->domain }}</p>
            @endif
            <div class="flex flex-wrap gap-4 text-sm">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>{{ ucfirst($service->billing_cycle ?? 'monthly') }} billing</span>
                </div>
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span>Next due: {{ $service->next_due_date ? $service->next_due_date->format('M d, Y') : 'N/A' }}</span>
                </div>
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="font-semibold">${{ number_format($service->price ?? 0, 2) }}</span>
                </div>
            </div>
        </div>
        <div class="hidden lg:block">
            <div class="w-24 h-24 bg-white bg-opacity-20 rounded-xl flex items-center justify-center">
                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                </svg>
            </div>
        </div>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-8">
    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Service Information -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Service Information</h2>
            </div>
            <div class="p-6">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-600 mb-1">Product</dt>
                        <dd class="text-sm text-gray-900">{{ $service->product->name ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-600 mb-1">Status</dt>
                        <dd>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{
                                $service->status === 'active' ? 'bg-green-100 text-green-800' :
                                ($service->status === 'suspended' ? 'bg-red-100 text-red-800' :
                                ($service->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800'))
                            }}">
                                {{ ucfirst($service->status) }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-600 mb-1">Domain/Hostname</dt>
                        <dd class="text-sm text-gray-900">{{ $service->domain ?? 'Not assigned' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-600 mb-1">Billing Cycle</dt>
                        <dd class="text-sm text-gray-900">{{ ucfirst($service->billing_cycle ?? 'monthly') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-600 mb-1">Price</dt>
                        <dd class="text-sm text-gray-900 font-semibold">${{ number_format($service->price ?? 0, 2) }}/{{ $service->billing_cycle ?? 'mo' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-600 mb-1">Next Due Date</dt>
                        <dd class="text-sm text-gray-900">{{ $service->next_due_date ? $service->next_due_date->format('M d, Y') : 'N/A' }}</dd>
                    </div>
                    @if($service->activated_at)
                    <div>
                        <dt class="text-sm font-medium text-gray-600 mb-1">Activated On</dt>
                        <dd class="text-sm text-gray-900">{{ $service->activated_at->format('M d, Y') }}</dd>
                    </div>
                    @endif
                    @if($service->username)
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-600 mb-1">Username</dt>
                        <dd class="text-sm text-gray-900 font-mono">{{ $service->username }}</dd>
                    </div>
                    @endif
                </dl>
            </div>
        </div>

        <!-- Login Credentials -->
        @if($service->username || $service->password)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200" x-data="{ showPassword: false }">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Login Credentials</h2>
            </div>
            <div class="p-6 space-y-4">
                @if($service->username)
                <div>
                    <label class="text-sm font-medium text-gray-600 block mb-2">Username</label>
                    <div class="flex items-center space-x-2">
                        <input type="text" value="{{ $service->username }}" readonly
                               class="flex-1 px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 font-mono text-sm">
                        <button onclick="copyToClipboard('{{ $service->username }}')"
                                class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                        </button>
                    </div>
                </div>
                @endif
                @if($service->password)
                <div>
                    <label class="text-sm font-medium text-gray-600 block mb-2">Password</label>
                    <div class="flex items-center space-x-2">
                        <input :type="showPassword ? 'text' : 'password'" value="{{ $service->password }}" readonly
                               class="flex-1 px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 font-mono text-sm">
                        <button @click="showPassword = !showPassword"
                                class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                            <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                        <button onclick="copyToClipboard('{{ $service->password }}')"
                                class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                        </button>
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Server Information -->
        @if($service->server)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Server Information</h2>
            </div>
            <div class="p-6">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-600 mb-1">Server Name</dt>
                        <dd class="text-sm text-gray-900">{{ $service->server->name ?? 'N/A' }}</dd>
                    </div>
                    @if($service->server->ip_address)
                    <div>
                        <dt class="text-sm font-medium text-gray-600 mb-1">IP Address</dt>
                        <dd class="text-sm text-gray-900 font-mono">{{ $service->server->ip_address }}</dd>
                    </div>
                    @endif
                    @if($service->server->hostname)
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-600 mb-1">Hostname</dt>
                        <dd class="text-sm text-gray-900 font-mono">{{ $service->server->hostname }}</dd>
                    </div>
                    @endif
                </dl>
            </div>
        </div>
        @endif

        <!-- Related Invoices -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Related Invoices</h2>
                <a href="{{ route('dashboard.billing') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">View All</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoice</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($related_invoices ?? [] as $invoice)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">#{{ $invoice->invoice_number }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $invoice->created_at->format('M d, Y') }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900 font-semibold">${{ number_format($invoice->total, 2) }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $invoice->status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ ucfirst($invoice->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right text-sm">
                                <a href="{{ route('dashboard.invoice-details', $invoice->id) }}" class="text-blue-600 hover:text-blue-700 font-medium">View</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-600">
                                No invoices found for this service
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
                @if($service->status === 'active')
                <button class="w-full px-4 py-2.5 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                    Renew Service
                </button>
                <button class="w-full px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    Upgrade/Downgrade
                </button>
                @endif
                <a href="{{ route('dashboard.tickets.create') }}" class="block w-full px-4 py-2.5 bg-white text-gray-700 text-center text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors">
                    Open Support Ticket
                </a>
                @if($service->status === 'active')
                <button onclick="confirmCancellation()" class="w-full px-4 py-2.5 bg-white text-red-600 text-sm font-medium rounded-lg border border-red-300 hover:bg-red-50 transition-colors">
                    Request Cancellation
                </button>
                @endif
            </div>
        </div>

        <!-- Service Actions -->
        @if($service->status === 'active')
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Service Actions</h2>
            <div class="space-y-3">
                <button class="w-full px-4 py-2.5 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors flex items-center justify-between">
                    <span>Reboot Server</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </button>
                <button class="w-full px-4 py-2.5 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors flex items-center justify-between">
                    <span>Reset Password</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                </button>
                <button class="w-full px-4 py-2.5 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors flex items-center justify-between">
                    <span>View Console</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </button>
            </div>
        </div>
        @endif

        <!-- Activity Log -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Activity Log</h2>
            <div class="space-y-4">
                @forelse($activity_log ?? [] as $log)
                <div class="flex items-start space-x-3 pb-4 border-b border-gray-100 last:border-0 last:pb-0">
                    <div class="w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
                    <div class="flex-1">
                        <p class="text-sm text-gray-900">{{ $log['action'] }}</p>
                        <p class="text-xs text-gray-600 mt-1">{{ $log['timestamp'] }}</p>
                    </div>
                </div>
                @empty
                <p class="text-sm text-gray-600 text-center py-4">No activity recorded</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('Copied to clipboard!');
    }, function(err) {
        console.error('Could not copy text: ', err);
    });
}

function confirmCancellation() {
    if (confirm('Are you sure you want to request cancellation for this service? This action cannot be undone.')) {
        // Handle cancellation request
        alert('Cancellation request submitted. Our team will contact you shortly.');
    }
}
</script>
@endsection
