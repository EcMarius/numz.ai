<?php

namespace App\Services\GrowthHacking;

use App\Models\User;
use App\Models\GrowthHackingProspect;
use App\Models\GrowthHackingLead;
use Wave\Plugins\EvenLeads\Models\Setting;
use Wave\Plugins\EvenLeads\Models\Lead;
use Wave\Plugins\EvenLeads\Models\Campaign;
use Illuminate\Support\Facades\Log;

class TrialActivationService
{
    /**
     * Activate trial and copy leads on first login
     */
    public function activateTrialOnFirstLogin(User $user): void
    {
        try {
            // Check if this is a growth hack account
            if (!$user->is_growth_hack_account || $user->trial_activated_at) {
                return; // Already activated or not a growth hack account
            }

            // Check if site has trial enabled
            $trialDays = Setting::getValue('trial_days', 0);

            if ($trialDays > 0) {
                // Activate trial
                $user->trial_ends_at = now()->addDays($trialDays);
                $user->trial_activated_at = now();
                $user->save();

                Log::info("Trial activated for growth hack user", [
                    'user_id' => $user->id,
                    'trial_days' => $trialDays,
                    'trial_ends_at' => $user->trial_ends_at,
                ]);
            }

            // Copy growth hacking leads to their EvenLeads account
            $this->copyLeadsToAccount($user);

            // Update campaign stats
            $prospect = $user->growthHackProspect;
            if ($prospect) {
                $prospect->campaign->increment('logged_in_count');
                $prospect->update(['status' => 'logged_in']);
            }

        } catch (\Exception $e) {
            Log::error("Failed to activate trial for user {$user->id}", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Copy growth hacking leads to user's EvenLeads account
     */
    protected function copyLeadsToAccount(User $user): void
    {
        try {
            $prospect = $user->growthHackProspect;

            if (!$prospect) {
                return;
            }

            // Get or create campaign for user
            $campaign = Campaign::where('user_id', $user->id)->first();

            if (!$campaign) {
                // Create default campaign
                $campaign = Campaign::create([
                    'user_id' => $user->id,
                    'name' => 'Lead Generation',
                    'offering' => 'My product/service',
                    'keywords' => [],
                    'platforms' => ['reddit'],
                    'status' => 'active',
                ]);
            }

            // Get growth hacking leads that haven't been copied yet
            $ghLeads = GrowthHackingLead::where('prospect_id', $prospect->id)
                ->where('copied_to_account', false)
                ->get();

            foreach ($ghLeads as $ghLead) {
                // Create lead in EvenLeads system
                $leadData = $ghLead->lead_data;

                Lead::create([
                    'campaign_id' => $campaign->id,
                    'user_id' => $user->id,
                    'platform' => $leadData['platform'] ?? 'reddit',
                    'title' => $leadData['title'] ?? '',
                    'description' => $leadData['description'] ?? '',
                    'author' => $leadData['author'] ?? '',
                    'url' => $leadData['url'] ?? '',
                    'confidence_score' => $ghLead->confidence_score,
                    'subreddit' => $leadData['subreddit'] ?? null,
                    'found_at' => now(),
                ]);

                // Mark as copied
                $ghLead->update([
                    'copied_to_account' => true,
                    'user_id' => $user->id,
                    'campaign_id' => $campaign->id,
                ]);
            }

            Log::info("Copied growth hack leads to user account", [
                'user_id' => $user->id,
                'leads_copied' => $ghLeads->count(),
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to copy leads to account for user {$user->id}", [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
