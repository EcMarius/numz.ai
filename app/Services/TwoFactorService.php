<?php

namespace App\Services;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageRenderer;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use Twilio\Rest\Client as TwilioClient;

class TwoFactorService
{
    protected Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * Generate a secret key for TOTP
     */
    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    /**
     * Generate QR code for Google Authenticator
     */
    public function generateQrCode(User $user, string $secret): string
    {
        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        $renderer = new ImageRenderer(
            new RendererStyle(250),
            new SvgImageRenderer()
        );

        $writer = new Writer($renderer);
        return $writer->writeString($qrCodeUrl);
    }

    /**
     * Generate backup/recovery codes
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = Str::upper(Str::random(4) . '-' . Str::random(4) . '-' . Str::random(4));
        }
        return $codes;
    }

    /**
     * Encrypt recovery codes for storage
     */
    public function encryptRecoveryCodes(array $codes): string
    {
        return Crypt::encryptString(json_encode($codes));
    }

    /**
     * Decrypt recovery codes
     */
    public function decryptRecoveryCodes(string $encrypted): array
    {
        return json_decode(Crypt::decryptString($encrypted), true);
    }

    /**
     * Verify TOTP code
     */
    public function verifyCode(string $secret, string $code): bool
    {
        return $this->google2fa->verifyKey($secret, $code);
    }

    /**
     * Verify recovery code
     */
    public function verifyRecoveryCode(User $user, string $code): bool
    {
        if (!$user->two_factor_recovery_codes) {
            return false;
        }

        $recoveryCodes = $this->decryptRecoveryCodes($user->two_factor_recovery_codes);

        if (in_array($code, $recoveryCodes)) {
            // Remove used recovery code
            $recoveryCodes = array_diff($recoveryCodes, [$code]);
            $user->two_factor_recovery_codes = $this->encryptRecoveryCodes(array_values($recoveryCodes));
            $user->save();

            return true;
        }

        return false;
    }

    /**
     * Enable 2FA for user
     */
    public function enable(User $user, string $secret, array $recoveryCodes): void
    {
        $user->two_factor_secret = Crypt::encryptString($secret);
        $user->two_factor_recovery_codes = $this->encryptRecoveryCodes($recoveryCodes);
        $user->two_factor_confirmed_at = now();
        $user->save();
    }

    /**
     * Disable 2FA for user
     */
    public function disable(User $user): void
    {
        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->save();
    }

    /**
     * Check if user has 2FA enabled
     */
    public function isEnabled(User $user): bool
    {
        return !is_null($user->two_factor_confirmed_at);
    }

    /**
     * Get decrypted secret for user
     */
    public function getSecret(User $user): ?string
    {
        if (!$user->two_factor_secret) {
            return null;
        }

        return Crypt::decryptString($user->two_factor_secret);
    }

    /**
     * Send SMS code via Twilio
     */
    public function sendSmsCode(User $user, string $phoneNumber): bool
    {
        if (!config('services.twilio.enabled', false)) {
            return false;
        }

        try {
            $code = rand(100000, 999999);

            // Store code in cache for 5 minutes
            Cache::put("2fa_sms_{$user->id}", $code, now()->addMinutes(5));

            $twilio = new TwilioClient(
                config('services.twilio.sid'),
                config('services.twilio.token')
            );

            $twilio->messages->create($phoneNumber, [
                'from' => config('services.twilio.from'),
                'body' => "Your " . config('app.name') . " verification code is: {$code}"
            ]);

            return true;
        } catch (\Exception $e) {
            \Log::error('2FA SMS sending failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify SMS code
     */
    public function verifySmsCode(User $user, string $code): bool
    {
        $cachedCode = Cache::get("2fa_sms_{$user->id}");

        if ($cachedCode && $cachedCode == $code) {
            Cache::forget("2fa_sms_{$user->id}");
            return true;
        }

        return false;
    }

    /**
     * Send email code
     */
    public function sendEmailCode(User $user): bool
    {
        try {
            $code = rand(100000, 999999);

            // Store code in cache for 10 minutes
            Cache::put("2fa_email_{$user->id}", $code, now()->addMinutes(10));

            // Send email
            $user->notify(new \App\Notifications\TwoFactorCodeNotification($code));

            return true;
        } catch (\Exception $e) {
            \Log::error('2FA Email sending failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify email code
     */
    public function verifyEmailCode(User $user, string $code): bool
    {
        $cachedCode = Cache::get("2fa_email_{$user->id}");

        if ($cachedCode && $cachedCode == $code) {
            Cache::forget("2fa_email_{$user->id}");
            return true;
        }

        return false;
    }

    /**
     * Check if 2FA is required for user
     */
    public function isRequired(User $user): bool
    {
        // Check if 2FA is enforced globally
        if (config('auth.2fa.enforce', false)) {
            return true;
        }

        // Check if enforced for specific roles
        $enforceForRoles = config('auth.2fa.enforce_for_roles', []);
        if (!empty($enforceForRoles) && $user->hasAnyRole($enforceForRoles)) {
            return true;
        }

        return false;
    }

    /**
     * Regenerate recovery codes
     */
    public function regenerateRecoveryCodes(User $user): array
    {
        $codes = $this->generateRecoveryCodes();
        $user->two_factor_recovery_codes = $this->encryptRecoveryCodes($codes);
        $user->save();

        return $codes;
    }

    /**
     * Get remaining recovery codes count
     */
    public function getRemainingRecoveryCodesCount(User $user): int
    {
        if (!$user->two_factor_recovery_codes) {
            return 0;
        }

        $codes = $this->decryptRecoveryCodes($user->two_factor_recovery_codes);
        return count($codes);
    }
}
