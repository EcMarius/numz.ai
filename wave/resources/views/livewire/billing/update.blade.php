<div class="relative w-full h-auto">
    @if(config('wave.billing_provider') == 'paddle')
        <script src="https://cdn.paddle.com/paddle/v2/paddle.js"></script>
        <script>
            Paddle.Initialize({
                token: '{{ config("wave.paddle.public_key") }}',
                checkout: {
                    settings: {
                        displayMode: "overlay",
                        frameStyle: "width: 100%; min-width: 312px; background-color: transparent; border: none;",
                        locale: "en",
                        allowLogout: false
                    }
                }
            });

            if("{{ config('wave.paddle.env') }}" == 'sandbox') {
                Paddle.Environment.set('sandbox');
            }
        </script>

        @if($error_retrieving_data)
            <div class="relative w-full rounded-lg border border-transparent bg-red-50 p-4 [&>svg]:absolute [&>svg]:text-foreground [&>svg]:left-4 [&>svg]:top-4 [&>svg+div]:translate-y-[-3px] [&:has(svg)]:pl-11 text-red-600">
                <svg class="w-5 h-5 -translate-y-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                <h5 class="mb-1 font-medium tracking-tight leading-none">Payment Provider Error</h5>
                <div class="text-sm opacity-80">Error fetching your subscription data. Please reload or contact support.</div>
            </div>
        @endif

        <div class="flex items-start space-x-2">
            
            <x-filament::modal width="lg" id="update-plan-modal" slide-over>
                <x-slot name="trigger">
                        <x-button x-on:click="setTimeout(function(){ window.dispatchEvent(new CustomEvent('reposition-interval-marker')); }, 10);" color="primary" class="flex-shrink-0">Change My Plan</x-button>
                </x-slot>
                <div class="flex relative flex-col justify-center items-center">
                    <livewire:billing.checkout :change="true" />
                </div>
                {{-- Modal content --}}
            </x-filament::modal>

            <x-button color="primary" href="{{ $update_url }}" tag="a" class="flex-shrink-0">Update Payment Details</x-button>

            @if($cancellation_scheduled)
                <p class="block flex-1 text-red-600">Your subscription will be canceled on {{ \Carbon\Carbon::parse($subscription_ends_at)->format('F jS, Y') }}. To re-activate it, please <button wire:click="cancelImmediately" wire:confirm="This will cancel your subscription immediately, are you sure?" class="underline">cancel immediately</button> and place a new order.
            @else
                <x-filament::modal width="lg" id="cancel-modal">
                    <x-slot name="trigger">
                            <x-button color="danger">Cancel My Subscription</x-button>
                    </x-slot>
                    <div x-data class="flex relative flex-col justify-center items-center">
                        <div class="flex flex-shrink-0 justify-center items-center mx-auto w-12 h-12 bg-red-100 rounded-full sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="w-6 h-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"></path></svg>
                        </div>
                        <div class="mt-3 mb-5 text-center">
                            <h3 class="text-base font-semibold leading-6 text-gray-900" id="modal-title">Cancel Subscription</h3>
                            <div class="mt-2 max-w-xs">
                                <p class="text-sm text-gray-500">Are you sure you want to cancel? <br>You will not be able to re-activate immediately.</p>
                            </div>
                        </div>
                        <div class="flex relative items-center space-x-3 w-full">
                            <x-button x-on:click="$dispatch('close-modal', { id: 'cancel-modal' })" color="secondary" class="w-1/2">No Thanks</x-button> 
                            <x-button wire:click="cancel" color="danger" class="w-1/2">Cancel Subscription</x-button>
                            {{-- <x-button tag="a" href="{{ $cancel_url }}" color="danger" class="w-1/2">Cancel Subscription</x-button> --}}
                        </div>
                    </div>
                    {{-- Modal content --}}
                </x-filament::modal>
            @endif
            
        </div>
    @else
        {{-- Stripe Billing Management --}}
        <div class="space-y-4">
            {{-- Subscription Details Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Subscription Details</h3>

                <dl class="space-y-3">
                    @if($subscription)
                        <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Plan</dt>
                            <dd class="text-sm text-gray-900 dark:text-white font-semibold">{{ auth()->user()->plan()?->name ?? 'N/A' }}</dd>
                        </div>

                        <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Billing Cycle</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">{{ ucfirst(auth()->user()->planInterval() ?? 'N/A') }}</dd>
                        </div>

                        <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                            <dd class="text-sm">
                                <span class="px-2 py-1 rounded-full text-xs font-medium
                                    @if($subscription->status === 'active') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400
                                    @elseif($subscription->status === 'cancelled') bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400
                                    @else bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400
                                    @endif">
                                    {{ ucfirst($subscription->status) }}
                                </span>
                            </dd>
                        </div>

                        @if(!is_null($subscription->ends_at))
                            <div class="flex justify-between py-2">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Subscription Ends</dt>
                                <dd class="text-sm text-red-600 dark:text-red-400 font-semibold">{{ \Carbon\Carbon::parse($subscription_ends_at)->format('F jS, Y') }}</dd>
                            </div>
                        @endif
                    @endif
                </dl>
            </div>

            {{-- Action Buttons --}}
            <div class="flex flex-wrap gap-3">
                <x-button :href="route('stripe.portal')" tag="a" color="primary">
                    Update Payment Method
                </x-button>

                @if($cancellation_scheduled || !is_null($subscription->ends_at))
                    <p class="text-sm text-red-600 dark:text-red-400 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg flex-1">
                        Your subscription is scheduled to cancel on {{ \Carbon\Carbon::parse($subscription_ends_at)->format('F jS, Y') }}.
                        <a href="{{ route('stripe.portal') }}" class="underline font-medium">Click here</a> to re-activate your subscription.
                    </p>
                @else
                    @if(!$showCancelForm)
                        <x-button wire:click="openCancelForm" color="danger">Cancel Subscription</x-button>
                    @endif
                @endif
            </div>

            {{-- Cancellation Form --}}
            @if($showCancelForm)
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-6">
                    <div class="flex items-start mb-4">
                        <div class="flex-shrink-0">
                            <svg class="w-6 h-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-base font-semibold text-red-900 dark:text-red-200">Cancel Subscription</h3>
                            <p class="mt-1 text-sm text-red-700 dark:text-red-300">We're sorry to see you go. Please help us improve by telling us why you're canceling.</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label for="cancellation_reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Reason for cancellation *</label>
                            <select wire:model="cancellation_reason" id="cancellation_reason" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                                <option value="">Select a reason...</option>
                                <option value="too_expensive">Too expensive</option>
                                <option value="missing_features">Missing features I need</option>
                                <option value="not_using">Not using it enough</option>
                                <option value="technical_issues">Technical issues</option>
                                <option value="switching_competitor">Switching to a competitor</option>
                                <option value="temporary">Taking a temporary break</option>
                                <option value="other">Other</option>
                            </select>
                            @error('cancellation_reason')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="cancellation_details" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Additional details (optional)</label>
                            <textarea wire:model="cancellation_details" id="cancellation_details" rows="3" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white focus:border-primary-500 focus:ring-primary-500" placeholder="Tell us more about your decision..."></textarea>
                            @error('cancellation_details')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex gap-3 pt-2">
                            <x-button wire:click="closeCancelForm" color="secondary" class="flex-1">
                                Nevermind, Keep My Subscription
                            </x-button>
                            <x-button wire:click="cancelSubscription" color="danger" class="flex-1">
                                Confirm Cancellation
                            </x-button>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>