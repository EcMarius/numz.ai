@php
    use Wave\Plugins\EvenLeads\Models\Setting;
    $trialDays = Setting::getValue('trial_days', 7);
@endphp

<section class="flex relative top-0 flex-col justify-center items-center -mt-24 w-full min-h-screen bg-white lg:min-h-screen">
    <div class="flex flex-col flex-1 gap-6 justify-between items-center px-8 pt-32 mx-auto w-full max-w-2xl text-left md:px-12 xl:px-20 lg:pt-32 lg:pb-16 lg:max-w-7xl lg:flex-row">
        <div class="w-full lg:w-1/2">
            <div class="inline-flex items-center px-4 py-2 mb-6 text-sm font-medium rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                Start Your {{ $trialDays }}-Day Free Trial Today
            </div>
            <h1 class="text-6xl font-bold tracking-tighter text-left sm:text-7xl md:text-[84px] sm:text-center lg:text-left text-zinc-900 text-balance">
                <span class="block origin-left lg:scale-90">Find Leads</span> <span class="pr-4 text-transparent bg-clip-text bg-gradient-to-b text-neutral-600 from-neutral-900 to-neutral-500">in Seconds</span>
            </h1>
            @php
                // Get active platforms with caching (1 hour TTL)
                $activePlatformNames = Cache::remember('evenleads.active_platforms', 3600, function() {
                    try {
                        return \Wave\Plugins\EvenLeads\Models\Platform::where('is_active', true)->pluck('name')->toArray();
                    } catch (\Exception $e) {
                        return ['reddit']; // Fallback if table doesn't exist (localhost)
                    }
                });

                // Get display names
                $activePlatforms = [];
                foreach ($activePlatformNames as $name) {
                    $activePlatforms[] = ucfirst($name === 'x' ? 'X (Twitter)' : $name);
                }

                // Default to Reddit if no platforms are enabled
                if (empty($activePlatforms)) {
                    $activePlatforms = ['Reddit'];
                    $activePlatformNames = ['reddit'];
                }

                $platformText = count($activePlatforms) > 1
                    ? implode(', ', array_slice($activePlatforms, 0, -1)) . ' and ' . end($activePlatforms)
                    : $activePlatforms[0];
            @endphp
            <p class="mx-auto mt-5 text-lg font-normal text-left md:text-xl sm:max-w-md lg:ml-0 lg:max-w-lg sm:text-center lg:text-left text-zinc-500">
                Stop wasting hours searching for potential customers. EvenLeads finds qualified leads from {{ $platformText }} automatically<span class="hidden sm:inline">. Get notified instantly when someone needs your service</span>.
            </p>
            <div class="flex flex-col gap-3 justify-center items-center mx-auto mt-8 md:gap-2 lg:justify-start md:ml-0 md:flex-row">
                <x-button tag="a" href="/register" size="lg" class="w-full lg:w-auto">Start Free Trial</x-button>
                <x-button tag="a" href="#pricing" size="lg" color="secondary" class="w-full lg:w-auto">View Pricing</x-button>
            </div>
            @php
                // Get all platforms with caching (1 hour TTL)
                $allPlatforms = Cache::remember('evenleads.all_platforms', 3600, function() {
                    try {
                        return \Wave\Plugins\EvenLeads\Models\Platform::orderBy('is_active', 'desc')->orderBy('id')->get()->toArray();
                    } catch (\Exception $e) {
                        // Fallback hardcoded platforms for localhost
                        return [
                            ['name' => 'reddit', 'display_name' => 'Reddit', 'is_active' => true],
                            ['name' => 'facebook', 'display_name' => 'Facebook', 'is_active' => false],
                            ['name' => 'x', 'display_name' => 'X (Twitter)', 'is_active' => false],
                            ['name' => 'linkedin', 'display_name' => 'LinkedIn', 'is_active' => false],
                            ['name' => 'fiverr', 'display_name' => 'Fiverr', 'is_active' => false],
                            ['name' => 'upwork', 'display_name' => 'Upwork', 'is_active' => false],
                        ];
                    }
                });

                // Platform icon SVGs - KEEP BRAND COLORS FOR RECOGNITION
                $platformIcons = [
                    'reddit' => '<svg class="w-5 h-5 flex-shrink-0" fill="#FF4500" viewBox="0 0 24 24"><path d="M12 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0zm5.01 4.744c.688 0 1.25.561 1.25 1.249a1.25 1.25 0 0 1-2.498.056l-2.597-.547-.8 3.747c1.824.07 3.48.632 4.674 1.488.308-.309.73-.491 1.207-.491.968 0 1.754.786 1.754 1.754 0 .716-.435 1.333-1.01 1.614a3.111 3.111 0 0 1 .042.52c0 2.694-3.13 4.87-7.004 4.87-3.874 0-7.004-2.176-7.004-4.87 0-.183.015-.366.043-.534A1.748 1.748 0 0 1 4.028 12c0-.968.786-1.754 1.754-1.754.463 0 .898.196 1.207.49 1.207-.883 2.878-1.43 4.744-1.487l.885-4.182a.342.342 0 0 1 .14-.197.35.35 0 0 1 .238-.042l2.906.617a1.214 1.214 0 0 1 1.108-.701zM9.25 12C8.561 12 8 12.562 8 13.25c0 .687.561 1.248 1.25 1.248.687 0 1.248-.561 1.248-1.249 0-.688-.561-1.249-1.249-1.249zm5.5 0c-.687 0-1.248.561-1.248 1.25 0 .687.561 1.248 1.249 1.248.688 0 1.249-.561 1.249-1.249 0-.687-.562-1.249-1.25-1.249zm-5.466 3.99a.327.327 0 0 0-.231.094.33.33 0 0 0 0 .463c.842.842 2.484.913 2.961.913.477 0 2.105-.056 2.961-.913a.361.361 0 0 0 .029-.463.33.33 0 0 0-.464 0c-.547.533-1.684.73-2.512.73-.828 0-1.979-.196-2.512-.73a.326.326 0 0 0-.232-.095z"/></svg>',
                    'facebook' => '<svg class="w-5 h-5 flex-shrink-0" fill="#1877F2" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
                    'fiverr' => '<svg class="w-5 h-5 flex-shrink-0" fill="#1DBF73" viewBox="0 0 24 24"><path d="M23.004 15.588a.995.995 0 1 1 .993-.995c0 .547-.446.995-.993.995zm0-5.483a.995.995 0 1 1 .993-.994c0 .545-.446.994-.993.994zM19.5 11.32a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3zm-1.546 6.197v-4.009c0-.49.399-.89.89-.89h.004c.49 0 .89.399.89.89v4.009a.889.889 0 0 1-.89.889h-.004a.889.889 0 0 1-.89-.889zm-9.938 0v-4.009c0-.49.399-.89.89-.89h.004c.49 0 .89.399.89.89v4.009a.889.889 0 0 1-.89.889h-.004a.889.889 0 0 1-.89-.889zm8.36-1.726c0 .49-.4.89-.891.89H13.46c-.49 0-.89-.4-.89-.89v-4.009c0-.491.4-.891.89-.891h2.025c.49 0 .89.4.89.89v4.01zM4.565 14.5c0-.761.618-1.379 1.379-1.379h2.163c.761 0 1.379.618 1.379 1.379v3.017a.889.889 0 0 1-.889.889H4.565c-.49 0-.89-.4-.89-.89V14.5z"/></svg>',
                    'upwork' => '<svg class="w-5 h-5 flex-shrink-0" fill="#6FDA44" viewBox="0 0 24 24"><path d="M18.561 13.158c-1.102 0-2.135-.467-3.074-1.227l.228-1.076.008-.042c.207-1.143.849-3.06 2.839-3.06 1.492 0 2.703 1.212 2.703 2.703-.001 1.489-1.212 2.702-2.704 2.702zm0-8.14c-2.539 0-4.51 1.649-5.31 4.366-1.22-1.834-2.148-4.036-2.687-5.892H7.828v7.112c-.002 1.406-1.141 2.546-2.547 2.548-1.405-.002-2.543-1.143-2.545-2.548V3.492H0v7.112c0 2.914 2.37 5.303 5.281 5.303 2.913 0 5.283-2.389 5.283-5.303v-1.19c.529 1.107 1.182 2.229 1.974 3.221l-1.673 7.873h2.797l1.213-5.71c1.063.679 2.285 1.109 3.686 1.109 3 0 5.439-2.452 5.439-5.45 0-3-2.439-5.439-5.439-5.439z"/></svg>',
                    'x' => '<svg class="w-5 h-5 flex-shrink-0" fill="#000000" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
                    'linkedin' => '<svg class="w-5 h-5 flex-shrink-0" fill="#0A66C2" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>',
                ];

                // Platform colors - BLACK AND WHITE ONLY
                $platformColors = [
                    'reddit' => ['bg' => 'bg-white', 'border' => 'border-zinc-200', 'text' => 'text-zinc-900', 'badge-bg' => 'bg-zinc-100', 'badge-text' => 'text-zinc-600'],
                    'facebook' => ['bg' => 'bg-white', 'border' => 'border-zinc-200', 'text' => 'text-zinc-900', 'badge-bg' => 'bg-zinc-100', 'badge-text' => 'text-zinc-600'],
                    'fiverr' => ['bg' => 'bg-white', 'border' => 'border-zinc-200', 'text' => 'text-zinc-900', 'badge-bg' => 'bg-zinc-100', 'badge-text' => 'text-zinc-600'],
                    'upwork' => ['bg' => 'bg-white', 'border' => 'border-zinc-200', 'text' => 'text-zinc-900', 'badge-bg' => 'bg-zinc-100', 'badge-text' => 'text-zinc-600'],
                    'x' => ['bg' => 'bg-white', 'border' => 'border-zinc-200', 'text' => 'text-zinc-900', 'badge-bg' => 'bg-zinc-100', 'badge-text' => 'text-zinc-600'],
                    'linkedin' => ['bg' => 'bg-white', 'border' => 'border-zinc-200', 'text' => 'text-zinc-900', 'badge-bg' => 'bg-zinc-100', 'badge-text' => 'text-zinc-600'],
                ];
            @endphp
            <div class="mt-6 sm:text-center lg:text-left">
                <p class="text-sm font-medium text-zinc-700 mb-3">Platforms we monitor:</p>
                <div class="flex flex-wrap gap-3 justify-center lg:justify-start">
                    @foreach($allPlatforms as $platform)
                        @php
                            $platformName = strtolower($platform['name']);
                            $displayName = $platform['display_name'] ?? ucfirst($platform['name']);
                            $colors = $platformColors[$platformName] ?? $platformColors['reddit'];
                            $icon = $platformIcons[$platformName] ?? $platformIcons['reddit'];
                            $isActive = $platform['is_active'] ?? false;
                        @endphp
                        <div class="flex items-center px-3 py-2 {{ $colors['bg'] }} border {{ $colors['border'] }} rounded-lg {{ $isActive ? '' : 'opacity-50' }}">
                            {!! $icon !!}
                            <span class="ml-2 text-sm font-medium {{ $colors['text'] }} whitespace-nowrap">{{ $displayName }}</span>
                            @if(!$isActive)
                                <span class="ml-2 text-xs {{ $colors['badge-text'] }} {{ $colors['badge-bg'] }} px-2 py-0.5 rounded-full">Soon</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="flex justify-center items-center mt-12 w-full lg:w-1/2 lg:mt-0">
            @php
                // Simplified lead data - just 3 leads per type for demo
                $demoLeads = [
                    'web-developers' => [
                        ['title' => 'Need a web developer for my startup', 'platform' => 'reddit', 'platformDetail' => 'r/webdev', 'author' => 'u/StartupCEO', 'score' => 9, 'time' => '12 min ago'],
                        ['title' => 'Looking for React developer', 'platform' => 'linkedin', 'platformDetail' => null, 'author' => 'Sarah Johnson', 'score' => 8, 'time' => '25 min ago'],
                        ['title' => 'Best web dev for e-commerce?', 'platform' => 'reddit', 'platformDetail' => 'r/entrepreneur', 'author' => 'u/OnlineStore', 'score' => 9, 'time' => '45 min ago'],
                    ],
                    'saas-startups' => [
                        ['title' => 'Looking for CRM solution', 'platform' => 'reddit', 'platformDetail' => 'r/entrepreneur', 'author' => 'u/TechFounder', 'score' => 9, 'time' => '2 min ago'],
                        ['title' => 'Need marketing automation tool', 'platform' => 'linkedin', 'platformDetail' => null, 'author' => 'Mike Chen', 'score' => 8, 'time' => '15 min ago'],
                        ['title' => 'Best lead generation tools?', 'platform' => 'reddit', 'platformDetail' => 'r/SaaS', 'author' => 'u/GrowthHacker', 'score' => 9, 'time' => '30 min ago'],
                    ],
                    'local-businesses' => [
                        ['title' => 'Need plumber for renovation', 'platform' => 'reddit', 'platformDetail' => 'r/homeimprovement', 'author' => 'u/Homeowner', 'score' => 8, 'time' => '10 min ago'],
                        ['title' => 'Best accounting services?', 'platform' => 'linkedin', 'platformDetail' => null, 'author' => 'Shop Owner', 'score' => 9, 'time' => '20 min ago'],
                        ['title' => 'Looking for local marketing help', 'platform' => 'reddit', 'platformDetail' => 'r/smallbusiness', 'author' => 'u/Restaurant', 'score' => 7, 'time' => '35 min ago'],
                    ],
                ];

                // Platform icon components (referenced from "Platforms we monitor" section)
                $platformIconsMap = $platformIcons; // Use the same icons defined earlier
            @endphp

            <script>
                // Platform icons for Alpine.js
                window.leadsPlatformIcons = {
                    'reddit': `{!! $platformIcons['reddit'] ?? '' !!}`,
                    'facebook': `{!! $platformIcons['facebook'] ?? '' !!}`,
                    'linkedin': `{!! $platformIcons['linkedin'] ?? '' !!}`,
                    'x': `{!! $platformIcons['x'] ?? '' !!}`,
                    'fiverr': `{!! $platformIcons['fiverr'] ?? '' !!}`,
                    'upwork': `{!! $platformIcons['upwork'] ?? '' !!}`
                };
            </script>

            <div class="relative w-full max-w-md"
                 x-data="{
                    activeBusinessType: 'saas-startups',
                    activePlatforms: {{ json_encode($activePlatformNames) }},
                    allLeads: {{ json_encode($demoLeads) }},
                    platformIcons: window.leadsPlatformIcons || {},
                    rotationInterval: null,
                    businessTypes: {
                        'web-developers': { label: 'Web Developers', icon: 'code' },
                        'saas-startups': { label: 'SaaS Startups', icon: 'rocket' },
                        'local-businesses': { label: 'Local Businesses', icon: 'store' }
                    },
                    get currentBusinessType() {
                        return this.businessTypes[this.activeBusinessType];
                    },
                    get currentLeads() {
                        // Filter leads by active platforms and pick 3 random ones
                        const allBusinessLeads = this.allLeads[this.activeBusinessType] || [];
                        const filtered = allBusinessLeads.filter(lead => this.activePlatforms.includes(lead.platform));
                        // Shuffle and take first 3
                        return filtered.sort(() => 0.5 - Math.random()).slice(0, 3);
                    },
                    startRotation() {
                        this.rotationInterval = setInterval(() => {
                            // Force Alpine to re-evaluate currentLeads (random selection)
                            this.$nextTick(() => {
                                // Trigger re-render by reassigning activePlatforms
                                this.activePlatforms = [...this.activePlatforms];
                            });
                        }, 10000); // 10 seconds
                    },
                    stopRotation() {
                        if (this.rotationInterval) {
                            clearInterval(this.rotationInterval);
                        }
                    }
                 }"
                 x-init="startRotation()"
                 @mouseover="stopRotation()"
                 @mouseleave="startRotation()">

                <!-- Business Type Tabs -->
                <div class="mb-4">
                    <div class="inline-flex w-full border border-zinc-200 rounded-lg overflow-hidden">
                        <button @click="activeBusinessType = 'web-developers'"
                                :class="activeBusinessType === 'web-developers' ? 'bg-zinc-900 text-white' : 'bg-white text-zinc-700 hover:bg-zinc-50'"
                                class="flex-1 px-4 py-2.5 text-xs font-medium transition-colors border-r border-zinc-200">
                            <span class="hidden sm:inline">Web Developers</span>
                            <span class="sm:hidden">Dev</span>
                        </button>
                        <button @click="activeBusinessType = 'saas-startups'"
                                :class="activeBusinessType === 'saas-startups' ? 'bg-zinc-900 text-white' : 'bg-white text-zinc-700 hover:bg-zinc-50'"
                                class="flex-1 px-4 py-2.5 text-xs font-medium transition-colors border-r border-zinc-200">
                            <span class="hidden sm:inline">SaaS Startups</span>
                            <span class="sm:hidden">SaaS</span>
                        </button>
                        <button @click="activeBusinessType = 'local-businesses'"
                                :class="activeBusinessType === 'local-businesses' ? 'bg-zinc-900 text-white' : 'bg-white text-zinc-700 hover:bg-zinc-50'"
                                class="flex-1 px-4 py-2.5 text-xs font-medium transition-colors">
                            <span class="hidden sm:inline">Local Businesses</span>
                            <span class="sm:hidden">Local</span>
                        </button>
                    </div>
                </div>

                <div class="relative px-6 py-6 bg-white rounded-lg border border-zinc-200">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-zinc-200">
                            <span class="text-sm font-medium text-zinc-700">New Leads Found</span>
                        </div>
                        <div class="space-y-3">
                            <template x-for="(lead, index) in currentLeads" :key="activeBusinessType + '-' + index">
                                <div class="p-3 border border-zinc-200 rounded-lg transition-all duration-300 hover:border-zinc-300">
                                    <div class="flex items-start justify-between mb-2">
                                        <div class="flex items-center space-x-2">
                                            <!-- Platform Icon - EXACTLY like "Platforms we monitor" section -->
                                            <span x-html="platformIcons[lead.platform] || platformIcons['reddit']"></span>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                New
                                            </span>
                                            <span class="text-xs text-zinc-500" x-text="lead.platformDetail" x-show="lead.platformDetail"></span>
                                        </div>
                                        <div class="flex flex-col items-end">
                                            <span class="text-xs text-zinc-500 mb-0.5">Confidence</span>
                                            <div class="flex items-center space-x-1">
                                                <span class="text-base font-bold"
                                                      :class="{
                                                          'text-green-600': lead.score >= 8,
                                                          'text-yellow-600': lead.score >= 5 && lead.score < 8,
                                                          'text-red-600': lead.score < 5
                                                      }"
                                                      x-text="lead.score"></span>
                                                <span class="text-xs text-zinc-500">/10</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-2 flex items-center space-x-2">
                                        <span class="text-xs text-zinc-500" x-text="lead.author" x-show="lead.author"></span>
                                        <template x-if="lead.author && lead.time">
                                            <span class="text-xs text-zinc-400">•</span>
                                        </template>
                                        <span class="text-xs text-zinc-500" x-text="lead.time"></span>
                                    </div>
                                    <h3 class="text-sm font-semibold text-zinc-900 mb-3 line-clamp-1" x-text="lead.title"></h3>
                                    <div class="pt-2 border-t border-zinc-100">
                                        <button disabled class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-zinc-400 bg-zinc-100 rounded-md cursor-not-allowed opacity-60">
                                            <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                            </svg>
                                            Generate reply
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="flex-shrink-0 lg:h-[150px] flex border-t border-zinc-200 items-center w-full bg-white">
        <div class="grid grid-cols-1 px-8 py-10 mx-auto space-y-5 max-w-7xl h-auto divide-y lg:space-y-0 lg:divide-y-0 divide-zinc-200 lg:py-0 lg:divide-x md:px-12 lg:px-20 lg:divide-zinc-200 lg:grid-cols-3">
            <div class="">
                <h3 class="flex items-center font-medium text-zinc-900">
                    <svg class="w-5 h-5 mr-2 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2 10.5a1.5 1.5 0 113 0v6a1.5 1.5 0 01-3 0v-6zM6 10.333v5.43a2 2 0 001.106 1.79l.05.025A4 4 0 008.943 18h5.416a2 2 0 001.962-1.608l1.2-6A2 2 0 0015.56 8H12V4a2 2 0 00-2-2 1 1 0 00-1 1v.667a4 4 0 01-.8 2.4L6.8 7.933a4 4 0 00-.8 2.4z"/>
                    </svg>
                    Multi-Platform Monitoring
                </h3>
                <p class="mt-2 text-sm font-medium text-zinc-500">
                    Track conversations across {{ $platformText }} 24/7. <span class="hidden lg:inline">Never miss a potential customer looking for solutions you offer.</span>
                </p>
            </div>
            <div class="pt-5 lg:pt-0 lg:px-10">
                <h3 class="flex items-center font-medium text-zinc-900">
                    <svg class="w-5 h-5 mr-2 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm9.707 5.707a1 1 0 00-1.414-1.414L9 12.586l-1.293-1.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    Fast & Intelligent
                </h3>
                <p class="mt-2 text-sm text-zinc-500">
                    AI-powered lead discovery and smart relevance scoring. <span class="hidden lg:inline">Automated syncing finds qualified leads instantly.</span>
                </p>
            </div>
            <div class="pt-5 lg:pt-0 lg:px-10">
                <h3 class="flex items-center font-medium text-zinc-900">
                    <svg class="w-5 h-5 mr-2 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/>
                    </svg>
                    Complete Solution
                </h3>
                <p class="mt-2 text-sm text-zinc-500">
                    From lead discovery to engagement. AI replies, post management, and campaign analytics all in one platform.
                </p>
            </div>
        </div>
    </div>
</section>
