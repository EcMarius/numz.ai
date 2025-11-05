<?php
    use function Laravel\Folio\{middleware, name};
    name('subscription.welcome');
    middleware('auth');

    $user = auth()->user();
    $subscription = null;
    $plan = null;
    $showCountrySelector = $user && !$user->country; // Show country selector if user exists and country not set

    if ($user) {
        try {
            // Get the LATEST ACTIVE subscription record from the database
            $subscription = \Wave\Subscription::where('billable_id', $user->id)
                ->where('billable_type', 'user')
                ->where('status', 'active')
                ->orderBy('created_at', 'desc')
                ->first();
            $plan = $subscription ? $subscription->plan : null;

            // No redirect here - users can view their subscription welcome page
            // Redirect to org setup only happens when accessing /team page
        } catch (\Exception $e) {
            \Log::error('Subscription welcome page error: ' . $e->getMessage());
        }
    }
?>

<x-layouts.app>
	<x-app.container x-data class="space-y-6" x-cloak>
        <div class="w-full">
            <x-app.heading
                title="Welcome to {{ $plan ? $plan->name : 'EvenLeads' }}! 🎉"
                description="Your subscription has been activated successfully."
            />

            @if($plan && $user)
                <div class="py-5 space-y-6">
                    <!-- Country Selector (Onboarding) -->
                    @if($showCountrySelector)
                        <div class="p-6 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-xl border-2 border-indigo-200 dark:border-indigo-700 shadow-sm" x-data="{ country: '{{ $user->country ?? '' }}', saving: false }">
                            <h3 class="text-lg font-semibold text-indigo-900 dark:text-indigo-100 mb-2">📍 One Last Thing!</h3>
                            <p class="text-sm text-indigo-800 dark:text-indigo-200 mb-4">Help us personalize your experience by selecting your country:</p>

                            <form @submit.prevent="
                                saving = true;
                                fetch('/api/user/update-country', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                                    },
                                    body: JSON.stringify({ country: country })
                                })
                                .then(res => res.json())
                                .then(data => {
                                    if (data.success) {
                                        $el.closest('[x-data]').remove();
                                    }
                                    saving = false;
                                })
                                .catch(() => { saving = false; })
                            " class="flex gap-3 items-end">
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-indigo-900 dark:text-indigo-100 mb-2">Country</label>
                                    <select x-model="country" required class="w-full rounded-lg border-indigo-300 dark:border-indigo-600 dark:bg-indigo-900/30 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">Select your country</option>
                                        <option value="US">United States</option>
                                        <option value="GB">United Kingdom</option>
                                        <option value="CA">Canada</option>
                                        <option value="AU">Australia</option>
                                        <option value="DE">Germany</option>
                                        <option value="FR">France</option>
                                        <option value="ES">Spain</option>
                                        <option value="IT">Italy</option>
                                        <option value="NL">Netherlands</option>
                                        <option value="SE">Sweden</option>
                                        <option value="NO">Norway</option>
                                        <option value="DK">Denmark</option>
                                        <option value="FI">Finland</option>
                                        <option value="PL">Poland</option>
                                        <option value="RO">Romania</option>
                                        <option value="BR">Brazil</option>
                                        <option value="MX">Mexico</option>
                                        <option value="AR">Argentina</option>
                                        <option value="IN">India</option>
                                        <option value="CN">China</option>
                                        <option value="JP">Japan</option>
                                        <option value="KR">South Korea</option>
                                        <option value="SG">Singapore</option>
                                        <option value="MY">Malaysia</option>
                                        <option value="TH">Thailand</option>
                                        <option value="ID">Indonesia</option>
                                        <option value="PH">Philippines</option>
                                        <option value="VN">Vietnam</option>
                                        <option value="ZA">South Africa</option>
                                        <option value="EG">Egypt</option>
                                        <option value="NG">Nigeria</option>
                                        <option value="KE">Kenya</option>
                                        <option value="NZ">New Zealand</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <button type="submit" :disabled="!country || saving" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-400 text-white font-medium rounded-lg transition-colors disabled:cursor-not-allowed flex items-center gap-2">
                                    <span x-show="!saving">Save</span>
                                    <span x-show="saving" class="flex items-center gap-2">
                                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Saving...
                                    </span>
                                </button>
                            </form>
                        </div>
                    @endif

                    <!-- Plan Details Card -->
                    <div class="p-6 bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Your Plan Details</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-zinc-600 dark:text-zinc-400">Plan Name</p>
                                <p class="text-base font-semibold text-zinc-900 dark:text-white">{{ $plan->name }}</p>
                            </div>

                            @if($subscription)
                                <div>
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400">Billing Cycle</p>
                                    <p class="text-base font-semibold text-zinc-900 dark:text-white">{{ ucfirst($subscription->cycle ?? 'Monthly') }}</p>
                                </div>

                                <div>
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400">Status</p>
                                    <p class="text-base font-semibold text-green-600 dark:text-green-400">
                                        {{ ucfirst($subscription->status) }}
                                    </p>
                                </div>

                                @if($subscription->trial_ends_at && \Carbon\Carbon::parse($subscription->trial_ends_at)->isFuture())
                                    <div>
                                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Trial Period</p>
                                        <p class="text-base font-semibold text-orange-600 dark:text-orange-400">
                                            {{ \Carbon\Carbon::parse($subscription->trial_ends_at)->diffInDays(now()) }} days remaining
                                        </p>
                                    </div>
                                @endif

                                @if($subscription->ends_at)
                                    <div>
                                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Ends At</p>
                                        <p class="text-base font-semibold text-zinc-900 dark:text-white">
                                            {{ \Carbon\Carbon::parse($subscription->ends_at)->format('M d, Y') }}
                                        </p>
                                    </div>
                                @endif

                                @if($plan && $plan->is_seated_plan && $subscription->seats_purchased)
                                    <div class="md:col-span-2">
                                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Team Seats</p>
                                        <p class="text-base font-semibold text-zinc-900 dark:text-white">
                                            {{ $subscription->seats_purchased }} {{ $subscription->seats_purchased == 1 ? 'seat' : 'seats' }}
                                            ({{ $subscription->seats_used ?? 0 }} used, {{ ($subscription->seats_purchased - ($subscription->seats_used ?? 0)) }} available)
                                        </p>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>

                    @if($plan && $plan->is_seated_plan)
                        <!-- Team Setup Card for Seated Plans -->
                        <div class="p-6 bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
                            <div class="flex items-start gap-4">
                                <svg class="w-10 h-10 text-zinc-900 dark:text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">
                                        Ready to Build Your Team?
                                    </h3>
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-4">
                                        You have {{ $subscription->seats_purchased ?? 1 }} team seats available. Set up your organization and start inviting team members to collaborate.
                                    </p>
                                    <a href="/team" class="inline-flex items-center gap-2 px-6 py-3 bg-zinc-900 hover:bg-black dark:bg-white dark:hover:bg-zinc-100 text-white dark:text-zinc-900 font-semibold rounded-lg transition-colors shadow-sm">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                        Setup Team
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($subscription && $subscription->scheduled_plan_id)
                        @php
                            $scheduledPlan = \Wave\Plan::find($subscription->scheduled_plan_id);
                            $scheduledDate = \Carbon\Carbon::parse($subscription->scheduled_plan_date);
                        @endphp

                        <!-- Pending Downgrade Warning -->
                        <div class="p-6 bg-orange-50 dark:bg-orange-900/20 rounded-xl border-2 border-orange-300 dark:border-orange-700">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0">
                                    <svg class="w-8 h-8 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-orange-900 dark:text-orange-200 mb-2">
                                        Plan Change Scheduled
                                    </h3>
                                    <p class="text-sm text-orange-800 dark:text-orange-300 mb-3">
                                        Your plan will change from <strong>{{ $subscription->plan->name }}</strong> to
                                        <strong>{{ $scheduledPlan->name }}</strong> on
                                        <strong>{{ $scheduledDate->format('F j, Y') }}</strong>.
                                        You will continue to enjoy your current plan features until then.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Plan Features -->
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

                    @if(!empty($features))
                        <div class="p-6 bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">What's Included</h3>
                            <ul class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @foreach($features as $feature)
                                    <li class="flex items-start">
                                        <svg class="mr-2 w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $feature }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Next Steps -->
                    <div class="p-6 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
                        <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-3">Next Steps</h3>
                        <ul class="space-y-2 text-sm text-blue-800 dark:text-blue-200">
                            <li class="flex items-start">
                                <span class="mr-2">1.</span>
                                <span>Create your first campaign to start generating leads</span>
                            </li>
                            <li class="flex items-start">
                                <span class="mr-2">2.</span>
                                <span>Set up your keywords and sync preferences</span>
                            </li>
                            <li class="flex items-start">
                                <span class="mr-2">3.</span>
                                <span>Review your leads and start engaging with them</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-wrap gap-3">
                        <a href="/dashboard" class="inline-flex items-center px-6 py-3 bg-zinc-900 hover:bg-black dark:bg-zinc-600 dark:hover:bg-zinc-500 text-white font-medium rounded-lg transition-colors">
                            <x-phosphor-house-duotone class="w-5 h-5 mr-2" />
                            Go to Dashboard
                        </a>
                        <a href="/settings/subscription" class="inline-flex items-center px-6 py-3 bg-white hover:bg-zinc-50 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-900 dark:text-white border border-zinc-200 dark:border-zinc-700 font-medium rounded-lg transition-colors">
                            <x-phosphor-gear-duotone class="w-5 h-5 mr-2" />
                            Manage Subscription
                        </a>
                    </div>
                </div>
            @else
                <div class="py-5 space-y-5">
                    @if(!$user)
                        <div class="p-6 bg-orange-50 dark:bg-orange-900/20 rounded-xl border border-orange-200 dark:border-orange-800">
                            <p class="text-orange-800 dark:text-orange-200">Please log in to view your subscription details.</p>
                        </div>
                        <div class="flex gap-3">
                            <a href="/login" class="inline-flex items-center px-6 py-3 bg-zinc-900 hover:bg-black dark:bg-zinc-600 dark:hover:bg-zinc-500 text-white font-medium rounded-lg transition-colors">
                                Log In
                            </a>
                            <a href="/register" class="inline-flex items-center px-6 py-3 bg-white hover:bg-zinc-50 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-900 dark:text-white border border-zinc-200 dark:border-zinc-700 font-medium rounded-lg transition-colors">
                                Sign Up
                            </a>
                        </div>
                    @else
                        <div class="p-6 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
                            <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-2">Thank you for your interest!</h3>
                            <p class="text-blue-800 dark:text-blue-200">Your subscription is being processed. If you don't see your subscription details, please check back in a few moments or contact support.</p>
                        </div>
                        <div class="flex gap-3">
                            <a href="/dashboard" class="inline-flex items-center px-6 py-3 bg-zinc-900 hover:bg-black dark:bg-zinc-600 dark:hover:bg-zinc-500 text-white font-medium rounded-lg transition-colors">
                                <x-phosphor-house-duotone class="w-5 h-5 mr-2" />
                                Go to Dashboard
                            </a>
                            <a href="/pricing" class="inline-flex items-center px-6 py-3 bg-white hover:bg-zinc-50 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-900 dark:text-white border border-zinc-200 dark:border-zinc-700 font-medium rounded-lg transition-colors">
                                <x-phosphor-shopping-cart-duotone class="w-5 h-5 mr-2" />
                                View Plans
                            </a>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </x-app.container>
    <x-slot name="javascript">
        <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js"></script>
        <script>
            confetti({
                particleCount: 100,
                spread: 70,
                origin: { y: 0.6 }
            });
        </script>
    </x-slot>
</x-layouts.app>