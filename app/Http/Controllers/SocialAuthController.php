<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\SocialLogin;
use App\Numz\Modules\Integrations\SocialAuthModule;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SocialAuthController extends Controller
{
    protected $socialAuth;

    public function __construct(SocialAuthModule $socialAuth)
    {
        $this->socialAuth = $socialAuth;
    }

    /**
     * Redirect to social provider
     */
    public function redirect(string $provider)
    {
        try {
            return $this->socialAuth->redirectToProvider($provider);
        } catch (\Exception $e) {
            return redirect()->route('login')
                ->with('error', 'Social login is not available: ' . $e->getMessage());
        }
    }

    /**
     * Handle callback from social provider
     */
    public function callback(string $provider)
    {
        $result = $this->socialAuth->handleCallback($provider);

        if (!$result['success']) {
            return redirect()->route('login')
                ->with('error', 'Social login failed: ' . $result['error']);
        }

        // Check if this social account is already linked
        $socialLogin = SocialLogin::findByProvider($provider, $result['provider_id']);

        if ($socialLogin) {
            // Social account already linked, log in the user
            Auth::login($socialLogin->user, true);

            // Update token
            $socialLogin->update([
                'provider_token' => $result['token'],
                'provider_refresh_token' => $result['refresh_token'],
            ]);

            return redirect()->intended('/dashboard');
        }

        // Check if user with this email exists
        $user = User::where('email', $result['email'])->first();

        if ($user) {
            // User exists, link social account
            SocialLogin::create([
                'user_id' => $user->id,
                'provider' => $provider,
                'provider_id' => $result['provider_id'],
                'provider_token' => $result['token'],
                'provider_refresh_token' => $result['refresh_token'],
            ]);

            Auth::login($user, true);

            return redirect()->intended('/dashboard')
                ->with('success', ucfirst($provider) . ' account linked successfully!');
        }

        // Create new user
        $user = User::create([
            'name' => $result['name'],
            'email' => $result['email'],
            'username' => $this->generateUsername($result['name'], $result['email']),
            'password' => Hash::make(Str::random(32)), // Random password
            'avatar' => $result['avatar'],
            'verified' => 1, // Auto-verify social login users
        ]);

        // Create social login record
        SocialLogin::create([
            'user_id' => $user->id,
            'provider' => $provider,
            'provider_id' => $result['provider_id'],
            'provider_token' => $result['token'],
            'provider_refresh_token' => $result['refresh_token'],
        ]);

        // Assign default role if exists
        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            try {
                $defaultRole = \Spatie\Permission\Models\Role::where('name', 'user')->first();
                if ($defaultRole) {
                    $user->assignRole($defaultRole);
                }
            } catch (\Exception $e) {
                \Log::warning('Could not assign role: ' . $e->getMessage());
            }
        }

        Auth::login($user, true);

        return redirect()->intended('/dashboard')
            ->with('success', 'Welcome! Your account has been created via ' . ucfirst($provider) . '.');
    }

    /**
     * Unlink social provider from account
     */
    public function unlink(Request $request, string $provider)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        $socialLogin = SocialLogin::where('user_id', $user->id)
            ->where('provider', $provider)
            ->first();

        if (!$socialLogin) {
            return back()->with('error', 'Social account not linked.');
        }

        // Check if user has a password set (prevent locking out)
        if (!$user->password || empty($user->password)) {
            $otherSocials = SocialLogin::where('user_id', $user->id)
                ->where('provider', '!=', $provider)
                ->count();

            if ($otherSocials == 0) {
                return back()->with('error', 'Cannot unlink. Please set a password first to prevent account lockout.');
            }
        }

        $socialLogin->delete();

        return back()->with('success', ucfirst($provider) . ' account unlinked successfully.');
    }

    /**
     * Generate unique username from name and email
     */
    protected function generateUsername(string $name, string $email): string
    {
        // Start with name
        $username = Str::slug(Str::lower($name), '');

        // If empty, use email prefix
        if (empty($username)) {
            $username = Str::before($email, '@');
            $username = Str::slug(Str::lower($username), '');
        }

        // Ensure uniqueness
        $originalUsername = $username;
        $counter = 1;

        while (User::where('username', $username)->exists()) {
            $username = $originalUsername . $counter;
            $counter++;
        }

        return $username;
    }
}
