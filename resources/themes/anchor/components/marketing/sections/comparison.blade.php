@php
    use Wave\Plugins\EvenLeads\Models\Setting;
    use Wave\Plugins\EvenLeads\Models\Platform;
    use Wave\Plan;

    $trialDays = Setting::getValue('trial_days', 7);
    $lowestPlan = Plan::where('active', 1)->where('monthly_price', '>', 0)->orderBy('monthly_price', 'asc')->first();
    $lowestPrice = $lowestPlan ? $lowestPlan->monthly_price : 29;

    // Get active platforms dynamically
    $activePlatforms = Platform::where('is_active', true)->get();
    $activePlatformNames = $activePlatforms->pluck('display_name')->toArray();
    $activePlatformCount = $activePlatforms->count();

    // Get platforms with messaging support (DM or Comment functionality)
    $platformsWithMessaging = Platform::where('is_active', true)
        ->where(function($query) {
            $query->where('requires_extension_dm', true)
                  ->orWhere('requires_extension_comment', true);
        })
        ->pluck('display_name')
        ->toArray();
@endphp

<div class="w-full max-w-7xl mx-auto">
    <div class="text-center mb-12">
        <h2 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-4xl">
            Why Choose EvenLeads?
        </h2>
        <p class="mt-4 text-lg text-gray-600 dark:text-gray-400">
            See how we compare to other lead generation tools
        </p>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full border-collapse bg-white dark:bg-gray-800 shadow-xl rounded-lg overflow-hidden">
            <thead>
                <tr class="bg-black dark:bg-gray-900">
                    <th class="px-6 py-4 text-left text-sm font-semibold text-white">Feature</th>
                    <th class="px-6 py-4 text-center text-sm font-semibold text-white">
                        <div class="flex flex-col items-center">
                            <span class="text-lg font-bold">EvenLeads</span>
                            <span class="text-xs font-normal opacity-90">You're here!</span>
                        </div>
                    </th>
                    <th class="px-6 py-4 text-center text-sm font-semibold text-white">Leadverse</th>
                    <th class="px-6 py-4 text-center text-sm font-semibold text-white">Redreach</th>
                    <th class="px-6 py-4 text-center text-sm font-semibold text-white">Tydal</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                {{-- AI Reply Generation --}}
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">AI Reply Generation</td>
                    <td class="px-6 py-4 text-center">
                        <svg class="w-6 h-6 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <svg class="w-6 h-6 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <svg class="w-6 h-6 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <svg class="w-6 h-6 text-red-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </td>
                </tr>

                {{-- Smart AI Lead Gathering --}}
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">Smart AI Lead Gathering</td>
                    <td class="px-6 py-4 text-center">
                        <svg class="w-6 h-6 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <svg class="w-6 h-6 text-red-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <svg class="w-6 h-6 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <svg class="w-6 h-6 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </td>
                </tr>

                {{-- Automated Syncing --}}
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">Automated Syncing</td>
                    <td class="px-6 py-4 text-center">
                        <svg class="w-6 h-6 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <svg class="w-6 h-6 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <svg class="w-6 h-6 text-red-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <svg class="w-6 h-6 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </td>
                </tr>

                {{-- Multi-Account Support --}}
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">Multi-Account Support</td>
                    <td class="px-6 py-4 text-center">
                        <svg class="w-6 h-6 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <svg class="w-6 h-6 text-red-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <svg class="w-6 h-6 text-red-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <svg class="w-6 h-6 text-red-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </td>
                </tr>

                {{-- Multi-platform Lead Generation --}}
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                        Multi-platform Lead Generation
                        @if(count($activePlatformNames) > 0)
                            <span class="text-xs text-gray-500 dark:text-gray-400 block mt-1">{{ implode(', ', $activePlatformNames) }}</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-center">
                        <svg class="w-6 h-6 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </td>
                    <td class="px-6 py-4 text-center">
                        @php
                            // Leadverse has Reddit and X only - check if we have EXACTLY the same
                            $leadversePlatforms = ['Reddit', 'X (Twitter)'];
                            $hasExactlySamePlatforms = count($activePlatformNames) === count($leadversePlatforms)
                                && count(array_intersect($activePlatformNames, $leadversePlatforms)) === count($leadversePlatforms);
                        @endphp
                        @if($hasExactlySamePlatforms)
                            <svg class="w-6 h-6 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        @else
                            <span class="text-xs text-gray-500 dark:text-gray-400">Reddit and X only</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-center">
                        @php
                            // Redreach has Reddit only
                            $redreachPlatforms = ['Reddit'];
                            $hasExactlyReddit = count($activePlatformNames) === 1 && in_array('Reddit', $activePlatformNames);
                        @endphp
                        @if($hasExactlyReddit)
                            <svg class="w-6 h-6 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        @else
                            <span class="text-xs text-gray-500 dark:text-gray-400">Reddit only</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-center">
                        @php
                            // Tydal has Reddit only
                            $tydalPlatforms = ['Reddit'];
                            $tydalHasExactlyReddit = count($activePlatformNames) === 1 && in_array('Reddit', $activePlatformNames);
                        @endphp
                        @if($tydalHasExactlyReddit)
                            <svg class="w-6 h-6 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        @else
                            <span class="text-xs text-gray-500 dark:text-gray-400">Reddit only</span>
                        @endif
                    </td>
                </tr>

                {{-- Multi-platform Direct Message / Comment --}}
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                        Multi-platform Direct Message / Comment
                        @if(count($platformsWithMessaging) > 0)
                            <span class="text-xs text-gray-500 dark:text-gray-400 block mt-1">{{ implode(', ', $platformsWithMessaging) }}</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-center">
                        <svg class="w-6 h-6 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </td>
                    <td class="px-6 py-4 text-center">
                        @php
                            // Leadverse has Reddit only for messaging - check if we have EXACTLY that
                            $leadverseMessagingPlatforms = ['Reddit'];
                            $hasExactlyLeadverseMessaging = count($platformsWithMessaging) === 1 && in_array('Reddit', $platformsWithMessaging);
                        @endphp
                        @if($hasExactlyLeadverseMessaging)
                            <svg class="w-6 h-6 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        @else
                            <span class="text-xs text-gray-500 dark:text-gray-400">Reddit only</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-center">
                        @php
                            // Redreach has Reddit only for messaging - check if we have EXACTLY that
                            $redreachMessagingPlatforms = ['Reddit'];
                            $hasExactlyRedreachMessaging = count($platformsWithMessaging) === 1 && in_array('Reddit', $platformsWithMessaging);
                        @endphp
                        @if($hasExactlyRedreachMessaging)
                            <svg class="w-6 h-6 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        @else
                            <span class="text-xs text-gray-500 dark:text-gray-400">Reddit only</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-center">
                        <svg class="w-6 h-6 text-red-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </td>
                </tr>

                {{-- Post Management --}}
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">Post Management</td>
                    <td class="px-6 py-4 text-center">
                        <svg class="w-6 h-6 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <svg class="w-6 h-6 text-red-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Cannot manage your own posts</span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <svg class="w-6 h-6 text-red-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </td>
                </tr>

                {{-- AI Post Management --}}
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">AI Post Management</td>
                    <td class="px-6 py-4 text-center">
                        <svg class="w-6 h-6 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <svg class="w-6 h-6 text-red-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <svg class="w-6 h-6 text-red-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <svg class="w-6 h-6 text-red-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </td>
                </tr>

                {{-- Campaign from Website --}}
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">Campaign Generation from Website</td>
                    <td class="px-6 py-4 text-center">
                        <svg class="w-6 h-6 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <svg class="w-6 h-6 text-red-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <svg class="w-6 h-6 text-red-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <svg class="w-6 h-6 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </td>
                </tr>

                {{-- AI Assistant Chat --}}
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">AI Assistant Chat</td>
                    <td class="px-6 py-4 text-center">
                        <svg class="w-6 h-6 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <svg class="w-6 h-6 text-red-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <svg class="w-6 h-6 text-red-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <svg class="w-6 h-6 text-red-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </td>
                </tr>

                {{-- Lead Generator API --}}
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">Lead Generator API</td>
                    <td class="px-6 py-4 text-center">
                        <svg class="w-6 h-6 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <svg class="w-6 h-6 text-red-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <svg class="w-6 h-6 text-red-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <svg class="w-6 h-6 text-red-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </td>
                </tr>


            </tbody>
        </table>
    </div>

    <div class="mt-12 text-center">
        <a href="{{ route('register') }}" class="inline-flex items-center gap-2 px-6 py-3 text-base font-semibold text-white bg-zinc-900 rounded-lg transition-opacity hover:opacity-90">
            Try EvenLeads Free for {{ $trialDays }} Days
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
            </svg>
        </a>
    </div>
</div>
