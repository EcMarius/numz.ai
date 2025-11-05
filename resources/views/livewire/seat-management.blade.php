<div class="w-full">
    @if($subscription && $subscription->plan && $subscription->plan->is_seated_plan)
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
            <h3 class="text-xl font-bold text-zinc-900 dark:text-white mb-4">Team Seats</h3>

            <!-- Current Seats Info -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-zinc-50 dark:bg-zinc-900 rounded-lg p-4">
                    <div class="text-sm text-zinc-600 dark:text-zinc-400 mb-1">Total Seats</div>
                    <div class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $subscription->seats_purchased }}</div>
                </div>
                <div class="bg-zinc-50 dark:bg-zinc-900 rounded-lg p-4">
                    <div class="text-sm text-zinc-600 dark:text-zinc-400 mb-1">Seats Used</div>
                    <div class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $organization->used_seats }}</div>
                </div>
                <div class="bg-zinc-50 dark:bg-zinc-900 rounded-lg p-4">
                    <div class="text-sm text-zinc-600 dark:text-zinc-400 mb-1">Available Seats</div>
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $organization->available_seats }}</div>
                </div>
            </div>

            <!-- Pending Changes Banner -->
            @if(session()->has('pending_seat_change'))
                @php
                    $pending = session('pending_seat_change');
                @endphp
                <div class="mb-6 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border-2 border-amber-200 dark:border-amber-700">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <div class="flex-1">
                            <h4 class="text-sm font-semibold text-amber-900 dark:text-amber-100 mb-1">Pending Seat Change</h4>
                            <p class="text-sm text-amber-800 dark:text-amber-200">
                                You have a pending increase from {{ $pending['old_seats'] }} to {{ $pending['new_seats'] }} seats.
                                Charge: {{ strtoupper($pending['currency']) }} {{ number_format($pending['prorated_amount'], 2) }}
                            </p>
                            <p class="text-xs text-amber-700 dark:text-amber-300 mt-2">
                                Complete your payment to activate the additional seats.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Manage Seats Section -->
            <div class="border-t border-zinc-200 dark:border-zinc-700 pt-6">
                <h4 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Manage Seats</h4>

                <div class="flex flex-col md:flex-row items-start md:items-center gap-4">
                    <!-- Seat Counter -->
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Total Seats:</span>
                        <button
                            wire:click="decrementSeats"
                            type="button"
                            @disabled($totalSeats <= $usedSeats)
                            class="w-10 h-10 flex items-center justify-center rounded-lg bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-600 hover:bg-zinc-100 dark:hover:bg-zinc-800 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                            <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                            </svg>
                        </button>
                        <span class="text-2xl font-bold text-zinc-900 dark:text-white min-w-[3ch] text-center">{{ $totalSeats }}</span>
                        <button
                            wire:click="incrementSeats"
                            type="button"
                            class="w-10 h-10 flex items-center justify-center rounded-lg bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-600 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                            <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Pricing Info -->
                    <div class="flex-1">
                        <div class="text-sm text-zinc-600 dark:text-zinc-400">
                            Price per seat:
                            <span class="font-semibold text-zinc-900 dark:text-white">
                                @if($subscription->cycle === 'month')
                                    €{{ $subscription->plan->monthly_price }}/month
                                @else
                                    €{{ $subscription->plan->yearly_price }}/year
                                @endif
                            </span>
                        </div>
                        @if($totalSeats != $currentSeats)
                            <div class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">
                                @php
                                    $difference = $totalSeats - $currentSeats;
                                    $pricePerSeat = $subscription->cycle === 'month' ? $subscription->plan->monthly_price : $subscription->plan->yearly_price;
                                    $proratedCharge = abs($difference) * $pricePerSeat * 0.5; // Approximate prorated amount
                                @endphp
                                Prorated {{ $difference > 0 ? 'charge' : 'credit' }}:
                                <span class="font-semibold text-zinc-900 dark:text-white">
                                    ~€{{ number_format($proratedCharge, 2) }}
                                </span>
                                <span class="text-xs text-zinc-500">(approximate)</span>
                            </div>
                        @endif
                    </div>

                    <!-- Update Button -->
                    <button
                        wire:click="updateSeats"
                        wire:loading.attr="disabled"
                        wire:target="updateSeats"
                        @disabled($totalSeats == $currentSeats)
                        class="inline-flex items-center px-6 py-3 bg-zinc-900 hover:bg-black dark:bg-white dark:hover:bg-zinc-100 text-white dark:text-zinc-900 disabled:opacity-50 disabled:cursor-not-allowed font-medium rounded-lg transition-colors duration-150">
                        <svg wire:loading.remove wire:target="updateSeats" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <svg wire:loading wire:target="updateSeats" class="animate-spin w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="updateSeats">Update Seats</span>
                        <span wire:loading wire:target="updateSeats">Processing...</span>
                    </button>
                </div>

                <div class="mt-4 p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700">
                    <p class="text-xs text-zinc-700 dark:text-zinc-300">
                        <strong>Note:</strong> You'll be charged or credited a prorated amount based on your changes. {{ $usedSeats > 0 ? "You cannot reduce seats below {$usedSeats} (currently in use)." : '' }}
                    </p>
                </div>
            </div>

            <!-- Team Management Link -->
            <div class="mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-700">
                <a href="/team" class="inline-flex items-center text-sm font-medium text-zinc-900 dark:text-white hover:text-zinc-700 dark:hover:text-zinc-300">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    Manage Team Members
                </a>
            </div>
        </div>

        <!-- Confirmation Modal for Seat Increases -->
        @if($showConfirmationModal)
            <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <!-- Background overlay -->
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 transition-opacity bg-zinc-900 bg-opacity-75" aria-hidden="true" wire:click="cancelConfirmation"></div>

                    <!-- Modal panel -->
                    <div class="inline-block w-full max-w-lg p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-zinc-800 shadow-2xl rounded-2xl border border-zinc-200 dark:border-zinc-700">
                        <!-- Header -->
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center">
                                <div class="flex items-center justify-center w-12 h-12 mr-3 bg-zinc-100 dark:bg-zinc-700 rounded-xl">
                                    <svg class="w-6 h-6 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-zinc-900 dark:text-white" id="modal-title">
                                        Confirm Seat Increase
                                    </h3>
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400">You will be charged immediately</p>
                                </div>
                            </div>
                            <button type="button" wire:click="cancelConfirmation" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Content -->
                        <div class="space-y-4">
                            <!-- Seat Change Summary -->
                            <div class="bg-zinc-50 dark:bg-zinc-900 rounded-xl p-4 border border-zinc-200 dark:border-zinc-700">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Current Seats</span>
                                    <span class="text-lg font-bold text-zinc-900 dark:text-white">{{ $currentSeats }}</span>
                                </div>
                                <div class="flex items-center justify-center my-2">
                                    <svg class="w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                    </svg>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400">New Seats</span>
                                    <span class="text-lg font-bold text-zinc-900 dark:text-white">{{ $totalSeats }}</span>
                                </div>
                                <div class="mt-3 pt-3 border-t border-zinc-200 dark:border-zinc-700">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Additional Seats</span>
                                        <span class="text-lg font-bold text-green-600 dark:text-green-400">+{{ $totalSeats - $currentSeats }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Prorated Charge -->
                            <div class="bg-gradient-to-br from-zinc-50 to-zinc-100 dark:from-zinc-800 dark:to-zinc-900 rounded-xl p-4 border-2 border-zinc-200 dark:border-zinc-700">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-semibold text-zinc-900 dark:text-white">Prorated Charge</span>
                                    <span class="text-xs text-zinc-700 dark:text-zinc-300">{{ $daysRemaining }} days remaining in cycle</span>
                                </div>
                                <div class="flex items-baseline gap-1">
                                    <span class="text-3xl font-bold text-zinc-900 dark:text-white">
                                        {{ $proratedCurrency }} {{ number_format($proratedAmount, 2) }}
                                    </span>
                                </div>
                                <p class="text-xs text-zinc-700 dark:text-zinc-300 mt-2">
                                    This charge will be processed immediately and is non-refundable.
                                </p>
                            </div>

                            <!-- Important Notice -->
                            <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-4 border border-amber-200 dark:border-amber-700">
                                <div class="flex">
                                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                    <div>
                                        <h4 class="text-sm font-semibold text-amber-900 dark:text-amber-100">Important</h4>
                                        <p class="text-xs text-amber-700 dark:text-amber-300 mt-1">
                                            By confirming, you authorize us to charge your payment method immediately for the prorated amount shown above. This charge covers the additional seats for the remainder of your current billing period.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-3 mt-6">
                            <button
                                type="button"
                                wire:click="cancelConfirmation"
                                class="flex-1 px-4 py-3 text-sm font-medium text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                                Cancel
                            </button>
                            <button
                                type="button"
                                wire:click="confirmSeatUpdate"
                                wire:loading.attr="disabled"
                                wire:target="confirmSeatUpdate"
                                class="flex-1 px-4 py-3 text-sm font-medium bg-zinc-900 hover:bg-black dark:bg-white dark:hover:bg-zinc-100 text-white dark:text-zinc-900 disabled:opacity-50 disabled:cursor-not-allowed rounded-lg transition-colors flex items-center justify-center">
                                <svg wire:loading.remove wire:target="confirmSeatUpdate" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <svg wire:loading wire:target="confirmSeatUpdate" class="animate-spin w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span wire:loading.remove wire:target="confirmSeatUpdate">Confirm & Pay {{ $proratedCurrency }} {{ number_format($proratedAmount, 2) }}</span>
                                <span wire:loading wire:target="confirmSeatUpdate">Processing Payment...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
