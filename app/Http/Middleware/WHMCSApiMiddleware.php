<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * WHMCS API Middleware
 *
 * Handles authentication and rate limiting for WHMCS API requests
 */
class WHMCSApiMiddleware
{
    /**
     * Handle an incoming request
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if WHMCS API is enabled
        if (!config('whmcs.api.enabled', false)) {
            return response()->json([
                'result' => 'error',
                'message' => 'WHMCS API is disabled',
            ], 403);
        }

        // Validate IP whitelist if configured
        if (!$this->validateIpWhitelist($request)) {
            return response()->json([
                'result' => 'error',
                'message' => 'IP address not authorized',
            ], 403);
        }

        // Validate API credentials
        if (!$this->validateCredentials($request)) {
            return response()->json([
                'result' => 'error',
                'message' => 'Invalid API credentials',
            ], 401);
        }

        // Apply rate limiting
        if ($this->isRateLimited($request)) {
            return response()->json([
                'result' => 'error',
                'message' => 'Rate limit exceeded',
            ], 429);
        }

        // Log API request if enabled
        if (config('whmcs.module_log.enabled', false)) {
            $this->logApiRequest($request);
        }

        return $next($request);
    }

    /**
     * Validate IP whitelist
     */
    protected function validateIpWhitelist(Request $request): bool
    {
        $allowedIps = config('whmcs.api.allowed_ips', '');

        // If no whitelist is configured, allow all IPs
        if (empty($allowedIps)) {
            return true;
        }

        $allowedIpsArray = array_map('trim', explode(',', $allowedIps));
        $clientIp = $request->ip();

        // Check if client IP is in whitelist
        foreach ($allowedIpsArray as $allowedIp) {
            if ($this->ipMatches($clientIp, $allowedIp)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if IP matches pattern (supports wildcards and CIDR)
     */
    protected function ipMatches(string $clientIp, string $pattern): bool
    {
        // Exact match
        if ($clientIp === $pattern) {
            return true;
        }

        // Wildcard match (e.g., 192.168.1.*)
        if (str_contains($pattern, '*')) {
            $regex = str_replace(['.', '*'], ['\.', '.*'], $pattern);
            if (preg_match("/^{$regex}$/", $clientIp)) {
                return true;
            }
        }

        // CIDR match (e.g., 192.168.1.0/24)
        if (str_contains($pattern, '/')) {
            return $this->ipInRange($clientIp, $pattern);
        }

        return false;
    }

    /**
     * Check if IP is in CIDR range
     */
    protected function ipInRange(string $ip, string $range): bool
    {
        list($subnet, $mask) = explode('/', $range);

        $ip_long = ip2long($ip);
        $subnet_long = ip2long($subnet);
        $mask_long = -1 << (32 - (int)$mask);
        $subnet_long &= $mask_long;

        return ($ip_long & $mask_long) === $subnet_long;
    }

    /**
     * Validate API credentials
     */
    protected function validateCredentials(Request $request): bool
    {
        // Get credentials from request
        $identifier = $request->input('identifier');
        $secret = $request->input('secret');
        $accesskey = $request->input('accesskey');

        // Support both old and new authentication methods

        // Method 1: Access Key + Secret Key (recommended)
        if ($accesskey && $secret) {
            $configAccessKey = config('whmcs.api.access_key');
            $configSecretKey = config('whmcs.api.secret_key');

            if ($configAccessKey && $configSecretKey) {
                return hash_equals($configAccessKey, $accesskey) &&
                       hash_equals($configSecretKey, $secret);
            }
        }

        // Method 2: Identifier + Secret (legacy)
        if ($identifier && $secret) {
            // Check against admin users with API access
            $admin = \App\Models\Admin::where('api_identifier', $identifier)
                ->where('is_active', true)
                ->first();

            if ($admin && hash_equals($admin->api_secret, $secret)) {
                // Store authenticated admin in request
                $request->merge(['_whmcs_admin' => $admin]);
                return true;
            }
        }

        // Method 3: Bearer token (modern API)
        $bearerToken = $request->bearerToken();
        if ($bearerToken) {
            $token = \Laravel\Sanctum\PersonalAccessToken::findToken($bearerToken);
            if ($token) {
                $request->merge(['_whmcs_admin' => $token->tokenable]);
                return true;
            }
        }

        return false;
    }

    /**
     * Check if request is rate limited
     */
    protected function isRateLimited(Request $request): bool
    {
        $rateLimit = config('whmcs.api.rate_limit', 60);

        // If rate limiting is disabled (0), allow all requests
        if ($rateLimit <= 0) {
            return false;
        }

        $key = $this->resolveRequestSignature($request);

        if (RateLimiter::tooManyAttempts($key, $rateLimit)) {
            return true;
        }

        RateLimiter::hit($key, 60); // 60 seconds window

        return false;
    }

    /**
     * Resolve rate limiter key
     */
    protected function resolveRequestSignature(Request $request): string
    {
        $identifier = $request->input('identifier') ??
                     $request->input('accesskey') ??
                     $request->ip();

        return 'whmcs-api:' . sha1($identifier);
    }

    /**
     * Log API request
     */
    protected function logApiRequest(Request $request): void
    {
        try {
            \DB::table('tblmodulelog')->insert([
                'module' => 'API',
                'action' => $request->input('action') ?? 'Unknown',
                'request' => json_encode([
                    'action' => $request->input('action'),
                    'params' => $request->except(['secret', 'password', 'api_secret', 'accesskey']),
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]),
                'response' => null, // Will be updated after response
                'arrdata' => null,
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to log WHMCS API request', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
