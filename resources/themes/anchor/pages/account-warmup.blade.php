<?php
    use function Laravel\Folio\{middleware, name};
    middleware('auth');
    name('account-warmup');
?>

<x-layouts.app>
    <x-app.container>
        @livewire('account-warmup.warmup-manager')
    </x-app.container>
</x-layouts.app>
