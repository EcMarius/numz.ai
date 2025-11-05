<div x-data="{ loading: false }" class="w-full">
    <a href="{{ route('social.redirect', $slug) }}"
       @click="loading = true"
       :class="{ 'pointer-events-none opacity-75': loading }"
       class="flex @if(config('devdojo.auth.settings.center_align_social_provider_button_content')){{ 'justify-center' }}@endif items-center px-4 py-3 space-x-2.5 w-full h-auto text-sm rounded-md border border-zinc-200 text-zinc-600 hover:bg-zinc-100 transition-all duration-200 hover:shadow-md">

        {{-- Normal state --}}
        <span x-show="!loading" class="flex items-center space-x-2.5">
            <span class="w-5 h-5 flex-shrink-0">
                @php
                    // Check multiple sources for SVG
                    $svg = null;
                    if (isset($provider->svg) && !empty(trim($provider->svg))) {
                        $svg = $provider->svg;
                    } elseif (isset($provider['svg']) && !empty(trim($provider['svg']))) {
                        $svg = $provider['svg'];
                    } else {
                        // Try to get from config
                        $configProviders = config('socialauth.providers', []);
                        if (isset($configProviders[$slug]['svg'])) {
                            $svg = $configProviders[$slug]['svg'];
                        }
                    }
                @endphp
                @if($svg)
                    {!! $svg !!}
                @else
                    <span class="block w-full h-full rounded-full bg-zinc-200"></span>
                @endif
            </span>
            <span>Continue with {{ $provider->name ?? ucfirst($slug) }}</span>
        </span>

        {{-- Loading state --}}
        <span x-show="loading" x-cloak class="flex items-center space-x-2.5">
            <span class="w-5 h-5 flex-shrink-0 opacity-50">
                @if($svg)
                    {!! $svg !!}
                @else
                    <span class="block w-full h-full rounded-full bg-zinc-200"></span>
                @endif
            </span>
            <svg class="animate-spin w-4 h-4 text-zinc-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Connecting to {{ $provider->name ?? ucfirst($slug) }}...</span>
        </span>
    </a>
</div>

@once
    @push('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
    @endpush

    @push('devdojo-auth-head-scripts')
    <!-- Alpine.js for social button loading state -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Reset loading state if navigation fails or user returns to page
            window.addEventListener('pageshow', function(event) {
                if (event.persisted || (performance.getEntriesByType && performance.getEntriesByType("navigation")[0] && performance.getEntriesByType("navigation")[0].type === 'back_forward')) {
                    // Page was loaded from cache (back button) - find all Alpine components and reset loading
                    setTimeout(function() {
                        document.querySelectorAll('[x-data]').forEach(element => {
                            if (element.__x && element.__x.$data && 'loading' in element.__x.$data) {
                                element.__x.$data.loading = false;
                            }
                        });
                    }, 100);
                }
            });
        });
    </script>
    @endpush
@endonce
