@php
    // Light mode: use dark/black logo (mini_logo or logo_dark)
    $logoLight = setting('site.mini_logo') ?: setting('site.logo_dark', '/storage/logos/mini-logo-black.svg');

    // Dark mode: use white logo (site.logo_white)
    $logoDark = setting('site.logo_white', '/storage/logos/mini-logo-white.svg');
@endphp

<img src="{{ $logoLight }}" alt="{{ setting('site.title', 'EvenLeads') }} Admin" class="h-8 w-auto dark:hidden" />
<img src="{{ $logoDark }}" alt="{{ setting('site.title', 'EvenLeads') }} Admin" class="h-8 w-auto hidden dark:block" />