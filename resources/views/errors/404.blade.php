<x-layouts.marketing
    :seo="[
        'title' => '404 - Page Not Found',
        'description' => 'The page you are looking for could not be found.',
    ]"
>

<div class="flex flex-col min-h-screen" x-data="{ show: false }" x-init="setTimeout(() => show = true, 100)">

    <!-- Main Content -->
    <div class="flex-grow flex items-center justify-center px-8 py-16 md:px-12 xl:px-20">
        <div class="w-full max-w-4xl mx-auto text-center">

            <!-- Image Above -->
            <div class="mb-12 flex items-center justify-center" x-show="show" x-transition:enter="transition ease-out duration-700" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                <div class="relative w-full max-w-xs">
                    @php
                        $notFoundImage = setting('site.404_image', null);
                        if (!$notFoundImage || !file_exists(public_path($notFoundImage))) {
                            $notFoundImage = null;
                        }
                    @endphp

                    @if($notFoundImage)
                        <img src="{{ $notFoundImage }}" alt="404 Not Found" class="w-full h-auto rounded-lg">
                    @else
                        <!-- Default SVG Illustration -->
                        <div class="w-full aspect-square flex items-center justify-center bg-zinc-50 rounded-lg border border-zinc-200 p-8">
                            <svg viewBox="0 0 500 500" class="w-full h-full" xmlns="http://www.w3.org/2000/svg">
                                <!-- Magnifying Glass -->
                                <circle cx="180" cy="180" r="120" fill="none" stroke="#18181b" stroke-width="16" stroke-linecap="round"/>
                                <line x1="270" y1="270" x2="350" y2="350" stroke="#18181b" stroke-width="16" stroke-linecap="round"/>

                                <!-- Question Mark inside magnifying glass -->
                                <text x="180" y="210" font-size="120" font-weight="bold" text-anchor="middle" fill="#52525b">?</text>

                                <!-- Floating 404 Numbers -->
                                <g opacity="0.3">
                                    <text x="380" y="120" font-size="60" font-weight="bold" fill="#71717a">4</text>
                                    <text x="420" y="200" font-size="60" font-weight="bold" fill="#71717a">0</text>
                                    <text x="380" y="450" font-size="60" font-weight="bold" fill="#71717a">4</text>
                                </g>

                                <!-- Decorative dots -->
                                <circle cx="400" cy="50" r="8" fill="#a1a1aa" opacity="0.4"/>
                                <circle cx="460" cy="380" r="12" fill="#a1a1aa" opacity="0.4"/>
                                <circle cx="50" cy="250" r="10" fill="#a1a1aa" opacity="0.4"/>
                                <circle cx="430" cy="280" r="6" fill="#a1a1aa" opacity="0.4"/>
                            </svg>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Text Content -->
            <div x-show="show" x-transition:enter="transition ease-out duration-700 delay-150" x-transition:enter-start="opacity-0 translate-y-8" x-transition:enter-end="opacity-100 translate-y-0">

                <!-- Heading -->
                <h1 class="text-6xl font-bold tracking-tighter sm:text-7xl md:text-[84px] text-zinc-900 text-balance mb-6">
                    <span class="block">Oops!</span>
                    <span class="text-transparent bg-clip-text bg-gradient-to-b text-neutral-600 from-neutral-900 to-neutral-500">
                        Page Not Found
                    </span>
                </h1>

                <!-- Description -->
                <p class="mt-5 text-lg font-normal md:text-xl text-zinc-500 mb-8 max-w-2xl mx-auto">
                    Looks like this page wandered off! Don't worry though, we've got plenty of other pages for you to explore.
                </p>

                <!-- Creative Message -->
                <div class="mb-8 p-4 bg-zinc-50 border border-zinc-200 rounded-lg max-w-2xl mx-auto">
                    <p class="text-sm text-zinc-700">
                        While you're here, EvenLeads is probably finding new potential leads for your business. Want to check them out?
                    </p>
                </div>

                <!-- Action Buttons - Centered with margin-bottom -->
                <div class="flex flex-col sm:flex-row gap-3 justify-center items-center mb-16">
                    <a href="/" class="inline-flex items-center justify-center px-6 py-3 text-base font-medium text-white bg-zinc-900 rounded-lg hover:bg-zinc-800 transition-all">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        Back to Home
                    </a>
                    @auth
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center px-6 py-3 text-base font-medium text-white bg-zinc-900 rounded-lg hover:bg-zinc-800 transition-all">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2 a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                            </svg>
                            Go to Dashboard
                        </a>
                    @else
                        <a href="{{ route('pricing') }}" class="inline-flex items-center justify-center px-6 py-3 text-base font-medium text-white bg-zinc-900 rounded-lg hover:bg-zinc-800 transition-all">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            View Pricing
                        </a>
                    @endauth
                </div>
            </div>

        </div>
    </div>

</div>

</x-layouts.marketing>
