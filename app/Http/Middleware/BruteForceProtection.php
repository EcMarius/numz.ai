<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class BruteForceProtection
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->resolveRequestSignature($request);
        $maxAttempts = config('security.brute_force.max_attempts', 5);
        $decayMinutes = config('security.brute_force.decay_minutes', 15);

        // Check if IP is blocked
        if ($this->isBlocked($key)) {
            $this->logBlockedAttempt($request);

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Too Many Attempts',
                    'message' => 'Too many login attempts. Please try again later.',
                    'retry_after' => $this->getBlockedTimeRemaining($key),
                ], 429);
            }

            return response('Too many login attempts. Please try again later.', 429);
        }

        // Check rate limit
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $this->block($key, $decayMinutes);

            $this->logBlockedAttempt($request);

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Too Many Attempts',
                    'message' => 'Too many login attempts. Please try again later.',
                    'retry_after' => $decayMinutes * 60,
                ], 429);
            }

            return response('Too many login attempts. Please try again later.', 429);
        }

        $response = $next($request);

        // If login failed, increment attempts
        if ($this->shouldCountAttempt($request, $response)) {
            RateLimiter::hit($key, $decayMinutes * 60);
        } else {
            // Clear attempts on successful login
            RateLimiter::clear($key);
            $this->unblock($key);
        }

        return $response;
    }

    /**
     * Resolve request signature
     */
    protected function resolveRequestSignature(Request $request): string
    {
        return 'brute_force:' . sha1($request->ip() . '|' . $request->path());
    }

    /**
     * Check if IP is blocked
     */
    protected function isBlocked(string $key): bool
    {
        return Cache::has($key . ':blocked');
    }

    /**
     * Block IP
     */
    protected function block(string $key, int $minutes): void
    {
        Cache::put($key . ':blocked', true, now()->addMinutes($minutes * 2));
    }

    /**
     * Unblock IP
     */
    protected function unblock(string $key): void
    {
        Cache::forget($key . ':blocked');
    }

    /**
     * Get remaining blocked time in seconds
     */
    protected function getBlockedTimeRemaining(string $key): int
    {
        $expiresAt = Cache::get($key . ':blocked_until');
        return $expiresAt ? max(0, $expiresAt - time()) : 0;
    }

    /**
     * Determine if attempt should be counted
     */
    protected function shouldCountAttempt(Request $request, Response $response): bool
    {
        // Count failed login attempts
        if ($request->is('login') && $response->getStatusCode() === 302) {
            // Check if redirected back with errors (failed login)
            return session()->has('errors');
        }

        // For API requests
        if ($request->expectsJson()) {
            return in_array($response->getStatusCode(), [401, 403, 422]);
        }

        return false;
    }

    /**
     * Log blocked attempt
     */
    protected function logBlockedAttempt(Request $request): void
    {
        \Log::warning('Brute force attempt blocked', [
            'ip' => $request->ip(),
            'url' => $request->fullUrl(),
            'user_agent' => $request->userAgent(),
        ]);

        // Log suspicious activity
        app(\App\Services\ActivityLogger::class)->logSuspicious(
            'Brute force attempt detected',
            null,
            [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
            ]
        );
    }
}
