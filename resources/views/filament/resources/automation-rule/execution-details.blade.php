<div class="space-y-4">
    {{-- Execution Summary --}}
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <span class="font-semibold text-gray-700 dark:text-gray-300">Execution ID:</span>
                <span class="text-gray-600 dark:text-gray-400">#{{ $execution->id }}</span>
            </div>
            <div>
                <span class="font-semibold text-gray-700 dark:text-gray-300">Status:</span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                    {{ $execution->success ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                    {{ $execution->status_text }}
                </span>
            </div>
            <div>
                <span class="font-semibold text-gray-700 dark:text-gray-300">Trigger Event:</span>
                <span class="text-gray-600 dark:text-gray-400">{{ $execution->trigger_event }}</span>
            </div>
            <div>
                <span class="font-semibold text-gray-700 dark:text-gray-300">Execution Time:</span>
                <span class="text-gray-600 dark:text-gray-400">{{ number_format($execution->execution_time, 3) }}s</span>
            </div>
            <div class="col-span-2">
                <span class="font-semibold text-gray-700 dark:text-gray-300">Executed At:</span>
                <span class="text-gray-600 dark:text-gray-400">{{ $execution->created_at->format('M d, Y H:i:s') }}</span>
            </div>
        </div>
    </div>

    {{-- Trigger Data --}}
    @if($execution->trigger_data)
    <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <h3 class="text-lg font-semibold mb-2 text-gray-900 dark:text-gray-100">Trigger Data</h3>
        <pre class="bg-gray-50 dark:bg-gray-800 rounded p-3 text-sm overflow-auto">{{ json_encode($execution->trigger_data, JSON_PRETTY_PRINT) }}</pre>
    </div>
    @endif

    {{-- Conditions --}}
    <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <h3 class="text-lg font-semibold mb-2 text-gray-900 dark:text-gray-100">Conditions</h3>
        <div class="flex items-center">
            @if($execution->conditions_met)
                <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span class="text-green-600 dark:text-green-400">All conditions were met</span>
            @else
                <svg class="w-5 h-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <span class="text-red-600 dark:text-red-400">Conditions were not met</span>
            @endif
        </div>
    </div>

    {{-- Actions Taken --}}
    @if($execution->actions_taken && count($execution->actions_taken) > 0)
    <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <h3 class="text-lg font-semibold mb-3 text-gray-900 dark:text-gray-100">Actions Taken</h3>
        <div class="space-y-3">
            @foreach($execution->actions_taken as $index => $action)
                <div class="bg-gray-50 dark:bg-gray-800 rounded p-3">
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-medium text-gray-700 dark:text-gray-300">
                            Action {{ $index + 1 }}: {{ $action['action'] ?? 'Unknown' }}
                        </span>
                        @if(isset($action['success']))
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $action['success'] ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                                {{ $action['success'] ? 'Success' : 'Failed' }}
                            </span>
                        @endif
                    </div>
                    @if(isset($action['message']))
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $action['message'] }}</p>
                    @endif
                    @if(isset($action['error']))
                        <p class="text-sm text-red-600 dark:text-red-400 mt-1">Error: {{ $action['error'] }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Error Message --}}
    @if($execution->error_message)
    <div class="bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800 p-4">
        <h3 class="text-lg font-semibold mb-2 text-red-900 dark:text-red-100">Error</h3>
        <pre class="text-sm text-red-800 dark:text-red-200 whitespace-pre-wrap">{{ $execution->error_message }}</pre>
    </div>
    @endif
</div>
