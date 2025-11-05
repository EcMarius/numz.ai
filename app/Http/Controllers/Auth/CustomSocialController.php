<?php

namespace App\Http\Controllers\Auth;

use Devdojo\Auth\Http\Controllers\SocialController as BaseSocialController;
use Devdojo\Auth\Models\SocialProvider;
use Devdojo\Auth\Models\SocialProviderUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;

class CustomSocialController extends BaseSocialController
{
    public function redirect(string $driver): RedirectResponse
    {
        // Store that user initiated OAuth (for terms check later)
        session()->put('oauth_initiated', true);
        session()->put('oauth_driver', $driver);

        $this->dynamicallySetSocialProviderCredentials($driver);

        return Socialite::driver($driver)->redirect();
    }

    private function dynamicallySetSocialProviderCredentials($provider)
    {
        $socialProvider = $this->getProviderCredentialsWithOverrides($provider);

        Config::set('services.'.$provider.'.client_id', $socialProvider->client_id);
        Config::set('services.'.$provider.'.client_secret', $socialProvider->client_secret);
        Config::set('services.'.$provider.'.redirect', '/auth/'.$provider.'/callback');
    }

    private function getProviderCredentialsWithOverrides($provider)
    {
        $socialProvider = SocialProvider::where('slug', $provider)->first();

        switch ($provider) {
            case 'facebook':
                $socialProvider->client_id = sprintf('%d', $socialProvider->client_id);
                break;
        }

        return $socialProvider;
    }

    public function callback(string $driver)
    {
        $this->dynamicallySetSocialProviderCredentials($driver);

        try {
            $socialiteUser = Socialite::driver($driver)->user();

            // Check if this is a new user who needs to accept terms
            $existingProviderUser = SocialProviderUser::where('provider_slug', $driver)
                ->where('provider_user_id', $socialiteUser->getId())
                ->first();

            $existingUser = app(config('auth.providers.users.model'))->where('email', $socialiteUser->getEmail())->first();

            // Check if user exists AND has accepted terms
            $hasAcceptedTerms = $existingUser && !is_null($existingUser->accepted_terms_at);

            // If user exists via OAuth provider OR has already accepted terms, proceed
            if ($existingProviderUser || $hasAcceptedTerms) {
                $providerUser = $this->findOrCreateProviderUser($socialiteUser, $driver);

                if ($providerUser instanceof RedirectResponse) {
                    return $providerUser;
                }

                Auth::login($providerUser->user);

                return redirect()->to(config('devdojo.auth.settings.redirect_after_auth'));
            }

            // New user - check if they've accepted terms via the consent page
            if (!session()->has('oauth_terms_accepted')) {
                // Store OAuth user data in session and redirect to consent page
                session()->put('oauth_pending_user', [
                    'driver' => $driver,
                    'id' => $socialiteUser->getId(),
                    'name' => $socialiteUser->getName(),
                    'email' => $socialiteUser->getEmail(),
                    'nickname' => $socialiteUser->getNickname(),
                    'avatar' => $socialiteUser->getAvatar(),
                    'token' => $socialiteUser->token,
                    'refresh_token' => $socialiteUser->refreshToken,
                    'expires_in' => $socialiteUser->expiresIn,
                    'user_data' => $socialiteUser->user,
                ]);

                return redirect()->route('auth.oauth.consent');
            }

            // Terms accepted, proceed with user creation
            $providerUser = $this->findOrCreateProviderUser($socialiteUser, $driver);

            if ($providerUser instanceof RedirectResponse) {
                return $providerUser;
            }

            Auth::login($providerUser->user);

            // Clear OAuth session data
            session()->forget(['oauth_pending_user', 'oauth_terms_accepted', 'oauth_initiated', 'oauth_driver']);

            return redirect()->to(config('devdojo.auth.settings.redirect_after_auth'));
        } catch (\Exception $e) {
            \Log::error('OAuth error: ' . $e->getMessage());
            return redirect()->route('auth.login')->with('error', 'An error occurred during authentication. Please try again.');
        }
    }

    private function findOrCreateProviderUser($socialiteUser, $driver)
    {
        $providerUser = SocialProviderUser::where('provider_slug', $driver)
            ->where('provider_user_id', $socialiteUser->getId())
            ->first();

        if ($providerUser) {
            return $providerUser;
        }

        $user = app(config('auth.providers.users.model'))->where('email', $socialiteUser->getEmail())->first();

        if ($user) {
            $existingProvider = $user->socialProviders()->first();
            if ($existingProvider) {
                return redirect()->route('auth.login')->with('error',
                    "This email is already associated with a {$existingProvider->provider_slug} account. Please login using that provider.");
            }
        }

        return DB::transaction(function () use ($socialiteUser, $driver, $user) {
            $user = $user ?? $this->createUser($socialiteUser);

            return $this->createSocialProviderUser($user, $socialiteUser, $driver);
        });
    }

    private function createUser($socialiteUser)
    {
        return app(config('auth.providers.users.model'))->create([
            'name' => $socialiteUser->getName(),
            'email' => $socialiteUser->getEmail(),
            'email_verified_at' => now(),
            'accepted_terms_at' => now(), // Record terms acceptance
        ]);
    }

    private function createSocialProviderUser($user, $socialiteUser, $driver)
    {
        return $user->socialProviders()->create([
            'provider_slug' => $driver,
            'provider_user_id' => $socialiteUser->getId(),
            'nickname' => $socialiteUser->getNickname(),
            'name' => $socialiteUser->getName(),
            'email' => $socialiteUser->getEmail(),
            'avatar' => $socialiteUser->getAvatar(),
            'provider_data' => json_encode($socialiteUser->user),
            'token' => $socialiteUser->token,
            'refresh_token' => $socialiteUser->refreshToken,
            'token_expires_at' => $socialiteUser->expiresIn ? now()->addSeconds($socialiteUser->expiresIn) : null,
        ]);
    }
}
