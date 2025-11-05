<x-filament-widgets::widget>
    @php
        $data = $this->getUserData();
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Subscription Information --}}
        @if($data['subscription'])
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                    Subscription Details
                </h3>

                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">
                            <span class="px-2 py-1 rounded-full text-xs font-medium
                                {{ $data['subscription']['status'] === 'Active' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : '' }}
                                {{ $data['subscription']['status'] === 'Cancelled' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : '' }}
                                {{ $data['subscription']['status'] === 'Trialing' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : '' }}">
                                {{ $data['subscription']['status'] }}
                            </span>
                        </dd>
                    </div>

                    <div class="flex justify-between">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Payment Provider</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">{{ $data['subscription']['vendor'] }}</dd>
                    </div>

                    @if($data['subscription']['vendor_customer_id'] !== 'N/A')
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Customer ID</dt>
                            <dd class="text-sm text-gray-900 dark:text-white font-mono text-xs">{{ $data['subscription']['vendor_customer_id'] }}</dd>
                        </div>
                    @endif

                    @if($data['subscription']['vendor_subscription_id'] !== 'N/A')
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Subscription ID</dt>
                            <dd class="text-sm text-gray-900 dark:text-white font-mono text-xs">{{ $data['subscription']['vendor_subscription_id'] }}</dd>
                        </div>
                    @endif

                    <div class="flex justify-between">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Seats</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">{{ $data['subscription']['seats'] }}</dd>
                    </div>

                    @if($data['subscription']['cancel_url'])
                        <div class="pt-2">
                            <a href="{{ $data['subscription']['cancel_url'] }}" target="_blank" class="text-sm text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                Cancel Subscription →
                            </a>
                        </div>
                    @endif

                    @if($data['subscription']['update_url'])
                        <div>
                            <a href="{{ $data['subscription']['update_url'] }}" target="_blank" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                Update Payment Method →
                            </a>
                        </div>
                    @endif

                    @if($data['subscription']['cancelled_at'])
                        <div class="pt-4 mt-4 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex justify-between mb-2">
                                <dt class="text-sm font-medium text-red-500 dark:text-red-400">Cancelled At</dt>
                                <dd class="text-sm text-gray-900 dark:text-white">{{ $data['subscription']['cancelled_at'] }}</dd>
                            </div>

                            @if($data['subscription']['days_since_cancelled'])
                                <div class="flex justify-between mb-2">
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Days Since Cancellation</dt>
                                    <dd class="text-sm text-gray-900 dark:text-white">{{ $data['subscription']['days_since_cancelled'] }} days ago</dd>
                                </div>
                            @endif

                            @if($data['subscription']['cancellation_reason'])
                                <div class="mt-2">
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Cancellation Reason</dt>
                                    <dd class="text-sm text-gray-900 dark:text-white bg-red-50 dark:bg-red-900/20 px-3 py-2 rounded">
                                        {{ ucfirst(str_replace('_', ' ', $data['subscription']['cancellation_reason'])) }}
                                    </dd>
                                </div>
                            @endif

                            @if($data['subscription']['cancellation_details'])
                                <div class="mt-2">
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Additional Details</dt>
                                    <dd class="text-sm text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-700 px-3 py-2 rounded">
                                        {{ $data['subscription']['cancellation_details'] }}
                                    </dd>
                                </div>
                            @endif
                        </div>
                    @endif
                </dl>
            </div>
        @endif

        {{-- Plan Information --}}
        @if($data['plan'])
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Plan Details
                </h3>

                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Plan Name</dt>
                        <dd class="text-sm font-semibold text-gray-900 dark:text-white">{{ $data['plan']['name'] }}</dd>
                    </div>

                    <div class="flex justify-between">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Price</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">{{ $data['plan']['price'] }} / {{ $data['plan']['cycle'] }}</dd>
                    </div>

                    @if($data['plan']['description'] !== 'N/A')
                        <div class="pt-2">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Description</dt>
                            <dd class="text-sm text-gray-700 dark:text-gray-300">{{ $data['plan']['description'] }}</dd>
                        </div>
                    @endif

                    @if(!empty($data['plan']['features']))
                        <div class="pt-2">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Features</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">
                                @if(is_array($data['plan']['features']))
                                    <ul class="list-disc list-inside space-y-1">
                                        @foreach($data['plan']['features'] as $feature)
                                            <li>{{ $feature }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    {{ $data['plan']['features'] }}
                                @endif
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>
        @endif

        {{-- Trial Information --}}
        @if($data['trial'])
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Trial Information
                </h3>

                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Trial Status</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $data['trial']['is_active'] ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400' }}">
                                {{ $data['trial']['status'] }}
                            </span>
                        </dd>
                    </div>

                    <div class="flex justify-between">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Ends At</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">{{ $data['trial']['ends_at'] }}</dd>
                    </div>

                    @if($data['trial']['is_active'])
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Days Remaining</dt>
                            <dd class="text-sm font-semibold text-yellow-600 dark:text-yellow-400">{{ $data['trial']['days_remaining'] }} days</dd>
                        </div>
                    @endif
                </dl>
            </div>
        @endif

        {{-- Billing Information --}}
        @if($data['billing'])
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Billing Cycle
                </h3>

                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Payment</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">{{ $data['billing']['last_payment'] }}</dd>
                    </div>

                    <div class="flex justify-between">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Next Payment</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">{{ $data['billing']['next_payment'] }}</dd>
                    </div>

                    @if($data['billing']['days_until_next'])
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Days Until Next Billing</dt>
                            <dd class="text-sm font-semibold text-blue-600 dark:text-blue-400">{{ $data['billing']['days_until_next'] }} days</dd>
                        </div>
                    @endif
                </dl>
            </div>
        @endif

        {{-- EvenLeads Usage --}}
        @if($data['usage'])
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 md:col-span-2">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    EvenLeads Usage Statistics
                </h3>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                        <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $data['usage']['campaigns_total'] }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Total Campaigns</div>
                        <div class="text-xs text-purple-600 dark:text-purple-400 mt-1">{{ $data['usage']['campaigns_active'] }} active</div>
                    </div>

                    <div class="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $data['usage']['leads_total'] }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Total Leads</div>
                        <div class="text-xs text-green-600 dark:text-green-400 mt-1">{{ $data['usage']['leads_this_month'] }} this month</div>
                    </div>

                    <div class="text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $data['usage']['leads_strong'] }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Strong Matches</div>
                        <div class="text-xs text-blue-600 dark:text-blue-400 mt-1">Score ≥ 8</div>
                    </div>

                    <div class="text-center p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                        <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $data['usage']['leads_new'] }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">New Leads</div>
                        <div class="text-xs text-yellow-600 dark:text-yellow-400 mt-1">Awaiting action</div>
                    </div>

                    <div class="text-center p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                        <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ $data['usage']['leads_contacted'] }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Contacted</div>
                    </div>

                    <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="text-2xl font-bold text-gray-600 dark:text-gray-400">{{ $data['usage']['leads_closed'] }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Closed</div>
                    </div>

                    <div class="text-center p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg">
                        <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ $data['usage']['manual_syncs_this_month'] }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Manual Syncs</div>
                        <div class="text-xs text-indigo-600 dark:text-indigo-400 mt-1">This month</div>
                    </div>

                    <div class="text-center p-4 bg-pink-50 dark:bg-pink-900/20 rounded-lg">
                        <div class="text-2xl font-bold text-pink-600 dark:text-pink-400">{{ $data['usage']['ai_generations_this_month'] }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">AI Replies</div>
                        <div class="text-xs text-pink-600 dark:text-pink-400 mt-1">This month</div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Activity Timeline --}}
        @if($data['activity'])
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 md:col-span-2">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    Recent Activity
                </h3>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Login</div>
                        <div class="text-sm font-semibold text-gray-900 dark:text-white mt-1">{{ $data['activity']['last_login'] }}</div>
                    </div>

                    <div>
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Campaign</div>
                        <div class="text-sm font-semibold text-gray-900 dark:text-white mt-1">{{ $data['activity']['last_campaign'] }}</div>
                    </div>

                    <div>
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Lead</div>
                        <div class="text-sm font-semibold text-gray-900 dark:text-white mt-1">{{ $data['activity']['last_lead'] }}</div>
                    </div>

                    <div>
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Sync</div>
                        <div class="text-sm font-semibold text-gray-900 dark:text-white mt-1">{{ $data['activity']['last_sync'] }}</div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-widgets::widget>
