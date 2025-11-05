@php
    // Use custom logo component that pulls from admin settings
    $logoPath = setting('site.logo_dark', '/images/logos/logo-black.svg');
@endphp

<a href="/" style="height:{{ $height ?? '32' }}px; width:auto; display:block" aria-label="{{ config('app.name') }} Logo">
    <img src="{{ $logoPath }}" style="height:100%; width:auto" alt="{{ config('app.name') }} Logo" class="dark:hidden" />
    <img src="{{ setting('site.logo_white', '/images/logos/logo-white.svg') }}" style="height:100%; width:auto" alt="{{ config('app.name') }} Logo" class="hidden dark:block" />
</a>
