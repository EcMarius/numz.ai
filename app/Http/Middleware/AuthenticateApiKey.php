<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Wave\ApiKey;

class AuthenticateApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key') ?? $request->input('api_key');

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'API key is required. Please provide it in the X-API-Key header or api_key parameter.',
            ], 401);
        }

        $key = ApiKey::where('key', $apiKey)->first();

        if (!$key) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid API key.',
            ], 401);
        }

        // Update last used timestamp
        $key->update(['last_used_at' => now()]);

        // Attach user to request
        $request->merge(['api_user' => $key->user]);
        auth()->setUser($key->user);

        return $next($request);
    }
}
