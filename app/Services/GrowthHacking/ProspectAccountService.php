<?php

namespace App\Services\GrowthHacking;

use App\Models\User;
use App\Models\GrowthHackingProspect;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ProspectAccountService
{
    /**
     * Create user account for a prospect
     */
    public function createProspectAccount(GrowthHackingProspect $prospect): ?User
    {
        try {
            $email = $prospect->primary_email;

            if (!$email) {
                throw new \Exception('No email available for prospect');
            }

            // Check if user already exists
            $existingUser = User::where('email', $email)->first();

            if ($existingUser) {
                // Link existing user to prospect
                $prospect->update(['user_id' => $existingUser->id]);
                return $existingUser;
            }

            // Generate secure random password (will be reset via token)
            $randomPassword = Str::random(32);

            // Create user account
            $user = User::create([
                'name' => $prospect->display_name,
                'email' => $email,
                'password' => Hash::make($randomPassword),
                'email_verified_at' => now(), // Auto-verify
                'growth_hack_prospect_id' => $prospect->id,
                'is_growth_hack_account' => true,
                // DO NOT activate trial yet - only on first login
            ]);

            // Generate secure token for password setup
            $prospect->generateSecureToken();
            $prospect->update(['user_id' => $user->id]);

            // Update campaign stats
            $prospect->campaign->increment('accounts_created');

            Log::info("Prospect account created", [
                'prospect_id' => $prospect->id,
                'user_id' => $user->id,
                'email' => $email,
            ]);

            return $user;

        } catch (\Exception $e) {
            Log::error("Failed to create prospect account for {$prospect->id}", [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Set password for prospect account via token
     */
    public function setPasswordViaToken(string $token, string $password): array
    {
        try {
            $prospect = GrowthHackingProspect::where('secure_token', $token)->first();

            if (!$prospect) {
                return [
                    'success' => false,
                    'error' => 'Invalid token',
                ];
            }

            if (!$prospect->isTokenValid()) {
                return [
                    'success' => false,
                    'error' => 'Token expired',
                ];
            }

            $user = $prospect->user;

            if (!$user) {
                return [
                    'success' => false,
                    'error' => 'User account not found',
                ];
            }

            // Set new password
            $user->update([
                'password' => Hash::make($password),
            ]);

            // Clear token
            $prospect->update([
                'secure_token' => null,
                'token_expires_at' => null,
                'status' => 'logged_in',
            ]);

            return [
                'success' => true,
                'user' => $user,
            ];

        } catch (\Exception $e) {
            Log::error("Failed to set password via token: {$token}", [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'An error occurred',
            ];
        }
    }
}
