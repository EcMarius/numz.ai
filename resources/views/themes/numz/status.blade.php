@extends('theme::layouts.app')

@section('content')
<!-- Hero Section -->
<section class="relative bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800 text-white">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="py-16 text-center">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-green-500 rounded-full mb-6 animate-pulse">
                <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
            </div>
            <h1 class="text-4xl sm:text-5xl font-bold mb-4">All Systems Operational</h1>
            <p class="text-xl text-blue-100 max-w-3xl mx-auto">
                Real-time status of our infrastructure and services
            </p>
            <p class="text-sm text-blue-200 mt-4">Last updated: {{ now()->format('M d, Y H:i:s') }} UTC</p>
        </div>
    </div>
</section>

<!-- Quick Status Overview -->
<section class="py-8 bg-white border-b border-gray-200">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 max-w-4xl mx-auto">
            <div class="text-center">
                <div class="text-3xl font-bold text-green-600">99.9%</div>
                <div class="text-sm text-gray-600 mt-1">Uptime (30d)</div>
            </div>
            <div class="text-center">
                <div class="text-3xl font-bold text-green-600">45ms</div>
                <div class="text-sm text-gray-600 mt-1">Avg Response</div>
            </div>
            <div class="text-center">
                <div class="text-3xl font-bold text-gray-900">0</div>
                <div class="text-sm text-gray-600 mt-1">Active Incidents</div>
            </div>
            <div class="text-center">
                <div class="text-3xl font-bold text-gray-900">6</div>
                <div class="text-sm text-gray-600 mt-1">Services</div>
            </div>
        </div>
    </div>
</section>

<!-- Current Status -->
<section class="py-16 bg-gray-50">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto">
            <h2 class="text-2xl font-bold text-gray-900 mb-8">Current Status</h2>

            <div class="space-y-4">
                <!-- Service Status Cards -->
                @php
                    $services = [
                        ['name' => 'Web Hosting', 'status' => 'operational', 'uptime' => '99.95%', 'response' => '42ms'],
                        ['name' => 'Email Services', 'status' => 'operational', 'uptime' => '99.98%', 'response' => '28ms'],
                        ['name' => 'DNS Services', 'status' => 'operational', 'uptime' => '100%', 'response' => '15ms'],
                        ['name' => 'Control Panel', 'status' => 'operational', 'uptime' => '99.92%', 'response' => '65ms'],
                        ['name' => 'API Services', 'status' => 'operational', 'uptime' => '99.89%', 'response' => '38ms'],
                        ['name' => 'Backup Services', 'status' => 'operational', 'uptime' => '99.96%', 'response' => '120ms'],
                    ];
                @endphp

                @foreach($services as $service)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center flex-1">
                            <div class="flex-shrink-0 mr-4">
                                <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-gray-900">{{ $service['name'] }}</h3>
                                <p class="text-sm text-gray-600 mt-1">
                                    {{ $service['uptime'] }} uptime · {{ $service['response'] }} avg response time
                                </p>
                            </div>
                        </div>
                        <span class="ml-4 px-3 py-1 bg-green-100 text-green-800 text-sm font-medium rounded-full">
                            Operational
                        </span>
                    </div>

                    <!-- Uptime Graph -->
                    <div class="mt-4 flex items-end h-12 space-x-1">
                        @for($i = 0; $i < 30; $i++)
                        <div class="flex-1 bg-green-500 rounded-t hover:bg-green-600 transition-colors cursor-pointer" style="height: {{ rand(85, 100) }}%;" title="Day {{ $i + 1 }}: 100% uptime"></div>
                        @endfor
                    </div>
                    <div class="mt-2 flex justify-between text-xs text-gray-500">
                        <span>30 days ago</span>
                        <span>Today</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

<!-- Incident History -->
<section class="py-16 bg-white">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto">
            <h2 class="text-2xl font-bold text-gray-900 mb-8">Recent Incidents</h2>

            @php
                $incidents = [
                    [
                        'date' => '2024-11-05',
                        'title' => 'Scheduled Maintenance',
                        'status' => 'resolved',
                        'severity' => 'maintenance',
                        'description' => 'Scheduled server maintenance completed successfully with no impact to services.',
                        'duration' => '15 minutes'
                    ],
                    [
                        'date' => '2024-10-28',
                        'title' => 'Brief Network Latency',
                        'status' => 'resolved',
                        'severity' => 'minor',
                        'description' => 'Brief increase in network latency affecting US East region. Issue was resolved quickly.',
                        'duration' => '8 minutes'
                    ],
                ];
            @endphp

            @if(count($incidents) > 0)
            <div class="space-y-6">
                @foreach($incidents as $incident)
                <div class="border-l-4 {{ $incident['severity'] === 'maintenance' ? 'border-blue-500' : 'border-yellow-500' }} bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <div class="flex items-center mb-2">
                                <h3 class="text-lg font-semibold text-gray-900">{{ $incident['title'] }}</h3>
                                <span class="ml-3 px-2.5 py-0.5 bg-gray-100 text-gray-800 text-xs font-medium rounded-full">
                                    Resolved
                                </span>
                            </div>
                            <p class="text-sm text-gray-600">{{ $incident['description'] }}</p>
                        </div>
                    </div>

                    <div class="flex items-center text-xs text-gray-500 space-x-4">
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            {{ \Carbon\Carbon::parse($incident['date'])->format('M d, Y') }}
                        </span>
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Duration: {{ $incident['duration'] }}
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-center py-12 bg-gray-50 rounded-lg border border-gray-200">
                <svg class="w-16 h-16 mx-auto text-green-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No Recent Incidents</h3>
                <p class="text-gray-600">All systems have been operating normally for the past 30 days.</p>
            </div>
            @endif
        </div>
    </div>
</section>

<!-- Scheduled Maintenance -->
<section class="py-16 bg-gray-50">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto">
            <h2 class="text-2xl font-bold text-gray-900 mb-8">Scheduled Maintenance</h2>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
                <svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No Scheduled Maintenance</h3>
                <p class="text-gray-600">There is no scheduled maintenance at this time. We'll notify you in advance of any planned work.</p>
            </div>
        </div>
    </div>
</section>

<!-- Subscribe to Updates -->
<section class="py-16 bg-white">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl mx-auto text-center">
            <svg class="w-12 h-12 mx-auto text-blue-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Subscribe to Status Updates</h2>
            <p class="text-gray-600 mb-6">Get notified about incidents and scheduled maintenance via email.</p>

            <form action="{{ route('status.subscribe') }}" method="POST" class="flex gap-3 max-w-md mx-auto">
                @csrf
                <input type="email" name="email" placeholder="Your email address" class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                <button type="submit" class="px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors whitespace-nowrap">
                    Subscribe
                </button>
            </form>
        </div>
    </div>
</section>

<!-- Status Key -->
<section class="py-8 bg-gray-100 border-t border-gray-200">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto">
            <div class="flex flex-wrap items-center justify-center gap-6 text-sm">
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-green-500 rounded-full mr-2"></div>
                    <span class="text-gray-700">Operational</span>
                </div>
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-yellow-500 rounded-full mr-2"></div>
                    <span class="text-gray-700">Degraded Performance</span>
                </div>
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-orange-500 rounded-full mr-2"></div>
                    <span class="text-gray-700">Partial Outage</span>
                </div>
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-red-500 rounded-full mr-2"></div>
                    <span class="text-gray-700">Major Outage</span>
                </div>
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-blue-500 rounded-full mr-2"></div>
                    <span class="text-gray-700">Maintenance</span>
                </div>
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script>
// Auto-refresh every 60 seconds
setTimeout(function() {
    location.reload();
}, 60000);
</script>
@endpush
@endsection
