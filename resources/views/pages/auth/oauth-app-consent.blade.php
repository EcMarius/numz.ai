<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Authorize {{ $client['name'] }} - EvenLeads</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="h-full bg-white dark:bg-zinc-900" x-data="{ approving: false, denying: false, submitForm(action) {
    console.log('submitForm called with action:', action);
    if (action === 'approve') {
        this.approving = true;
    } else {
        this.denying = true;
    }
    this.$refs.consentForm.submit();
} }">
    <div class="min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <!-- Card -->
            <div class="bg-white dark:bg-zinc-800 border-2 border-zinc-200 dark:border-zinc-700 rounded-xl shadow-sm overflow-hidden">

                <!-- Header -->
                <div class="border-b-2 border-zinc-200 dark:border-zinc-700 p-6 text-center bg-white dark:bg-zinc-800">
                    <div class="flex items-center justify-center gap-4 mb-4">
                        <!-- Extension Icon -->
                        <div class="w-12 h-12 bg-zinc-900 dark:bg-white rounded-lg flex items-center justify-center">
                            <x-phosphor-puzzle-piece class="w-6 h-6 text-white dark:text-zinc-900" />
                        </div>

                        <!-- Arrow -->
                        <x-phosphor-arrow-right class="w-6 h-6 text-zinc-400" />

                        <!-- EvenLeads Logo -->
                        @php
                            $logoPath = setting('site.logo');
                            $logoUrl = null;
                            if ($logoPath) {
                                // Check if it starts with http:// or https://
                                if (str_starts_with($logoPath, 'http://') || str_starts_with($logoPath, 'https://')) {
                                    $logoUrl = $logoPath;
                                } else {
                                    // Try to get public asset path
                                    $logoUrl = asset('storage/' . str_replace('public/', '', $logoPath));
                                }
                            }
                        @endphp
                        @if($logoUrl)
                            <img src="{{ $logoUrl }}" alt="EvenLeads" class="h-12 w-auto" onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="w-12 h-12 bg-zinc-900 dark:bg-white rounded-lg flex items-center justify-center" style="display:none;">
                                <span class="text-white dark:text-zinc-900 font-bold text-xl">1L</span>
                            </div>
                        @else
                            <div class="w-12 h-12 bg-zinc-900 dark:bg-white rounded-lg flex items-center justify-center">
                                <span class="text-white dark:text-zinc-900 font-bold text-xl">1L</span>
                            </div>
                        @endif
                    </div>

                    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white mb-2">
                        Authorize {{ $client['name'] }}
                    </h1>
                    <p class="text-zinc-600 dark:text-zinc-400 text-sm">
                        {{ $client['description'] }}
                    </p>
                </div>

                <!-- User Info -->
                <div class="bg-zinc-50 dark:bg-zinc-900 border-b-2 border-zinc-200 dark:border-zinc-700 p-4">
                    <div class="flex items-center gap-3">
                        @php
                            $avatarUrl = null;
                            if ($user->avatar) {
                                // Check if it's already a full URL
                                if (str_starts_with($user->avatar, 'http://') || str_starts_with($user->avatar, 'https://')) {
                                    $avatarUrl = $user->avatar;
                                } else {
                                    // Try to get public asset path
                                    $avatarUrl = asset('storage/' . str_replace('public/', '', $user->avatar));
                                }
                            }

                            // Fallback to default avatar from settings
                            if (!$avatarUrl) {
                                $defaultAvatar = setting('site.default_avatar');
                                if ($defaultAvatar) {
                                    if (str_starts_with($defaultAvatar, 'http://') || str_starts_with($defaultAvatar, 'https://')) {
                                        $avatarUrl = $defaultAvatar;
                                    } else {
                                        $avatarUrl = asset('storage/' . str_replace('public/', '', $defaultAvatar));
                                    }
                                }
                            }
                        @endphp
                        @if($avatarUrl)
                            <img src="{{ $avatarUrl }}" alt="{{ $user->name }}" class="w-10 h-10 rounded-full object-cover" onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="w-10 h-10 bg-zinc-900 dark:bg-white rounded-full flex items-center justify-center" style="display:none;">
                                <span class="text-white dark:text-zinc-900 font-semibold text-sm">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </span>
                            </div>
                        @else
                            <div class="w-10 h-10 bg-zinc-900 dark:bg-white rounded-full flex items-center justify-center">
                                <span class="text-white dark:text-zinc-900 font-semibold text-sm">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </span>
                            </div>
                        @endif
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-zinc-900 dark:text-white text-sm truncate">{{ $user->name }}</p>
                            <p class="text-xs text-zinc-600 dark:text-zinc-400 truncate">{{ $user->email }}</p>
                        </div>
                    </div>
                </div>

                <!-- Permissions -->
                <div class="p-6">
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-white mb-4">
                        <strong>{{ $client['name'] }}</strong> is requesting permission to:
                    </h2>

                    <div class="space-y-3">
                        @foreach($permissions as $permission)
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0 w-5 h-5 text-zinc-900 dark:text-white mt-0.5">
                                    @if($permission['icon'] === '👤')
                                        <x-phosphor-user />
                                    @elseif($permission['icon'] === '✏️')
                                        <x-phosphor-pencil />
                                    @elseif($permission['icon'] === '📊')
                                        <x-phosphor-chart-bar />
                                    @elseif($permission['icon'] === '🎯')
                                        <x-phosphor-target />
                                    @elseif($permission['icon'] === '🔌')
                                        <x-phosphor-plug />
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-zinc-900 dark:text-white text-sm">
                                        {{ $permission['title'] }}
                                    </p>
                                    <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-0.5">
                                        {{ $permission['description'] }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Security Notice -->
                    <div class="mt-6 p-3 bg-zinc-100 dark:bg-zinc-900 border-2 border-zinc-200 dark:border-zinc-700 rounded-lg">
                        <p class="text-xs text-zinc-700 dark:text-zinc-300 flex items-start gap-2">
                            <x-phosphor-info class="w-4 h-4 flex-shrink-0 mt-0.5" />
                            <span><strong>Important:</strong> You can revoke this access anytime from your account settings.</span>
                        </p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="border-t-2 border-zinc-200 dark:border-zinc-700 p-6 bg-zinc-50 dark:bg-zinc-900">
                    <form method="POST" action="{{ route('oauth.consent.handle') }}" class="space-y-3" x-ref="consentForm">
                        @csrf
                        <input type="hidden" name="action" x-model="approving ? 'approve' : (denying ? 'deny' : '')" />

                        <button
                            type="button"
                            @click="approving = true; $refs.consentForm.querySelector('input[name=action]').value = 'approve'; $refs.consentForm.submit();"
                            :disabled="approving || denying"
                            class="w-full px-6 py-3 bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 rounded-lg font-medium hover:bg-zinc-800 dark:hover:bg-zinc-100 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                        >
                            <span x-show="approving" x-cloak>
                                <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </span>
                            <span x-text="approving ? 'Authorizing...' : 'Authorize'"></span>
                        </button>

                        <button
                            type="button"
                            @click="denying = true; $refs.consentForm.querySelector('input[name=action]').value = 'deny'; localStorage.setItem('evenleads_auth_denied', Date.now().toString()); $refs.consentForm.submit();"
                            :disabled="approving || denying"
                            class="w-full px-6 py-3 bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 border-2 border-zinc-200 dark:border-zinc-700 rounded-lg font-medium hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                        >
                            <span x-show="denying" x-cloak>
                                <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </span>
                            <span x-text="denying ? 'Denying...' : 'Deny'"></span>
                        </button>
                    </form>

                    <p class="mt-4 text-xs text-center text-zinc-500 dark:text-zinc-500">
                        By authorizing, you agree to share the information listed above with {{ $client['name'] }}.
                    </p>
                </div>
            </div>

            <!-- Back Link -->
            <div class="mt-4 text-center">
                <a href="{{ route('home') }}" class="text-sm text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white inline-flex items-center gap-1">
                    <x-phosphor-arrow-left class="w-4 h-4" />
                    Back to EvenLeads
                </a>
            </div>
        </div>
    </div>

    <!-- Handle window close to notify extension -->
    <script>
        // Debug: Log when Alpine is ready
        document.addEventListener('alpine:init', () => {
            console.log('Alpine.js initialized');
        });

        // Handle form submission
        const form = document.getElementById('consentForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                console.log('Form submitting...', new FormData(this));
                const submitter = e.submitter || document.activeElement;
                const action = submitter.getAttribute('value');
                console.log('Action:', action);

                // Set the loading state based on which button was clicked
                if (action === 'approve') {
                    window.Alpine && Alpine.store && (Alpine.store('approving', true));
                } else if (action === 'deny') {
                    window.Alpine && Alpine.store && (Alpine.store('denying', true));
                    localStorage.setItem('evenleads_auth_denied', Date.now().toString());
                }
            });
        }

        // Notify extension when window is closing
        window.addEventListener('beforeunload', function() {
            localStorage.setItem('evenleads_auth_closed', Date.now().toString());
        });

        // Debug: Check if form and buttons exist
        console.log('Consent page loaded');
        console.log('Form exists:', !!document.getElementById('consentForm'));
        console.log('Approve button exists:', !!document.querySelector('button[value="approve"]'));
        console.log('Deny button exists:', !!document.querySelector('button[value="deny"]'));
        console.log('CSRF token exists:', !!document.querySelector('input[name="_token"]'));
    </script>
</body>
</html>
