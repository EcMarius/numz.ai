<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingCompleted
{
    /**
     * Handle an incoming request.
     * Ensures users complete onboarding before accessing protected areas.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Skip if user is not authenticated
        if (!$user) {
            return $next($request);
        }

        // Allow admins to bypass onboarding
        if ($user->hasRole('admin')) {
            return $next($request);
        }

        // Check if user has completed onboarding
        if ($user->onboarding_completed) {
            return $next($request);
        }

        // Excluded routes that don't require onboarding completion
        $excludedRoutes = [
            // Auth routes
            'login',
            'register',
            'logout',
            'logout.get',
            'password.request',
            'password.reset',
            'password.email',
            'password.update',
            'auth.login',
            'auth.register',

            // Verification routes
            'verification.notice',
            'verification.verify',
            'verification.send',

            // Onboarding routes (obviously!)
            'onboarding',
            'onboarding.*',

            // Public routes
            'home',
            'pricing',
            'contact',
            'about',
            'privacy',
            'terms',
            'blog',
            'blog.*',

            // Admin routes (admins can bypass onboarding)
            'admin',
            'admin.*',
            'filament.*',
        ];

        if ($request->routeIs($excludedRoutes)) {
            return $next($request);
        }

        // Also exclude by path patterns
        if ($request->is('auth/*') ||
            $request->is('login') ||
            $request->is('register') ||
            $request->is('password/*') ||
            $request->is('email/*') ||
            $request->is('onboarding') ||
            $request->is('onboarding/*') ||
            $request->is('pricing') ||
            $request->is('contact') ||
            $request->is('about') ||
            $request->is('privacy') ||
            $request->is('terms') ||
            $request->is('blog') ||
            $request->is('blog/*') ||
            $request->is('admin') ||
            $request->is('admin/*') ||
            $request->is('filament') ||
            $request->is('filament/*') ||
            $request->is('livewire/*') ||
            $request->is('api/*')) {
            return $next($request);
        }

        // Exclude static assets and special paths
        if ($request->is('_debugbar/*') ||
            $request->is('build/*') ||
            $request->is('storage/*') ||
            $request->is('css/*') ||
            $request->is('js/*') ||
            $request->is('images/*') ||
            $request->is('fonts/*') ||
            $request->is('favicon.ico')) {
            return $next($request);
        }

        // Check if user's email is verified (onboarding requires verified email)
        if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !$user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        // Redirect to onboarding
        \Log::info('Redirecting user to onboarding (incomplete)', [
            'user_id' => $user->id,
            'email' => $user->email,
            'attempted_route' => $request->route()?->getName(),
            'attempted_path' => $request->path(),
            'onboarding_completed' => $user->onboarding_completed,
        ]);

        return redirect('/onboarding')
            ->with('info', 'Please complete your onboarding to continue.');
    }
}
