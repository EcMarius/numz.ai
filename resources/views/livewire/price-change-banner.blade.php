@if($subscription)
<!-- PRICE CHANGE WARNING BANNER - LEGALLY REQUIRED TO BE VISIBLE -->
<div class="mb-6 p-5 bg-gradient-to-r from-orange-50 to-red-50 dark:from-orange-900/20 dark:to-red-900/20 rounded-xl border-2 border-orange-300 dark:border-orange-700 shadow-lg">
    <div class="flex items-start gap-4">
        <svg class="w-8 h-8 text-orange-600 dark:text-orange-400 animate-pulse flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
        </svg>

        <div class="flex-1 space-y-4">
            <div>
                <h3 class="text-lg font-bold text-orange-900 dark:text-orange-100">
                    ACTION REQUIRED: Price Change
                </h3>
                <p class="text-sm text-orange-800 dark:text-orange-200 mt-1">
                    Your <strong>{{ $plan->name }}</strong> plan price will change on <strong>{{ $renewalDate }}</strong> (in {{ $daysUntilRenewal }} days)
                </p>
            </div>

            <div class="grid grid-cols-3 gap-3 p-4 bg-white/60 dark:bg-black/20 rounded-lg">
                <div>
                    <p class="text-xs text-zinc-600 dark:text-zinc-400">Current</p>
                    <p class="text-lg font-bold text-zinc-900 dark:text-white">{{ $currentPrice }} {{ $currency }}</p>
                </div>
                <div>
                    <p class="text-xs text-zinc-600 dark:text-zinc-400">New Price</p>
                    <p class="text-lg font-bold text-orange-600">{{ $newPrice }} {{ $currency }}</p>
                </div>
            </div>

            <div class="flex gap-3">
                <button wire:click="openModal" class="px-6 py-2.5 bg-orange-600 hover:bg-orange-700 text-white font-semibold rounded-lg">
                    Review & Accept
                </button>
                <a href="/settings/subscription" class="px-6 py-2.5 bg-white dark:bg-zinc-800 border-2 border-orange-300 text-orange-900 dark:text-orange-100 font-semibold rounded-lg">
                    Cancel Subscription
                </a>
                <button wire:click="dismiss" class="ml-auto text-sm text-orange-700 dark:text-orange-300 font-medium">
                    Remind Later
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ACCEPTANCE MODAL -->
@if($showModal)
<div class="fixed inset-0 z-50 overflow-y-auto" x-data>
    <div class="fixed inset-0 bg-black bg-opacity-50"></div>
    <div class="flex min-h-screen items-center justify-center p-4">
        <div class="relative w-full max-w-2xl bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl">
            <div class="border-b border-zinc-200 dark:border-zinc-700 px-8 py-6">
                <h2 class="text-2xl font-bold text-zinc-900 dark:text-white">Price Change Confirmation</h2>
            </div>

            <div class="px-8 py-6 space-y-6">
                <div class="bg-zinc-50 dark:bg-zinc-800 rounded-xl p-6">
                    <h3 class="font-semibold text-zinc-900 dark:text-white mb-4">{{ $plan->name }} Subscription</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span>Current:</span>
                            <span class="text-2xl font-bold">{{ $currentPrice }} {{ $currency }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>New:</span>
                            <span class="text-2xl font-bold text-orange-600">{{ $newPrice }} {{ $currency }}</span>
                        </div>
                        <div class="pt-3 border-t flex justify-between">
                            <span class="font-medium">Increase:</span>
                            <span class="text-xl font-bold text-red-600">+{{ number_format($priceIncrease, 2) }} ({{ $percentIncrease }}%)</span>
                        </div>
                    </div>
                </div>

                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <p class="text-sm">Effective: <strong>{{ $renewalDate }}</strong> ({{ $daysUntilRenewal }} days)</p>
                </div>

                <div class="border-2 border-orange-300 rounded-lg p-4 bg-orange-50 dark:bg-orange-900/20">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" wire:model="acceptanceConfirmed" class="mt-1 w-5 h-5">
                        <span class="text-sm">
                            <strong>I understand and accept this price change.</strong><br>
                            I agree to pay {{ $newPrice }} {{ $currency }} starting {{ $renewalDate }}.
                        </span>
                    </label>
                </div>
            </div>

            <div class="border-t px-8 py-6 flex gap-3">
                <button wire:click="acceptPriceChange" :disabled="!$wire.acceptanceConfirmed" class="px-8 py-3 bg-orange-600 hover:bg-orange-700 disabled:bg-zinc-400 text-white font-bold rounded-lg">
                    Accept New Price
                </button>
                <a href="/settings/subscription" class="px-8 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg">
                    Cancel Subscription
                </a>
                <button wire:click="closeModal" class="ml-auto px-6 py-3 text-zinc-700 dark:text-zinc-300">
                    Decide Later
                </button>
            </div>
        </div>
    </div>
</div>
@endif
@endif
