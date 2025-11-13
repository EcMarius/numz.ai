<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiVersionCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $version = 'v1'): Response
    {
        $acceptedVersions = ['v1'];

        if (!in_array($version, $acceptedVersions)) {
            return response()->json([
                'success' => false,
                'error' => 'API version not supported',
                'message' => "API version '{$version}' is not supported. Supported versions: " . implode(', ', $acceptedVersions),
            ], 400);
        }

        // Add version to request for later use
        $request->merge(['api_version' => $version]);

        $response = $next($request);

        // Add API version header to response
        $response->headers->set('X-API-Version', $version);

        return $response;
    }
}
