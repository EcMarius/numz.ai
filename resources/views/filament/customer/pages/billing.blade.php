<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <x-filament-panels::form.actions
            :actions="$this->getCachedFormActions()"
            :full-width="$this->hasFullWidthFormActions()"
        />
    </form>

    <div class="mt-8">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
