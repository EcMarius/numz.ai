<?php
    use function Laravel\Folio\{middleware, name};
    name('pricing');
?>

<x-layouts.marketing
    :seo="[
        'title' => 'Pricing - EvenLeads',
        'description' => 'Choose the perfect plan for your lead generation needs. Start with a free trial, no credit card required.',
    ]"
>

    <x-container class="py-10 sm:py-20">
        <x-marketing.sections.pricing />
    </x-container>

</x-layouts.marketing>
