<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ApiUsageLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'platform',
        'endpoint',
        'method',
        'status_code',
        'response_time_ms',
        'rate_limit_remaining',
        'rate_limit_reset',
        'user_id',
        'campaign_id',
        'request_data',
        'response_data',
        'created_at',
    ];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
        'rate_limit_reset' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function scopeForPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForEndpoint($query, string $endpoint)
    {
        return $query->where('endpoint', $endpoint);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeThisHour($query)
    {
        return $query->where('created_at', '>=', now()->subHour());
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);
    }

    public function scopeLastNMinutes($query, int $minutes)
    {
        return $query->where('created_at', '>=', now()->subMinutes($minutes));
    }

    public static function getUsagePerMinute(string $platform): int
    {
        return static::forPlatform($platform)
            ->lastNMinutes(60)
            ->count();
    }

    public static function getUsagePerHour(string $platform): int
    {
        return static::forPlatform($platform)
            ->thisHour()
            ->count();
    }

    public static function getUsagePerDay(string $platform): int
    {
        return static::forPlatform($platform)
            ->today()
            ->count();
    }

    public static function getUsagePerMonth(string $platform): int
    {
        return static::forPlatform($platform)
            ->thisMonth()
            ->count();
    }

    public static function getTopEndpoints(string $platform, int $limit = 10): array
    {
        return static::forPlatform($platform)
            ->select('endpoint', DB::raw('COUNT(*) as count'))
            ->groupBy('endpoint')
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->pluck('count', 'endpoint')
            ->toArray();
    }

    public static function getStatusCodeDistribution(string $platform): array
    {
        return static::forPlatform($platform)
            ->where('created_at', '>=', now()->subDay())
            ->select('status_code', DB::raw('COUNT(*) as count'))
            ->groupBy('status_code')
            ->get()
            ->pluck('count', 'status_code')
            ->toArray();
    }

    public static function getHourlyStats(string $platform, int $hours = 24): array
    {
        return static::forPlatform($platform)
            ->where('created_at', '>=', now()->subHours($hours))
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m-%d %H:00:00") as hour'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->pluck('count', 'hour')
            ->toArray();
    }

    public static function getAverageResponseTime(string $platform): int
    {
        return (int) static::forPlatform($platform)
            ->whereNotNull('response_time_ms')
            ->where('created_at', '>=', now()->subDay())
            ->avg('response_time_ms');
    }
}
