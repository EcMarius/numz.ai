@component('mail::layout')
    {{-- Header --}}
    @slot('header')
        @component('mail::header', ['url' => config('app.url')])
            @php
                $emailLogo = setting('site.logo', '/images/logos/logo-black.svg');

                // Handle storage URLs properly
                if (str_starts_with($emailLogo, 'storage/')) {
                    // Convert storage path to full URL
                    $emailLogo = asset($emailLogo);
                } elseif (!str_starts_with($emailLogo, 'http') && !str_starts_with($emailLogo, '/')) {
                    // Relative path without leading slash
                    $emailLogo = asset($emailLogo);
                } elseif (!str_starts_with($emailLogo, 'http')) {
                    // Absolute path starting with /
                    $emailLogo = url($emailLogo);
                }

                // Log the logo URL for debugging
                \Log::info('Email logo URL', [
                    'logo' => $emailLogo,
                    'setting_value' => setting('site.logo'),
                ]);
            @endphp
            <img src="{{ $emailLogo }}" alt="{{ config('app.name') }}" style="height: 40px; max-height: 40px;">
        @endcomponent
    @endslot

    {{-- Body --}}
    {{ $slot }}

    {{-- Subcopy --}}
    @isset($subcopy)
        @slot('subcopy')
            @component('mail::subcopy')
                {{ $subcopy }}
            @endcomponent
        @endslot
    @endisset

    {{-- Footer --}}
    @slot('footer')
        @component('mail::footer')
            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        @endcomponent
    @endslot
@endcomponent
