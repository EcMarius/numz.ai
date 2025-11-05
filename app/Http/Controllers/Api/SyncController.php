<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Wave\Plugins\EvenLeads\Models\Campaign;
use Wave\Plugins\EvenLeads\Models\Platform;
use Wave\Plugins\EvenLeads\Models\SyncHistory;
use Wave\Plugins\EvenLeads\Jobs\SyncCampaignJob;

/**
 * @group Sync
 *
 * API endpoints for syncing campaigns
 */
class SyncController extends Controller
{
    /**
     * Trigger manual sync for a campaign
     *
     * Manually trigger a sync operation for a specific campaign to fetch new leads immediately.
     *
     * @authenticated
     * @urlParam id integer required The campaign ID to sync. Example: 1
     * @response 200 {
     *   "success": true,
     *   "message": "Sync started successfully",
     *   "data": {
     *     "campaign_id": 1,
     *     "campaign_name": "Web Development Services",
     *     "status": "queued",
     *     "queued_at": "2025-01-15T12:00:00.000000Z"
     *   }
     * }
     * @response 404 {
     *   "success": false,
     *   "message": "Campaign not found"
     * }
     * @response 429 {
     *   "success": false,
     *   "message": "Campaign was synced recently. Please wait before syncing again.",
     *   "data": {
     *     "last_sync_at": "2025-01-15T11:55:00.000000Z",
     *     "next_available_sync": "2025-01-15T12:10:00.000000Z"
     *   }
     * }
     */
    public function syncCampaign(Request $request, $id)
    {
        $campaign = Campaign::where('user_id', auth()->id())->find($id);

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found',
            ], 404);
        }

        // Check if any platform requires extension sync (and doesn't have Apify configured)
        $platformsRequiringExtension = [];
        $campaignPlatforms = $campaign->platforms ?? [];

        foreach ($campaignPlatforms as $platformName) {
            $platform = Platform::where('name', $platformName)->first();

            if ($platform && $platform->requires_extension_sync) {
                // Check if this platform has Apify configured (can sync without extension)
                $hasApify = $platform->hasPluginConfig('apify');

                if (!$hasApify) {
                    $platformsRequiringExtension[] = $platformName;
                }
            }
        }

        if (!empty($platformsRequiringExtension)) {
            return response()->json([
                'success' => true,
                'requires_extension' => true,
                'message' => 'Some platforms require the browser extension to sync',
                'data' => [
                    'campaign_id' => $campaign->id,
                    'campaign_name' => $campaign->name,
                    'platforms' => $campaign->platforms,
                    'platforms_requiring_extension' => $platformsRequiringExtension,
                ],
            ]);
        }

        // Check for concurrent sync (per campaign)
        if ($campaign->status === 'syncing') {
            return response()->json([
                'success' => false,
                'message' => 'Campaign is currently syncing. Please wait for the current sync to complete.',
                'data' => [
                    'campaign_id' => $campaign->id,
                    'campaign_name' => $campaign->name,
                    'status' => 'syncing',
                ],
            ], 409);
        }

        // Also check sync_history for any running sync (edge case: status not updated yet)
        $runningSyncHistory = SyncHistory::where('campaign_id', $campaign->id)
            ->whereIn('status', ['queued', 'running'])
            ->first();

        if ($runningSyncHistory) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign has a sync already in progress.',
                'data' => [
                    'campaign_id' => $campaign->id,
                    'campaign_name' => $campaign->name,
                    'sync_id' => $runningSyncHistory->id,
                    'status' => $runningSyncHistory->status,
                ],
            ], 409);
        }

        // Check if campaign was synced recently (rate limiting)
        if ($campaign->last_sync_at && $campaign->last_sync_at->gt(now()->subMinutes(15))) {
            $nextAvailableSync = $campaign->last_sync_at->addMinutes(15);

            // Check if platform is chronological and sync was within 30 minutes
            $platforms = \Wave\Plugins\EvenLeads\Models\Platform::whereIn('name', $campaign->platforms)->get();
            $isChronological = $platforms->contains('is_chronological', true);

            if ($isChronological && $campaign->last_sync_at->gt(now()->subMinutes(30))) {
                // Return confirmation required instead of error
                return response()->json([
                    'success' => false,
                    'requires_confirmation' => true,
                    'message' => 'Recent sync detected on chronological platform',
                    'data' => [
                        'last_sync_at' => $campaign->last_sync_at,
                        'minutes_ago' => now()->diffInMinutes($campaign->last_sync_at),
                        'warning' => [
                            'title' => 'Chronological Platform Warning',
                            'message' => 'This platform processes data chronologically. Since you synced ' . now()->diffInMinutes($campaign->last_sync_at) . ' minutes ago, there is a high chance no new leads will be found until new posts appear.',
                            'important' => 'This will consume 1 manual sync from your quota even if no new leads are found.',
                            'platforms' => $platforms->where('is_chronological', true)->pluck('display_name')->toArray(),
                        ],
                    ],
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'Campaign was synced recently. Please wait before syncing again.',
                'data' => [
                    'last_sync_at' => $campaign->last_sync_at,
                    'next_available_sync' => $nextAvailableSync,
                ],
            ], 429);
        }

        // Get sync mode from request (default: fast)
        $syncMode = $request->input('sync_mode', 'fast');
        $syncMode = in_array($syncMode, ['intelligent', 'fast']) ? $syncMode : 'fast';

        // Create sync_history record BEFORE dispatching job
        $syncHistory = SyncHistory::create([
            'campaign_id' => $campaign->id,
            'user_id' => auth()->id(),
            'platform' => $campaign->platforms[0] ?? 'unknown', // Primary platform
            'sync_type' => 'manual',
            'sync_mode' => $syncMode,
            'status' => 'queued',
            'started_at' => now(),
        ]);

        // Schedule sync
        $campaign->scheduleSyncNow();

        // Dispatch sync job (manual sync with specified mode)
        SyncCampaignJob::dispatch($campaign, true, $syncMode, $syncHistory->id);

        return response()->json([
            'success' => true,
            'message' => 'Sync started successfully',
            'data' => [
                'sync_id' => $syncHistory->id,
                'campaign_id' => $campaign->id,
                'status' => 'queued',
            ],
        ]);
    }

    /**
     * Sync all active campaigns
     *
     * Trigger a sync operation for all active campaigns belonging to the authenticated user.
     *
     * @authenticated
     * @response 200 {
     *   "success": true,
     *   "message": "Sync started for 3 campaigns",
     *   "data": {
     *     "total_campaigns": 3,
     *     "queued_campaigns": [
     *       {
     *         "id": 1,
     *         "name": "Web Development Services"
     *       },
     *       {
     *         "id": 2,
     *         "name": "Design Services"
     *       },
     *       {
     *         "id": 3,
     *         "name": "SEO Consulting"
     *       }
     *     ],
     *     "queued_at": "2025-01-15T12:00:00.000000Z"
     *   }
     * }
     * @response 404 {
     *   "success": false,
     *   "message": "No active campaigns found"
     * }
     */
    public function syncAll(Request $request)
    {
        $campaigns = Campaign::where('user_id', auth()->id())
            ->where('status', 'active')
            ->get();

        if ($campaigns->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No active campaigns found',
            ], 404);
        }

        $queuedCampaigns = [];

        foreach ($campaigns as $campaign) {
            // Skip campaigns that require extension
            if ($campaign->requires_extension_sync) {
                continue;
            }

            // Skip if synced recently
            if ($campaign->last_sync_at && $campaign->last_sync_at->gt(now()->subMinutes(15))) {
                continue;
            }

            // Get sync mode from request (default: fast)
            $syncMode = $request->input('sync_mode', 'fast');
            $syncMode = in_array($syncMode, ['intelligent', 'fast']) ? $syncMode : 'fast';

            $campaign->scheduleSyncNow();
            SyncCampaignJob::dispatch($campaign, true, $syncMode);

            $queuedCampaigns[] = [
                'id' => $campaign->id,
                'name' => $campaign->name,
            ];
        }

        return response()->json([
            'success' => true,
            'message' => "Sync started for " . count($queuedCampaigns) . " campaigns",
            'data' => [
                'total_campaigns' => count($queuedCampaigns),
                'queued_campaigns' => $queuedCampaigns,
                'queued_at' => now(),
            ],
        ]);
    }

    /**
     * Get sync history for a campaign
     *
     * Retrieve the sync history for a specific campaign with details about each sync operation.
     *
     * @authenticated
     * @urlParam id integer required The campaign ID. Example: 1
     * @queryParam page integer Page number for pagination. Example: 1
     * @queryParam per_page integer Number of items per page (max 100). Example: 15
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "campaign_id": 1,
     *     "campaign_name": "Web Development Services",
     *     "sync_history": [
     *       {
     *         "id": 1,
     *         "started_at": "2025-01-15T10:00:00.000000Z",
     *         "completed_at": "2025-01-15T10:05:00.000000Z",
     *         "status": "completed",
     *         "leads_found": 5,
     *         "errors": null,
     *         "duration_seconds": 300
     *       }
     *     ],
     *     "pagination": {
     *       "current_page": 1,
     *       "per_page": 15,
     *       "total": 50,
     *       "last_page": 4
     *     }
     *   }
     * }
     * @response 404 {
     *   "success": false,
     *   "message": "Campaign not found"
     * }
     */
    public function syncHistory(Request $request, $id)
    {
        $campaign = Campaign::where('user_id', auth()->id())->find($id);

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found',
            ], 404);
        }

        $perPage = min((int) $request->input('per_page', 15), 100);

        $syncHistory = $campaign->syncHistory()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'campaign_id' => $campaign->id,
                'campaign_name' => $campaign->name,
                'sync_history' => $syncHistory->items(),
                'pagination' => [
                    'current_page' => $syncHistory->currentPage(),
                    'per_page' => $syncHistory->perPage(),
                    'total' => $syncHistory->total(),
                    'last_page' => $syncHistory->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * Get running sync for a campaign
     *
     * Retrieve the currently running or queued sync for a specific campaign.
     * Returns null if no sync is currently active.
     *
     * @authenticated
     * @urlParam campaignId integer required The campaign ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "sync_id": 123,
     *     "campaign_id": 1,
     *     "status": "running",
     *     "sync_mode": "fast",
     *     "started_at": "2025-01-15T12:00:00.000000Z"
     *   }
     * }
     * @response 200 {
     *   "success": true,
     *   "data": null,
     *   "message": "No sync currently running for this campaign"
     * }
     * @response 404 {
     *   "success": false,
     *   "message": "Campaign not found"
     * }
     */
    public function getRunningSyncForCampaign($campaignId)
    {
        $campaign = Campaign::where('user_id', auth()->id())->find($campaignId);

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found',
            ], 404);
        }

        // Get running or queued sync
        $runningSync = SyncHistory::where('campaign_id', $campaign->id)
            ->whereIn('status', ['queued', 'running'])
            ->orderBy('started_at', 'desc')
            ->first();

        if (!$runningSync) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'No sync currently running for this campaign',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'sync_id' => $runningSync->id,
                'campaign_id' => $runningSync->campaign_id,
                'status' => $runningSync->status,
                'sync_mode' => $runningSync->sync_mode,
                'sync_type' => $runningSync->sync_type,
                'started_at' => $runningSync->started_at,
            ],
        ]);
    }

    /**
     * Get sync details by ID
     *
     * Retrieve detailed information about a specific sync operation by its sync history ID.
     *
     * @authenticated
     * @urlParam syncId integer required The sync history ID. Example: 123
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "sync_id": 123,
     *     "campaign_id": 1,
     *     "campaign_name": "Web Development Services",
     *     "status": "completed",
     *     "sync_mode": "fast",
     *     "sync_type": "manual",
     *     "platform": "reddit",
     *     "started_at": "2025-01-15T12:00:00.000000Z",
     *     "completed_at": "2025-01-15T12:05:00.000000Z",
     *     "posts_found": 25,
     *     "leads_created": 5,
     *     "error_message": null,
     *     "metadata": {
     *       "keywords_used": ["web development help", "need developer"],
     *       "subreddits_searched": ["webdev", "forhire"]
     *     }
     *   }
     * }
     * @response 404 {
     *   "success": false,
     *   "message": "Sync not found"
     * }
     */
    public function getSyncDetails($syncId)
    {
        // Get sync history with campaign
        $syncHistory = SyncHistory::with('campaign')
            ->where('id', $syncId)
            ->whereHas('campaign', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->first();

        if (!$syncHistory) {
            return response()->json([
                'success' => false,
                'message' => 'Sync not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'sync_id' => $syncHistory->id,
                'campaign_id' => $syncHistory->campaign_id,
                'campaign_name' => $syncHistory->campaign->name ?? 'Unknown',
                'status' => $syncHistory->status,
                'sync_mode' => $syncHistory->sync_mode,
                'sync_type' => $syncHistory->sync_type,
                'platform' => $syncHistory->platform,
                'started_at' => $syncHistory->started_at,
                'completed_at' => $syncHistory->completed_at,
                'posts_found' => $syncHistory->posts_found,
                'leads_created' => $syncHistory->leads_created,
                'error_message' => $syncHistory->error_message,
                'metadata' => $syncHistory->metadata,
            ],
        ]);
    }
}
