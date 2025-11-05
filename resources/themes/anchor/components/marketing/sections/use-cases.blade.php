@php
    // Check if use cases section should be shown
    $showUseCases = setting('site.show_use_cases', '1') == '1';

    // Load active use cases from database
    $useCases = $showUseCases
        ? \App\Models\UseCase::active()->ordered()->get()
        : collect();
@endphp

@if($showUseCases && $useCases->isNotEmpty())
<section class="w-full">
    <div class="text-center mb-12">
        <h2 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white sm:text-4xl">Who Benefits from EvenLeads?</h2>
        <p class="mt-4 text-lg text-zinc-600 dark:text-zinc-400">Discover how professionals across industries use EvenLeads to find their next opportunity</p>
    </div>

    <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($useCases as $useCase)
        <div class="relative group bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6 hover:shadow-xl hover:border-{{ $useCase->color }}-300 dark:hover:border-{{ $useCase->color }}-600 transition-all duration-300">
            @if($useCase->icon)
            <div class="flex items-center justify-center w-12 h-12 mb-4 rounded-lg bg-{{ $useCase->color }}-100 dark:bg-{{ $useCase->color }}-900/30">
                <x-dynamic-component :component="'phosphor-' . str_replace('phosphor-', '', $useCase->icon)" class="w-6 h-6 text-{{ $useCase->color }}-600 dark:text-{{ $useCase->color }}-400" />
            </div>
            @endif

            <h3 class="text-lg font-bold text-zinc-900 dark:text-white mb-2">
                {{ $useCase->title }}
            </h3>

            @if($useCase->target_audience)
            <div class="mb-3">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $useCase->color }}-100 dark:bg-{{ $useCase->color }}-900/30 text-{{ $useCase->color }}-800 dark:text-{{ $useCase->color }}-300">
                    {{ $useCase->target_audience }}
                </span>
            </div>
            @endif

            <p class="text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed">
                {{ $useCase->description }}
            </p>

            <!-- Decorative gradient line -->
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-{{ $useCase->color }}-400 to-{{ $useCase->color }}-600 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-b-xl"></div>
        </div>
        @endforeach
    </div>
</section>
@endif
