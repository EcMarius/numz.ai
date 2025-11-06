<?php

namespace App\Numz\Modules\Integrations;

use App\Models\ModuleSetting;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthModule
{
    protected $moduleName = 'socialauth';
    protected $providers = ['google', 'facebook', 'github'];

    public function isProviderEnabled(string $provider): bool
    {
        $enabled = ModuleSetting::get('integration', $this->moduleName, "{$provider}_enabled", 'false');
        return $enabled === 'true';
    }

    public function getProviderConfig(string $provider): array
    {
        if (!in_array($provider, $this->providers)) {
            return [];
        }

        $clientId = ModuleSetting::get('integration', $this->moduleName, "{$provider}_client_id")
            ?? config("services.{$provider}.client_id");

        $clientSecret = ModuleSetting::get('integration', $this->moduleName, "{$provider}_client_secret")
            ?? config("services.{$provider}.client_secret");

        $redirectUrl = ModuleSetting::get('integration', $this->moduleName, "{$provider}_redirect_url")
            ?? config("services.{$provider}.redirect")
            ?? url("/auth/social/{$provider}/callback");

        return [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect' => $redirectUrl,
        ];
    }

    public function redirectToProvider(string $provider)
    {
        if (!$this->isProviderEnabled($provider)) {
            abort(404, 'Social provider not enabled');
        }

        $config = $this->getProviderConfig($provider);

        if (empty($config['client_id']) || empty($config['client_secret'])) {
            abort(500, 'Social provider not configured');
        }

        // Dynamically set configuration
        config(["services.{$provider}" => $config]);

        return Socialite::driver($provider)->redirect();
    }

    public function handleCallback(string $provider)
    {
        if (!$this->isProviderEnabled($provider)) {
            abort(404, 'Social provider not enabled');
        }

        $config = $this->getProviderConfig($provider);

        // Dynamically set configuration
        config(["services.{$provider}" => $config]);

        try {
            $user = Socialite::driver($provider)->user();

            return [
                'success' => true,
                'provider' => $provider,
                'provider_id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'avatar' => $user->getAvatar(),
                'token' => $user->token,
                'refresh_token' => $user->refreshToken ?? null,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getEnabledProviders(): array
    {
        $enabled = [];
        foreach ($this->providers as $provider) {
            if ($this->isProviderEnabled($provider)) {
                $enabled[] = $provider;
            }
        }
        return $enabled;
    }

    public function getConfig(): array
    {
        return [
            'name' => 'Social Authentication',
            'description' => 'Allow users to login with Google, Facebook, or GitHub accounts.',
            'settings' => [
                // Google
                [
                    'key' => 'google_enabled',
                    'label' => 'Enable Google Login',
                    'type' => 'boolean',
                    'encrypted' => false,
                    'required' => false,
                    'default' => 'false',
                ],
                [
                    'key' => 'google_client_id',
                    'label' => 'Google Client ID',
                    'type' => 'text',
                    'encrypted' => false,
                    'required' => false,
                ],
                [
                    'key' => 'google_client_secret',
                    'label' => 'Google Client Secret',
                    'type' => 'password',
                    'encrypted' => true,
                    'required' => false,
                ],
                [
                    'key' => 'google_redirect_url',
                    'label' => 'Google Redirect URL',
                    'type' => 'text',
                    'encrypted' => false,
                    'required' => false,
                    'default' => url('/auth/social/google/callback'),
                ],
                // Facebook
                [
                    'key' => 'facebook_enabled',
                    'label' => 'Enable Facebook Login',
                    'type' => 'boolean',
                    'encrypted' => false,
                    'required' => false,
                    'default' => 'false',
                ],
                [
                    'key' => 'facebook_client_id',
                    'label' => 'Facebook App ID',
                    'type' => 'text',
                    'encrypted' => false,
                    'required' => false,
                ],
                [
                    'key' => 'facebook_client_secret',
                    'label' => 'Facebook App Secret',
                    'type' => 'password',
                    'encrypted' => true,
                    'required' => false,
                ],
                [
                    'key' => 'facebook_redirect_url',
                    'label' => 'Facebook Redirect URL',
                    'type' => 'text',
                    'encrypted' => false,
                    'required' => false,
                    'default' => url('/auth/social/facebook/callback'),
                ],
                // GitHub
                [
                    'key' => 'github_enabled',
                    'label' => 'Enable GitHub Login',
                    'type' => 'boolean',
                    'encrypted' => false,
                    'required' => false,
                    'default' => 'false',
                ],
                [
                    'key' => 'github_client_id',
                    'label' => 'GitHub Client ID',
                    'type' => 'text',
                    'encrypted' => false,
                    'required' => false,
                ],
                [
                    'key' => 'github_client_secret',
                    'label' => 'GitHub Client Secret',
                    'type' => 'password',
                    'encrypted' => true,
                    'required' => false,
                ],
                [
                    'key' => 'github_redirect_url',
                    'label' => 'GitHub Redirect URL',
                    'type' => 'text',
                    'encrypted' => false,
                    'required' => false,
                    'default' => url('/auth/social/github/callback'),
                ],
            ],
        ];
    }
}
