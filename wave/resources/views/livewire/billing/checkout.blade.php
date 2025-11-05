@php
    use Wave\Plugins\EvenLeads\Models\Setting;
    $trialDays = Setting::getValue('trial_days', 7);
    $defaultTrialPlan = Setting::getValue('trial_plan_id', null);
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

    // Get user's current plan if logged in
    $currentPlanId = null;
    $userHasUsedTrial = false;
    if (auth()->check()) {
        if (auth()->user()->subscription) {
            $currentPlanId = auth()->user()->subscription->plan_id;
        }
        // Check if user has already used a trial
        // Check both user trial_ends_at AND trial_activated_at (either means they used trial)
        $userHasUsedTrial = !empty(auth()->user()->trial_ends_at) || !empty(auth()->user()->trial_activated_at);
    }

    // Separate regular plans from enterprise/on-request plans
    $regularPlans = $plans->filter(function($plan) {
        return !$plan->is_on_request;
    });

    $enterprisePlans = $plans->filter(function($plan) {
        return $plan->is_on_request;
    });
@endphp

<section>
    <div x-data="{
            billing_cycle_available: @entangle('billing_cycle_available'),
            billing_cycle_selected: @entangle('billing_cycle_selected'),
            loading: {},
            seats: {},
            setLoading(planId, state) {
                this.loading[planId] = state;
            },
            initSeats(planId) {
                if (!this.seats[planId]) {
                    this.seats[planId] = 1;
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
            toggleButtonClicked(el, month_or_year){
                this.toggleRepositionMarker(el);
                this.billing_cycle_selected = month_or_year;
            },
            toggleRepositionMarker(toggleButton){
                if(this.$refs.marker && toggleButton){
                    this.$refs.marker.style.width=toggleButton.offsetWidth + 'px';
                    this.$refs.marker.style.height=toggleButton.offsetHeight + 'px';
                    this.$refs.marker.style.left=toggleButton.offsetLeft + 'px';
                }
            },
            fullScreenLoader: false,
            fullScreenLoaderMessage: 'Loading'
        }"
        x-init="
            setTimeout(function(){
                // Position marker based on selected billing cycle
                const selectedRef = billing_cycle_selected === 'year' ? $refs.yearly : $refs.monthly;
                toggleRepositionMarker(selectedRef);
                if($refs.marker){
                    $refs.marker.classList.remove('opacity-0');
                    setTimeout(function(){
                        $refs.marker.classList.add('duration-300', 'ease-out');
                    }, 10);
                }
            }, 1);
        "
        @loader-show.window="fullScreenLoader = true"
        @loader-hide.window="fullScreenLoader = false"
        @loader-message.window="fullScreenLoaderMessage = event.detail.message"
        class="w-full">

        <x-billing.billing_cycle_toggle></x-billing.billing_cycle_toggle>

        <!-- Regular Plans Grid (3 columns) -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12 mt-8">
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

                    $hasMonthly = !empty($plan->monthly_price) && $plan->monthly_price > 0;
                    $hasYearly = !empty($plan->yearly_price) && $plan->yearly_price > 0;

                    // Get plan-specific trial days, fallback to global setting
                    $planTrialDays = $plan->trial_days ?? $trialDays;

                    // Determine if this plan should show trial option
                    // Only show trial if:
                    // 1. This plan is marked as the trial plan in settings
                    // 2. Trial days > 0
                    // 3. User hasn't already used a trial (trial_ends_at is not set)
                    $showTrial = $defaultTrialPlan == $plan->id && $planTrialDays > 0 && !$userHasUsedTrial;

                    // Use plan-specific currency, fallback to site currency
                    $planCurrency = $plan->currency ?? $currency;
                    $planCurrencyDisplay = $planCurrency;
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
                        $planCurrencyDisplay = $currencySymbols[$planCurrency] ?? $planCurrency;
                    }
                @endphp

                <div
                    x-show="(billing_cycle_selected == 'month' && {{ $hasMonthly ? 'true' : 'false' }}) || (billing_cycle_selected == 'year' && {{ $hasYearly ? 'true' : 'false' }})"
                    x-init="
                        initSeats({{ $plan->id }});
                        const urlParams = new URLSearchParams(window.location.search);
                        const selectedPlanId = urlParams.get('plan');
                        if (selectedPlanId == '{{ $plan->id }}') {
                            setTimeout(() => {
                                $el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            }, 300);
                        }
                    "
                    id="plan-{{ $plan->id }}"
                    class="flex flex-col h-full bg-white dark:bg-zinc-800 rounded-xl border-2 @if(request()->get('plan') == $plan->id || (!request()->has('plan') && $plan->default)) border-zinc-900 dark:border-zinc-100 lg:scale-105 @else border-zinc-200 dark:border-zinc-700 @endif shadow-sm"
                    x-cloak>
                    <div class="px-8 pt-8">
                        <span class="px-4 py-1 text-base font-medium text-white dark:text-zinc-900 rounded-full bg-zinc-900 dark:bg-zinc-100 text-uppercase">
                            {{ $plan->name }}
                        </span>
                    </div>

                    <div class="px-8 mt-5">
                        <div class="flex items-baseline gap-2">
                            <span class="text-5xl font-bold text-zinc-900 dark:text-white">
                                @if($currencyPosition === 'prepend'){{ $planCurrencyDisplay }} @endif<span x-text="billing_cycle_selected == 'month' ? '{{ $plan->monthly_price }}' : '{{ $plan->yearly_price }}'"></span>@if($currencyPosition === 'append') {{ $planCurrencyDisplay }}@endif
                            </span>
                            <span class="text-xl font-bold text-zinc-500 dark:text-zinc-400">
                                <span x-text="billing_cycle_selected == 'month' ? '/mo' : '/yr'"></span>@if($plan->is_seated_plan) per seat @endif
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
                                            @click="decrementSeats({{ $plan->id }})"
                                            type="button"
                                            class="w-8 h-8 flex items-center justify-center rounded-lg bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                                            <svg class="w-4 h-4 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                            </svg>
                                        </button>
                                        <span class="text-xl font-bold text-zinc-900 dark:text-white min-w-[2ch] text-center" x-text="seats[{{ $plan->id }}] || 1"></span>
                                        <button
                                            @click="incrementSeats({{ $plan->id }})"
                                            type="button"
                                            class="w-8 h-8 flex items-center justify-center rounded-lg bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                                            <svg class="w-4 h-4 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Live description showing seat breakdown -->
                                <div class="mt-2 text-xs text-zinc-600 dark:text-zinc-400 text-center">
                                    <span x-show="(seats[{{ $plan->id }}] || 1) === 1">Just you (owner)</span>
                                    <span x-show="(seats[{{ $plan->id }}] || 1) === 2">You + 1 team member</span>
                                    <span x-show="(seats[{{ $plan->id }}] || 1) > 2" x-text="'You + ' + ((seats[{{ $plan->id }}] || 1) - 1) + ' team members'"></span>
                                </div>

                                <div class="mt-3 pt-3 border-t border-zinc-200 dark:border-zinc-700">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-zinc-600 dark:text-zinc-400">Total</span>
                                        <span class="text-lg font-bold text-zinc-900 dark:text-white">
                                            @if($currencyPosition === 'prepend'){{ $planCurrencyDisplay }} @endif<span x-text="(billing_cycle_selected == 'month' ? {{ $plan->monthly_price }} : {{ $plan->yearly_price }}) * (seats[{{ $plan->id }}] || 1)"></span>@if($currencyPosition === 'append') {{ $planCurrencyDisplay }}@endif<span class="text-sm text-zinc-500 dark:text-zinc-400" x-text="billing_cycle_selected == 'month' ? '/mo' : '/yr'"></span>
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
                                                    <svg class="w-4 h-4 text-zinc-400 dark:text-zinc-500 hover:text-zinc-600 dark:hover:text-zinc-300 cursor-help" fill="currentColor" viewBox="0 0 20 20">
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
                            @php $isCurrentPlan = $currentPlanId && $currentPlanId == $plan->id; @endphp
                            @if($isCurrentPlan)
                                <button disabled class="w-full px-6 py-3 bg-zinc-200 dark:bg-zinc-700 text-zinc-500 dark:text-zinc-400 font-medium rounded-lg cursor-not-allowed opacity-75">
                                    You're on this plan
                                </button>
                            @else
                                <button
                                    @if(config('wave.billing_provider') == 'stripe')
                                        @if($plan->is_seated_plan)
                                            x-on:click="
                                                const seatsParam = seats[{{ $plan->id }}] || 1;
                                                $wire.call('redirectToStripeCheckout', '{{ $plan->id }}', seatsParam);
                                                setLoading('{{ $plan->id }}', true);
                                            "
                                        @else
                                            wire:click="redirectToStripeCheckout('{{ $plan->id }}')"
                                            x-on:click="setLoading('{{ $plan->id }}', true)"
                                        @endif
                                    @endif
                                    :disabled="loading['{{ $plan->id }}']"
                                    class="w-full px-6 py-3 bg-zinc-900 hover:bg-black dark:bg-white dark:hover:bg-zinc-100 text-white dark:text-zinc-900 font-medium rounded-lg transition-colors duration-150 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center">
                                    <span x-show="!loading['{{ $plan->id }}']">
                                        @if($showTrial)
                                            Try for {{ $planTrialDays }} days
                                        @else
                                            Get Started
                                        @endif
                                    </span>
                                    <span x-show="loading['{{ $plan->id }}']" x-cloak>
                                        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </span>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Enterprise Plans -->
        @if($enterprisePlans->isNotEmpty())
            <div class="mt-12">
                @foreach($enterprisePlans as $plan)
                    @php
                        // Decode features
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
                            <!-- Left Column: Plan Info -->
                            <div>
                                <div class="flex items-center gap-3">
                                    <span class="px-4 py-1 text-base font-medium text-white dark:text-zinc-900 rounded-full bg-zinc-900 dark:bg-white text-uppercase">
                                        {{ $plan->name }}
                                    </span>
                                    <span class="text-sm text-zinc-500 dark:text-zinc-400 font-medium">
                                        Custom Pricing
                                    </span>
                                </div>

                                <div class="mt-6">
                                    <span class="text-4xl font-bold text-zinc-900 dark:text-white">Let's Discuss</span>
                                    <p class="mt-4 text-base text-zinc-600 dark:text-zinc-400">{{ $plan->description }}</p>
                                    @if($plan->custom_plan_description)
                                        <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ $plan->custom_plan_description }}</p>
                                    @endif
                                </div>

                                <div class="mt-8">
                                    <a href="/contact" class="inline-flex items-center px-6 py-3 bg-zinc-900 hover:bg-black dark:bg-white dark:hover:bg-zinc-100 text-white dark:text-zinc-900 font-medium rounded-lg transition-colors duration-150">
                                        Contact Sales
                                        <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>

                            <!-- Right Column: Features -->
                            <div>
                                <h4 class="font-semibold text-zinc-900 dark:text-white mb-4">Everything you need:</h4>
                                @if(!empty($features))
                                    <ul class="flex flex-col space-y-2">
                                        @foreach($features as $feature)
                                            @php
                                                $featureText = is_string($feature) ? $feature : json_encode($feature);
                                                $tooltip = null;

                                                // Add tooltips for specific features
                                                if (stripos($featureText, 'Smart lead retrieval') !== false || stripos($featureText, 'smart retrieval') !== false) {
                                                    $tooltip = 'AI scans posts not only by keywords but also analyzes content to ensure you get the perfect leads for your business';
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
                                                                <svg class="w-4 h-4 text-zinc-400 dark:text-zinc-500 hover:text-zinc-600 dark:hover:text-zinc-300 cursor-help" fill="currentColor" viewBox="0 0 20 20">
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
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Scheduled Downgrade Conflict Modal -->
<div x-data x-init="
    $watch('$wire.showDowngradeConflictModal', value => {
        if (value === true) { document.body.classList.add('overflow-hidden') }
        else { document.body.classList.remove('overflow-hidden') }
    });"
    x-show="$wire.showDowngradeConflictModal"
    class="fixed inset-0 z-[9999] overflow-y-auto"
    x-cloak
    style="z-index: 9999;">
    <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <!-- Overlay/Backdrop -->
        <div x-show="$wire.showDowngradeConflictModal"
             @click="$wire.showDowngradeConflictModal = false"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 transition-opacity z-[9998]"
             style="z-index: 9998;">
            <div class="absolute inset-0 bg-black opacity-50"></div>
        </div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen"></span>&#8203;
        <!-- Modal Content -->
        <div x-show="$wire.showDowngradeConflictModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="relative inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white dark:bg-zinc-800 rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6 z-[10000]"
             style="z-index: 10000;"
             role="dialog"
             @click.stop>
            <div class="flex flex-col justify-between w-full mt-2">
                <div class="flex flex-col items-center">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto text-center rounded-full bg-orange-100 dark:bg-orange-900/20">
                        <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <div class="mt-3 text-center sm:ml-4">
                        <h3 class="text-lg font-medium leading-6 text-zinc-900 dark:text-white" id="modal-headline">
                            Downgrade Already Scheduled
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm leading-5 text-zinc-600 dark:text-zinc-400">
                                You have a plan change to <strong x-text="$wire.scheduledPlanName"></strong> scheduled for <strong x-text="$wire.scheduledDate"></strong>.
                            </p>
                            <p class="mt-3 text-sm leading-5 text-zinc-600 dark:text-zinc-400">
                                To upgrade to <strong x-text="$wire.conflictPlanName"></strong>, we'll cancel your scheduled downgrade and proceed with the upgrade.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="mt-5 sm:mt-6 flex flex-col sm:flex-row-reverse gap-3">
                    <div class="flex flex-1 w-full rounded-md shadow-sm">
                        <button
                            wire:click="cancelDowngradeAndUpgrade"
                            wire:loading.attr="disabled"
                            wire:target="cancelDowngradeAndUpgrade"
                            type="button"
                            class="inline-flex justify-center items-center w-full px-3 py-2 text-sm font-medium text-white dark:text-zinc-900 transition duration-150 ease-in-out border border-transparent rounded-md shadow-sm cursor-pointer bg-zinc-900 hover:bg-zinc-800 dark:bg-white dark:hover:bg-zinc-100 disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-zinc-900 dark:focus:ring-white whitespace-nowrap">
                            <svg wire:loading wire:target="cancelDowngradeAndUpgrade" class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="cancelDowngradeAndUpgrade">Proceed with Upgrade</span>
                            <span wire:loading wire:target="cancelDowngradeAndUpgrade">Processing...</span>
                        </button>
                    </div>
                    <div class="flex flex-1 w-full rounded-md shadow-sm">
                        <button
                            @click="$wire.showDowngradeConflictModal = false"
                            type="button"
                            class="inline-flex justify-center items-center w-full px-3 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 transition duration-150 ease-in-out bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-md shadow-sm hover:bg-zinc-50 dark:hover:bg-zinc-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-zinc-500 whitespace-nowrap">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- END Scheduled Downgrade Conflict Modal -->
</section>
