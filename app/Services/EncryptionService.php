<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;

class EncryptionService
{
    /**
     * Encrypt data
     */
    public function encrypt($data): string
    {
        if (is_array($data) || is_object($data)) {
            $data = json_encode($data);
        }

        return Crypt::encryptString((string)$data);
    }

    /**
     * Decrypt data
     */
    public function decrypt(string $encrypted)
    {
        $decrypted = Crypt::decryptString($encrypted);

        // Try to decode as JSON
        $json = json_decode($decrypted, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $json;
        }

        return $decrypted;
    }

    /**
     * Encrypt array
     */
    public function encryptArray(array $data): string
    {
        return $this->encrypt($data);
    }

    /**
     * Decrypt to array
     */
    public function decryptToArray(string $encrypted): array
    {
        $decrypted = $this->decrypt($encrypted);
        return is_array($decrypted) ? $decrypted : [];
    }

    /**
     * Hash data (one-way)
     */
    public function hash(string $data): string
    {
        return hash('sha256', $data);
    }

    /**
     * Hash data with salt
     */
    public function hashWithSalt(string $data, string $salt): string
    {
        return hash('sha256', $data . $salt);
    }

    /**
     * Verify hash
     */
    public function verifyHash(string $data, string $hash, ?string $salt = null): bool
    {
        if ($salt) {
            return hash_equals($hash, $this->hashWithSalt($data, $salt));
        }

        return hash_equals($hash, $this->hash($data));
    }

    /**
     * Generate secure random string
     */
    public function generateRandomString(int $length = 32): string
    {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * Generate secure token
     */
    public function generateToken(int $length = 64): string
    {
        return $this->generateRandomString($length);
    }

    /**
     * Encrypt file
     */
    public function encryptFile(string $sourcePath, string $destinationPath): bool
    {
        try {
            $data = file_get_contents($sourcePath);
            $encrypted = $this->encrypt($data);
            return file_put_contents($destinationPath, $encrypted) !== false;
        } catch (\Exception $e) {
            \Log::error('File encryption failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Decrypt file
     */
    public function decryptFile(string $sourcePath, string $destinationPath): bool
    {
        try {
            $encrypted = file_get_contents($sourcePath);
            $decrypted = $this->decrypt($encrypted);
            return file_put_contents($destinationPath, $decrypted) !== false;
        } catch (\Exception $e) {
            \Log::error('File decryption failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Encrypt sensitive database field
     */
    public function encryptField($value): ?string
    {
        if ($value === null) {
            return null;
        }

        return $this->encrypt($value);
    }

    /**
     * Decrypt sensitive database field
     */
    public function decryptField(?string $value)
    {
        if ($value === null) {
            return null;
        }

        try {
            return $this->decrypt($value);
        } catch (\Exception $e) {
            \Log::error('Field decryption failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate HMAC signature
     */
    public function generateHmac(string $data, string $key): string
    {
        return hash_hmac('sha256', $data, $key);
    }

    /**
     * Verify HMAC signature
     */
    public function verifyHmac(string $data, string $signature, string $key): bool
    {
        $expected = $this->generateHmac($data, $key);
        return hash_equals($expected, $signature);
    }

    /**
     * Secure string comparison
     */
    public function secureCompare(string $a, string $b): bool
    {
        return hash_equals($a, $b);
    }
}
