<?php

namespace App\Services;

class PasswordPolicy
{
    /**
     * Validate password against policy
     */
    public function validate(string $password): array
    {
        $errors = [];

        // Minimum length
        $minLength = config('security.password.min_length', 8);
        if (strlen($password) < $minLength) {
            $errors[] = "Password must be at least {$minLength} characters long.";
        }

        // Maximum length
        $maxLength = config('security.password.max_length', 128);
        if (strlen($password) > $maxLength) {
            $errors[] = "Password must not exceed {$maxLength} characters.";
        }

        // Require uppercase
        if (config('security.password.require_uppercase', true)) {
            if (!preg_match('/[A-Z]/', $password)) {
                $errors[] = 'Password must contain at least one uppercase letter.';
            }
        }

        // Require lowercase
        if (config('security.password.require_lowercase', true)) {
            if (!preg_match('/[a-z]/', $password)) {
                $errors[] = 'Password must contain at least one lowercase letter.';
            }
        }

        // Require number
        if (config('security.password.require_number', true)) {
            if (!preg_match('/[0-9]/', $password)) {
                $errors[] = 'Password must contain at least one number.';
            }
        }

        // Require special character
        if (config('security.password.require_special', true)) {
            if (!preg_match('/[^A-Za-z0-9]/', $password)) {
                $errors[] = 'Password must contain at least one special character.';
            }
        }

        // Check against common passwords
        if (config('security.password.check_common', true)) {
            if ($this->isCommonPassword($password)) {
                $errors[] = 'Password is too common. Please choose a more unique password.';
            }
        }

        // Check for sequential characters
        if (config('security.password.check_sequential', false)) {
            if ($this->hasSequentialCharacters($password)) {
                $errors[] = 'Password contains sequential characters. Please choose a more secure password.';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Check if password is common
     */
    protected function isCommonPassword(string $password): bool
    {
        $commonPasswords = [
            'password', 'password123', '12345678', 'qwerty', 'abc123',
            'monkey', '1234567', 'letmein', 'trustno1', 'dragon',
            'baseball', 'iloveyou', 'master', 'sunshine', 'ashley',
            'bailey', 'passw0rd', 'shadow', '123123', '654321',
            'superman', 'qazwsx', 'michael', 'football', 'welcome',
        ];

        return in_array(strtolower($password), $commonPasswords);
    }

    /**
     * Check for sequential characters
     */
    protected function hasSequentialCharacters(string $password): bool
    {
        // Check for sequential numbers
        if (preg_match('/012|123|234|345|456|567|678|789/', $password)) {
            return true;
        }

        // Check for sequential letters
        if (preg_match('/abc|bcd|cde|def|efg|fgh|ghi|hij|ijk|jkl|klm|lmn|mno|nop|opq|pqr|qrs|rst|stu|tuv|uvw|vwx|wxy|xyz/i', $password)) {
            return true;
        }

        // Check for keyboard patterns
        if (preg_match('/qwerty|asdfgh|zxcvbn/i', $password)) {
            return true;
        }

        return false;
    }

    /**
     * Generate a secure password
     */
    public function generate(int $length = 16): string
    {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $special = '!@#$%^&*()_+-=[]{}|;:,.<>?';

        $all = $uppercase . $lowercase . $numbers . $special;

        $password = '';
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $special[random_int(0, strlen($special) - 1)];

        for ($i = 4; $i < $length; $i++) {
            $password .= $all[random_int(0, strlen($all) - 1)];
        }

        return str_shuffle($password);
    }

    /**
     * Calculate password strength
     */
    public function calculateStrength(string $password): array
    {
        $strength = 0;
        $feedback = [];

        // Length
        $length = strlen($password);
        if ($length >= 8) {
            $strength += 20;
        }
        if ($length >= 12) {
            $strength += 10;
        }
        if ($length >= 16) {
            $strength += 10;
        }

        // Character variety
        if (preg_match('/[a-z]/', $password)) {
            $strength += 15;
        } else {
            $feedback[] = 'Add lowercase letters';
        }

        if (preg_match('/[A-Z]/', $password)) {
            $strength += 15;
        } else {
            $feedback[] = 'Add uppercase letters';
        }

        if (preg_match('/[0-9]/', $password)) {
            $strength += 15;
        } else {
            $feedback[] = 'Add numbers';
        }

        if (preg_match('/[^A-Za-z0-9]/', $password)) {
            $strength += 15;
        } else {
            $feedback[] = 'Add special characters';
        }

        // Deductions
        if ($this->isCommonPassword($password)) {
            $strength -= 20;
            $feedback[] = 'Avoid common passwords';
        }

        if ($this->hasSequentialCharacters($password)) {
            $strength -= 10;
            $feedback[] = 'Avoid sequential characters';
        }

        $strength = max(0, min(100, $strength));

        return [
            'score' => $strength,
            'level' => $this->getStrengthLevel($strength),
            'feedback' => $feedback,
        ];
    }

    /**
     * Get strength level
     */
    protected function getStrengthLevel(int $score): string
    {
        if ($score >= 80) {
            return 'strong';
        } elseif ($score >= 60) {
            return 'good';
        } elseif ($score >= 40) {
            return 'fair';
        } else {
            return 'weak';
        }
    }

    /**
     * Check password history
     */
    public function checkHistory($user, string $password): bool
    {
        // This would require a password_history table
        // For now, we'll just check the current password

        if (!$user) {
            return true;
        }

        return !\Hash::check($password, $user->password);
    }
}
