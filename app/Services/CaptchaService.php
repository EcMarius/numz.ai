<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class CaptchaService
{
    /**
     * Verify reCAPTCHA v2 response
     */
    public function verifyRecaptchaV2(string $response, ?string $remoteIp = null): bool
    {
        if (!config('services.recaptcha.enabled', false)) {
            return true; // Disabled, skip verification
        }

        $secret = config('services.recaptcha.secret_key');

        if (!$secret) {
            \Log::warning('reCAPTCHA secret key not configured');
            return false;
        }

        try {
            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $secret,
                'response' => $response,
                'remoteip' => $remoteIp ?? request()->ip(),
            ]);

            $result = $response->json();

            return $result['success'] ?? false;
        } catch (\Exception $e) {
            \Log::error('reCAPTCHA verification failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify reCAPTCHA v3 response
     */
    public function verifyRecaptchaV3(string $response, ?string $action = null, float $minScore = 0.5): bool
    {
        if (!config('services.recaptcha_v3.enabled', false)) {
            return true; // Disabled, skip verification
        }

        $secret = config('services.recaptcha_v3.secret_key');

        if (!$secret) {
            \Log::warning('reCAPTCHA v3 secret key not configured');
            return false;
        }

        try {
            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $secret,
                'response' => $response,
            ]);

            $result = $response->json();

            if (!($result['success'] ?? false)) {
                return false;
            }

            // Verify score
            $score = $result['score'] ?? 0;
            if ($score < $minScore) {
                \Log::warning('reCAPTCHA v3 score too low', [
                    'score' => $score,
                    'min_score' => $minScore,
                ]);
                return false;
            }

            // Verify action if provided
            if ($action && ($result['action'] ?? '') !== $action) {
                \Log::warning('reCAPTCHA v3 action mismatch', [
                    'expected' => $action,
                    'actual' => $result['action'] ?? '',
                ]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('reCAPTCHA v3 verification failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify hCaptcha response
     */
    public function verifyHCaptcha(string $response, ?string $remoteIp = null): bool
    {
        if (!config('services.hcaptcha.enabled', false)) {
            return true; // Disabled, skip verification
        }

        $secret = config('services.hcaptcha.secret_key');

        if (!$secret) {
            \Log::warning('hCaptcha secret key not configured');
            return false;
        }

        try {
            $response = Http::asForm()->post('https://hcaptcha.com/siteverify', [
                'secret' => $secret,
                'response' => $response,
                'remoteip' => $remoteIp ?? request()->ip(),
            ]);

            $result = $response->json();

            return $result['success'] ?? false;
        } catch (\Exception $e) {
            \Log::error('hCaptcha verification failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get reCAPTCHA site key
     */
    public function getRecaptchaSiteKey(): ?string
    {
        return config('services.recaptcha.site_key');
    }

    /**
     * Get reCAPTCHA v3 site key
     */
    public function getRecaptchaV3SiteKey(): ?string
    {
        return config('services.recaptcha_v3.site_key');
    }

    /**
     * Get hCaptcha site key
     */
    public function getHCaptchaSiteKey(): ?string
    {
        return config('services.hcaptcha.site_key');
    }

    /**
     * Check if any captcha is enabled
     */
    public function isEnabled(): bool
    {
        return config('services.recaptcha.enabled', false) ||
               config('services.recaptcha_v3.enabled', false) ||
               config('services.hcaptcha.enabled', false);
    }
}
