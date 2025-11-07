<div class="p-6">
    @if($changelog)
        <div class="prose prose-sm dark:prose-invert max-w-none">
            {!! \Illuminate\Support\Str::markdown($changelog) !!}
        </div>
    @else
        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
            <x-filament::icon
                icon="heroicon-o-document-text"
                class="w-12 h-12 mx-auto mb-4 text-gray-400"
            />
            <p>No changelog available for this version.</p>
        </div>
    @endif
</div>
