<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Campaign Stats -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total Prospects</div>
                    <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ $campaign->total_prospects }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Ready to Email</div>
                    <div class="text-3xl font-bold text-green-600">{{ $prospects->count() - count($skippedProspects) }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Skipped</div>
                    <div class="text-3xl font-bold text-gray-400">{{ count($skippedProspects) }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Accounts Created</div>
                    <div class="text-3xl font-bold text-blue-600">{{ $campaign->accounts_created }}</div>
                </div>
            </div>
        </div>

        <!-- Prospects List -->
        <div class="space-y-4">
            @forelse($prospects as $prospect)
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 {{ in_array($prospect->id, $skippedProspects) ? 'opacity-50' : '' }}">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <!-- Business Info -->
                            <div class="flex items-center gap-3 mb-3">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    {{ $prospect->business_name ?? 'Unknown Business' }}
                                </h3>
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                    {{ $prospect->industry }}
                                </span>
                            </div>

                            <!-- Contact Details -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4 text-sm">
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Email:</span>
                                    <span class="font-medium text-gray-900 dark:text-white ml-1">{{ $prospect->primary_email }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Contact:</span>
                                    <span class="font-medium text-gray-900 dark:text-white ml-1">{{ $prospect->display_name }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Leads Found:</span>
                                    <span class="font-medium text-green-600 ml-1">{{ $prospect->leads_found }}</span>
                                </div>
                            </div>

                            <!-- Email Preview -->
                            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 mb-3">
                                <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-2">Email Preview</div>
                                <div class="text-sm font-semibold text-gray-900 dark:text-white mb-2">
                                    Subject: {{ $campaign->email_subject_template ?? 'No subject' }}
                                </div>
                                <div class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">
                                    {{ Str::limit($campaign->email_body_template ?? 'No email body', 200) }}
                                </div>
                            </div>

                            <!-- Leads Preview -->
                            @if($prospect->leads->count() > 0)
                                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                                    <div class="text-xs font-semibold text-blue-700 dark:text-blue-400 uppercase mb-2">
                                        Top Leads ({{ $prospect->leads->count() }})
                                    </div>
                                    <div class="space-y-2">
                                        @foreach($prospect->leads->take(3) as $lead)
                                            <div class="flex items-start gap-2 text-sm">
                                                <span class="px-2 py-0.5 text-xs font-bold rounded bg-blue-600 text-white">
                                                    {{ number_format($lead->confidence_score, 1) }}
                                                </span>
                                                <span class="text-gray-900 dark:text-white">{{ $lead->title }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Actions -->
                        <div class="ml-4">
                            @if(!in_array($prospect->id, $skippedProspects))
                                <x-filament::button
                                    wire:click="skipProspect({{ $prospect->id }})"
                                    color="danger"
                                    size="sm"
                                >
                                    Skip
                                </x-filament::button>
                            @else
                                <span class="text-sm text-gray-500">Skipped</span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-12 text-gray-500">
                    No prospects ready for review.
                </div>
            @endforelse
        </div>

        <!-- Send All Button -->
        @if($prospects->count() > 0)
            <div class="flex justify-end gap-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                <x-filament::button
                    wire:click="sendAllEmails"
                    wire:confirm="Are you sure you want to send {{ $prospects->count() - count($skippedProspects) }} emails?"
                    size="lg"
                    color="primary"
                >
                    <x-heroicon-o-paper-airplane class="w-5 h-5 mr-2" />
                    Send {{ $prospects->count() - count($skippedProspects) }} Emails
                </x-filament::button>
            </div>
        @endif
    </div>
</x-filament-panels::page>
