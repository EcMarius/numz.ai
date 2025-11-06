<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApiCredential extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_name',
        'display_name',
        'credential_type',
        'api_key',
        'api_secret',
        'access_token',
        'refresh_token',
        'additional_config',
        'is_active',
        'expires_at',
        'last_used_at',
        'usage_count',
        'rate_limit',
        'rate_limit_remaining',
        'rate_limit_reset_at',
    ];

    protected $casts = [
        'api_key' => 'encrypted',
        'api_secret' => 'encrypted',
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'additional_config' => 'array',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'rate_limit_reset_at' => 'datetime',
    ];

    /**
     * Check if credentials are expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if rate limit is reached
     */
    public function isRateLimited(): bool
    {
        if (!$this->rate_limit) {
            return false;
        }

        return $this->rate_limit_remaining <= 0 &&
               $this->rate_limit_reset_at &&
               $this->rate_limit_reset_at->isFuture();
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->update([
            'usage_count' => $this->usage_count + 1,
            'last_used_at' => now(),
        ]);
    }

    /**
     * Update rate limit info
     */
    public function updateRateLimit(int $remaining, ?\Carbon\Carbon $resetAt = null): void
    {
        $this->update([
            'rate_limit_remaining' => $remaining,
            'rate_limit_reset_at' => $resetAt,
        ]);
    }

    /**
     * Get credential for service
     */
    public static function getForService(string $serviceName): ?self
    {
        return self::where('service_name', $serviceName)
            ->where('is_active', true)
            ->first();
    }
}
