<x-layouts.app>
    <div class="flex flex-col items-center justify-center min-h-screen px-4 py-12 bg-gradient-to-br from-red-50 to-orange-50 dark:from-zinc-900 dark:to-zinc-800">
        <div class="w-full max-w-4xl mx-auto">
            {{-- Header --}}
            <div class="text-center mb-12">
                <div class="inline-flex items-center justify-center w-20 h-20 mb-6 rounded-full bg-red-100 dark:bg-red-900/30">
                    <svg class="w-10 h-10 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>

                <h1 class="text-4xl md:text-5xl font-bold text-zinc-900 dark:text-white mb-4">
                    Your Plan Has Expired
                </h1>
                <p class="text-xl text-zinc-600 dark:text-zinc-400 max-w-2xl mx-auto mb-6">
                    We miss you! Let's get you back to generating leads
                </p>

                @php
                    $user = auth()->user();
                    $stats = [];
                    try {
                        $leadCount = \Wave\Plugins\EvenLeads\Models\Lead::whereHas('campaign', function($q) use ($user) {
                            $q->where('user_id', $user->id);
                        })->count();
                        $campaignCount = \Wave\Plugins\EvenLeads\Models\Campaign::where('user_id', $user->id)->count();
                        $contactedLeads = \Wave\Plugins\EvenLeads\Models\Lead::whereHas('campaign', function($q) use ($user) {
                            $q->where('user_id', $user->id);
                        })->where('status', 'contacted')->count();
                        $stats = [
                            'leads' => $leadCount,
                            'campaigns' => $campaignCount,
                            'contacted' => $contactedLeads,
                        ];
                    } catch (\Exception $e) {
                        $stats = ['leads' => 0, 'campaigns' => 0, 'contacted' => 0];
                    }
                @endphp

                @if($stats['leads'] > 0)
                    <div class="inline-flex flex-col items-center px-8 py-4 bg-white dark:bg-zinc-800 rounded-xl border-2 border-zinc-200 dark:border-zinc-700 shadow-lg">
                        <div class="text-sm text-zinc-600 dark:text-zinc-400 mb-2">Your EvenLeads Stats</div>
                        <div class="flex items-center gap-6">
                            <div class="text-center">
                                <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['leads'] }}</div>
                                <div class="text-xs text-zinc-600 dark:text-zinc-400">Total Leads</div>
                            </div>
                            <div class="w-px h-12 bg-zinc-300 dark:bg-zinc-600"></div>
                            <div class="text-center">
                                <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $stats['contacted'] }}</div>
                                <div class="text-xs text-zinc-600 dark:text-zinc-400">Contacted</div>
                            </div>
                            <div class="w-px h-12 bg-zinc-300 dark:bg-zinc-600"></div>
                            <div class="text-center">
                                <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ $stats['campaigns'] }}</div>
                                <div class="text-xs text-zinc-600 dark:text-zinc-400">Campaigns</div>
                            </div>
                        </div>
                        <p class="text-xs text-zinc-500 dark:text-zinc-500 mt-3">
                            All your data is safely stored and waiting for you
                        </p>
                    </div>
                @endif
            </div>

            {{-- Why Renew Section --}}
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl p-8 md:p-10 mb-8 border border-zinc-200 dark:border-zinc-700">
                <h2 class="text-2xl font-bold text-zinc-900 dark:text-white mb-6 text-center">
                    Why Thousands of Businesses Trust EvenLeads
                </h2>

                <div class="grid md:grid-cols-2 gap-6 mb-8">
                    <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-green-100 dark:bg-green-900">
                                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h3 class="font-semibold text-zinc-900 dark:text-white mb-1">Save 10+ Hours Per Week</h3>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                Automate lead discovery and focus on closing deals, not searching
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900">
                                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h3 class="font-semibold text-zinc-900 dark:text-white mb-1">3x More Qualified Leads</h3>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                AI-powered matching finds prospects actively looking for solutions
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900">
                                <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h3 class="font-semibold text-zinc-900 dark:text-white mb-1">Never Miss an Opportunity</h3>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                Real-time monitoring ensures you're first to respond
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-orange-100 dark:bg-orange-900">
                                <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h3 class="font-semibold text-zinc-900 dark:text-white mb-1">AI-Generated Replies</h3>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                Personalized responses that sound human and convert better
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Welcome Back Offer --}}
                <div class="bg-gradient-to-r from-green-50 to-blue-50 dark:from-green-900/20 dark:to-blue-900/20 rounded-xl p-6 border-2 border-green-200 dark:border-green-800">
                    <div class="flex items-start">
                        <svg class="w-8 h-8 text-green-600 dark:text-green-400 mr-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2H6v1a1 1 0 11-2 0v-1H3a1 1 0 110-2h1v-1a1 1 0 011-1zM12 2a1 1 0 01.967.744L14.146 7.2 17.5 9.134a1 1 0 010 1.732l-3.354 1.935-1.18 4.455a1 1 0 01-1.933 0L9.854 12.8 6.5 10.866a1 1 0 010-1.732l3.354-1.935 1.18-4.455A1 1 0 0112 2z" clip-rule="evenodd"/>
                        </svg>
                        <div class="flex-1">
                            <h4 class="font-bold text-lg text-zinc-900 dark:text-white mb-2">Welcome Back Offer! 🎉</h4>
                            <p class="text-zinc-700 dark:text-zinc-300 mb-3">
                                We've missed you! Renew today and get <strong class="text-green-700 dark:text-green-300">25% off your first month</strong> with code:
                            </p>
                            <div class="inline-flex items-center px-4 py-2 bg-white dark:bg-zinc-800 rounded-lg border-2 border-green-300 dark:border-green-700">
                                <code class="font-mono font-bold text-lg text-green-700 dark:text-green-300">WELCOME25</code>
                                <button onclick="navigator.clipboard.writeText('WELCOME25')" class="ml-3 text-sm text-green-600 dark:text-green-400 hover:text-green-700 dark:hover:text-green-300 flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                    Copy
                                </button>
                            </div>
                            <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-2">
                                Offer valid for the next 7 days only
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Quick Reactivation CTA --}}
            <div class="text-center mb-8">
                <a href="{{ route('settings.subscription') }}"
                   class="inline-flex items-center px-8 py-4 text-lg font-bold text-white bg-gradient-to-r from-green-600 to-blue-600 hover:from-green-700 hover:to-blue-700 rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    Reactivate My Account Now
                </a>
                <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-4">
                    Pick up right where you left off • All data intact • Instant access
                </p>
            </div>

            {{-- Footer --}}
            <div class="text-center">
                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                    Need help? <a href="mailto:support@evenleads.com" class="text-blue-600 dark:text-blue-400 hover:underline">Contact our support team</a>
                </p>
            </div>
        </div>
    </div>
</x-layouts.app>
