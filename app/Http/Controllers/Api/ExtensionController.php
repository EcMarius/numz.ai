<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SchemaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;
use Wave\Plugins\EvenLeads\Models\Campaign;
use Wave\Plugins\EvenLeads\Services\PlanLimitService;

/**
 * @group Extension Helpers
 *
 * API endpoints for browser extension helper functions
 */
class ExtensionController extends Controller
{
    /**
     * Generate Search Terms
     *
     * Uses AI to generate relevant search keywords from campaign data.
     * Server-side processing - extension only sends campaign_id.
     *
     * @authenticated
     * @bodyParam campaign_id int required The campaign ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "keywords": ["Laravel developer", "PHP developer", "web development", "SaaS development", "backend developer"]
     * }
     */
    public function generateSearchTerms(Request $request)
    {
        $request->validate([
            'campaign_id' => 'required|integer|exists:evenleads_campaigns,id',
        ]);

        $campaignId = $request->input('campaign_id');
        $user = Auth::user();

        // Load campaign with validation
        $campaign = Campaign::where('id', $campaignId)
            ->where('user_id', $user->id)
            ->first();

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'error' => 'Campaign not found or access denied',
            ], 404);
        }

        // Get offering and platforms from campaign
        $offering = $campaign->offering;
        $platforms = $campaign->platforms ?? [];

        if (empty($offering)) {
            return response()->json([
                'success' => false,
                'error' => 'Campaign offering is required',
            ], 422);
        }

        if (empty($platforms)) {
            return response()->json([
                'success' => false,
                'error' => 'Campaign must have at least one platform enabled',
            ], 422);
        }

        // Use the first enabled platform for keyword generation
        $platform = $platforms[0];

        try {
            // Use AIReplyService (same as RedditService)
            $aiService = app(\Wave\Plugins\EvenLeads\Services\AIReplyService::class);

            // Build prompt - generate 5-10 search queries (not single keywords)
            $prompt = "Generate 8-10 search queries to find posts from people who NEED help with:\n\n";
            $prompt .= "TARGET: {$offering}\n\n";
            $prompt .= "Platform: {$platform}\n\n";
            $prompt .= "INSTRUCTIONS:\n";
            $prompt .= "- Write queries as if you're searching on {$platform}\n";
            $prompt .= "- Focus on NEED-based language (\"looking for\", \"need help\", \"hiring\", etc.)\n";
            $prompt .= "- Be specific to {$platform}'s search patterns\n";
            $prompt .= "- Mix of question-based and statement-based queries\n";
            $prompt .= "- Natural conversational language\n\n";
            $prompt .= "Return ONLY search queries, one per line, no numbers or explanations.";

            // Call OpenAI using AIReplyService (token-efficient, all at once)
            $response = $aiService->callOpenAI($prompt);

            // Parse keywords from response
            $keywords = $aiService->parseKeywordsFromResponse($response['text']);

            // Ensure we have at least some keywords
            if (empty($keywords)) {
                $keywords = [$offering]; // Fallback to offering itself
            }

            \Log::info('Generated search terms for extension', [
                'campaign_id' => $campaignId,
                'platform' => $platform,
                'keywords_count' => count($keywords),
                'model' => $aiService->getModel(),
                'tokens_used' => $response['tokens_used'] ?? 'unknown',
            ]);

            return response()->json([
                'success' => true,
                'keywords' => array_values($keywords),
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to generate search terms', [
                'error' => $e->getMessage(),
                'campaign_id' => $campaignId,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to generate search terms',
                'keywords' => [$offering], // Fallback
            ], 500);
        }
    }

    /**
     * Record Extension Sync Start
     *
     * Called by extension when sync actually begins (not on errors).
     * Records manual sync for quota tracking.
     *
     * @authenticated
     * @bodyParam campaign_id int required The campaign ID
     * @response 200 {
     *   "success": true,
     *   "message": "Manual sync recorded"
     * }
     */
    public function recordSyncStart(Request $request)
    {
        $request->validate([
            'campaign_id' => 'required|integer|exists:evenleads_campaigns,id',
        ]);

        $campaignId = $request->input('campaign_id');
        $user = Auth::user();

        // Verify campaign belongs to user
        $campaign = Campaign::where('id', $campaignId)
            ->where('user_id', $user->id)
            ->first();

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'error' => 'Campaign not found or access denied',
            ], 404);
        }

        try {
            // Record manual sync (this counts against quota)
            $limitService = app(\Wave\Plugins\EvenLeads\Services\PlanLimitService::class);
            $limitService->recordManualSync($user, $campaign);

            Log::info('Extension sync started and recorded', [
                'user_id' => $user->id,
                'campaign_id' => $campaign->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Manual sync recorded',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to record extension sync start', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'campaign_id' => $campaignId,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to record sync: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate Lead Relevance
     *
     * Uses AI to determine if a lead is relevant to the campaign.
     *
     * @authenticated
     * @bodyParam offering string required The campaign offering. Example: Looking for web development clients
     * @bodyParam title string required The lead title. Example: Need a Laravel developer
     * @bodyParam description string required The lead description. Example: Building a SaaS platform...
     * @response 200 {
     *   "success": true,
     *   "is_relevant": true,
     *   "confidence": 9,
     *   "reasoning": "This lead is highly relevant as they explicitly need Laravel development which matches your offering."
     * }
     */
    public function validateLead(Request $request)
    {
        $request->validate([
            'offering' => 'required|string|max:5000',
            'title' => 'required|string|max:1000',
            'description' => 'required|string|max:10000',
        ]);

        $offering = $request->input('offering');
        $title = $request->input('title');
        $description = $request->input('description');

        try {
            $prompt = "Analyze if this lead is relevant to our service offering.\n\n";
            $prompt .= "Our Offering: {$offering}\n\n";
            $prompt .= "Lead Title: {$title}\n";
            $prompt .= "Lead Description: {$description}\n\n";
            $prompt .= "Respond with ONLY a JSON object in this exact format:\n";
            $prompt .= '{"is_relevant": true/false, "confidence": 1-10, "reasoning": "brief explanation"}';

            $response = OpenAI::chat()->create([
                'model' => auth()->user()->plan()?->openai_model ?? 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.3,
                'max_tokens' => 150,
            ]);

            $content = $response->choices[0]->message->content ?? '{}';

            // Clean JSON formatting
            $content = trim($content);
            if (str_starts_with($content, '```json')) {
                $content = preg_replace('/^```json\s*/', '', $content);
                $content = preg_replace('/```\s*$/', '', $content);
            } elseif (str_starts_with($content, '```')) {
                $content = preg_replace('/^```\s*/', '', $content);
                $content = preg_replace('/```\s*$/', '', $content);
            }

            $result = json_decode(trim($content), true);

            if (!is_array($result) || !isset($result['is_relevant'])) {
                throw new \Exception('AI response was not valid');
            }

            return response()->json([
                'success' => true,
                'is_relevant' => $result['is_relevant'] ?? false,
                'confidence' => $result['confidence'] ?? 5,
                'reasoning' => $result['reasoning'] ?? '',
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to validate lead: ' . $e->getMessage());

            return response()->json([
                'success' => true,
                'is_relevant' => true, // Default to true to avoid false negatives
                'confidence' => 5,
                'reasoning' => 'Could not validate lead relevance',
            ]);
        }
    }

    /**
     * Get Campaign Context
     *
     * Returns complete campaign data including search terms and platform schemas.
     * This is the main endpoint the extension uses to get all necessary data for syncing.
     *
     * @authenticated
     * @urlParam id int required The campaign ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "campaign": {...},
     *   "search_terms": [...],
     *   "schemas": {...}
     * }
     */
    public function getCampaignContext(int $id)
    {
        $user = Auth::user();

        // Load campaign
        $campaign = Campaign::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'error' => 'Campaign not found or access denied',
            ], 404);
        }

        // Get platforms from campaign
        $platforms = $campaign->platforms ?? [];

        // Get schemas for all enabled platforms
        $schemas = SchemaService::getSchemasForPlatforms($platforms);

        // Generate search terms if not already set
        $searchTerms = $campaign->keywords ?? [];
        if (empty($searchTerms) && !empty($campaign->offering)) {
            // Generate search terms
            $platform = $platforms[0] ?? 'linkedin';

            try {
                $aiService = app(\Wave\Plugins\EvenLeads\Services\AIReplyService::class);

                $prompt = "Generate 8-10 search queries to find posts from people who NEED help with:\n\n";
                $prompt .= "TARGET: {$campaign->offering}\n\n";
                $prompt .= "Platform: {$platform}\n\n";
                $prompt .= "INSTRUCTIONS:\n";
                $prompt .= "- Write queries as if you're searching on {$platform}\n";
                $prompt .= "- Focus on NEED-based language (\"looking for\", \"need help\", \"hiring\", etc.)\n";
                $prompt .= "- Be specific to {$platform}'s search patterns\n";
                $prompt .= "- Mix of question-based and statement-based queries\n";
                $prompt .= "- Natural conversational language\n\n";
                $prompt .= "Return ONLY search queries, one per line, no numbers or explanations.";

                $response = $aiService->callOpenAI($prompt);
                $searchTerms = $aiService->parseKeywordsFromResponse($response['text']);

                if (empty($searchTerms)) {
                    $searchTerms = [$campaign->offering];
                }
            } catch (\Exception $e) {
                \Log::error('Failed to generate search terms for campaign context', [
                    'campaign_id' => $id,
                    'error' => $e->getMessage(),
                ]);
                $searchTerms = [$campaign->offering];
            }
        }

        return response()->json([
            'success' => true,
            'campaign' => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'offering' => $campaign->offering,
                'platforms' => $campaign->platforms,
                'keywords' => $campaign->keywords,
                'negative_keywords' => $campaign->negative_keywords,
                'include_keywords' => $campaign->include_keywords,
                'reddit_subreddits' => $campaign->reddit_subreddits,
                'linkedin_groups' => $campaign->linkedin_groups,
                'twitter_communities' => $campaign->twitter_communities,
                'facebook_groups' => $campaign->facebook_groups,
                'selected_accounts' => $campaign->selected_accounts,
                'requires_extension_sync' => $campaign->requires_extension_sync,
                'ai_settings' => $campaign->ai_settings,
            ],
            'search_terms' => array_values($searchTerms),
            'schemas' => $schemas,
        ]);
    }

    /**
     * Validate Extension Token
     *
     * Validates if the current token is still valid and returns user info.
     * Called when extension sidebar opens to verify access hasn't been revoked.
     *
     * @authenticated
     * @response 200 {
     *   "success": true,
     *   "valid": true,
     *   "user": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "email": "john@example.com",
     *     "avatar": "https://...",
     *     "roles": ["admin"]
     *   },
     *   "token_info": {
     *     "name": "browser-extension",
     *     "created_at": "2025-01-01T00:00:00Z",
     *     "last_used_at": "2025-01-15T12:30:00Z"
     *   }
     * }
     * @response 401 {
     *   "success": false,
     *   "valid": false,
     *   "message": "Token has been revoked"
     * }
     */
    public function validateToken(Request $request)
    {
        $user = Auth::user();
        $token = $request->user()->currentAccessToken();

        // If we got here, token is valid (middleware already verified it)
        return response()->json([
            'success' => true,
            'valid' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar(),
                'role_id' => $user->role_id,
                'roles' => $user->getRoleNames()->toArray(),
            ],
            'token_info' => [
                'name' => $token->name,
                'created_at' => $token->created_at,
                'last_used_at' => $token->last_used_at,
            ],
        ]);
    }
}
