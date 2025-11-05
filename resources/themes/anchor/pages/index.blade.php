<?php
    use function Laravel\Folio\{name};
    name('home');
?>

<x-layouts.marketing
    :seo="[
        'title'         => setting('site.title', 'Laravel Wave'),
        'description'   => setting('site.description', 'Software as a Service Starter Kit'),
        'image'         => url('/og_image.png'),
        'type'          => 'website'
    ]"
>

        <x-marketing.sections.hero />

        <x-container class="py-12 border-t sm:py-24 border-zinc-200">
            <x-marketing.sections.features />
        </x-container>

        <x-container class="py-12 border-t sm:py-24 border-zinc-200">
            <x-marketing.sections.see-what-we-can-do />
        </x-container>

        @php
            $showUseCases = setting('site.show_use_cases', '1') == '1';
            $hasUseCases = $showUseCases && \App\Models\UseCase::active()->count() > 0;
        @endphp
        @if($hasUseCases)
        <x-container class="py-12 border-t sm:py-24 border-zinc-200">
            <x-marketing.sections.use-cases />
        </x-container>
        @endif

        @php
            $showStats = setting('site.show_stats', '1') == '1';
            $hasStats = $showStats && \App\Models\Stat::active()->count() > 0;
        @endphp
        @if($hasStats)
        <x-container class="py-12 border-t sm:py-24 border-zinc-200">
            <x-marketing.sections.stats />
        </x-container>
        @endif

        @php
            $showTestimonials = setting('site.show_testimonials', '1') == '1';
            $hasTestimonials = $showTestimonials && \App\Models\Testimonial::active()->count() > 0;
        @endphp
        @if($hasTestimonials)
        <x-container class="py-12 border-t sm:py-24 border-zinc-200">
            <x-marketing.sections.testimonials />
        </x-container>
        @endif

        <x-container class="py-12 border-t sm:py-24 border-zinc-200">
            <x-marketing.sections.pricing />
        </x-container>

        {{-- ROI Calculator hidden for now --}}
        {{-- <x-marketing.sections.roi /> --}}

        <x-container class="py-12 border-t sm:py-24 border-zinc-200">
            <x-marketing.sections.comparison />
        </x-container>

        <x-container class="py-12 border-t sm:py-24 border-zinc-200">
            <x-marketing.sections.faq />
        </x-container>

</x-layouts.marketing>
