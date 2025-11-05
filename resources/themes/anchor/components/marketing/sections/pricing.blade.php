@php
    use Wave\Plugins\EvenLeads\Models\Setting;
    $trialDays = Setting::getValue('trial_days', 7);
    $currency = Setting::getValue('site.currency', 'EUR');
    $currencyPosition = Setting::getValue('site.currency_position', 'append');
    $currencyFormat = Setting::getValue('site.currency_format', 'symbol');

    // Convert currency to symbol if needed
    if ($currencyFormat === 'symbol') {
        $currencySymbols = [
            'EUR' => '€',
            'USD' => '$',
            'GBP' => '£',
            'JPY' => '¥',
            'CHF' => 'CHF',
            'AUD' => 'A$',
            'CAD' => 'C$',
            'CNY' => '¥',
        ];
        $currencyDisplay = $currencySymbols[$currency] ?? $currency;
    } else {
        $currencyDisplay = $currency;
    }

    $plans = \Wave\Plan::getActivePlans();

    // Get user's current plan if logged in
    $currentPlanId = null;
    $userHasUsedTrial = false;
    $subscription = null;
    if (auth()->check()) {
        $user = auth()->user();
        // Get active subscription
        $subscription = \Wave\Subscription::where('billable_id', $user->id)
            ->where('status', 'active')
            ->with('plan')
            ->first();

        if ($subscription) {
            $currentPlanId = $subscription->plan_id;
        }
        // Check if user has already used a trial (trial_ends_at is set)
        $userHasUsedTrial = !empty($user->trial_ends_at);
    }

    // Separate regular plans from enterprise/on-request plans
    $regularPlans = $plans->filter(function($plan) {
        return !$plan->is_on_request;
    })->take(3);

    $enterprisePlans = $plans->filter(function($plan) {
        return $plan->is_on_request;
    });
@endphp

<section id="pricing">
    @if(!request()->is('plan-selection'))
    <x-marketing.elements.heading
        level="h2"
        title="Simple, Transparent Pricing"
        description="Choose the plan that fits your lead generation needs."
    />
    @endif

    <div x-data="{ on: false, billing: '{{ get_default_billing_cycle() }}',
            loading: {},
            seats: {},
            currentUserSeats: {{ $subscription && $subscription->plan && $subscription->plan->is_seated_plan ? $subscription->seats_purchased : 'null' }},
            currentUserPlanId: {{ $currentPlanId ?? 'null' }},
            setLoading(planId, state) {
                this.loading[planId] = state;
            },
            initSeats(planId) {
                if (!this.seats[planId]) {
                    // If this is user's current seated plan, initialize with their current seat count
                    if (this.currentUserPlanId === planId && this.currentUserSeats) {
                        this.seats[planId] = this.currentUserSeats;
                    } else {
                        this.seats[planId] = 1;
                    }
                }
            },
            incrementSeats(planId) {
                if (!this.seats[planId]) this.seats[planId] = 1;
                if (this.seats[planId] < 50) {
                    this.seats[planId]++;
                }
            },
            decrementSeats(planId) {
                if (!this.seats[planId]) this.seats[planId] = 1;
                if (this.seats[planId] > 1) {
                    this.seats[planId]--;
                }
            },
            toggleRepositionMarker(toggleButton){
                if(this.$refs.marker && toggleButton){
                    this.$refs.marker.style.width=toggleButton.offsetWidth + 'px';
                    this.$refs.marker.style.height=toggleButton.offsetHeight + 'px';
                    this.$refs.marker.style.left=toggleButton.offsetLeft + 'px';
                }
            }
         }"
        x-init="
                setTimeout(function(){
                    toggleRepositionMarker($refs.monthly);
                    if($refs.marker){
                        $refs.marker.classList.remove('opacity-0');
                        setTimeout(function(){
                            $refs.marker.classList.add('duration-300', 'ease-out');
                        }, 10);
                    }
                }, 1);
        "
        class="mx-auto mt-6 mb-2 w-full max-w-6xl md:my-6" x-cloak>

        @if(has_monthly_yearly_toggle())
            <div class="flex relative justify-start items-center pb-5 -translate-y-2 md:justify-center">
                <div class="inline-flex relative justify-center items-center p-1 w-auto text-center rounded-full border-2 -translate-y-3 md:mx-auto border-zinc-900 dark:border-zinc-100">
                    <div x-ref="monthly" x-on:click="billing='Monthly'; toggleRepositionMarker($el)" :class="{ 'text-white dark:text-zinc-900': billing == 'Monthly', 'text-zinc-900 dark:text-white' : billing != 'Monthly' }" class="relative z-20 px-3.5 py-1 text-sm font-medium leading-6 rounded-full duration-300 ease-out cursor-pointer">
                        Monthly
                    </div>
                    <div x-ref="yearly" x-on:click="billing='Yearly'; toggleRepositionMarker($el)" :class="{ 'text-white dark:text-zinc-900': billing == 'Yearly', 'text-zinc-900 dark:text-white' : billing != 'Yearly' }" class="relative z-20 px-3.5 py-1 text-sm font-medium leading-6 rounded-full duration-300 ease-out cursor-pointer">
                        Yearly
                    </div>
                    <div x-ref="marker" class="absolute left-0 z-10 w-1/2 h-full opacity-0" x-cloak>
                        <div class="w-full h-full rounded-full shadow-sm bg-zinc-900 dark:bg-zinc-100"></div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Regular Plans Grid (3 columns, wider on plan-selection) -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12 @if(request()->is('plan-selection')){{ 'max-w-7xl mx-auto' }}@endif">
            @if($regularPlans->isEmpty())
                <div class="col-span-full text-center py-10">
                    <p class="text-zinc-500">No pricing plans available at the moment.</p>
                </div>
            @endif

            @foreach($regularPlans as $plan)
                @php
                    // Decode features properly - handle both JSON and comma-separated
                    $features = [];
                    if (is_string($plan->features)) {
                        $decoded = json_decode($plan->features, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            $features = $decoded;
                        } else {
                            $features = array_map('trim', explode(',', $plan->features));
                        }
                    } elseif (is_array($plan->features)) {
                        $features = $plan->features;
                    }

                    $hasMonthly = !empty($plan->monthly_price);
                    $hasYearly = !empty($plan->yearly_price);

                    // Get trial plan setting from EvenLeads settings
                    $defaultTrialPlanId = Setting::getValue('trial_plan_id', null);

                    // Get trial days from EvenLeads settings
                    $planTrialDays = Setting::getValue('trial_days', 7);

                    // Determine if this plan should show trial option
                    // Only show trial if:
                    // 1. This plan is marked as the trial plan in settings
                    // 2. Trial days > 0
                    // 3. User hasn't already used a trial (trial_ends_at is not set)
                    $showTrial = $defaultTrialPlanId == $plan->id && $planTrialDays > 0 && !$userHasUsedTrial;
                @endphp
                <div
                    x-show="(billing == 'Monthly' && {{ $hasMonthly ? 'true' : 'false' }}) || (billing == 'Yearly' && {{ $hasYearly ? 'true' : 'false' }})"
                    x-init="initSeats({{ $plan->id }})"
                    class="flex flex-col h-full bg-white dark:bg-zinc-800 rounded-xl border-2 @if($plan->default){{ 'border-zinc-900 dark:border-zinc-100 lg:scale-105' }}@else{{ 'border-zinc-200 dark:border-zinc-700' }}@endif shadow-sm" x-cloak>
                    <div class="px-8 pt-8">
                        <span class="px-4 py-1 text-base font-medium text-white dark:text-zinc-900 rounded-full bg-zinc-900 dark:bg-zinc-100 text-uppercase">
                            {{ $plan->name }}
                        </span>
                    </div>

                    <div class="px-8 mt-5">
                        <div class="flex items-baseline gap-2">
                            <span class="text-5xl font-bold text-zinc-900 dark:text-white">
                                @if($currencyPosition === 'prepend'){{ $currencyDisplay }}@endif<span x-text="billing == 'Monthly' ? '{{ $plan->monthly_price }}' : '{{ $plan->yearly_price }}'"></span>@if($currencyPosition === 'append'){{ $currencyDisplay }}@endif
                            </span>
                            <span class="text-xl font-bold text-zinc-500 dark:text-zinc-400">
                                <span x-text="billing == 'Monthly' ? '/mo' : '/yr'"></span>@if($plan->is_seated_plan) per seat @endif
                            </span>
                        </div>
                        @if($showTrial)
                            <div class="mt-2">
                                <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ $planTrialDays }}-day free trial</span>
                            </div>
                        @endif

                        @if($plan->is_seated_plan)
                            <div class="mt-6 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Number of Seats</span>
                                    <div class="flex items-center gap-3">
                                        <button
                                            x-on:click="decrementSeats({{ $plan->id }})"
                                            type="button"
                                            class="w-8 h-8 flex items-center justify-center rounded-lg bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                                            <svg class="w-4 h-4 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                            </svg>
                                        </button>
                                        <span class="text-xl font-bold text-zinc-900 dark:text-white min-w-[2ch] text-center" x-text="seats[{{ $plan->id }}] || 1"></span>
                                        <button
                                            x-on:click="incrementSeats({{ $plan->id }})"
                                            type="button"
                                            class="w-8 h-8 flex items-center justify-center rounded-lg bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                                            <svg class="w-4 h-4 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="mt-3 pt-3 border-t border-zinc-200 dark:border-zinc-700">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-zinc-600 dark:text-zinc-400">Total</span>
                                        <span class="text-lg font-bold text-zinc-900 dark:text-white">
                                            @if($currencyPosition === 'prepend'){{ $currencyDisplay }}@endif<span x-text="(billing == 'Monthly' ? {{ $plan->monthly_price }} : {{ $plan->yearly_price }}) * (seats[{{ $plan->id }}] || 1)"></span>@if($currencyPosition === 'append'){{ $currencyDisplay }}@endif<span class="text-sm text-zinc-500 dark:text-zinc-400" x-text="billing == 'Monthly' ? '/mo' : '/yr'"></span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="px-8 pb-4 mt-3">
                        <p class="text-base leading-7 text-zinc-500 dark:text-zinc-400">{{ $plan->description }}</p>
                    </div>

                    <div class="p-8 pt-4 flex flex-col flex-1 rounded-b-xl bg-zinc-50 dark:bg-zinc-900">
                        <ul class="flex flex-col space-y-2 mb-8">
                            @foreach($features as $feature)
                                @php
                                    $featureText = is_string($feature) ? $feature : json_encode($feature);
                                    $tooltip = null;

                                    // Add tooltips for specific features
                                    if (stripos($featureText, 'Smart lead retrieval') !== false || stripos($featureText, 'smart retrieval') !== false) {
                                        $tooltip = 'AI scans posts not only by keywords but also analyzes content to ensure you get the perfect leads for your business';
                                    } elseif (stripos($featureText, 'Intelligent lead parsing') !== false || stripos($featureText, 'intelligent parsing') !== false) {
                                        $tooltip = 'AI-powered lead analysis that parses scanned posts to ensure they are high-quality leads, beyond just keyword matching';
                                    } elseif (stripos($featureText, 'Intelligent lead retrieval') !== false || stripos($featureText, 'intelligent retrieval') !== false) {
                                        $tooltip = 'AI parses scanned Reddit/Facebook posts to ensure they are high-quality leads, beyond just keyword matching';
                                    } elseif (stripos($featureText, 'AI Post Management') !== false) {
                                        $tooltip = 'Sentiment analysis, Reply / comment generator, AI post tips';
                                    } elseif (stripos($featureText, 'leads storage') !== false || stripos($featureText, 'lead storage') !== false) {
                                        $tooltip = 'Maximum number of leads that can be stored in your account. Delete old leads to make room for new ones.';
                                    }
                                @endphp
                                <li>
                                    <span class="flex items-start text-green-500">
                                        <svg class="mr-3 w-4 h-4 fill-current flex-shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M0 11l2-2 5 5L18 3l2 2L7 18z"></path></svg>
                                        <span class="text-zinc-700 dark:text-zinc-300 text-sm flex items-center gap-1">
                                            {{ $featureText }}
                                            @if($tooltip)
                                                <span x-data="{ show: false }" @mouseenter="show = true" @mouseleave="show = false" class="relative inline-flex">
                                                    <svg class="w-4 h-4 text-zinc-400 hover:text-zinc-600 cursor-help" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                                                    </svg>
                                                    <div x-show="show" x-cloak class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-3 w-64 p-2 bg-zinc-900 text-white text-xs rounded-lg shadow-lg z-10">
                                                        {{ $tooltip }}
                                                        <div class="absolute top-full left-1/2 transform -translate-x-1/2 -mt-1">
                                                            <div class="border-4 border-transparent border-t-zinc-900"></div>
                                                        </div>
                                                    </div>
                                                </span>
                                            @endif
                                        </span>
                                    </span>
                                </li>
                            @endforeach
                        </ul>

                        <div class="mt-auto">
                            @php
                                $isCurrentPlan = $currentPlanId && $currentPlanId == $plan->id;
                                $isSeatedPlan = $plan->is_seated_plan;

                                // For seated plans, check if user has this plan AND is changing seat count
                                $currentSeats = null;
                                if ($isCurrentPlan && $isSeatedPlan && $subscription) {
                                    $currentSeats = $subscription->seats_purchased;
                                }

                                // ALWAYS use /checkout route for consistency and proper trial handling
                                $checkoutUrl = $isSeatedPlan
                                    ? "'/checkout/{$plan->id}?billing=' + billing.toLowerCase() + '&seats=' + (seats[{$plan->id}] || 1)"
                                    : "'/checkout/{$plan->id}?billing=' + billing.toLowerCase()";
                            @endphp

                            @if($isCurrentPlan && !$isSeatedPlan)
                                <button disabled class="w-full px-6 py-3 bg-zinc-200 dark:bg-zinc-700 text-zinc-500 dark:text-zinc-400 font-medium rounded-lg cursor-not-allowed opacity-75">
                                    You're on this plan
                                </button>
                            @elseif($isCurrentPlan && $isSeatedPlan)
                                <!-- Seated plan - show button if seats changed -->
                                <div x-data="{ currentSeats: {{ $currentSeats }} }">
                                    <button
                                        x-show="(seats[{{ $plan->id }}] || 1) === currentSeats"
                                        disabled
                                        class="w-full px-6 py-3 bg-zinc-200 dark:bg-zinc-700 text-zinc-500 dark:text-zinc-400 font-medium rounded-lg cursor-not-allowed opacity-75">
                                        You're on this plan
                                    </button>
                                    <a
                                        x-show="(seats[{{ $plan->id }}] || 1) !== currentSeats"
                                        x-bind:href="{{ $checkoutUrl }}"
                                        x-on:click="setLoading('{{ $plan->id }}', true)"
                                        :class="loading['{{ $plan->id }}'] ? 'opacity-50 cursor-not-allowed' : ''"
                                        class="flex items-center justify-center w-full px-6 py-3 bg-zinc-900 hover:bg-black dark:bg-white dark:hover:bg-zinc-100 text-white dark:text-zinc-900 font-medium rounded-lg transition-colors duration-150">
                                        <span x-show="!loading['{{ $plan->id }}']">Update Subscription</span>
                                        <span x-show="loading['{{ $plan->id }}']" x-cloak>
                                            <svg class="animate-spin h-5 w-5 text-white dark:text-zinc-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </span>
                                    </a>
                                </div>
                            @else
                                <a
                                    x-bind:href="{{ $checkoutUrl }}"
                                    x-on:click="console.log('Clicking plan {{ $plan->id }}', $event.target.href); setLoading('{{ $plan->id }}', true)"
                                    :class="loading['{{ $plan->id }}'] ? 'opacity-50 cursor-not-allowed' : ''"
                                    class="flex items-center justify-center w-full px-6 py-3 bg-zinc-900 hover:bg-black dark:bg-white dark:hover:bg-zinc-100 text-white dark:text-zinc-900 font-medium rounded-lg transition-colors duration-150">
                                    <span x-show="!loading['{{ $plan->id }}']">
                                        @if($showTrial)
                                            Try for {{ $planTrialDays }} days
                                        @else
                                            Get Started
                                        @endif
                                    </span>
                                    <span x-show="loading['{{ $plan->id }}']" x-cloak>
                                        <svg class="animate-spin h-5 w-5 text-white dark:text-zinc-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </span>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Enterprise/On-Request Plans (Below, full width with special styling) -->
        @if($enterprisePlans->isNotEmpty())
            <div class="mt-16">
                <div class="text-center mb-8">
                    <h3 class="text-2xl font-bold text-zinc-900 dark:text-white mb-2">Need More? Let's Talk</h3>
                    <p class="text-zinc-600 dark:text-zinc-400">Custom solutions for high-volume lead generation</p>
                </div>

                @foreach($enterprisePlans as $plan)
                    @php
                        $features = [];
                        if (is_string($plan->features)) {
                            $decoded = json_decode($plan->features, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                $features = $decoded;
                            } else {
                                $features = array_map('trim', explode(',', $plan->features));
                            }
                        } elseif (is_array($plan->features)) {
                            $features = $plan->features;
                        }
                    @endphp
                    <div class="max-w-4xl mx-auto bg-gradient-to-br from-zinc-50 to-zinc-100 dark:from-zinc-800 dark:to-zinc-900 rounded-xl border-2 border-zinc-900 dark:border-white shadow-lg p-8">
                        <div class="grid md:grid-cols-2 gap-8">
                            <div>
                                <span class="px-4 py-1 text-base font-medium text-white rounded-full bg-zinc-900 dark:bg-white dark:text-zinc-900 text-uppercase">
                                    {{ $plan->name }}
                                </span>
                                <div class="mt-6">
                                    <span class="text-4xl font-bold text-zinc-900 dark:text-white">Let's Discuss</span>
                                    <p class="mt-4 text-base text-zinc-600 dark:text-zinc-400">{{ $plan->description }}</p>
                                </div>
                                <div class="mt-8">
                                    <a href="{{ route('contact') }}" class="inline-flex items-center justify-center px-6 py-3 bg-zinc-900 hover:bg-black dark:bg-white dark:hover:bg-zinc-100 text-white dark:text-zinc-900 font-medium rounded-lg transition-colors duration-150">
                                        Contact Us
                                    </a>
                                </div>
                            </div>
                            <div>
                                <h4 class="font-semibold text-zinc-900 dark:text-white mb-4">Everything you need:</h4>
                                <ul class="flex flex-col space-y-2">
                                    @foreach($features as $feature)
                                        <li>
                                            <span class="flex items-start text-green-500">
                                                <svg class="mr-3 w-4 h-4 fill-current flex-shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M0 11l2-2 5 5L18 3l2 2L7 18z"></path></svg>
                                                <span class="text-zinc-700 dark:text-zinc-300 text-sm">
                                                    {{ is_string($feature) ? $feature : json_encode($feature) }}
                                                </span>
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Footer Info -->
    <div class="mt-8 mb-8 w-full text-center sm:my-12">
        <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
            <div class="flex items-center text-sm text-zinc-600 dark:text-zinc-400">
                <svg class="w-5 h-5 mr-2 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                Cancel anytime
            </div>
            <div class="flex items-center text-sm text-zinc-600 dark:text-zinc-400">
                <svg class="w-5 h-5 mr-2 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                Priority support
            </div>
        </div>
    </div>
</section>
