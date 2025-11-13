<?php

namespace App\Services;

use App\Models\ApiCredential;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ApiSecurityService
{
    /**
     * Generate a new API key
     */
    public function generateApiKey(): string
    {
        return 'sk_' . Str::random(32) . '_' . time();
    }

    /**
     * Generate a new API secret
     */
    public function generateApiSecret(): string
    {
        return Str::random(64);
    }

    /**
     * Create API credentials for service
     */
    public function createCredentials(
        string $serviceName,
        string $displayName,
        ?string $apiKey = null,
        ?string $apiSecret = null,
        ?array $additionalConfig = null,
        ?\DateTime $expiresAt = null
    ): ApiCredential {
        return ApiCredential::create([
            'service_name' => $serviceName,
            'display_name' => $displayName,
            'api_key' => $apiKey ?? $this->generateApiKey(),
            'api_secret' => $apiSecret ?? $this->generateApiSecret(),
            'additional_config' => $additionalConfig,
            'is_active' => true,
            'expires_at' => $expiresAt,
            'usage_count' => 0,
        ]);
    }

    /**
     * Rotate API key
     */
    public function rotateApiKey(ApiCredential $credential): ApiCredential
    {
        $oldKey = $credential->api_key;
        $newKey = $this->generateApiKey();

        $credential->update([
            'api_key' => $newKey,
            'additional_config' => array_merge(
                $credential->additional_config ?? [],
                [
                    'previous_key' => $oldKey,
                    'rotated_at' => now()->toDateTimeString(),
                ]
            ),
        ]);

        // Log rotation
        app(ActivityLogger::class)->log(
            'api_key_rotate',
            "API key rotated for service: {$credential->service_name}",
            null,
            ApiCredential::class,
            $credential->id,
            ['service' => $credential->service_name]
        );

        return $credential->fresh();
    }

    /**
     * Rotate API secret
     */
    public function rotateApiSecret(ApiCredential $credential): ApiCredential
    {
        $newSecret = $this->generateApiSecret();

        $credential->update([
            'api_secret' => $newSecret,
            'additional_config' => array_merge(
                $credential->additional_config ?? [],
                [
                    'secret_rotated_at' => now()->toDateTimeString(),
                ]
            ),
        ]);

        // Log rotation
        app(ActivityLogger::class)->log(
            'api_secret_rotate',
            "API secret rotated for service: {$credential->service_name}",
            null,
            ApiCredential::class,
            $credential->id,
            ['service' => $credential->service_name]
        );

        return $credential->fresh();
    }

    /**
     * Set expiration for API credentials
     */
    public function setExpiration(ApiCredential $credential, \DateTime $expiresAt): ApiCredential
    {
        $credential->update(['expires_at' => $expiresAt]);
        return $credential->fresh();
    }

    /**
     * Revoke API credentials
     */
    public function revokeCredentials(ApiCredential $credential): bool
    {
        $credential->update(['is_active' => false]);

        // Log revocation
        app(ActivityLogger::class)->log(
            'api_credentials_revoke',
            "API credentials revoked for service: {$credential->service_name}",
            null,
            ApiCredential::class,
            $credential->id,
            ['service' => $credential->service_name]
        );

        return true;
    }

    /**
     * Activate API credentials
     */
    public function activateCredentials(ApiCredential $credential): bool
    {
        $credential->update(['is_active' => true]);
        return true;
    }

    /**
     * Validate API key
     */
    public function validateApiKey(string $apiKey): ?ApiCredential
    {
        $credential = ApiCredential::where('api_key', $apiKey)
            ->where('is_active', true)
            ->first();

        if (!$credential) {
            return null;
        }

        // Check expiration
        if ($credential->isExpired()) {
            return null;
        }

        // Check rate limit
        if ($credential->isRateLimited()) {
            return null;
        }

        // Update usage
        $credential->incrementUsage();

        return $credential;
    }

    /**
     * Generate signature for request
     */
    public function generateSignature(array $data, string $secret): string
    {
        ksort($data);
        $payload = http_build_query($data);
        return hash_hmac('sha256', $payload, $secret);
    }

    /**
     * Verify request signature
     */
    public function verifySignature(array $data, string $signature, string $secret): bool
    {
        $expectedSignature = $this->generateSignature($data, $secret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Add IP restriction to credentials
     */
    public function addIpRestriction(ApiCredential $credential, string $ip): ApiCredential
    {
        $config = $credential->additional_config ?? [];
        $ipRestrictions = $config['ip_restrictions'] ?? [];

        if (!in_array($ip, $ipRestrictions)) {
            $ipRestrictions[] = $ip;
        }

        $config['ip_restrictions'] = $ipRestrictions;

        $credential->update(['additional_config' => $config]);

        return $credential->fresh();
    }

    /**
     * Remove IP restriction from credentials
     */
    public function removeIpRestriction(ApiCredential $credential, string $ip): ApiCredential
    {
        $config = $credential->additional_config ?? [];
        $ipRestrictions = $config['ip_restrictions'] ?? [];

        $ipRestrictions = array_filter($ipRestrictions, fn($item) => $item !== $ip);

        $config['ip_restrictions'] = array_values($ipRestrictions);

        $credential->update(['additional_config' => $config]);

        return $credential->fresh();
    }

    /**
     * Check if IP is allowed for credentials
     */
    public function isIpAllowed(ApiCredential $credential, string $ip): bool
    {
        $config = $credential->additional_config ?? [];
        $ipRestrictions = $config['ip_restrictions'] ?? [];

        // If no restrictions, allow all
        if (empty($ipRestrictions)) {
            return true;
        }

        // Check if IP is in allowed list
        foreach ($ipRestrictions as $allowedIp) {
            if ($ip === $allowedIp) {
                return true;
            }

            // Support CIDR notation
            if (str_contains($allowedIp, '/')) {
                if ($this->ipInRange($ip, $allowedIp)) {
                    return true;
                }
            }

            // Support wildcards
            if (str_contains($allowedIp, '*')) {
                $pattern = str_replace('.', '\.', $allowedIp);
                $pattern = str_replace('*', '\d{1,3}', $pattern);
                if (preg_match("/^{$pattern}$/", $ip)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if IP is in CIDR range
     */
    protected function ipInRange(string $ip, string $range): bool
    {
        list($subnet, $bits) = explode('/', $range);
        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - $bits);
        $subnet &= $mask;
        return ($ip & $mask) == $subnet;
    }

    /**
     * Get API credentials statistics
     */
    public function getStatistics(ApiCredential $credential): array
    {
        return [
            'service_name' => $credential->service_name,
            'display_name' => $credential->display_name,
            'is_active' => $credential->is_active,
            'usage_count' => $credential->usage_count,
            'last_used_at' => $credential->last_used_at?->toDateTimeString(),
            'expires_at' => $credential->expires_at?->toDateTimeString(),
            'is_expired' => $credential->isExpired(),
            'rate_limit' => $credential->rate_limit,
            'rate_limit_remaining' => $credential->rate_limit_remaining,
            'ip_restrictions' => $credential->additional_config['ip_restrictions'] ?? [],
        ];
    }

    /**
     * Clean expired credentials
     */
    public function cleanExpiredCredentials(): int
    {
        return ApiCredential::where('expires_at', '<', now())
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }

    /**
     * Get all active credentials
     */
    public function getActiveCredentials(): \Illuminate\Database\Eloquent\Collection
    {
        return ApiCredential::where('is_active', true)
            ->whereNull('expires_at')
            ->orWhere('expires_at', '>', now())
            ->get();
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature(string $payload, string $signature, string $secret): bool
    {
        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Generate webhook signature
     */
    public function generateWebhookSignature(string $payload, string $secret): string
    {
        return hash_hmac('sha256', $payload, $secret);
    }
}
