<?php

namespace App\Services\GrowthHacking;

use App\Models\GrowthHackingProspect;
use App\Models\GrowthHackingLead;
use Wave\Plugins\EvenLeads\Services\RedditService;
use Wave\Plugins\EvenLeads\Services\LeadRelevanceService;
use Illuminate\Support\Facades\Log;

class ProspectLeadScannerService
{
    protected RedditService $redditService;
    protected LeadRelevanceService $relevanceService;

    public function __construct(
        RedditService $redditService,
        LeadRelevanceService $relevanceService
    ) {
        $this->redditService = $redditService;
        $this->relevanceService = $relevanceService;
    }

    /**
     * Scan and create leads for a prospect based on their business
     */
    public function scanAndCreateLeads(GrowthHackingProspect $prospect, array $campaignData): int
    {
        try {
            $keywords = $campaignData['keywords'] ?? [];
            $subreddits = $campaignData['reddit_subreddits'] ?? [];

            if (empty($keywords)) {
                throw new \Exception('No keywords available for scanning');
            }

            $allLeads = [];

            // Search Reddit for each keyword
            foreach ($keywords as $keyword) {
                try {
                    $results = $this->redditService->searchReddit($keyword, $subreddits, 10);

                    foreach ($results as $result) {
                        // Calculate relevance score
                        $offering = $campaignData['offering'] ?? '';
                        $relevanceScore = $this->relevanceService->calculateRelevanceScore(
                            $result,
                            $keyword,
                            $offering
                        );

                        // Only add leads with score > 6
                        if ($relevanceScore >= 6) {
                            $allLeads[] = [
                                'prospect_id' => $prospect->id,
                                'user_id' => null, // Will be set when account is created
                                'campaign_id' => null, // Will be set when campaign is created
                                'lead_data' => [
                                    'title' => $result['title'] ?? '',
                                    'description' => $result['body'] ?? $result['selftext'] ?? '',
                                    'platform' => 'reddit',
                                    'author' => $result['author'] ?? 'Unknown',
                                    'url' => $result['url'] ?? '',
                                    'subreddit' => $result['subreddit'] ?? '',
                                    'created_at' => $result['created_utc'] ?? time(),
                                ],
                                'confidence_score' => $relevanceScore,
                                'copied_to_account' => false,
                                'added_at' => now(),
                            ];
                        }
                    }

                } catch (\Exception $e) {
                    Log::warning("Lead scanning failed for keyword: {$keyword}", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Sort by confidence score and take top 20
            usort($allLeads, function($a, $b) {
                return $b['confidence_score'] <=> $a['confidence_score'];
            });

            $topLeads = array_slice($allLeads, 0, 20);

            // Save leads
            foreach ($topLeads as $leadData) {
                GrowthHackingLead::create($leadData);
            }

            // Update prospect's leads_found count
            $prospect->update(['leads_found' => count($topLeads)]);

            return count($topLeads);

        } catch (\Exception $e) {
            Log::error("Lead scanning failed for prospect {$prospect->id}", [
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }
}
