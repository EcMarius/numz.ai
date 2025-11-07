<div class="p-6">
    @if($error)
        <div class="bg-danger-50 dark:bg-danger-900/20 border border-danger-200 dark:border-danger-800 rounded-lg p-4">
            <div class="flex items-start">
                <x-filament::icon
                    icon="heroicon-o-exclamation-circle"
                    class="w-6 h-6 text-danger-500 mr-3 mt-0.5"
                />
                <div class="flex-1">
                    <h3 class="text-sm font-semibold text-danger-900 dark:text-danger-100 mb-2">
                        Error Message:
                    </h3>
                    <div class="bg-white dark:bg-gray-900 rounded p-3 font-mono text-xs text-danger-700 dark:text-danger-300 overflow-x-auto">
                        {{ $error }}
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
            <p class="mb-2">
                <strong>What to do:</strong>
            </p>
            <ul class="list-disc list-inside space-y-1">
                <li>Check system logs for more details</li>
                <li>Ensure all system requirements are met</li>
                <li>Verify sufficient disk space is available</li>
                <li>Contact support if the issue persists</li>
            </ul>
        </div>
    @else
        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
            <p>No error message available.</p>
        </div>
    @endif
</div>
