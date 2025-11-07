@extends('dashboard.layouts.app')

@section('title', 'SSH Keys')

@section('content')
<div class="max-w-6xl mx-auto">
    <!-- Header -->
    <div class="flex items-start justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">SSH Keys</h1>
            <p class="text-gray-600">Manage your SSH keys for secure server access</p>
        </div>
        <button onclick="document.getElementById('addKeyModal').classList.remove('hidden')" class="px-4 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-medium rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-all shadow-lg shadow-blue-500/30">
            <svg class="w-5 h-5 inline-block mr-2 -mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add SSH Key
        </button>
    </div>

    <!-- Success Message -->
    @if(session('success'))
    <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-start">
        <svg class="w-5 h-5 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    <!-- Info Box -->
    <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-start">
            <svg class="w-5 h-5 text-blue-600 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <h3 class="text-sm font-semibold text-blue-900 mb-1">What are SSH Keys?</h3>
                <p class="text-sm text-blue-800">SSH keys provide a secure way to access your server without using passwords. Add your public key here to enable SSH access to your services.</p>
            </div>
        </div>
    </div>

    <!-- SSH Keys List -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Your SSH Keys</h2>
        </div>

        @php
            $sshKeys = $sshKeys ?? [];
        @endphp

        @if(count($sshKeys) > 0)
        <div class="divide-y divide-gray-200">
            @foreach($sshKeys as $key)
            <div class="p-6 hover:bg-gray-50 transition-colors">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center mb-2">
                            <h3 class="text-lg font-semibold text-gray-900">{{ $key->name }}</h3>
                            <span class="ml-3 px-2.5 py-0.5 rounded-full text-xs font-medium {{ $key->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $key->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>

                        @if($key->description)
                        <p class="text-sm text-gray-600 mb-3">{{ $key->description }}</p>
                        @endif

                        <div class="space-y-2">
                            <div>
                                <span class="text-xs font-medium text-gray-500">Type:</span>
                                <code class="ml-2 text-xs font-mono text-gray-700 bg-gray-100 px-2 py-1 rounded">{{ $key->type ?? 'RSA' }}</code>
                            </div>
                            <div>
                                <span class="text-xs font-medium text-gray-500">Fingerprint:</span>
                                <code class="ml-2 text-xs font-mono text-gray-700 bg-gray-100 px-2 py-1 rounded">{{ $key->fingerprint }}</code>
                            </div>
                            <div>
                                <span class="text-xs font-medium text-gray-500">Public Key:</span>
                                <div class="mt-1 bg-gray-50 p-3 rounded border border-gray-200">
                                    <code class="text-xs font-mono text-gray-700 break-all">{{ substr($key->public_key, 0, 80) }}...</code>
                                    <button onclick="copyToClipboard('key-{{ $key->id }}')" class="ml-2 text-blue-600 hover:text-blue-700 text-xs font-medium">
                                        Copy Full Key
                                    </button>
                                    <input type="hidden" id="key-{{ $key->id }}" value="{{ $key->public_key }}">
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 flex items-center text-xs text-gray-500">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Added {{ $key->created_at->format('M d, Y') }}
                            @if($key->last_used_at)
                            <span class="ml-3">Last used {{ $key->last_used_at->diffForHumans() }}</span>
                            @else
                            <span class="ml-3 text-gray-400">Never used</span>
                            @endif
                        </div>
                    </div>

                    <div class="ml-6 flex items-center gap-2">
                        <button onclick="editKey({{ $key->id }})" class="p-2 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded transition-colors" title="Edit">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>
                        <form action="{{ route('dashboard.ssh-keys.destroy', $key->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this SSH key? This action cannot be undone.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-2 text-gray-600 hover:text-red-600 hover:bg-red-50 rounded transition-colors" title="Delete">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <!-- Empty State -->
        <div class="px-6 py-12 text-center">
            <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No SSH Keys Yet</h3>
            <p class="text-sm text-gray-600 mb-6">Add your first SSH key to enable secure server access.</p>
            <button onclick="document.getElementById('addKeyModal').classList.remove('hidden')" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                Add Your First SSH Key
            </button>
        </div>
        @endif
    </div>

    <!-- How to Generate SSH Keys -->
    <div class="mt-8 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-gradient-to-r from-purple-50 to-pink-50 px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">How to Generate SSH Keys</h2>
        </div>
        <div class="p-6">
            <div class="space-y-6">
                <!-- Linux/Mac -->
                <div>
                    <h3 class="text-base font-semibold text-gray-900 mb-3 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-gray-600" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                        </svg>
                        Linux / macOS
                    </h3>
                    <div class="bg-gray-900 rounded-lg p-4 text-sm">
                        <code class="text-green-400 font-mono">
                            ssh-keygen -t rsa -b 4096 -C "your_email@example.com"<br>
                            cat ~/.ssh/id_rsa.pub
                        </code>
                    </div>
                    <p class="mt-2 text-sm text-gray-600">Copy the output and paste it in the form above.</p>
                </div>

                <!-- Windows -->
                <div>
                    <h3 class="text-base font-semibold text-gray-900 mb-3 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-gray-600" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                        </svg>
                        Windows (PowerShell)
                    </h3>
                    <div class="bg-gray-900 rounded-lg p-4 text-sm">
                        <code class="text-green-400 font-mono">
                            ssh-keygen -t rsa -b 4096<br>
                            type $env:USERPROFILE\.ssh\id_rsa.pub
                        </code>
                    </div>
                    <p class="mt-2 text-sm text-gray-600">Or use PuTTYgen to generate SSH keys on Windows.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add SSH Key Modal -->
<div id="addKeyModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-6 py-4 flex items-center justify-between">
            <h3 class="text-xl font-semibold">Add SSH Key</h3>
            <button onclick="document.getElementById('addKeyModal').classList.add('hidden')" class="text-white hover:text-gray-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form action="{{ route('dashboard.ssh-keys.store') }}" method="POST" class="p-6">
            @csrf

            <div class="space-y-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Key Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" placeholder="e.g., My Laptop" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    <p class="mt-1 text-xs text-gray-500">A friendly name to identify this key</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <input type="text" name="description" placeholder="Optional description" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Public Key <span class="text-red-500">*</span></label>
                    <textarea name="public_key" rows="6" placeholder="Paste your public SSH key here (starts with ssh-rsa, ssh-ed25519, etc.)" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono text-sm" required></textarea>
                    <p class="mt-1 text-xs text-gray-500">Paste the contents of your .pub file (public key only, never paste your private key!)</p>
                </div>
            </div>

            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-yellow-600 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div>
                        <h4 class="text-sm font-semibold text-yellow-900 mb-1">Security Warning</h4>
                        <p class="text-sm text-yellow-800">Only paste your PUBLIC key. Never share or upload your private key!</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
                <button type="button" onclick="document.getElementById('addKeyModal').classList.add('hidden')" class="px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button type="submit" class="px-6 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-medium rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-all">
                    Add SSH Key
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function copyToClipboard(elementId) {
    const element = document.getElementById(elementId);
    const text = element.value;
    navigator.clipboard.writeText(text).then(() => {
        alert('SSH key copied to clipboard!');
    });
}

function editKey(keyId) {
    window.location.href = `/dashboard/ssh-keys/${keyId}/edit`;
}
</script>
@endsection
