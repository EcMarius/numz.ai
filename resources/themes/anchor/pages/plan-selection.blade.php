<?php
    use function Laravel\Folio\{middleware, name};
    name('plan-selection');
    middleware(['auth', 'verified']);
?>

<x-layouts.app>
    <section class="py-5 sm:py-8 lg:py-10 mx-auto w-full max-w-7xl">
        @if(session('error'))
            <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                <strong>Error:</strong> {{ session('error') }}
            </div>
        @endif

        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-zinc-900 dark:text-white mb-4">Choose Your Plan</h1>
            <p class="text-lg text-zinc-600 dark:text-zinc-400">Select a plan to get started with EvenLeads</p>
        </div>

        <x-marketing.sections.pricing />
    </section>
</x-layouts.app>
