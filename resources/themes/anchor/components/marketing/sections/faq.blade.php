@php
    // Check if FAQ section should be shown
    $showFaq = setting('site.show_faq', '1') == '1';

    // Load active FAQs from database
    $faqs = $showFaq
        ? \App\Models\Faq::active()->ordered()->get()
        : collect();
@endphp

@if($showFaq && $faqs->isNotEmpty())
<section class="w-full" x-data="{ openFaq: null }">
    <x-marketing.elements.heading
        level="h2"
        title="Frequently Asked Questions"
        description="Get answers to common questions about EvenLeads"
    />

    <div class="mx-auto max-w-3xl mt-12 space-y-4">
        @foreach($faqs as $index => $faq)
        <div class="border border-zinc-200 rounded-lg overflow-hidden bg-white hover:shadow-md transition-shadow duration-200">
            <button
                @click="openFaq = openFaq === {{ $index }} ? null : {{ $index }}"
                class="w-full px-6 py-4 text-left flex items-center justify-between focus:outline-none rounded-lg"
                :class="{ 'bg-emerald-50': openFaq === {{ $index }} }"
            >
                <span class="text-lg font-semibold text-zinc-900 pr-8">{{ $faq->question }}</span>
                <svg
                    class="w-5 h-5 text-emerald-600 flex-shrink-0 transition-transform duration-200"
                    :class="{ 'rotate-180': openFaq === {{ $index }} }"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div
                x-show="openFaq === {{ $index }}"
                x-collapse
                class="px-6 pb-4"
            >
                <div class="pt-4 text-zinc-600 leading-relaxed prose prose-sm max-w-none">
                    {!! $faq->answer !!}
                </div>
            </div>
        </div>
        @endforeach
    </div>
</section>
@endif
