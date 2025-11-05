<?php

    use Filament\Forms\Components\TextInput;
    use Livewire\Volt\Component;
    use function Laravel\Folio\{middleware, name};
    use Filament\Forms\Concerns\InteractsWithForms;
    use Filament\Forms\Contracts\HasForms;
    use Filament\Forms\Form;
    use Filament\Notifications\Notification;

    middleware('auth');
    name('settings.subscription');

	new class extends Component
	{
        public $cancellingDowngrade = false;

        public function mount(): void
        {
            // No redirect logic needed - all plan buttons now go directly to /checkout route
        }

        public function cancelScheduledDowngrade()
        {
            $this->cancellingDowngrade = true;

            $subscription = \Wave\Subscription::where('billable_id', auth()->user()->id)
                ->where('status', 'active')
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$subscription || !$subscription->scheduled_plan_id) {
                \Filament\Notifications\Notification::make()
                    ->title('No Scheduled Downgrade')
                    ->body('You don\'t have any scheduled plan changes.')
                    ->warning()
                    ->send();

                $this->cancellingDowngrade = false;
                return;
            }

            try {
                $stripe = new \Stripe\StripeClient(config('wave.stripe.secret_key'));

                // Revert Stripe subscription back to current plan
                $stripeSubscription = $stripe->subscriptions->retrieve($subscription->vendor_subscription_id);
                $currentPlan = $subscription->plan;

                $current_price_id = $subscription->cycle == 'month'
                    ? $currentPlan->monthly_price_id
                    : $currentPlan->yearly_price_id;

                $stripe->subscriptions->update($subscription->vendor_subscription_id, [
                    'items' => [
                        [
                            'id' => $stripeSubscription->items->data[0]->id,
                            'price' => $current_price_id,
                        ],
                    ],
                    'proration_behavior' => 'none',
                ]);

                // Clear scheduled downgrade from database
                $scheduledPlanName = \Wave\Plan::find($subscription->scheduled_plan_id)->name ?? 'Unknown';
                $subscription->scheduled_plan_id = null;
                $subscription->scheduled_plan_date = null;
                $subscription->scheduled_plan_limits = null;
                $subscription->save();

                \Log::info('Scheduled downgrade cancelled', [
                    'subscription_id' => $subscription->id,
                    'cancelled_plan' => $scheduledPlanName
                ]);

                \Filament\Notifications\Notification::make()
                    ->title('Downgrade Cancelled')
                    ->body('Your scheduled plan change has been cancelled. You will continue on your current plan.')
                    ->success()
                    ->send();

            } catch (\Exception $e) {
                \Log::error('Failed to cancel scheduled downgrade', [
                    'error' => $e->getMessage(),
                    'subscription_id' => $subscription->id
                ]);

                \Filament\Notifications\Notification::make()
                    ->title('Cancellation Failed')
                    ->body('Unable to cancel scheduled downgrade. Please contact support.')
                    ->danger()
                    ->send();
            }

            $this->cancellingDowngrade = false;
        }
    }

?>

<x-layouts.app>
    @volt('settings.subscription')
        <div class="relative w-full">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

                @php
                    $user = auth()->user();
                    $subscription = \Wave\Subscription::where('billable_id', $user->id)
                        ->where('billable_type', 'user')
                        ->where('status', 'active')
                        ->with('plan')
                        ->first();
                    $hasSubscription = !is_null($subscription);
                @endphp

                <!-- My Plan Section -->
                @if($hasSubscription)
                    <div class="mb-12">
                        <div class="mb-6">
                            <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">My Plan</h1>
                            <p class="mt-2 text-zinc-600 dark:text-zinc-400">Your current subscription details</p>
                        </div>

                        @if($subscription->scheduled_plan_id)
                            @php
                                $scheduledPlan = \Wave\Plan::find($subscription->scheduled_plan_id);
                                $scheduledDate = \Carbon\Carbon::parse($subscription->scheduled_plan_date);
                            @endphp

                            <!-- Pending Downgrade Warning -->
                            <div class="mb-6 p-6 bg-orange-50 dark:bg-orange-900/20 rounded-xl border-2 border-orange-300 dark:border-orange-700">
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
                                            <strong>{{ $scheduledDate->format('F j, Y') }}</strong>
                                            ({{ $scheduledDate->diffForHumans() }}).
                                        </p>
                                        <p class="text-sm text-orange-800 dark:text-orange-300 mb-3">
                                            Your current {{ $subscription->plan->name }} plan will remain active until {{ $scheduledDate->format('F j, Y') }}.
                                            After this date, your plan limits will change to the {{ $scheduledPlan->name }} plan limits.
                                        </p>

                                        <div class="mt-4 p-4 bg-white dark:bg-orange-950 rounded-lg">
                                            <p class="text-xs font-medium text-orange-900 dark:text-orange-200 mb-2">
                                                New Plan Limits (starting {{ $scheduledDate->format('M j') }}):
                                            </p>
                                            @php
                                                $limits = is_string($subscription->scheduled_plan_limits)
                                                    ? json_decode($subscription->scheduled_plan_limits, true)
                                                    : $subscription->scheduled_plan_limits;
                                            @endphp
                                            <ul class="text-xs text-orange-800 dark:text-orange-300 space-y-1">
                                                <li>• Campaigns: {{ $limits['campaigns'] ?? 0 }}</li>
                                                <li>• Leads Storage: {{ $limits['leads_storage'] ?? 0 }}</li>
                                                <li>• AI Replies/Month: {{ $limits['ai_replies_per_month'] ?? 0 }}</li>
                                                <li>• Manual Syncs/Month: {{ $limits['manual_syncs_per_month'] ?? 0 }}</li>
                                            </ul>
                                        </div>

                                        <div class="mt-4">
                                            <button
                                                wire:click="cancelScheduledDowngrade"
                                                wire:loading.attr="disabled"
                                                wire:target="cancelScheduledDowngrade"
                                                class="inline-flex items-center px-4 py-2 bg-orange-600 hover:bg-orange-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-medium rounded-lg transition-colors duration-150">
                                                <svg wire:loading.remove wire:target="cancelScheduledDowngrade" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                                <svg wire:loading wire:target="cancelScheduledDowngrade" class="animate-spin w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                <span wire:loading.remove wire:target="cancelScheduledDowngrade">Cancel Scheduled Downgrade</span>
                                                <span wire:loading wire:target="cancelScheduledDowngrade">Cancelling...</span>
                                            </button>
                                            <p class="mt-2 text-xs text-orange-700 dark:text-orange-400">
                                                Click to keep your current {{ $subscription->plan->name }} plan
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm overflow-hidden">
                            <div class="p-6">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3">
                                            <h2 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $subscription->plan->name }}</h2>
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                {{ ucfirst($subscription->status) }}
                                            </span>
                                        </div>
                                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $subscription->plan->description }}</p>

                                        <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div>
                                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Billing Cycle</p>
                                                <p class="mt-1 text-sm font-semibold text-zinc-900 dark:text-white">{{ ucfirst($subscription->cycle) }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Started</p>
                                                <p class="mt-1 text-sm font-semibold text-zinc-900 dark:text-white">{{ \Carbon\Carbon::parse($subscription->created_at)->format('M d, Y') }}</p>
                                            </div>
                                            @if($subscription->ends_at)
                                            <div>
                                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Ends At</p>
                                                <p class="mt-1 text-sm font-semibold text-orange-600 dark:text-orange-400">{{ \Carbon\Carbon::parse($subscription->ends_at)->format('M d, Y') }}</p>
                                            </div>
                                            @endif
                                        </div>

                                        @if (session('update'))
                                            <div class="mt-4 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                                                <p class="text-sm text-green-800 dark:text-green-200">Successfully updated your subscription</p>
                                            </div>
                                        @endif

                                        @if (session('error'))
                                            <div class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                                                <p class="text-sm text-red-800 dark:text-red-200">{{ session('error') }}</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-700" x-data="{ showManage: false }">
                                    <button @click="showManage = !showManage"
                                            class="inline-flex items-center justify-center px-6 py-3 bg-zinc-900 hover:bg-black dark:bg-zinc-700 dark:hover:bg-zinc-600 text-white font-medium rounded-lg transition-colors duration-150">
                                        <x-phosphor-gear-duotone class="w-5 h-5 mr-2" />
                                        Manage my subscription
                                        <svg x-show="!showManage" class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                        <svg x-show="showManage" class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                        </svg>
                                    </button>

                                    <!-- Expandable Management Options -->
                                    <div x-show="showManage"
                                         x-collapse
                                         class="mt-6 space-y-4">

                                        <!-- Stripe Portal Button -->
                                        <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700">
                                            <h4 class="text-sm font-semibold text-zinc-900 dark:text-white mb-2">Stripe Billing Portal</h4>
                                            <p class="text-xs text-zinc-600 dark:text-zinc-400 mb-3">
                                                Access the secure Stripe portal to view invoices, update billing info, and manage your subscription
                                            </p>
                                            <a href="{{ route('stripe.portal') }}"
                                               class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition-colors duration-150">
                                                <x-phosphor-bank-duotone class="w-4 h-4 mr-2" />
                                                Open Stripe Portal
                                            </a>
                                        </div>

                                        <!-- Update Payment & Cancel Options -->
                                        <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700">
                                            <h4 class="text-sm font-semibold text-zinc-900 dark:text-white mb-3">Quick Actions</h4>
                                            <livewire:billing.update />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="mb-8">
                        <x-app.alert id="no_subscriptions" :dismissable="false" type="info">
                            <div class="flex items-center space-x-1.5">
                                <x-phosphor-shopping-bag-open-duotone class="flex-shrink-0 mr-1.5 -ml-1.5 w-6 h-6" />
                                <span>No active subscriptions found. Please select a plan below to get started.</span>
                            </div>
                        </x-app.alert>
                    </div>
                @endif

                <!-- Seat Management Section (for seated plans) -->
                @if($hasSubscription && auth()->user()->isOrganizationOwner())
                    <div class="mb-12">
                        @livewire('seat-management')
                    </div>
                @endif

                <!-- Available Plans Section -->
                <div>
                    <div class="mb-6">
                        <h2 class="text-3xl font-bold text-zinc-900 dark:text-white">
                            @if($hasSubscription)
                                Switch or Upgrade Plan
                            @else
                                Available Plans
                            @endif
                        </h2>
                        <p class="mt-2 text-zinc-600 dark:text-zinc-400">
                            @if($hasSubscription)
                                Choose a different plan or upgrade to unlock more features
                            @else
                                Choose the perfect plan for your needs
                            @endif
                        </p>
                    </div>

                    {{-- Use same pricing component as plan-selection for consistent trial handling --}}
                    <x-marketing.sections.pricing />

                    <p class="flex items-center justify-center mt-6 text-sm text-zinc-600 dark:text-zinc-400">
                        <x-phosphor-shield-check-duotone class="w-4 h-4 mr-1" />
                        <span class="mr-1">Billing is securely managed via </span><strong>{{ ucfirst(config('wave.billing_provider')) }} Payment Platform</strong>.
                    </p>
                </div>

            </div>
        </div>
    @endvolt
</x-layouts.app>
