@extends('dashboard.layouts.app')

@section('title', 'API Keys')

@section('content')
<div class="max-w-6xl mx-auto">
    <!-- Header -->
    <div class="flex items-start justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">API Keys</h1>
            <p class="text-gray-600">Manage your API keys for programmatic access</p>
        </div>
        <button onclick="document.getElementById('createKeyModal').classList.remove('hidden')" class="px-4 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-medium rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-all shadow-lg shadow-blue-500/30">
            <svg class="w-5 h-5 inline-block mr-2 -mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Create New Key
        </button>
    </div>

    <!-- Success/Error Messages -->
    @if(session('success'))
    <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-start">
        <svg class="w-5 h-5 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    @if(session('new_key'))
    <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-start mb-3">
            <svg class="w-5 h-5 text-blue-600 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="flex-1">
                <h3 class="text-sm font-semibold text-blue-900 mb-1">API Key Created Successfully</h3>
                <p class="text-sm text-blue-800 mb-3">Make sure to copy your API key now. You won't be able to see it again!</p>
                <div class="bg-white p-3 rounded border border-blue-300 font-mono text-sm break-all flex items-center justify-between">
                    <span id="newApiKey">{{ session('new_key') }}</span>
                    <button onclick="copyToClipboard('newApiKey')" class="ml-3 px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded hover:bg-blue-700 transition-colors flex-shrink-0">
                        Copy
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- API Documentation Link -->
    <div class="mb-6 bg-gradient-to-r from-purple-50 to-pink-50 border border-purple-200 rounded-lg p-4 flex items-center justify-between">
        <div class="flex items-start">
            <svg class="w-5 h-5 text-purple-600 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
            <div>
                <h3 class="text-sm font-semibold text-purple-900 mb-1">API Documentation</h3>
                <p class="text-sm text-purple-800 mb-2">Learn how to integrate with our API and explore available endpoints.</p>
                <a href="{{ route('docs.api') }}" class="text-sm font-medium text-purple-600 hover:text-purple-700">
                    View Documentation →
                </a>
            </div>
        </div>
    </div>

    <!-- API Keys List -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Your API Keys</h2>
        </div>

        @php
            $apiKeys = $apiKeys ?? [];
        @endphp

        @if(count($apiKeys) > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Key</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Permissions</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Used</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($apiKeys as $key)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ $key->name }}</div>
                            @if($key->description)
                            <div class="text-xs text-gray-500 mt-1">{{ $key->description }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <code class="text-xs font-mono text-gray-600 bg-gray-100 px-2 py-1 rounded">{{ substr($key->key, 0, 16) }}...{{ substr($key->key, -4) }}</code>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-1">
                                @foreach($key->permissions ?? ['read'] as $permission)
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ $permission === 'write' ? 'bg-orange-100 text-orange-800' : 'bg-blue-100 text-blue-800' }}">
                                    {{ ucfirst($permission) }}
                                </span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            @if($key->last_used_at)
                            {{ $key->last_used_at->diffForHumans() }}
                            @else
                            <span class="text-gray-400">Never</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            {{ $key->created_at->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $key->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $key->is_active ? 'Active' : 'Disabled' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right text-sm">
                            <div class="flex items-center justify-end gap-2">
                                <button onclick="viewKeyUsage({{ $key->id }})" class="text-blue-600 hover:text-blue-700 font-medium" title="View Usage">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                </button>
                                <button onclick="editKey({{ $key->id }})" class="text-gray-600 hover:text-gray-700 font-medium" title="Edit">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                <form action="{{ route('dashboard.api-keys.revoke', $key->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to revoke this API key? This action cannot be undone.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-700 font-medium" title="Revoke">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Usage Statistics -->
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
            <div class="flex items-center justify-between text-sm">
                <div class="text-gray-600">
                    <span class="font-medium text-gray-900">{{ count($apiKeys) }}</span> API key(s)
                </div>
                <div class="text-gray-600">
                    Total requests this month: <span class="font-medium text-gray-900">{{ $totalRequests ?? 0 }}</span>
                </div>
            </div>
        </div>
        @else
        <!-- Empty State -->
        <div class="px-6 py-12 text-center">
            <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No API Keys Yet</h3>
            <p class="text-sm text-gray-600 mb-6">Create your first API key to start integrating with our platform.</p>
            <button onclick="document.getElementById('createKeyModal').classList.remove('hidden')" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                Create Your First API Key
            </button>
        </div>
        @endif
    </div>

    <!-- Rate Limits Card -->
    <div class="mt-6 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-gradient-to-r from-orange-50 to-amber-50 px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Rate Limits</h2>
        </div>
        <div class="p-6">
            <div class="grid md:grid-cols-3 gap-6">
                <div class="text-center">
                    <div class="text-3xl font-bold text-gray-900 mb-1">{{ $rateLimits['per_minute'] ?? 60 }}</div>
                    <div class="text-sm text-gray-600">Requests per minute</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-gray-900 mb-1">{{ $rateLimits['per_hour'] ?? 1000 }}</div>
                    <div class="text-sm text-gray-600">Requests per hour</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-gray-900 mb-1">{{ $rateLimits['per_day'] ?? 10000 }}</div>
                    <div class="text-sm text-gray-600">Requests per day</div>
                </div>
            </div>
            <p class="text-sm text-gray-600 mt-4 text-center">Need higher limits? <a href="{{ route('contact') }}" class="text-blue-600 hover:text-blue-700 font-medium">Contact us</a></p>
        </div>
    </div>
</div>

<!-- Create API Key Modal -->
<div id="createKeyModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-6 py-4 flex items-center justify-between">
            <h3 class="text-xl font-semibold">Create New API Key</h3>
            <button onclick="document.getElementById('createKeyModal').classList.add('hidden')" class="text-white hover:text-gray-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form action="{{ route('dashboard.api-keys.store') }}" method="POST" class="p-6">
            @csrf

            <div class="space-y-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Key Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" placeholder="e.g., Production Server" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    <p class="mt-1 text-xs text-gray-500">A descriptive name to help you identify this key</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" rows="2" placeholder="Optional description of what this key is used for" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Permissions</label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="checkbox" name="permissions[]" value="read" checked class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Read - View resources and data</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="permissions[]" value="write" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Write - Create and modify resources</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Expiration (Optional)</label>
                    <select name="expires_in" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Never expires</option>
                        <option value="30">30 days</option>
                        <option value="90">90 days</option>
                        <option value="180">180 days</option>
                        <option value="365">1 year</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
                <button type="button" onclick="document.getElementById('createKeyModal').classList.add('hidden')" class="px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button type="submit" class="px-6 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-medium rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-all">
                    Create API Key
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function copyToClipboard(elementId) {
    const element = document.getElementById(elementId);
    const text = element.textContent;
    navigator.clipboard.writeText(text).then(() => {
        // Show success feedback
        const button = element.nextElementSibling;
        const originalText = button.textContent;
        button.textContent = 'Copied!';
        setTimeout(() => {
            button.textContent = originalText;
        }, 2000);
    });
}

function viewKeyUsage(keyId) {
    // Implementation for viewing usage statistics
    window.location.href = `/dashboard/api-keys/${keyId}/usage`;
}

function editKey(keyId) {
    // Implementation for editing key
    window.location.href = `/dashboard/api-keys/${keyId}/edit`;
}
</script>
@endsection
