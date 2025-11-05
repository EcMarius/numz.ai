<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>{{ $title ?? 'Auth' }}</title>
@if(config('devdojo.auth.settings.dev_mode'))
    @vite(['packages/devdojo/auth/resources/css/auth.css', 'packages/devdojo/auth/resources/css/auth.js'])
@else
    <script src="{{ asset('/auth/build/assets/scripts.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('/auth/build/assets/styles.css') }}" />
@endif

@php
    $buttonRGBColor = \Devdojo\Auth\Helper::convertHexToRGBString(config('devdojo.auth.appearance.color.button'));
    $inputBorderRGBColor = \Devdojo\Auth\Helper::convertHexToRGBString(config('devdojo.auth.appearance.color.input_border'));
@endphp
<style>
    .auth-component-button:focus{
        --tw-ring-opacity: 1; --tw-ring-color: rgb({{ $buttonRGBColor }} / var(--tw-ring-opacity));
    }
    .auth-component-input{
        color: {{ config('devdojo.auth.appearance.color.input_text') }}
    }
    .auth-component-input:focus, .auth-component-code-input:focus{
        --tw-ring-color: rgb({{ $inputBorderRGBColor }} / var(--tw-ring-opacity));
        border-color: rgb({{ $inputBorderRGBColor }} / var(--tw-border-opacity));
    }
    .auth-component-input-label-focused{
        color: {{ config('devdojo.auth.appearance.color.input_border') }}
    }
</style>

@if(file_exists(public_path('auth/app.css')))
    <link rel="stylesheet" href="/auth/app.css" />
@endif

{{-- Use site favicon settings --}}
<link rel="icon" href="{{ setting('site.favicon', '/storage/logos/mini-logo-black.svg') }}" type="image/svg+xml">
<link rel="icon" href="{{ setting('site.favicon_dark', '/storage/logos/mini-logo-white.svg') }}" type="image/svg+xml" media="(prefers-color-scheme: dark)">

{{-- PostHog Analytics --}}
@include('posthog::script')

@stack('devdojo-auth-head-scripts')
