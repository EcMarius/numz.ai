<div class="flex flex-col h-full max-h-[600px]">
    <!-- Chat Header -->
    <div class="bg-white border-b border-zinc-200 px-6 py-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-zinc-900">{{ $lead->title }}</h3>
                <p class="text-sm text-zinc-600">
                    {{ $lead->platform }} - {{ $lead->author }}
                    @if($lead->subreddit)
                        • r/{{ $lead->subreddit }}
                    @endif
                </p>
            </div>
            @if($hasAIAccess && $aiQuota)
                <div class="text-right">
                    <p class="text-xs text-zinc-500">AI Quota</p>
                    <p class="text-sm font-medium text-zinc-900">{{ $aiQuota['remaining'] }}/{{ $aiQuota['limit'] }} left</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Messages Area -->
    <div class="flex-1 overflow-y-auto p-6 space-y-4 bg-zinc-50">
        @forelse($messages as $message)
            <div class="flex {{ $message['direction'] === 'outgoing' ? 'justify-end' : 'justify-start' }}">
                <div class="max-w-[70%]">
                    <div class="px-4 py-3 rounded-lg {{ $message['direction'] === 'outgoing' ? 'bg-zinc-900 text-white' : 'bg-white border border-zinc-200 text-zinc-900' }}">
                        <p class="text-sm whitespace-pre-wrap">{{ $message['text'] }}</p>
                    </div>
                    <div class="flex items-center gap-2 mt-1 px-1">
                        <p class="text-xs text-zinc-500">
                            {{ $message['sent_at'] ?? 'Draft' }}
                        </p>
                        @if($message['is_ai'])
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                AI Generated
                            </span>
                        @endif
                        @if($message['status'])
                            <span class="text-xs text-zinc-500">• {{ ucfirst($message['status']) }}</span>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <p class="mt-2 text-sm text-zinc-600">No messages yet. Start the conversation!</p>
            </div>
        @endforelse
    </div>

    <!-- Message Input Area -->
    <div class="bg-white border-t border-zinc-200 p-4">
        <form wire:submit.prevent="sendMessage" class="space-y-3">
            <textarea wire:model.defer="newMessage" rows="3" placeholder="Type your message..."
                      class="block w-full rounded-lg border-zinc-200 shadow-sm focus:border-zinc-900 focus:ring-zinc-900 text-sm resize-none"></textarea>

            <div class="flex items-center justify-between gap-3">
                <div class="flex gap-2">
                    @if($hasAIAccess)
                        <button type="button" wire:click="generateAIMessage" wire:loading.attr="disabled"
                                :disabled="generatingAI"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg wire:loading.remove wire:target="generateAIMessage" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            <svg wire:loading wire:target="generateAIMessage" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="generateAIMessage">Generate with AI</span>
                            <span wire:loading wire:target="generateAIMessage">Generating...</span>
                        </button>
                    @endif
                    <button type="button" wire:click="saveDraft"
                            class="px-4 py-2 bg-zinc-100 hover:bg-zinc-200 text-zinc-700 text-sm font-medium rounded-lg transition">
                        Save Draft
                    </button>
                </div>
                <button type="submit" wire:loading.attr="disabled"
                        class="px-6 py-2 bg-zinc-900 hover:bg-zinc-800 text-white text-sm font-medium rounded-lg transition disabled:opacity-50">
                    Send Message
                </button>
            </div>
        </form>

        @if(session()->has('success'))
            <div class="mt-3 rounded-lg bg-green-50 border border-green-200 p-3">
                <p class="text-sm text-green-800">{{ session('success') }}</p>
            </div>
        @endif

        @if(session()->has('error'))
            <div class="mt-3 rounded-lg bg-red-50 border border-red-200 p-3">
                <p class="text-sm text-red-800">{{ session('error') }}</p>
            </div>
        @endif
    </div>
</div>
