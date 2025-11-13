@extends('dashboard.layouts.app')

@section('title', 'Support Tickets')

@section('content')
<div class="mb-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Support Tickets</h1>
            <p class="text-gray-600">Get help from our support team</p>
        </div>
        <a href="{{ route('dashboard.tickets.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Open New Ticket
        </a>
    </div>
</div>

<!-- Stats Overview -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <h3 class="text-2xl font-bold text-gray-900">{{ $stats['open'] ?? 0 }}</h3>
        <p class="text-sm text-gray-600">Open Tickets</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
        </div>
        <h3 class="text-2xl font-bold text-gray-900">{{ $stats['closed'] ?? 0 }}</h3>
        <p class="text-sm text-gray-600">Closed Tickets</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
        </div>
        <h3 class="text-2xl font-bold text-gray-900">{{ $stats['total'] ?? 0 }}</h3>
        <p class="text-sm text-gray-600">Total Tickets</p>
    </div>
</div>

<!-- Search and Filters -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6">
    <div class="p-6">
        <form method="GET" action="{{ route('dashboard.tickets') }}" class="flex flex-col lg:flex-row gap-4">
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
                           placeholder="Search tickets by subject or number...">
                </div>
            </div>

            <!-- Status Filter -->
            <div class="sm:w-48">
                <select name="status" class="block w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All Status</option>
                    <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>Open</option>
                    <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="waiting_customer" {{ request('status') == 'waiting_customer' ? 'selected' : '' }}>Waiting on Customer</option>
                    <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>Closed</option>
                </select>
            </div>

            <!-- Department Filter -->
            <div class="sm:w-48">
                <select name="department" class="block w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <option value="all" {{ request('department') == 'all' ? 'selected' : '' }}>All Departments</option>
                    <option value="general" {{ request('department') == 'general' ? 'selected' : '' }}>General Support</option>
                    <option value="technical" {{ request('department') == 'technical' ? 'selected' : '' }}>Technical</option>
                    <option value="billing" {{ request('department') == 'billing' ? 'selected' : '' }}>Billing</option>
                    <option value="sales" {{ request('department') == 'sales' ? 'selected' : '' }}>Sales</option>
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
                All ({{ $stats['total'] ?? 0 }})
            </a>
            <a href="?status=open" class="{{ request('status') == 'open' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Open ({{ $stats['open'] ?? 0 }})
            </a>
            <a href="?status=closed" class="{{ request('status') == 'closed' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Closed ({{ $stats['closed'] ?? 0 }})
            </a>
        </nav>
    </div>
</div>

<!-- Tickets List -->
<div class="space-y-4">
    @forelse($tickets ?? [] as $ticket)
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-shadow">
        <a href="{{ route('dashboard.tickets.show', $ticket->id) }}" class="block p-6">
            <div class="flex items-start justify-between">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center space-x-3 mb-2">
                        <h3 class="text-lg font-semibold text-gray-900 truncate">{{ $ticket->subject }}</h3>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{
                            $ticket->status === 'open' ? 'bg-green-100 text-green-800' :
                            ($ticket->status === 'in_progress' ? 'bg-blue-100 text-blue-800' :
                            ($ticket->status === 'waiting_customer' ? 'bg-yellow-100 text-yellow-800' :
                            ($ticket->status === 'closed' ? 'bg-gray-100 text-gray-800' : 'bg-purple-100 text-purple-800')))
                        }}">
                            {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                        </span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{
                            $ticket->priority === 'urgent' ? 'bg-red-100 text-red-800' :
                            ($ticket->priority === 'high' ? 'bg-orange-100 text-orange-800' :
                            ($ticket->priority === 'normal' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'))
                        }}">
                            {{ ucfirst($ticket->priority) }}
                        </span>
                    </div>

                    <div class="flex items-center space-x-6 text-sm text-gray-600">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                            <span>{{ $ticket->ticket_number }}</span>
                        </div>
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            <span>{{ ucfirst($ticket->department) }}</span>
                        </div>
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>{{ $ticket->created_at->diffForHumans() }}</span>
                        </div>
                        @if($ticket->last_reply_at)
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                            </svg>
                            <span>Last reply: {{ $ticket->last_reply_at->diffForHumans() }}</span>
                        </div>
                        @endif
                    </div>

                    @if($ticket->latestReply)
                    <div class="mt-3 text-sm text-gray-600 line-clamp-2">
                        {{ Str::limit($ticket->latestReply->message, 150) }}
                    </div>
                    @endif
                </div>

                <div class="ml-6 flex-shrink-0">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            </div>
        </a>
    </div>
    @empty
    <!-- Empty State -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-16">
        <div class="text-center max-w-sm mx-auto">
            <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">No Tickets Found</h3>
            <p class="text-gray-600 mb-8">
                @if(request('search') || request('status') != 'all')
                    No tickets match your search criteria.
                @else
                    You don't have any support tickets yet. Need help? Open a ticket and our team will assist you.
                @endif
            </p>
            @if(!request('search') && !request('status'))
            <a href="{{ route('dashboard.tickets.create') }}" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Open New Ticket
            </a>
            @else
            <a href="{{ route('dashboard.tickets') }}" class="inline-flex items-center px-6 py-3 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition-colors">
                Clear Filters
            </a>
            @endif
        </div>
    </div>
    @endforelse
</div>

<!-- Pagination -->
@if(isset($tickets) && $tickets->hasPages())
<div class="mt-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 px-6 py-4">
        {{ $tickets->links() }}
    </div>
</div>
@endif

<!-- Support Info -->
<div class="mt-8 grid md:grid-cols-2 gap-6">
    <!-- Response Time -->
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-900">Average Response Time</h3>
                <p class="mt-2 text-sm text-blue-800">
                    Our support team typically responds within <strong>2-4 hours</strong> during business hours (9 AM - 6 PM EST, Monday-Friday).
                </p>
            </div>
        </div>
    </div>

    <!-- Knowledge Base -->
    <div class="bg-green-50 border border-green-200 rounded-xl p-6">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-green-900">Knowledge Base</h3>
                <p class="mt-2 text-sm text-green-800 mb-3">
                    Find instant answers to common questions in our comprehensive knowledge base.
                </p>
                <a href="/knowledge-base" class="text-sm font-medium text-green-700 hover:text-green-600">
                    Browse Articles →
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
