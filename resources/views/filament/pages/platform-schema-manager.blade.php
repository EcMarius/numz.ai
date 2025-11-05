<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Instructions -->
        <x-filament::section>
            <x-slot name="heading">
                Platform Schema Management
            </x-slot>
            <x-slot name="description">
                Import and export platform scraping schemas. Use the "Export Schema" button to download existing schemas,
                or "Import Schema" to upload new schemas from the extension's DEV mode.
            </x-slot>

            <div class="prose dark:prose-invert max-w-none">
                <h3>How to use:</h3>
                <ol>
                    <li><strong>Export:</strong> Click "Export Schema" and select a platform/page type to download the current schema.</li>
                    <li><strong>Import:</strong> Click "Import Schema" and paste the JSON from the extension's DEV mode.</li>
                    <li><strong>Clear Cache:</strong> After making changes, clear the schema cache to ensure extensions get the latest version.</li>
                </ol>

                <h3>Schema Structure:</h3>
                <p>Each schema defines how to extract elements from a platform's page:</p>
                <ul>
                    <li><strong>platform:</strong> linkedin, reddit, x, facebook, etc.</li>
                    <li><strong>page_type:</strong> post, person, group, comment, etc.</li>
                    <li><strong>elements:</strong> Array of selectors for different page elements</li>
                </ul>
            </div>
        </x-filament::section>

        <!-- Current Schemas Overview -->
        <x-filament::section>
            <x-slot name="heading">
                Current Schemas
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($platforms as $platform)
                    <div class="border rounded-lg p-4 dark:border-gray-700">
                        <h4 class="font-semibold text-lg mb-2 capitalize">{{ $platform }}</h4>
                        <ul class="space-y-1 text-sm">
                            @foreach($pageTypes as $pageType)
                                @php
                                    $exists = \App\Services\SchemaService::schemaExists($platform, $pageType);
                                @endphp
                                <li class="flex items-center justify-between">
                                    <span class="capitalize">{{ $pageType }}</span>
                                    @if($exists)
                                        <x-filament::badge color="success">
                                            Configured
                                        </x-filament::badge>
                                    @else
                                        <x-filament::badge color="gray">
                                            Missing
                                        </x-filament::badge>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        <!-- Exported Schema Output -->
        @if($currentSchema)
            <x-filament::section>
                <x-slot name="heading">
                    Exported Schema
                </x-slot>
                <x-slot name="description">
                    Copy this JSON to share or backup the schema configuration.
                </x-slot>

                <div class="bg-gray-900 text-gray-100 p-4 rounded-lg overflow-x-auto">
                    <pre class="text-xs"><code>{{ json_encode($currentSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                </div>

                <div class="mt-4">
                    <x-filament::button
                        onclick="navigator.clipboard.writeText(this.previousElementSibling.querySelector('code').textContent)"
                        size="sm"
                        icon="heroicon-o-clipboard"
                    >
                        Copy to Clipboard
                    </x-filament::button>
                </div>
            </x-filament::section>
        @endif

        <!-- Missing Schemas Alert -->
        @php
            $missingSchemas = \App\Services\SchemaService::getMissingSchemas($platforms);
        @endphp
        @if(!empty($missingSchemas))
            <x-filament::section>
                <x-slot name="heading">
                    Missing Schemas
                </x-slot>

                <div class="bg-warning-50 dark:bg-warning-900/20 border border-warning-200 dark:border-warning-800 rounded-lg p-4">
                    <p class="text-sm text-warning-800 dark:text-warning-200 mb-2">
                        The following platform/page type combinations don't have schemas configured:
                    </p>
                    <ul class="list-disc list-inside text-sm text-warning-700 dark:text-warning-300">
                        @foreach($missingSchemas as $platform => $pageTypes)
                            @foreach($pageTypes as $pageType)
                                <li class="capitalize">{{ $platform }} - {{ $pageType }}</li>
                            @endforeach
                        @endforeach
                    </ul>
                    <p class="text-sm text-warning-800 dark:text-warning-200 mt-2">
                        Use the extension's DEV mode to create schemas for these combinations.
                    </p>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
