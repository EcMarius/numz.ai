<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RolePermissionMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $permission = null, string $guard = null): Response
    {
        $authGuard = Auth::guard($guard);

        if ($authGuard->guest()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Unauthorized',
                    'message' => 'You must be logged in to access this resource.'
                ], 401);
            }

            return redirect()->route('login')->with('error', 'You must be logged in to access this resource.');
        }

        $user = $authGuard->user();

        // Super admin bypasses all permission checks
        if ($user->hasRole('super-admin')) {
            return $next($request);
        }

        // Check if permission parameter was provided
        if ($permission) {
            $permissions = is_array($permission) ? $permission : explode('|', $permission);

            // Check if user has any of the required permissions
            if (!$user->hasAnyPermission($permissions)) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'error' => 'Forbidden',
                        'message' => 'You do not have permission to perform this action.'
                    ], 403);
                }

                abort(403, 'You do not have permission to perform this action.');
            }
        }

        return $next($request);
    }
}
