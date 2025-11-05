@props([
    'separator' => true,
    'separator_text' => 'or'
])

@php
    // Try to use SocialAuth plugin service if available, otherwise fall back to config
    $socialProviders = [];
    try {
        if (class_exists(\Wave\Plugins\SocialAuth\Services\SocialAuthService::class)) {
            $service = app(\Wave\Plugins\SocialAuth\Services\SocialAuthService::class);
            $enabledProviders = $service->getEnabledProviders();

            // Convert to objects for compatibility with social-button component
            foreach ($enabledProviders as $slug => $provider) {
                $socialProviders[$slug] = (object) $provider;
            }
        }
    } catch (\Exception $e) {
        \Log::warning('SocialAuth plugin not available, falling back to config: ' . $e->getMessage());
    }

    // Fallback to original helper if plugin didn't provide any providers
    if (empty($socialProviders)) {
        $socialProviders = \Devdojo\Auth\Helper::activeProviders();
    }
@endphp

@if(count($socialProviders))
    @if($separator && config('devdojo.auth.settings.social_providers_location') != 'top')
        <x-auth::elements.separator class="my-6">{{ $separator_text }}</x-auth::elements.separator>
    @endif
    <div class="relative space-y-2 w-full @if(config('devdojo.auth.settings.social_providers_location') != 'top' && !$separator){{ 'mt-3' }}@endif">
        @foreach($socialProviders as $slug => $provider)
            <x-auth::elements.social-button :$slug :$provider />
        @endforeach
    </div>
    @if($separator && config('devdojo.auth.settings.social_providers_location') == 'top')
        <x-auth::elements.separator class="my-6">{{ $separator_text }}</x-auth::elements.separator>
    @endif
@endif
