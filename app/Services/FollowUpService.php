<?php

namespace App\Services;

use Wave\Plugins\EvenLeads\Models\Lead;
use App\Models\LeadMessage;
use Wave\Plugins\EvenLeads\Services\AIReplyService;
use Wave\Plugins\EvenLeads\Services\RedditService;
use Wave\Plugins\EvenLeads\Services\XService;
use Illuminate\Support\Facades\Log;

class FollowUpService
{
    protected $aiReplyService;
    protected $redditService;
    protected $xService;

    public function __construct()
    {
        $this->aiReplyService = app(AIReplyService::class);
        $this->redditService = app(RedditService::class);
        $this->xService = app(XService::class);
    }

    /**
     * Schedule a follow-up message for a lead
     *
     * @param Lead $lead
     * @param int $daysFromNow Days to wait before sending (default: 3)
     * @param string $mode 'ai' or 'template'
     * @param string|null $templateMessage Predefined message (required if mode is 'template')
     * @param bool $useVariations Whether to use AI variations of the template (only for template mode)
     * @return LeadMessage
     */
    public function scheduleFollowUp(
        Lead $lead,
        int $daysFromNow = 3,
        string $mode = 'ai',
        ?string $templateMessage = null,
        bool $useVariations = false
    ): LeadMessage {
        // Get the parent message (last message we sent)
        $parentMessage = $lead->messages()
            ->where('direction', 'outgoing')
            ->latest()
            ->first();

        // Determine the message text
        if ($mode === 'template' && $templateMessage && !$useVariations) {
            // Use template as-is (no variations)
            $messageText = $templateMessage;
            $isAiGenerated = false;
        } elseif ($mode === 'template' && $templateMessage && $useVariations) {
            // Store template, but mark as AI-generated so it creates variations at send time
            $messageText = $templateMessage; // Store template for reference
            $isAiGenerated = true; // Will generate variation at send time
        } else {
            // Pure AI mode - generate at send time
            $messageText = null;
            $isAiGenerated = true;
        }

        // Create the follow-up message
        $followUpMessage = LeadMessage::create([
            'user_id' => $lead->user_id,
            'lead_id' => $lead->id,
            'message_text' => $messageText,
            'direction' => 'outgoing',
            'channel' => $lead->last_contact_channel ?? 'comment',
            'status' => 'draft',
            'is_ai_generated' => $isAiGenerated,
            'is_follow_up' => true,
            'parent_message_id' => $parentMessage?->id,
            'scheduled_send_at' => now()->addDays($daysFromNow),
        ]);

        Log::info('Follow-up scheduled', [
            'lead_id' => $lead->id,
            'follow_up_id' => $followUpMessage->id,
            'scheduled_at' => $followUpMessage->scheduled_send_at,
            'mode' => $mode,
        ]);

        return $followUpMessage;
    }

    /**
     * Send a scheduled follow-up message
     *
     * @param LeadMessage $followUpMessage
     * @return bool Success status
     */
    public function sendFollowUp(LeadMessage $followUpMessage): bool
    {
        $lead = $followUpMessage->lead;

        // Check if lead has already responded - skip if yes
        if ($lead->response_received_at) {
            Log::info('Skipping follow-up - lead already responded', [
                'lead_id' => $lead->id,
                'follow_up_id' => $followUpMessage->id,
            ]);

            // Mark as cancelled
            $followUpMessage->update(['status' => 'cancelled']);
            return false;
        }

        // Generate AI message if needed
        if ($followUpMessage->is_ai_generated && !$followUpMessage->message_text) {
            try {
                $messageText = $this->generateFollowUpMessage($lead, $followUpMessage);
                $followUpMessage->message_text = $messageText;
            } catch (\Exception $e) {
                Log::error('Failed to generate AI follow-up message', [
                    'lead_id' => $lead->id,
                    'follow_up_id' => $followUpMessage->id,
                    'error' => $e->getMessage(),
                ]);

                $followUpMessage->update(['status' => 'failed']);
                return false;
            }
        }

        // Send the message via appropriate platform
        try {
            $success = $this->sendMessage($lead, $followUpMessage);

            if ($success) {
                $followUpMessage->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);

                // Update lead's last contact
                $lead->last_contact_at = now();
                $lead->saveQuietly();

                Log::info('Follow-up sent successfully', [
                    'lead_id' => $lead->id,
                    'follow_up_id' => $followUpMessage->id,
                    'channel' => $followUpMessage->channel,
                ]);

                return true;
            }

            $followUpMessage->update(['status' => 'failed']);
            return false;

        } catch (\Exception $e) {
            Log::error('Failed to send follow-up message', [
                'lead_id' => $lead->id,
                'follow_up_id' => $followUpMessage->id,
                'error' => $e->getMessage(),
            ]);

            $followUpMessage->update(['status' => 'failed']);
            return false;
        }
    }

    /**
     * Generate AI-powered follow-up message
     */
    protected function generateFollowUpMessage(Lead $lead, LeadMessage $followUpMessage): string
    {
        // Get parent message for context
        $parentMessage = $followUpMessage->parentMessage;

        // Check if this is a template variation request
        $isTemplateVariation = !empty($followUpMessage->message_text); // Template was stored

        if ($isTemplateVariation) {
            // Generate a variation of the template
            $prompt = "Create a variation of the following follow-up message template. Keep the same meaning and intent, but rephrase it differently.\n\n";
            $prompt .= "Original Template:\n{$followUpMessage->message_text}\n\n";
            $prompt .= "Context:\n";
            $prompt .= "- Lead Post: {$lead->title}\n";
            $prompt .= "- Our Campaign: " . ($lead->campaign->offering ?? 'N/A') . "\n";
            $prompt .= "\nGenerate a unique variation that:\n";
            $prompt .= "1. Maintains the same tone and intent\n";
            $prompt .= "2. Uses different phrasing and structure\n";
            $prompt .= "3. Stays approximately the same length\n";
            $prompt .= "4. Sounds natural and personal\n\n";
            $prompt .= "Variation:";
        } else {
            // Pure AI generation
            $prompt = "Generate a brief, friendly follow-up message to a potential lead who hasn't responded yet.\n\n";
            $prompt .= "Context:\n";
            $prompt .= "- Lead Post Title: {$lead->title}\n";
            $prompt .= "- Our Campaign: " . ($lead->campaign->offering ?? 'N/A') . "\n";

            if ($parentMessage) {
                $prompt .= "- Our Previous Message: {$parentMessage->message_text}\n";
            }

            $prompt .= "\nGenerate a short follow-up message (2-3 sentences max) that:\n";
            $prompt .= "1. Is friendly and non-pushy\n";
            $prompt .= "2. Adds value or asks if they found a solution\n";
            $prompt .= "3. Keeps the door open for conversation\n";
            $prompt .= "4. Does NOT repeat the previous message\n\n";
            $prompt .= "Follow-up message:";
        }

        // Use AI service to generate
        $response = $this->aiReplyService->generateCustomReply($prompt, $lead);

        return $response;
    }

    /**
     * Send message via appropriate platform and channel
     */
    protected function sendMessage(Lead $lead, LeadMessage $message): bool
    {
        if ($lead->platform === 'reddit') {
            return $this->sendRedditMessage($lead, $message);
        }

        if ($lead->platform === 'x') {
            return $this->sendXMessage($lead, $message);
        }

        return false;
    }

    /**
     * Send message via Reddit
     */
    protected function sendRedditMessage(Lead $lead, LeadMessage $message): bool
    {
        try {
            if ($message->channel === 'dm') {
                $this->redditService->sendMessage($lead->user_id, $lead->author, $message->message_text);
            } else {
                // Comment reply
                $this->redditService->postComment($lead->user_id, $lead->post_id, $message->message_text);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send Reddit message', [
                'lead_id' => $lead->id,
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send message via X
     */
    protected function sendXMessage(Lead $lead, LeadMessage $message): bool
    {
        try {
            if ($message->channel === 'dm') {
                $this->xService->sendDirectMessage($lead->user_id, $lead->author_id, $message->message_text);
            } else {
                // Tweet reply
                $this->xService->replyToTweet($lead->user_id, $lead->post_id, $message->message_text);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send X message', [
                'lead_id' => $lead->id,
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Mark lead as responded (can be called manually)
     */
    protected function markLeadAsResponded(Lead $lead): void
    {
        if (!$lead->response_received_at) {
            $lead->response_received_at = now();
        }

        $lead->response_count++;
        $lead->updateEngagementScore();
        $lead->save();
    }
}
