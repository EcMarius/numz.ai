<?php

namespace App\Http\Middleware;

use App\Models\ApiUsageLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiLogger
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $endTime = microtime(true);
        $responseTime = round(($endTime - $startTime) * 1000, 2); // Convert to milliseconds

        // Log API request
        try {
            ApiUsageLog::create([
                'platform' => 'api',
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'status_code' => $response->status(),
                'response_time_ms' => $responseTime,
                'user_id' => auth()->id(),
                'request_data' => $this->getRequestData($request),
                'response_data' => $this->getResponseData($response),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Silently fail logging to avoid breaking the API
            \Log::error('API logging failed: ' . $e->getMessage());
        }

        return $response;
    }

    /**
     * Get sanitized request data
     */
    private function getRequestData(Request $request): array
    {
        $data = [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'query' => $request->query(),
        ];

        // Don't log sensitive data
        $input = $request->except(['password', 'password_confirmation', 'api_key', 'secret']);
        if (!empty($input)) {
            $data['body'] = $input;
        }

        return $data;
    }

    /**
     * Get sanitized response data
     */
    private function getResponseData(Response $response): ?array
    {
        $content = $response->getContent();

        if (empty($content)) {
            return null;
        }

        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['raw' => substr($content, 0, 1000)]; // Limit to 1000 chars
        }

        // Don't log sensitive response data
        if (isset($decoded['api_key'])) {
            unset($decoded['api_key']);
        }
        if (isset($decoded['secret'])) {
            unset($decoded['secret']);
        }

        return $decoded;
    }
}
