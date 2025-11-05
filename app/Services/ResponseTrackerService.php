<?php

namespace App\Services;

use Wave\Plugins\EvenLeads\Models\Lead;
use Wave\Plugins\EvenLeads\Services\RedditService;
use Wave\Plugins\EvenLeads\Services\XService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ResponseTrackerService
{
    protected $redditService;
    protected $xService;

    public function __construct()
    {
        $this->redditService = app(RedditService::class);
        $this->xService = app(XService::class);
    }

    /**
     * Check for responses on a single lead
     * Returns true if response was detected
     */
    public function checkForResponses(Lead $lead): bool
    {
        // Skip if already has response
        if ($lead->response_received_at) {
            return false;
        }

        // Skip if not contacted
        if ($lead->status !== 'contacted' || !$lead->last_contact_at) {
            return false;
        }

        // Update last checked timestamp
        $lead->last_checked_for_response_at = now();
        $lead->saveQuietly();

        // Check based on channel
        try {
            $hasResponse = false;

            if ($lead->last_contact_channel === 'comment') {
                $hasResponse = $this->detectCommentResponse($lead);
            } elseif ($lead->last_contact_channel === 'dm') {
                $hasResponse = $this->detectDMResponse($lead);
            }

            if ($hasResponse) {
                $this->markLeadAsResponded($lead);
                return true;
            }
        } catch (\Exception $e) {
            Log::error('Error checking responses for lead', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);
        }

        return false;
    }

    /**
     * Batch check responses for multiple leads (rate limit optimized)
     * Returns count of new responses detected
     */
    public function batchCheckResponses(Collection $leads): int
    {
        $newResponsesCount = 0;

        // Group leads by platform for efficient batching
        $leadsByPlatform = $leads->groupBy('platform');

        foreach ($leadsByPlatform as $platform => $platformLeads) {
            // Limit to 10 leads per platform to respect rate limits
            $platformLeads = $platformLeads->take(10);

            foreach ($platformLeads as $lead) {
                if ($this->checkForResponses($lead)) {
                    $newResponsesCount++;
                }

                // Small delay between checks to avoid rate limits (100ms)
                usleep(100000);
            }
        }

        return $newResponsesCount;
    }

    /**
     * Detect if our comment got a reply
     */
    protected function detectCommentResponse(Lead $lead): bool
    {
        // Get the platform service
        $service = $this->getServiceForPlatform($lead->platform);
        if (!$service) {
            return false;
        }

        // For Reddit: Check if the post/comment we replied to has new replies
        if ($lead->platform === 'reddit') {
            return $this->checkRedditCommentResponse($lead);
        }

        // For X: Check if our tweet got replies
        if ($lead->platform === 'x') {
            return $this->checkXCommentResponse($lead);
        }

        return false;
    }

    /**
     * Detect if we got a DM response
     */
    protected function detectDMResponse(Lead $lead): bool
    {
        // Get the platform service
        $service = $this->getServiceForPlatform($lead->platform);
        if (!$service) {
            return false;
        }

        // For Reddit: Check DM inbox
        if ($lead->platform === 'reddit') {
            return $this->checkRedditDMResponse($lead);
        }

        // For X: Check DM inbox
        if ($lead->platform === 'x') {
            return $this->checkXDMResponse($lead);
        }

        return false;
    }

    /**
     * Check Reddit comment for responses
     */
    protected function checkRedditCommentResponse(Lead $lead): bool
    {
        // TODO: Implement Reddit comment response detection
        // This would use Reddit API to check if the comment/post we replied to has new replies
        // For now, return false (manual marking only)
        return false;
    }

    /**
     * Check Reddit DM for responses
     */
    protected function checkRedditDMResponse(Lead $lead): bool
    {
        // TODO: Implement Reddit DM response detection
        // This would check the DM conversation for new messages from the lead
        // For now, return false (manual marking only)
        return false;
    }

    /**
     * Check X comment/tweet for responses
     */
    protected function checkXCommentResponse(Lead $lead): bool
    {
        // TODO: Implement X comment response detection
        // This would use X API to check if our tweet/reply got responses
        // For now, return false (manual marking only)
        return false;
    }

    /**
     * Check X DM for responses
     */
    protected function checkXDMResponse(Lead $lead): bool
    {
        try {
            // Check if user has X API configured
            $user = $lead->user;
            if (!$user || !$user->x_use_custom_api) {
                // Can't check without API access
                return false;
            }

            // Get the lead's conversation/thread ID
            if (empty($lead->conversation_id) && empty($lead->author_id)) {
                // No conversation/author info to check
                return false;
            }

            // Get the user's X connection
            $connection = \Wave\Plugins\EvenLeads\Models\PlatformConnection::where('user_id', $user->id)
                ->where('platform', 'x')
                ->where('status', 'active')
                ->first();

            if (!$connection) {
                return false;
            }

            // Check if there are new messages in the conversation after our last contact
            $hasNewMessages = $this->xService->checkForNewDMMessages(
                $connection,
                $lead->conversation_id ?? $lead->author_id,
                $lead->last_contact_at
            );

            return $hasNewMessages;
        } catch (\Exception $e) {
            Log::error('Error checking X DM response', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Mark lead as responded and update stats
     */
    protected function markLeadAsResponded(Lead $lead): void
    {
        $lead->response_received_at = now();
        $lead->response_count++;
        $lead->updateEngagementScore();
        $lead->save();

        Log::info('Lead marked as responded', [
            'lead_id' => $lead->id,
            'platform' => $lead->platform,
            'channel' => $lead->last_contact_channel,
            'response_time_hours' => $lead->getResponseTime(),
        ]);
    }

    /**
     * Get service for platform
     */
    protected function getServiceForPlatform(string $platform)
    {
        return match($platform) {
            'reddit' => $this->redditService,
            'x' => $this->xService,
            default => null,
        };
    }
}
