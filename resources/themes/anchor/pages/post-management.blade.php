<?php
    use function Laravel\Folio\{middleware, name};
    middleware('auth');
    name('post-management');
?>

<x-layouts.app>
    <div class="px-4 py-8 mx-auto max-w-7xl sm:px-6 lg:px-8">
        @livewire('evenleads.post-management')
    </div>
</x-layouts.app>
