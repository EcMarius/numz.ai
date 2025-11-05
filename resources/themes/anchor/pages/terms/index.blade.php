<?php
    use function Laravel\Folio\{name};

    name('terms');
?>

<x-layouts.marketing
    :seo="[
        'title' => 'Terms and Conditions - EvenLeads',
        'description' => 'Our terms and conditions outline the rules and regulations for using EvenLeads.',
    ]"
>
    <x-container class="py-10 sm:py-20">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-12">
                <h1 class="text-4xl sm:text-5xl md:text-6xl font-bold tracking-tighter text-zinc-900 dark:text-white mb-4">
                    Terms and Conditions
                </h1>
                <p class="text-lg md:text-xl text-zinc-500 dark:text-zinc-400 max-w-2xl mx-auto">
                    Last updated: {{ now()->format('F d, Y') }}
                </p>
            </div>

            <!-- Content -->
            <div class="bg-white dark:bg-zinc-800 rounded-xl border-2 border-zinc-200 dark:border-zinc-700 shadow-sm p-8 md:p-12">
                <div class="prose prose-zinc dark:prose-invert max-w-none">
                    @php
                        $content = setting('legal.terms_conditions', '<p>Terms and conditions content not available.</p>');
                        $content = str_replace('{{ company_name }}', setting('company_name', 'SoftGala SRL'), $content);
                        $content = str_replace('{{ company_address }}', setting('company_address', 'Romania'), $content);
                        $content = str_replace('{{ company_registration_code }}', setting('company_registration_code', 'N/A'), $content);
                        $content = str_replace('{{ company_email }}', setting('company_email', 'contact@softgala.com'), $content);
                    @endphp
                    {!! $content !!}
                </div>
            </div>
        </div>
    </x-container>
</x-layouts.marketing>
