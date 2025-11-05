<x-layouts.app>
    <div class="flex flex-col items-center justify-center min-h-screen bg-gradient-to-br from-zinc-50 to-zinc-100 dark:from-zinc-900 dark:to-zinc-800">
        <div class="w-full max-w-5xl mx-auto">
            {{-- Header --}}
            <div class="text-center mb-12">
                <div class="inline-flex items-center justify-center w-20 h-20 mb-6 rounded-full bg-orange-100 dark:bg-orange-900">
                    <svg class="w-10 h-10 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>

                <h1 class="text-4xl md:text-5xl font-bold text-zinc-900 dark:text-white mb-4">
                    Your Trial Has Ended
                </h1>
                <p class="text-xl text-zinc-600 dark:text-zinc-400 max-w-2xl mx-auto mb-6">
                    But your lead generation journey doesn't have to!
                </p>

                @php
                    $user = auth()->user();
                    $stats = [];
                    try {
                        $leadCount = \Wave\Plugins\EvenLeads\Models\Lead::whereHas('campaign', function($q) use ($user) {
                            $q->where('user_id', $user->id);
                        })->count();
                        $campaignCount = \Wave\Plugins\EvenLeads\Models\Campaign::where('user_id', $user->id)->count();
                        $stats = [
                            'leads' => $leadCount,
                            'campaigns' => $campaignCount,
                        ];
                    } catch (\Exception $e) {
                        $stats = ['leads' => 0, 'campaigns' => 0];
                    }
                @endphp

                @if($stats['leads'] > 0 || $stats['campaigns'] > 0)
                    <div class="inline-flex items-center px-6 py-3 bg-blue-50 dark:bg-blue-900/30 rounded-lg border border-blue-200 dark:border-blue-800">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-blue-900 dark:text-blue-100 font-medium">
                            You generated <strong>{{ $stats['leads'] }} leads</strong> across <strong>{{ $stats['campaigns'] }} campaigns</strong> during your trial
                        </span>
                    </div>
                @endif
            </div>

            {{-- Value Proposition --}}
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl p-8 md:p-12 mb-8 border border-zinc-200 dark:border-zinc-700">
                <h2 class="text-2xl md:text-3xl font-bold text-zinc-900 dark:text-white mb-6 text-center">
                    Continue Growing Your Business
                </h2>

                <div class="grid md:grid-cols-3 gap-6 mb-8">
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-12 h-12 mb-3 rounded-full bg-green-100 dark:bg-green-900">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-zinc-900 dark:text-white mb-2">Don't Lose Momentum</h3>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">
                            Keep discovering qualified leads that match your criteria
                        </p>
                    </div>

                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-12 h-12 mb-3 rounded-full bg-blue-100 dark:bg-blue-900">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-zinc-900 dark:text-white mb-2">Your Data Stays Safe</h3>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">
                            All your campaigns and leads are securely stored
                        </p>
                    </div>

                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-12 h-12 mb-3 rounded-full bg-purple-100 dark:bg-purple-900">
                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-zinc-900 dark:text-white mb-2">AI-Powered Engagement</h3>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">
                            Generate personalized replies that convert
                        </p>
                    </div>
                </div>
            </div>

            {{-- Plans Section - Inline Checkout --}}
            <div class="mb-8">
                <h2 class="text-3xl font-bold text-zinc-900 dark:text-white mb-4 text-center">
                    Choose Your Plan
                </h2>
                <p class="text-zinc-600 dark:text-zinc-400 text-center mb-8">
                    Select the perfect plan for your needs and continue generating leads
                </p>

                <livewire:billing.checkout :change="false" />

                <p class="flex items-center justify-center mt-6 text-sm text-zinc-600 dark:text-zinc-400">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <span class="mr-1">Billing is securely managed via </span><strong>{{ ucfirst(config('wave.billing_provider')) }} Payment Platform</strong>.
                </p>
            </div>

            {{-- Footer CTA --}}
            <div class="text-center">
                <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-4">
                    Questions? <a href="mailto:support@evenleads.com" class="text-blue-600 dark:text-blue-400 hover:underline">Contact our team</a>
                </p>
                <p class="text-xs text-zinc-500 dark:text-zinc-500">
                    30-day money-back guarantee • Cancel anytime • No hidden fees
                </p>
            </div>
        </div>
    </div>
</x-layouts.app>
