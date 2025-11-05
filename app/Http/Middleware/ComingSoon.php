<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ComingSoon
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip coming soon for Livewire requests
        if ($request->is('livewire/*') || $request->header('X-Livewire')) {
            return $next($request);
        }

        // Skip coming soon for admin routes (in case someone accesses /admin without login)
        if ($request->is('admin') || $request->is('admin/*')) {
            return $next($request);
        }

        // Skip coming soon for auth routes (login, register, password reset, etc.)
        if ($request->is('auth') ||
            $request->is('auth/*') ||
            $request->is('login') ||
            $request->is('register') ||
            $request->is('password/*') ||
            $request->is('forgot-password') ||
            $request->is('reset-password') ||
            $request->is('email/*')) {
            return $next($request);
        }

        // Skip coming soon for OAuth routes
        if ($request->is('oauth') || $request->is('oauth/*')) {
            return $next($request);
        }

        // Skip coming soon for API routes
        if ($request->is('api') || $request->is('api/*')) {
            return $next($request);
        }

        // Skip coming soon for webhook routes
        if ($request->is('webhook/*') || $request->is('stripe/webhook')) {
            return $next($request);
        }

        // Check if coming soon mode is enabled
        $comingSoonEnabled = setting('site.coming_soon_mode', '0');

        if ($comingSoonEnabled === '1' || $comingSoonEnabled === 1 || $comingSoonEnabled === true) {
            // Only show coming soon on marketing pages (homepage, pricing, contact, terms, privacy)
            $marketingPages = [
                '/',
                'pricing',
                'contact',
                'terms',
                'privacy',
                'privacy-policy',
                'terms-and-conditions',
            ];

            foreach ($marketingPages as $page) {
                if ($request->is($page)) {
                    // Skip coming soon if user is authenticated (check here after session is initialized)
                    if ($request->user() || auth()->check()) {
                        return $next($request);
                    }

                    // Check if coming-soon.html exists
                    $comingSoonPath = public_path('coming-soon.html');

                    if (file_exists($comingSoonPath)) {
                        return response()->file($comingSoonPath);
                    }

                    // Fallback if file doesn't exist
                    return response()->view('errors.coming-soon', [], 503);
                }
            }
        }

        return $next($request);
    }
}
