@extends('dashboard.layouts.app')

@section('title', 'Support Tickets')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <p class="text-gray-600">View and manage your support tickets</p>
    <a href="{{ route('dashboard.tickets.create') }}" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors inline-flex items-center">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
        </svg>
        New Ticket
    </a>
</div>

<!-- Stats -->
<div class="grid md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Open</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['open'] ?? 0 }}</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">In Progress</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['in_progress'] ?? 0 }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Resolved</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['resolved'] ?? 0 }}</p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Avg Response</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">2h</p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Tickets List -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200">
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-900">Your Tickets</h2>
        <select class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <option>All Tickets</option>
            <option>Open</option>
            <option>In Progress</option>
            <option>Resolved</option>
            <option>Closed</option>
        </select>
    </div>

    <div class="divide-y divide-gray-200">
        @forelse($tickets ?? [] as $ticket)
        <div class="px-6 py-4 hover:bg-gray-50 cursor-pointer" onclick="window.location='{{ route('dashboard.tickets.show', $ticket) }}'">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center space-x-3 mb-2">
                        <h3 class="text-sm font-semibold text-gray-900">{{ $ticket->subject }}</h3>
                        @php
                            $statusColors = [
                                'open' => 'bg-green-100 text-green-800',
                                'in_progress' => 'bg-blue-100 text-blue-800',
                                'waiting_customer' => 'bg-yellow-100 text-yellow-800',
                                'resolved' => 'bg-purple-100 text-purple-800',
                                'closed' => 'bg-gray-100 text-gray-800',
                            ];
                            $priorityColors = [
                                'low' => 'text-gray-600',
                                'medium' => 'text-yellow-600',
                                'high' => 'text-orange-600',
                                'urgent' => 'text-red-600',
                            ];
                        @endphp
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$ticket->status] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ str_replace('_', ' ', ucfirst($ticket->status)) }}
                        </span>
                        <span class="text-xs font-medium {{ $priorityColors[$ticket->priority] ?? 'text-gray-600' }}">
                            {{ ucfirst($ticket->priority) }} Priority
                        </span>
                    </div>
                    <p class="text-sm text-gray-600 line-clamp-2">{{ $ticket->message }}</p>
                    <div class="flex items-center space-x-4 mt-3 text-xs text-gray-500">
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                            {{ $ticket->department }}
                        </span>
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ $ticket->created_at->diffForHumans() }}
                        </span>
                        @if($ticket->replies_count > 0)
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                            {{ $ticket->replies_count }} {{ $ticket->replies_count === 1 ? 'reply' : 'replies' }}
                        </span>
                        @endif
                    </div>
                </div>
                <svg class="w-5 h-5 text-gray-400 ml-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </div>
        </div>
        @empty
        <div class="px-6 py-12 text-center">
            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">No Tickets Yet</h3>
            <p class="text-gray-600 mb-6">Need help? Create your first support ticket</p>
            <a href="{{ route('dashboard.tickets.create') }}" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Create Ticket
            </a>
        </div>
        @endforelse
    </div>

    @if(isset($tickets) && $tickets->hasPages())
    <div class="px-6 py-4 border-t border-gray-200">
        {{ $tickets->links() }}
    </div>
    @endif
</div>

<!-- Knowledge Base -->
<div class="mt-8 bg-gradient-to-br from-blue-600 to-indigo-700 text-white rounded-xl shadow-lg p-8">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold mb-2">Need Quick Help?</h2>
            <p class="text-blue-100 mb-4">Browse our knowledge base for instant answers to common questions</p>
            <a href="/docs" class="inline-flex items-center px-6 py-3 bg-white text-blue-600 font-semibold rounded-lg hover:bg-blue-50 transition-colors">
                Browse Knowledge Base
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
        <svg class="w-32 h-32 text-blue-400 opacity-20 hidden lg:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
        </svg>
    </div>
</div>
@endsection
