<div x-data="{ loading: false }" class="w-full">
    <a href="{{ route('social.redirect', $slug) }}"
       @click="loading = true"
       :class="{ 'pointer-events-none opacity-75': loading }"
       class="flex @if(config('devdojo.auth.settings.center_align_social_provider_button_content')){{ 'justify-center' }}@endif items-center px-4 py-3 space-x-2.5 w-full h-auto text-sm rounded-md border border-zinc-200 text-zinc-600 hover:bg-zinc-100 transition-all duration-200 hover:shadow-md">

        {{-- Normal state --}}
        <span x-show="!loading" class="flex items-center space-x-2.5">
            <span class="w-5 h-5 flex-shrink-0">
                @php
                    // Define SVGs for all providers
                    $svgs = [
                        'google' => '<svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" fill="none"><path fill="#4285F4" d="M24 19.636v9.295h12.916c-.567 2.989-2.27 5.52-4.822 7.222l7.79 6.043c4.537-4.188 7.155-10.341 7.155-17.65 0-1.702-.152-3.339-.436-4.910H24Z"/><path fill="#34A853" d="m10.55 28.568-1.757 1.345-6.219 4.843C6.524 42.59 14.617 48 24 48c6.48 0 11.913-2.138 15.884-5.804l-7.79-6.043c-2.138 1.44-4.865 2.313-8.094 2.313-6.24 0-11.541-4.211-13.44-9.884l-.01-.014Z"/><path fill="#FBBC05" d="M2.574 13.244A23.704 23.704 0 0 0 0 24c0 3.883.938 7.527 2.574 10.756 0 .022 7.986-6.196 7.986-6.196A14.384 14.384 0 0 1 9.796 24c0-1.593.284-3.12.764-4.56l-7.986-6.196Z"/><path fill="#EA4335" d="M24 9.556c3.534 0 6.676 1.222 9.185 3.579l6.873-6.873C35.89 2.378 30.48 0 24 0 14.618 0 6.523 5.39 2.574 13.244l7.986 6.196c1.898-5.673 7.2-9.884 13.44-9.884Z"/></svg>',
                        'facebook' => '<svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" fill="none"><path fill="#0866FF" d="M48 24C48 10.745 37.255 0 24 0S0 10.745 0 24c0 11.255 7.75 20.7 18.203 23.293V31.334h-4.95V24h4.95v-3.16c0-8.169 3.697-11.955 11.716-11.955 1.521 0 4.145.298 5.218.596v6.648c-.566-.06-1.55-.09-2.773-.09-3.935 0-5.455 1.492-5.455 5.367V24h7.84L33.4 31.334H26.91v16.49C38.793 46.39 48 36.271 48 24H48Z"/><path fill="#fff" d="M33.4 31.334 34.747 24h-7.84v-2.594c0-3.875 1.521-5.366 5.457-5.366 1.222 0 2.206.03 2.772.089V9.481c-1.073-.299-3.697-.596-5.218-.596-8.02 0-11.716 3.786-11.716 11.955V24h-4.95v7.334h4.95v15.96a24.042 24.042 0 0 0 8.705.53v-16.49H33.4Z"/></svg>',
                        'github' => '<svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" fill="none"><path fill="#24292F" fill-rule="evenodd" d="M24.02 0C10.738 0 0 10.817 0 24.198 0 34.895 6.88 43.95 16.424 47.154c1.193.241 1.63-.52 1.63-1.161 0-.561-.039-2.484-.039-4.488-6.682 1.443-8.073-2.884-8.073-2.884-1.074-2.805-2.665-3.525-2.665-3.525-2.187-1.483.16-1.483.16-1.483 2.425.16 3.698 2.484 3.698 2.484 2.147 3.686 5.607 2.644 7 2.003.198-1.562.834-2.644 1.51-3.245-5.329-.56-10.936-2.644-10.936-11.939 0-2.644.954-4.807 2.466-6.49-.239-.6-1.074-3.085.239-6.41 0 0 2.028-.641 6.6 2.484 1.959-.53 3.978-.8 6.006-.802 2.028 0 4.095.281 6.005.802 4.573-3.125 6.601-2.484 6.601-2.484 1.313 3.325.477 5.81.239 6.41 1.55 1.683 2.465 3.846 2.465 6.49 0 9.295-5.607 11.338-10.976 11.94.876.76 1.63 2.202 1.63 4.486 0 3.245-.039 5.85-.039 6.65 0 .642.438 1.403 1.63 1.163C41.12 43.949 48 34.895 48 24.198 48.04 10.817 37.262 0 24.02 0Z" clip-rule="evenodd"/></svg>',
                        'twitter' => '<svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" fill="none"><path fill="#000" d="M36.653 3.808H43.4L28.66 20.655 46 43.58H32.422L21.788 29.676 9.62 43.58H2.869l15.766-18.02L2 3.808h13.922l9.613 12.709 11.118-12.71ZM34.285 39.54h3.738L13.891 7.634H9.879l24.406 31.907Z"/></svg>',
                    ];

                    // Get the SVG for this provider
                    $svg = $svgs[$slug] ?? null;

                    // Fallback: try to get from provider object/array
                    if (!$svg && isset($provider->svg) && !empty(trim($provider->svg))) {
                        $svg = $provider->svg;
                    } elseif (!$svg && isset($provider['svg']) && !empty(trim($provider['svg']))) {
                        $svg = $provider['svg'];
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