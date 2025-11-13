<?php

namespace App\Http\Middleware;

use App\Services\PasswordPolicy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurePasswordMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only check password on registration and password change requests
        if (!$this->shouldCheckPassword($request)) {
            return $next($request);
        }

        $password = $request->input('password');

        if (!$password) {
            return $next($request);
        }

        $passwordPolicy = app(PasswordPolicy::class);

        // Validate password against policy
        $validation = $passwordPolicy->validate($password);

        if (!$validation['valid']) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Validation Error',
                    'message' => 'Password does not meet security requirements.',
                    'errors' => [
                        'password' => $validation['errors']
                    ]
                ], 422);
            }

            return redirect()->back()
                ->withErrors(['password' => $validation['errors']])
                ->withInput($request->except('password', 'password_confirmation'));
        }

        return $next($request);
    }

    /**
     * Determine if password should be checked
     */
    protected function shouldCheckPassword(Request $request): bool
    {
        return $request->isMethod('post') && (
            $request->is('register') ||
            $request->is('password/reset') ||
            $request->is('user/password') ||
            $request->is('api/register') ||
            $request->is('api/password/*')
        );
    }
}
