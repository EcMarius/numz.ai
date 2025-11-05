<?php

namespace App\Services\GrowthHacking;

use App\Models\GrowthHackingProspect;
use Wave\Plugins\EvenLeads\Models\Campaign;
use Wave\Plugins\EvenLeads\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProspectCampaignService
{
    /**
     * Generate campaign settings for a prospect using AI
     */
    public function generateCampaignForProspect(GrowthHackingProspect $prospect): array
    {
        try {
            $aiAnalysis = $prospect->ai_analysis;

            if (!$aiAnalysis) {
                throw new \Exception('No AI analysis available for prospect');
            }

            $apiKey = Setting::getValue('openai_api_key');

            if (!$apiKey) {
                throw new \Exception('OpenAI API key not configured');
            }

            $prompt = $this->buildCampaignPrompt($aiAnalysis);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert lead generation specialist. Generate optimized campaign settings for Reddit lead generation. Return only valid JSON.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.5,
                'max_tokens' => 500,
            ]);

            if (!$response->successful()) {
                throw new \Exception('OpenAI API request failed');
            }

            $result = $response->json();
            $content = $result['choices'][0]['message']['content'] ?? null;

            if (!$content) {
                throw new \Exception('Empty response from OpenAI');
            }

            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON response from AI');
            }

            return [
                'success' => true,
                'campaign_data' => $data,
            ];

        } catch (\Exception $e) {
            Log::error("Campaign generation failed for prospect {$prospect->id}", [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Build prompt for campaign generation
     */
    protected function buildCampaignPrompt(array $aiAnalysis): string
    {
        $businessName = $aiAnalysis['business_name'] ?? 'the business';
        $industry = $aiAnalysis['industry'] ?? 'unknown';
        $description = $aiAnalysis['description'] ?? '';
        $targetMarket = $aiAnalysis['target_market'] ?? 'unknown';

        return <<<PROMPT
Generate optimized Reddit lead generation campaign settings for this business:

**Business:** {$businessName}
**Industry:** {$industry}
**What they offer:** {$description}
**Target market:** {$targetMarket}

Return ONLY a JSON object with these fields:

{
  "campaign_name": "Campaign name (e.g., '{$businessName} - Lead Generation')",
  "offering": "What they offer in one sentence",
  "keywords": ["keyword1", "keyword2", "keyword3", "keyword4", "keyword5"],
  "reddit_subreddits": ["subreddit1", "subreddit2", "subreddit3"]
}

**Requirements:**
- Keywords should be relevant to their target market and industry
- Subreddits should be active communities where their target audience hangs out
- Keep it focused and specific

Return ONLY the JSON object, no additional text.
PROMPT;
    }

    /**
     * Create EvenLeads campaign for prospect's user account
     */
    public function createCampaignForUser(int $userId, array $campaignData): ?Campaign
    {
        try {
            $campaign = Campaign::create([
                'user_id' => $userId,
                'name' => $campaignData['campaign_name'] ?? 'Lead Generation',
                'offering' => $campaignData['offering'] ?? '',
                'keywords' => $campaignData['keywords'] ?? [],
                'reddit_subreddits' => $campaignData['reddit_subreddits'] ?? [],
                'platforms' => ['reddit'],
                'status' => 'active',
                'next_sync_at' => now()->addHours(24),
            ]);

            return $campaign;

        } catch (\Exception $e) {
            Log::error("Failed to create campaign for user {$userId}", [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
