<div class="space-y-4">
    {{-- Email Header Information --}}
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 space-y-2">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <span class="font-semibold text-gray-700 dark:text-gray-300">From:</span>
                <span class="text-gray-600 dark:text-gray-400">
                    {{ $template->from_name ?? config('mail.from.name') }}
                    &lt;{{ $template->from_email ?? config('mail.from.address') }}&gt;
                </span>
            </div>
            @if($template->reply_to)
            <div>
                <span class="font-semibold text-gray-700 dark:text-gray-300">Reply-To:</span>
                <span class="text-gray-600 dark:text-gray-400">{{ $template->reply_to }}</span>
            </div>
            @endif
        </div>

        <div>
            <span class="font-semibold text-gray-700 dark:text-gray-300">Subject:</span>
            <span class="text-gray-600 dark:text-gray-400">{{ $template->subject }}</span>
        </div>

        @if($template->available_variables && count($template->available_variables) > 0)
        <div>
            <span class="font-semibold text-gray-700 dark:text-gray-300">Available Variables:</span>
            <div class="flex flex-wrap gap-2 mt-2">
                @foreach($template->available_variables as $variable)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                        {{ '{{' }} {{ $variable }} {{ '}}' }}
                    </span>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- Email Body Preview --}}
    <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">HTML Preview</h3>
        <div class="prose dark:prose-invert max-w-none">
            {!! $template->html_body !!}
        </div>
    </div>

    {{-- Plain Text Preview (if available) --}}
    @if($template->text_body)
    <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Plain Text Version</h3>
        <pre class="whitespace-pre-wrap text-sm text-gray-700 dark:text-gray-300 font-mono">{{ $template->text_body }}</pre>
    </div>
    @endif

    {{-- Template Information --}}
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <span class="font-semibold text-gray-700 dark:text-gray-300">Category:</span>
                <span class="text-gray-600 dark:text-gray-400">{{ ucfirst($template->category) }}</span>
            </div>
            <div>
                <span class="font-semibold text-gray-700 dark:text-gray-300">Status:</span>
                <span class="text-gray-600 dark:text-gray-400">
                    @if($template->is_active)
                        <span class="text-green-600 dark:text-green-400">Active</span>
                    @else
                        <span class="text-red-600 dark:text-red-400">Inactive</span>
                    @endif
                </span>
            </div>
            <div>
                <span class="font-semibold text-gray-700 dark:text-gray-300">Type:</span>
                <span class="text-gray-600 dark:text-gray-400">
                    @if($template->is_system)
                        System Template
                    @else
                        Custom Template
                    @endif
                </span>
            </div>
            <div>
                <span class="font-semibold text-gray-700 dark:text-gray-300">Created:</span>
                <span class="text-gray-600 dark:text-gray-400">{{ $template->created_at->format('M d, Y') }}</span>
            </div>
        </div>
    </div>
</div>
