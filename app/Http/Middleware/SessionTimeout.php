<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SessionTimeout
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $timeout = config('security.session.timeout', 1800); // 30 minutes default
        $lastActivity = session('last_activity_time');

        if ($lastActivity && (time() - $lastActivity) > $timeout) {
            // Session has timed out
            $this->logTimeout($request);

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Session Timeout',
                    'message' => 'Your session has expired due to inactivity. Please log in again.',
                ], 401);
            }

            return redirect()->route('login')
                ->with('warning', 'Your session has expired due to inactivity. Please log in again.');
        }

        // Update last activity time
        session(['last_activity_time' => time()]);

        return $next($request);
    }

    /**
     * Log session timeout
     */
    protected function logTimeout(Request $request): void
    {
        if (Auth::check()) {
            app(\App\Services\ActivityLogger::class)->log(
                'session_timeout',
                'Session timed out due to inactivity',
                Auth::user(),
                null,
                null,
                [
                    'timeout_minutes' => config('security.session.timeout', 1800) / 60,
                ],
                $request
            );
        }
    }
}
