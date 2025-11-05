<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Wave\Plugins\EvenLeads\Services\RedditService;
use Wave\Plugins\EvenLeads\Services\LeadRelevanceService;
use Wave\Plugins\EvenLeads\Models\PlatformConnection;
use Wave\Plugins\EvenLeads\Models\Campaign;
use Illuminate\Support\Facades\Auth;
use Wave\Setting;

class RedditSearchPlayground extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static ?string $navigationLabel = 'Reddit Search Playground';

    protected static ?int $navigationSort = 3;

    // Form data
    public string $search_query = '';
    public string $subreddit = '';
    public int $limit;
    public string $search_type = 'global';
    public string $sort_by = 'relevance';
    public bool $limit_per_subreddit = false;
    public bool $test_ai_scoring = false;
    public ?int $test_campaign_id = null;

    // Results
    public ?array $results = null;
    public bool $isLoading = false;
    public ?string $errorMessage = null;

    // Timing stats
    public ?float $totalTime = null;
    public ?float $avgTimePerResult = null;
    public ?float $avgTimePerSearch = null;
    public ?int $searchCount = null;

    // Live progress tracking
    public array $liveResults = [];
    public int $progressCurrent = 0;
    public int $progressTotal = 0;
    public ?float $progressEta = null;
    public ?string $progressStatus = null;
    public int $errorCount = 0;

    protected $rules = [
        'search_query' => 'required|string|min:1',
        'subreddit' => 'required_if:search_type,subreddit|string',
        'limit' => 'required|integer|min:1|max:100',
    ];

    public function getView(): string
    {
        return 'filament.pages.reddit-search-playground';
    }

    public function mount(): void
    {
        // Get default limit from settings (default: 10 if not found)
        $settingLimit = Setting::where('key', 'reddit_search_limit')->value('value');
        $this->limit = $settingLimit ? min((int)$settingLimit, 100) : 10;
    }

    public function getCampaigns()
    {
        return Campaign::where('user_id', Auth::id())
            ->pluck('name', 'id')
            ->toArray();
    }

    public function getFormSchema(): array
    {
        return [];
    }

    public function search(): void
    {
        $this->validate();

        $this->isLoading = true;
        $this->results = null;
        $this->liveResults = [];
        $this->errorMessage = null;
        $this->progressCurrent = 0;
        $this->progressTotal = 0;
        $this->progressEta = null;
        $this->progressStatus = 'Initializing...';
        $this->errorCount = 0;
        $startTime = microtime(true);

        try {
            $redditService = app(RedditService::class);
            $connection = PlatformConnection::forUser(Auth::id())
                ->byPlatform('reddit')
                ->active()
                ->first();

            if (!$connection) {
                throw new \Exception('Reddit account not connected. Please connect your Reddit account first.');
            }

            $searchQueryInput = $this->search_query;
            $limit = $this->limit;
            $searchType = $this->search_type;

            // Parse search queries (support comma-separated or line-separated)
            $searchQueries = array_filter(array_map('trim', preg_split('/[\n,]+/', $searchQueryInput)));

            if (empty($searchQueries)) {
                throw new \Exception('Please enter at least one search query.');
            }

            // Calculate total searches for progress tracking
            if ($searchType === 'subreddit') {
                $subredditInput = $this->subreddit;
                $subreddits = array_filter(array_map(function($sub) {
                    return trim(preg_replace('/^r\//', '', trim($sub, '/')));
                }, preg_split('/[\n,]+/', $subredditInput)));
                $this->progressTotal = count($searchQueries) * count($subreddits);
            } else {
                $this->progressTotal = count($searchQueries);
            }

            $this->progressStatus = "Starting search (0/{$this->progressTotal} searches)";
            $allResults = [];

            if ($searchType === 'global') {
                // Global search - search each query
                foreach ($searchQueries as $index => $searchQuery) {
                    try {
                        $this->progressCurrent++;
                        $elapsed = microtime(true) - $startTime;
                        $avgTimePerSearch = $elapsed / $this->progressCurrent;
                        $remaining = $this->progressTotal - $this->progressCurrent;
                        $this->progressEta = round($remaining * $avgTimePerSearch, 1);
                        $this->progressStatus = "Searching: \"{$searchQuery}\" ({$this->progressCurrent}/{$this->progressTotal})";

                        $response = \Illuminate\Support\Facades\Http::withHeaders([
                            'Authorization' => 'Bearer ' . $connection->access_token,
                            'User-Agent' => config('evenleads.reddit.user_agent'),
                        ])->get('https://oauth.reddit.com/search', [
                            'q' => $searchQuery,
                            'type' => 'link',
                            'sort' => $this->sort_by,
                            'limit' => $limit,
                            't' => 'week',
                        ]);

                        if ($response->successful()) {
                            $data = $response->json();
                            $posts = $data['data']['children'] ?? [];
                            $allResults = array_merge($allResults, $posts);

                            // Update live results
                            $this->liveResults = array_merge($this->liveResults, array_map(fn($p) => [
                                'title' => $p['data']['title'] ?? 'No title',
                                'subreddit' => $p['data']['subreddit'] ?? 'Unknown',
                            ], $posts));
                        } else {
                            $this->errorCount++;
                        }
                    } catch (\Exception $e) {
                        $this->errorCount++;
                        \Log::error("Error searching query '{$searchQuery}': " . $e->getMessage());
                    }
                }

                // Remove duplicates for global search
                $uniqueResults = [];
                $seenIds = [];
                foreach ($allResults as $post) {
                    $postId = $post['data']['id'] ?? null;
                    if ($postId && !in_array($postId, $seenIds)) {
                        $uniqueResults[] = $post;
                        $seenIds[] = $postId;
                    }
                }
                $allResults = $uniqueResults;
            } else {
                // Subreddit search - support multiple subreddits
                $subredditInput = $this->subreddit;

                // Parse subreddits (support comma-separated or line-separated)
                $subreddits = array_filter(array_map('trim', preg_split('/[\n,]+/', $subredditInput)));

                if (empty($subreddits)) {
                    throw new \Exception('Please enter at least one subreddit name.');
                }

                // Clean subreddit names (remove r/ prefix, trim whitespace, etc)
                $subreddits = array_map(function($sub) {
                    $sub = trim($sub);
                    // Remove r/ prefix if present
                    $sub = preg_replace('/^r\//', '', $sub);
                    // Remove leading/trailing slashes
                    $sub = trim($sub, '/');
                    return $sub;
                }, $subreddits);

                // Remove empty entries after cleaning
                $subreddits = array_filter($subreddits);

                if (empty($subreddits)) {
                    throw new \Exception('Please enter valid subreddit names.');
                }

                // Search each subreddit with each query
                foreach ($subreddits as $subreddit) {
                    foreach ($searchQueries as $searchQuery) {
                        try {
                            $this->progressCurrent++;
                            $elapsed = microtime(true) - $startTime;
                            $avgTimePerSearch = $elapsed / $this->progressCurrent;
                            $remaining = $this->progressTotal - $this->progressCurrent;
                            $this->progressEta = round($remaining * $avgTimePerSearch, 1);
                            $this->progressStatus = "Searching r/{$subreddit} for \"{$searchQuery}\" ({$this->progressCurrent}/{$this->progressTotal})";

                            $response = \Illuminate\Support\Facades\Http::withHeaders([
                                'Authorization' => 'Bearer ' . $connection->access_token,
                                'User-Agent' => config('evenleads.reddit.user_agent'),
                            ])->get("https://oauth.reddit.com/r/{$subreddit}/search", [
                                'q' => $searchQuery,
                                'restrict_sr' => true,
                                'sort' => $this->sort_by,
                                'limit' => $limit,
                                't' => 'week',
                            ]);

                            if ($response->successful()) {
                                $data = $response->json();
                                $posts = $data['data']['children'] ?? [];
                                $allResults = array_merge($allResults, $posts);

                                // Update live results
                                $this->liveResults = array_merge($this->liveResults, array_map(fn($p) => [
                                    'title' => $p['data']['title'] ?? 'No title',
                                    'subreddit' => $p['data']['subreddit'] ?? 'Unknown',
                                ], $posts));

                                // If limit is total (not per subreddit), check if we've reached it
                                if (!$this->limit_per_subreddit && count($allResults) >= $limit) {
                                    $this->progressStatus = "Limit reached ({$limit} results)";
                                    break 2; // Break both foreach loops
                                }
                            } else {
                                $this->errorCount++;
                                \Log::warning("Failed to search r/{$subreddit} for '{$searchQuery}'", [
                                    'status' => $response->status()
                                ]);
                            }
                        } catch (\Exception $e) {
                            $this->errorCount++;
                            \Log::error("Error searching r/{$subreddit} for '{$searchQuery}': " . $e->getMessage());
                        }
                    }
                }

                // Remove duplicates by post ID
                $uniqueResults = [];
                $seenIds = [];
                foreach ($allResults as $post) {
                    $postId = $post['data']['id'] ?? null;
                    if ($postId && !in_array($postId, $seenIds)) {
                        $uniqueResults[] = $post;
                        $seenIds[] = $postId;
                    }
                }
                $allResults = $uniqueResults;
            }

            // Process all results
            $results = [];
            foreach ($allResults as $post) {
                $postData = $post['data'];

                $result = [
                    'id' => $postData['id'] ?? 'N/A',
                    'title' => $postData['title'] ?? 'No Title',
                    'selftext' => $postData['selftext'] ?? '',
                    'author' => $postData['author'] ?? 'Unknown',
                    'subreddit' => $postData['subreddit'] ?? 'Unknown',
                    'created_utc' => $postData['created_utc'] ?? time(),
                    'url' => 'https://reddit.com' . ($postData['permalink'] ?? ''),
                    'num_comments' => $postData['num_comments'] ?? 0,
                    'score' => $postData['score'] ?? 0,
                ];

                // If AI testing is enabled
                if ($this->test_ai_scoring && !empty($this->test_campaign_id)) {
                    $campaign = Campaign::find($this->test_campaign_id);
                    if ($campaign) {
                        $relevanceService = app(LeadRelevanceService::class);
                        $relevanceResult = $relevanceService->checkPostRelevance(
                            $result['title'],
                            $result['selftext'],
                            $campaign,
                            $result['id']
                        );

                        $result['ai_score'] = $relevanceResult['confidence_score'];
                        $result['ai_relevant'] = $relevanceResult['is_relevant'];
                        $result['ai_reason'] = $relevanceResult['reason'] ?? 'No reason provided';
                    }
                }

                $results[] = $result;
            }

            // Sort results: if AI testing enabled, show relevant first (sorted by score desc), then not relevant (sorted by score desc)
            if ($this->test_ai_scoring && !empty($this->test_campaign_id)) {
                usort($results, function($a, $b) {
                    // First sort by relevance (relevant first)
                    if (isset($a['ai_relevant']) && isset($b['ai_relevant'])) {
                        if ($a['ai_relevant'] != $b['ai_relevant']) {
                            return $b['ai_relevant'] ? 1 : -1;
                        }
                        // Then sort by AI score (higher first)
                        if (isset($a['ai_score']) && isset($b['ai_score'])) {
                            return $b['ai_score'] - $a['ai_score'];
                        }
                    }
                    return 0;
                });
            }

            $this->results = $results;

            // Calculate timing stats
            $endTime = microtime(true);
            $this->totalTime = round($endTime - $startTime, 2);

            $totalResults = count($results);
            $this->avgTimePerResult = $totalResults > 0 ? round($this->totalTime / $totalResults, 2) : 0;

            // Count searches (queries * subreddits or just queries for global)
            $queryCount = count($searchQueries);
            if ($searchType === 'subreddit') {
                $subredditInput = $this->subreddit;
                $subreddits = array_filter(array_map('trim', preg_split('/[\n,]+/', $subredditInput)));
                $this->searchCount = $queryCount * count($subreddits);
            } else {
                $this->searchCount = $queryCount;
            }
            $this->avgTimePerSearch = $this->searchCount > 0 ? round($this->totalTime / $this->searchCount, 2) : 0;

            $relevantCount = 0;
            if ($this->test_ai_scoring) {
                $relevantCount = count(array_filter($results, fn($r) => $r['ai_relevant'] ?? false));
            }

            $message = "Found {$totalResults} result(s)";
            if ($this->test_ai_scoring && $relevantCount > 0) {
                $message .= " ({$relevantCount} relevant)";
            }
            $message .= " in {$this->totalTime}s";

            Notification::make()
                ->success()
                ->title('Search Complete')
                ->body($message)
                ->send();

        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();

            Notification::make()
                ->danger()
                ->title('Search Failed')
                ->body($e->getMessage())
                ->send();
        } finally {
            $this->isLoading = false;
        }
    }

    public function clearResults(): void
    {
        $this->results = null;
        $this->errorMessage = null;
    }
}
