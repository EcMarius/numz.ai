<?php

namespace App\Numz\Services;

use App\Models\SystemInstallation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class LicenseService
{
    protected $apiUrl = 'https://license.numz.ai/api/verify';

    /**
     * Verify license key
     */
    public function verify(string $licenseKey, string $email): array
    {
        try {
            // For now, use a simple validation
            // In production, this would call your license server
            if (strlen($licenseKey) < 20) {
                return [
                    'valid' => false,
                    'message' => 'Invalid license key format',
                ];
            }

            // Simulate API call (replace with real API)
            /*
            $response = Http::post($this->apiUrl, [
                'license_key' => $licenseKey,
                'email' => $email,
                'domain' => request()->getHost(),
                'ip' => request()->ip(),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'valid' => $data['valid'] ?? false,
                    'message' => $data['message'] ?? '',
                    'expires_at' => $data['expires_at'] ?? null,
                ];
            }
            */

            // For demo/development - accept any key over 20 chars
            return [
                'valid' => true,
                'message' => 'License verified successfully',
                'expires_at' => now()->addYear(),
            ];

        } catch (\Exception $e) {
            return [
                'valid' => false,
                'message' => 'Failed to verify license: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check if current installation has valid license
     */
    public function isValid(): bool
    {
        return SystemInstallation::isLicenseValid();
    }

    /**
     * Generate installation ID
     */
    public function generateInstallationId(): string
    {
        return Str::uuid()->toString();
    }

    /**
     * Activate license
     */
    public function activate(string $licenseKey, string $email): bool
    {
        $verification = $this->verify($licenseKey, $email);

        if ($verification['valid']) {
            $installation = SystemInstallation::first();
            $installation->update([
                'license_key' => $licenseKey,
                'license_email' => $email,
                'license_status' => 'active',
                'license_verified_at' => now(),
            ]);

            return true;
        }

        return false;
    }
}
