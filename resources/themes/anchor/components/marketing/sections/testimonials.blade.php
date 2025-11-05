@php
    // Check if testimonials section should be shown
    $showTestimonials = setting('site.show_testimonials', '1') == '1';

    // Load active testimonials from database
    $testimonials = $showTestimonials
        ? \App\Models\Testimonial::active()->ordered()->get()
        : collect();
@endphp

@if($showTestimonials && $testimonials->isNotEmpty())
<section class="w-full">
    <x-marketing.elements.heading level="h2" title="Trusted by Growing Businesses" description="See how EvenLeads is helping companies discover high-quality leads and grow their customer base." />
    <div class="grid grid-cols-1 gap-6 py-12 mx-auto max-w-5xl lg:grid-cols-2">
        @foreach($testimonials as $testimonial)
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md border border-zinc-200 dark:border-zinc-700 p-6 hover:shadow-lg transition-shadow">
            <div class="flex items-start gap-4">
                @if($testimonial->avatar)
                    {{-- Display uploaded avatar image --}}
                    <img
                        src="{{ $testimonial->avatar_url }}"
                        alt="{{ $testimonial->name }}"
                        class="w-12 h-12 rounded-full object-cover flex-shrink-0"
                    />
                @else
                    {{-- Display gradient avatar with initials --}}
                    <div class="flex items-center justify-center w-12 h-12 bg-gradient-to-br from-{{ $testimonial->gradient_from }} to-{{ $testimonial->gradient_to }} rounded-full flex-shrink-0">
                        <span class="text-lg font-bold text-white">{{ $testimonial->initials }}</span>
                    </div>
                @endif

                <div class="flex-1 min-w-0">
                    <div class="flex items-baseline gap-2 mb-2">
                        <h3 class="font-semibold text-zinc-900 dark:text-white">{{ $testimonial->name }}</h3>
                        <span class="text-xs text-zinc-400">•</span>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">
                            {{ $testimonial->position }}@if($testimonial->company), {{ $testimonial->company }}@endif
                        </p>
                    </div>
                    <blockquote>
                        <p class="text-sm text-zinc-700 dark:text-zinc-300 leading-relaxed">
                            "{{ $testimonial->content }}"
                        </p>
                    </blockquote>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</section>
@endif
