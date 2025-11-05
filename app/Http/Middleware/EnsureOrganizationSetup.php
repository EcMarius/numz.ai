<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to ensure users with seated plan subscriptions complete organization setup
 *
 * This middleware redirects users who have subscribed to a seated plan (team plan)
 * to complete their organization setup before accessing the application.
 */
class EnsureOrganizationSetup
{
    /**
     * Paths that are always accessible regardless of organization setup status
     */
    private const EXEMPT_PATHS = [
        'organization',     // Organization setup routes
        'logout',          // Allow logout
        'livewire',        // Livewire AJAX requests
        'subscription',    // Subscription/billing pages
        'settings',        // Settings pages
        'team',            // Team pages
        'admin',           // Admin panel
        'css', 'js', 'build', 'fonts', 'images', // Static assets
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Allow access to exempt paths (prevents redirect loops)
        if ($this->isExemptPath($request)) {
            return $next($request);
        }

        // 2. Skip check for unauthenticated users
        if (!auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();

        // 3. Skip check for team members (they don't create organizations)
        if ($user->isTeamMember()) {
            return $next($request);
        }

        // 4. Check if user needs to complete organization setup
        // The subscription/welcome page handles the initial redirect
        // This middleware serves as a safety net for direct navigation
        if ($user->needsOrganizationSetup()) {
            // Only redirect from dashboard - other routes are handled by subscription flow
            if ($request->path() === 'dashboard') {
                return redirect('/organization/setup');
            }
        }

        return $next($request);
    }

    /**
     * Check if the current request path is exempt from organization check
     */
    private function isExemptPath(Request $request): bool
    {
        $path = $request->path();

        foreach (self::EXEMPT_PATHS as $exemptPath) {
            if (str_starts_with($path, $exemptPath)) {
                return true;
            }
        }

        return false;
    }
}
