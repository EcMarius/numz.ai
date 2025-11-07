@extends('dashboard.layouts.app')

@section('title', 'Activity Log')

@section('content')
<div class="max-w-6xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Activity Log</h1>
        <p class="text-gray-600">View all activities and actions performed on your account</p>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <form method="GET" class="grid md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Event Type</label>
                <select name="type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">All Types</option>
                    <option value="login">Login</option>
                    <option value="logout">Logout</option>
                    <option value="service">Service Actions</option>
                    <option value="billing">Billing Events</option>
                    <option value="security">Security</option>
                    <option value="settings">Settings</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                <select name="range" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="7">Last 7 days</option>
                    <option value="30">Last 30 days</option>
                    <option value="90">Last 90 days</option>
                    <option value="all">All time</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">IP Address</label>
                <input type="text" name="ip" placeholder="Filter by IP" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    Apply Filters
                </button>
            </div>
        </form>
    </div>

    <!-- Activity Timeline -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Recent Activity</h2>
        </div>

        @php
            $activities = $activities ?? [];
        @endphp

        @if(count($activities) > 0)
        <div class="divide-y divide-gray-200">
            @foreach($activities as $activity)
            <div class="p-6 hover:bg-gray-50 transition-colors">
                <div class="flex items-start">
                    <!-- Icon -->
                    <div class="flex-shrink-0 mr-4">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center {{ $activity->getIconClass() }}">
                            {!! $activity->getIcon() !!}
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">{{ $activity->description }}</p>
                                <div class="mt-2 flex flex-wrap items-center gap-4 text-xs text-gray-500">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        {{ $activity->created_at->diffForHumans() }}
                                    </span>
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                                        </svg>
                                        {{ $activity->ip_address }}
                                    </span>
                                    @if($activity->user_agent)
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                        {{ $activity->getBrowserInfo() }}
                                    </span>
                                    @endif
                                </div>
                                @if($activity->details)
                                <div class="mt-3 bg-gray-50 rounded-lg p-3">
                                    <pre class="text-xs text-gray-600 font-mono">{{ json_encode($activity->details, JSON_PRETTY_PRINT) }}</pre>
                                </div>
                                @endif
                            </div>
                            <span class="ml-4 px-2.5 py-0.5 rounded-full text-xs font-medium {{ $activity->getStatusClass() }}">
                                {{ $activity->type }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Pagination -->
        @if($activities->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $activities->links() }}
        </div>
        @endif
        @else
        <!-- Empty State -->
        <div class="px-6 py-12 text-center">
            <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No Activity Yet</h3>
            <p class="text-sm text-gray-600">Your account activity will appear here.</p>
        </div>
        @endif
    </div>

    <!-- Export Options -->
    <div class="mt-6 flex items-center justify-between bg-gray-50 rounded-lg p-4 border border-gray-200">
        <div class="flex items-center text-sm text-gray-600">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Need a copy of your activity log?
        </div>
        <div class="flex gap-2">
            <a href="{{ route('dashboard.activity.export', ['format' => 'csv']) }}" class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-white transition-colors">
                Export as CSV
            </a>
            <a href="{{ route('dashboard.activity.export', ['format' => 'pdf']) }}" class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-white transition-colors">
                Export as PDF
            </a>
        </div>
    </div>
</div>
@endsection
