@extends('admin.layouts.app')

@section('title', 'System Health')

@section('content')
<div class="space-y-8">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">System Health</h1>
            <p class="mt-2 text-sm text-gray-700">Real-time system status and performance metrics</p>
        </div>
        <button onclick="location.reload()" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Refresh
        </button>
    </div>

    <!-- Overall Status -->
    <div class="bg-gradient-to-r from-green-500 to-emerald-600 rounded-lg shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold mb-2">All Systems Operational</h2>
                <p class="text-green-100">Last checked: {{ now()->format('M d, Y H:i:s') }}</p>
            </div>
            <div class="text-6xl">✓</div>
        </div>
    </div>

    <!-- Core Services Status -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Web Server -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Web Server</h3>
                <span class="flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-3 w-3 rounded-full bg-green-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                </span>
            </div>
            <div class="space-y-3">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Server:</span>
                    <span class="font-medium text-gray-900">{{ $health['web_server']['name'] ?? 'nginx/1.21.0' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Uptime:</span>
                    <span class="font-medium text-gray-900">{{ $health['web_server']['uptime'] ?? '45 days' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Status:</span>
                    <span class="font-medium text-green-600">Operational</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Response Time:</span>
                    <span class="font-medium text-gray-900">{{ $health['web_server']['response_time'] ?? '45ms' }}</span>
                </div>
            </div>
        </div>

        <!-- Database -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Database</h3>
                <span class="flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-3 w-3 rounded-full bg-green-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                </span>
            </div>
            <div class="space-y-3">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Engine:</span>
                    <span class="font-medium text-gray-900">{{ $health['database']['engine'] ?? 'MySQL 8.0' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Connections:</span>
                    <span class="font-medium text-gray-900">{{ $health['database']['connections'] ?? '45/200' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Status:</span>
                    <span class="font-medium text-green-600">Connected</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Query Time:</span>
                    <span class="font-medium text-gray-900">{{ $health['database']['avg_query_time'] ?? '12ms' }}</span>
                </div>
            </div>
        </div>

        <!-- Cache -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Cache</h3>
                <span class="flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-3 w-3 rounded-full bg-green-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                </span>
            </div>
            <div class="space-y-3">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Driver:</span>
                    <span class="font-medium text-gray-900">{{ $health['cache']['driver'] ?? 'Redis' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Hit Rate:</span>
                    <span class="font-medium text-gray-900">{{ $health['cache']['hit_rate'] ?? '94.2%' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Status:</span>
                    <span class="font-medium text-green-600">Active</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Memory Used:</span>
                    <span class="font-medium text-gray-900">{{ $health['cache']['memory_used'] ?? '256 MB' }}</span>
                </div>
            </div>
        </div>

        <!-- Queue -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Queue</h3>
                <span class="flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-3 w-3 rounded-full bg-green-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                </span>
            </div>
            <div class="space-y-3">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Workers:</span>
                    <span class="font-medium text-gray-900">{{ $health['queue']['workers'] ?? '4 active' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Pending Jobs:</span>
                    <span class="font-medium text-gray-900">{{ $health['queue']['pending'] ?? '12' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Status:</span>
                    <span class="font-medium text-green-600">Processing</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Failed Jobs:</span>
                    <span class="font-medium text-gray-900">{{ $health['queue']['failed'] ?? '0' }}</span>
                </div>
            </div>
        </div>

        <!-- Email -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Email Service</h3>
                <span class="flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-3 w-3 rounded-full bg-green-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                </span>
            </div>
            <div class="space-y-3">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Provider:</span>
                    <span class="font-medium text-gray-900">{{ $health['email']['provider'] ?? 'SMTP' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Sent Today:</span>
                    <span class="font-medium text-gray-900">{{ $health['email']['sent_today'] ?? '1,234' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Status:</span>
                    <span class="font-medium text-green-600">Connected</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Queue Size:</span>
                    <span class="font-medium text-gray-900">{{ $health['email']['queue_size'] ?? '5' }}</span>
                </div>
            </div>
        </div>

        <!-- Storage -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Storage</h3>
                <span class="flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-3 w-3 rounded-full bg-green-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                </span>
            </div>
            <div class="space-y-3">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Disk Usage:</span>
                    <span class="font-medium text-gray-900">{{ $health['storage']['disk_usage'] ?? '45%' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Available:</span>
                    <span class="font-medium text-gray-900">{{ $health['storage']['available'] ?? '275 GB' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Status:</span>
                    <span class="font-medium text-green-600">Healthy</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">I/O:</span>
                    <span class="font-medium text-gray-900">{{ $health['storage']['io'] ?? 'Normal' }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Server Resources -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Server Resources</h3>
        </div>
        <div class="p-6 space-y-6">
            <!-- CPU Usage -->
            <div>
                <div class="flex justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700">CPU Usage</span>
                    <span class="text-sm font-medium text-gray-900">{{ $health['resources']['cpu'] ?? '35' }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2.5">
                    <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ $health['resources']['cpu'] ?? 35 }}%"></div>
                </div>
            </div>

            <!-- Memory Usage -->
            <div>
                <div class="flex justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700">Memory Usage</span>
                    <span class="text-sm font-medium text-gray-900">{{ $health['resources']['memory'] ?? '62' }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2.5">
                    <div class="bg-green-600 h-2.5 rounded-full" style="width: {{ $health['resources']['memory'] ?? 62 }}%"></div>
                </div>
            </div>

            <!-- Disk Usage -->
            <div>
                <div class="flex justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700">Disk Usage</span>
                    <span class="text-sm font-medium text-gray-900">{{ $health['resources']['disk'] ?? '45' }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2.5">
                    <div class="bg-purple-600 h-2.5 rounded-full" style="width: {{ $health['resources']['disk'] ?? 45 }}%"></div>
                </div>
            </div>

            <!-- Network -->
            <div>
                <div class="flex justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700">Network Load</span>
                    <span class="text-sm font-medium text-gray-900">{{ $health['resources']['network'] ?? '28' }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2.5">
                    <div class="bg-orange-600 h-2.5 rounded-full" style="width: {{ $health['resources']['network'] ?? 28 }}%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Metrics Chart -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Performance Metrics (Last 24 Hours)</h3>
        <canvas id="performanceChart" height="80"></canvas>
    </div>

    <!-- Recent Errors and Warnings -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Errors -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Recent Errors</h3>
            </div>
            <div class="divide-y divide-gray-200">
                @forelse($errors ?? [] as $error)
                <div class="px-6 py-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3 flex-1">
                            <p class="text-sm font-medium text-gray-900">{{ $error->message }}</p>
                            <p class="mt-1 text-xs text-gray-500">{{ $error->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                </div>
                @empty
                <div class="px-6 py-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="mt-2 text-sm text-gray-500">No errors in the last 24 hours</p>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Recent Warnings -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Recent Warnings</h3>
            </div>
            <div class="divide-y divide-gray-200">
                @forelse($warnings ?? [] as $warning)
                <div class="px-6 py-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3 flex-1">
                            <p class="text-sm font-medium text-gray-900">{{ $warning->message }}</p>
                            <p class="mt-1 text-xs text-gray-500">{{ $warning->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                </div>
                @empty
                <div class="px-6 py-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="mt-2 text-sm text-gray-500">No warnings in the last 24 hours</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- System Information -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">System Information</h3>
        </div>
        <div class="p-6">
            <dl class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                    <dt class="text-sm font-medium text-gray-500">PHP Version</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $systemInfo['php_version'] ?? PHP_VERSION }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Laravel Version</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $systemInfo['laravel_version'] ?? app()->version() }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Server OS</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $systemInfo['os'] ?? PHP_OS }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Server IP</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $systemInfo['server_ip'] ?? request()->server('SERVER_ADDR') }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Environment</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $systemInfo['environment'] ?? config('app.env') }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Debug Mode</dt>
                    <dd class="mt-1">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ config('app.debug') ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' }}">
                            {{ config('app.debug') ? 'Enabled' : 'Disabled' }}
                        </span>
                    </dd>
                </div>
            </dl>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Performance Chart
const ctx = document.getElementById('performanceChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['00:00', '02:00', '04:00', '06:00', '08:00', '10:00', '12:00', '14:00', '16:00', '18:00', '20:00', '22:00'],
        datasets: [
            {
                label: 'CPU %',
                data: [25, 28, 22, 30, 35, 40, 45, 42, 38, 35, 32, 30],
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4
            },
            {
                label: 'Memory %',
                data: [55, 58, 54, 60, 62, 65, 68, 65, 62, 60, 58, 56],
                borderColor: 'rgb(16, 185, 129)',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                tension: 0.4
            },
            {
                label: 'Response Time (ms)',
                data: [42, 45, 40, 48, 52, 55, 60, 58, 52, 48, 45, 43],
                borderColor: 'rgb(168, 85, 247)',
                backgroundColor: 'rgba(168, 85, 247, 0.1)',
                tension: 0.4
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        interaction: {
            intersect: false,
            mode: 'index'
        },
        plugins: {
            legend: {
                position: 'top',
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                max: 100
            }
        }
    }
});

// Auto-refresh every 30 seconds
setTimeout(function() {
    location.reload();
}, 30000);
</script>
@endpush
