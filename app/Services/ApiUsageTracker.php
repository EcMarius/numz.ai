<?php

namespace App\Services;

use App\Models\ApiUsageLog;
use Illuminate\Support\Facades\Log;

class ApiUsageTracker
{
    /**
     * Log an API request
     */
    public static function logRequest(
        string $platform,
        string $endpoint,
        string $method,
        int $statusCode,
        ?int $responseTime = null,
        ?array $rateLimitData = null,
        ?int $userId = null,
        ?int $campaignId = null,
        ?array $requestData = null,
        ?array $responseData = null
    ): void {
        try {
            ApiUsageLog::create([
                'platform' => $platform,
                'endpoint' => $endpoint,
                'method' => $method,
                'status_code' => $statusCode,
                'response_time_ms' => $responseTime,
                'rate_limit_remaining' => $rateLimitData['remaining'] ?? null,
                'rate_limit_reset' => isset($rateLimitData['reset'])
                    ? (is_numeric($rateLimitData['reset']) ? \Carbon\Carbon::createFromTimestamp($rateLimitData['reset']) : $rateLimitData['reset'])
                    : null,
                'user_id' => $userId,
                'campaign_id' => $campaignId,
                'request_data' => $requestData,
                'response_data' => $responseData,
                'created_at' => now(),
            ]);

            // Check if we should alert
            if (static::shouldAlert($platform)) {
                static::sendAlert($platform);
            }
        } catch (\Exception $e) {
            // Don't throw - logging shouldn't break the main flow
            Log::error('Failed to log API usage', [
                'platform' => $platform,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get usage statistics for a platform
     */
    public static function getUsageStats(string $platform, string $timeframe = '24h'): array
    {
        return [
            'per_minute' => ApiUsageLog::getUsagePerMinute($platform),
            'per_hour' => ApiUsageLog::getUsagePerHour($platform),
            'per_day' => ApiUsageLog::getUsagePerDay($platform),
            'per_month' => ApiUsageLog::getUsagePerMonth($platform),
            'avg_response_time' => ApiUsageLog::getAverageResponseTime($platform),
            'top_endpoints' => ApiUsageLog::getTopEndpoints($platform, 10),
            'status_codes' => ApiUsageLog::getStatusCodeDistribution($platform),
        ];
    }

    /**
     * Get current rate limit status
     */
    public static function getCurrentRateLimitStatus(string $platform): ?array
    {
        $latest = ApiUsageLog::forPlatform($platform)
            ->whereNotNull('rate_limit_remaining')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$latest) {
            return null;
        }

        $limit = match($platform) {
            'reddit' => 600, // Reddit: 600 requests per 10 minutes
            'twitter', 'x' => 300,
            default => 1000,
        };

        return [
            'remaining' => $latest->rate_limit_remaining,
            'limit' => $limit,
            'percentage_used' => $limit > 0 ? (($limit - $latest->rate_limit_remaining) / $limit) * 100 : 0,
            'resets_at' => $latest->rate_limit_reset,
            'resets_in' => $latest->rate_limit_reset ? $latest->rate_limit_reset->diffForHumans() : null,
        ];
    }

    /**
     * Check if we should send an alert
     */
    public static function shouldAlert(string $platform): bool
    {
        $status = static::getCurrentRateLimitStatus($platform);

        if (!$status) {
            return false;
        }

        $thresholds = [80, 90, 95]; // Alert at 80%, 90%, 95%

        foreach ($thresholds as $threshold) {
            if ($status['percentage_used'] >= $threshold) {
                // Check if we've already alerted for this threshold recently
                $cacheKey = "api_alert_{$platform}_{$threshold}";

                if (!\Cache::has($cacheKey)) {
                    // Set cache for 10 minutes to avoid spam
                    \Cache::put($cacheKey, true, 600);
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Send alert notification
     */
    protected static function sendAlert(string $platform): void
    {
        $status = static::getCurrentRateLimitStatus($platform);

        Log::warning('API rate limit alert', [
            'platform' => $platform,
            'percentage_used' => $status['percentage_used'],
            'remaining' => $status['remaining'],
        ]);

        // Could send email to admin here
        // \Mail::to(config('mail.admin'))->send(new ApiRateLimitAlert($platform, $status));
    }

    /**
     * Get rate limit percentage used
     */
    public static function getRateLimitPercentage(string $platform): float
    {
        $status = static::getCurrentRateLimitStatus($platform);
        return $status['percentage_used'] ?? 0;
    }

    /**
     * Cleanup old logs (keep last 90 days)
     */
    public static function cleanupOldLogs(int $days = 90): int
    {
        return ApiUsageLog::where('created_at', '<', now()->subDays($days))->delete();
    }

    /**
     * Get hourly request chart data
     */
    public static function getHourlyChartData(string $platform, int $hours = 24): array
    {
        $stats = ApiUsageLog::getHourlyStats($platform, $hours);

        // Fill in missing hours with 0
        $data = [];
        for ($i = $hours - 1; $i >= 0; $i--) {
            $hour = now()->subHours($i)->format('Y-m-d H:00:00');
            $data[$hour] = $stats[$hour] ?? 0;
        }

        return $data;
    }
}
