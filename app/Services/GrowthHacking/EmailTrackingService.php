<?php

namespace App\Services\GrowthHacking;

use App\Models\GrowthHackingEmail;
use Illuminate\Support\Facades\Log;

class EmailTrackingService
{
    /**
     * Track email open
     */
    public function trackOpen(string $token): void
    {
        try {
            $email = GrowthHackingEmail::where('unsubscribe_token', $token)->first();

            if ($email) {
                $email->markAsOpened();

                Log::info("Email opened", [
                    'email_id' => $email->id,
                    'campaign_id' => $email->campaign_id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Failed to track email open", [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Track email click
     */
    public function trackClick(string $token, string $url): string
    {
        try {
            $email = GrowthHackingEmail::where('unsubscribe_token', $token)->first();

            if ($email) {
                $email->markAsClicked();

                Log::info("Email link clicked", [
                    'email_id' => $email->id,
                    'campaign_id' => $email->campaign_id,
                    'url' => $url,
                ]);
            }

            // Redirect to original URL
            return $url;

        } catch (\Exception $e) {
            Log::error("Failed to track email click", [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);

            return $url;
        }
    }

    /**
     * Track bounce (webhook from email provider)
     */
    public function trackBounce(string $emailAddress, string $reason): void
    {
        try {
            $email = GrowthHackingEmail::where('email_address', $emailAddress)
                ->whereNull('bounce_reason')
                ->latest()
                ->first();

            if ($email) {
                $email->markAsBounced($reason);

                Log::warning("Email bounced", [
                    'email_id' => $email->id,
                    'email_address' => $emailAddress,
                    'reason' => $reason,
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Failed to track bounce", [
                'email_address' => $emailAddress,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
