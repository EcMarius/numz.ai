@php
    // Get both logos
    $logoLight = setting('site.logo_dark', '/images/logos/logo-black.svg'); // Light mode uses dark/black logo
    $logoDark = setting('site.logo_white', '/images/logos/logo-white.svg'); // Dark mode uses white logo

    // Add width to attributes if only height is specified
    $classes = $attributes->get('class', '');
    $defaultClasses = 'w-auto';
    if (!str_contains($classes, 'w-')) {
        $defaultClasses = 'w-auto';
    }
@endphp

<img src="{{ $logoLight }}" {{ $attributes->merge(['class' => 'dark:hidden ' . $defaultClasses, 'alt' => setting('site.title', 'EvenLeads')]) }} />
<img src="{{ $logoDark }}" {{ $attributes->merge(['class' => 'hidden dark:block ' . $defaultClasses, 'alt' => setting('site.title', 'EvenLeads')]) }} />
