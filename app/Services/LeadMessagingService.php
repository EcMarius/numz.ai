<?php

namespace App\Services;

use App\Models\LeadMessage;
use App\Models\User;
use Wave\Plugins\EvenLeads\Models\Lead;
use Wave\Plugins\EvenLeads\Services\AIReplyService;
use Wave\Plugins\EvenLeads\Services\PlanLimitService;
use Illuminate\Support\Facades\Log;

class LeadMessagingService
{
    public function checkAIChatAccess(User $user): bool
    {
        if (!$user->subscription('default')) {
            return false;
        }

        $plan = $user->subscription('default')->plan;
        return $plan->getCustomProperty('evenleads.ai_chat_access', false);
    }

    public function getMessageHistory(Lead $lead): array
    {
        return LeadMessage::where('lead_id', $lead->id)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'text' => $message->message_text,
                    'direction' => $message->direction,
                    'sent_at' => $message->sent_at?->format('M j, g:i A'),
                    'is_ai' => $message->is_ai_generated,
                    'status' => $message->status,
                ];
            })->toArray();
    }

    public function sendMessageToLead(Lead $lead, string $messageText, int $userId, string $channel = 'comment'): ?LeadMessage
    {
        // Create message record
        $message = LeadMessage::create([
            'user_id' => $userId,
            'lead_id' => $lead->id,
            'message_text' => $messageText,
            'direction' => 'outgoing',
            'channel' => $channel,
            'status' => 'draft',
            'is_ai_generated' => false,
        ]);

        // Send via platform API (placeholder - implement based on platform)
        try {
            // TODO: Implement actual sending based on lead platform
            // For now, just mark as sent
            $message->markAsSent();

            // Update lead status and tracking
            $lead->markContacted($channel);

            // Check if campaign has follow-ups enabled and schedule
            if ($lead->campaign && $lead->campaign->follow_up_enabled) {
                $followUpService = app(\App\Services\FollowUpService::class);
                $followUpService->scheduleFollowUp(
                    $lead,
                    $lead->campaign->follow_up_days ?? 3,
                    $lead->campaign->follow_up_mode ?? 'ai',
                    $lead->campaign->follow_up_template
                );
            }

            return $message;
        } catch (\Exception $e) {
            Log::error('Failed to send message to lead', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);

            $message->markAsFailed();
            return null;
        }
    }

    public function generateAIMessage(Lead $lead, ?string $context = null): array
    {
        $userId = $lead->user_id;
        $user = User::find($userId);

        // Check AI chat access
        if (!$this->checkAIChatAccess($user)) {
            return [
                'success' => false,
                'message' => null,
                'error' => 'AI Chat is not available on your current plan. Upgrade to Business or Enterprise plan.',
            ];
        }

        // Check AI reply limits
        $limitService = app(PlanLimitService::class);
        if (!$limitService->canGenerateAIReply($user)) {
            return [
                'success' => false,
                'message' => null,
                'error' => $limitService->getLimitMessage('ai_replies'),
            ];
        }

        try {
            $aiService = new AIReplyService($userId);

            if (!$aiService->canGenerate()) {
                return [
                    'success' => false,
                    'message' => null,
                    'error' => 'Daily AI generation limit reached.',
                ];
            }

            // Build prompt for direct message
            $prompt = $this->buildDirectMessagePrompt($lead, $context);

            $response = $aiService->generateCustom($prompt, [
                'max_tokens' => 300,
                'temperature' => 0.7,
            ]);

            // Record generation for limits
            $limitService->recordAIGeneration($user, $lead->id, $aiService->getModel());

            return [
                'success' => true,
                'message' => $response['reply'],
                'error' => null,
            ];

        } catch (\Exception $e) {
            Log::error('AI message generation failed', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => null,
                'error' => 'Failed to generate AI message. Please try again.',
            ];
        }
    }

    protected function buildDirectMessagePrompt(Lead $lead, ?string $context): string
    {
        $campaign = $lead->campaign;

        return <<<PROMPT
You are writing a direct message to a potential lead on {$lead->platform}.

Lead Information:
- Title: {$lead->title}
- Description: {$lead->description}
- Platform: {$lead->platform}
- Author: {$lead->author}

Your Background/Offering: {$campaign->offering}

Additional Context: {$context}

Write a personalized direct message (2-3 sentences, max 100 words). Be genuine, helpful, and natural. Reference their specific post/need. No em-dashes (—), use regular speech patterns.

CRITICAL:
- Sound like a real person texting, not ChatGPT
- Be specific to their actual post
- Keep it brief and conversational
- Use contractions (I'm, you're, that's)
- No corporate language

Generate the message:
PROMPT;
    }

    public function saveDraft(Lead $lead, string $messageText, int $userId, bool $isAiGenerated = false, ?string $aiModel = null, string $channel = 'comment'): LeadMessage
    {
        return LeadMessage::create([
            'user_id' => $userId,
            'lead_id' => $lead->id,
            'message_text' => $messageText,
            'direction' => 'outgoing',
            'channel' => $channel,
            'status' => 'draft',
            'is_ai_generated' => $isAiGenerated,
            'ai_model_used' => $aiModel,
        ]);
    }
}
