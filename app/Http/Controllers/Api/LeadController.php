<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Wave\Plugins\EvenLeads\Models\Lead;
use Wave\Plugins\EvenLeads\Models\Campaign;

/**
 * @group Leads
 *
 * API endpoints for managing leads
 */
class LeadController extends Controller
{
    /**
     * List all leads
     *
     * Get a paginated list of all leads with comprehensive filtering options.
     *
     * @authenticated
     * @queryParam page integer Page number for pagination. Example: 1
     * @queryParam per_page integer Number of items per page (max 100). Example: 15
     * @queryParam campaign_id integer Filter by campaign ID. Example: 1
     * @queryParam status string Filter by status (new, contacted, closed). Example: new
     * @queryParam match_type string Filter by match type (strong, partial). Example: strong
     * @queryParam platform string Filter by platform (reddit, facebook, etc). Example: reddit
     * @queryParam min_confidence integer Minimum confidence score (0-10). Example: 7
     * @queryParam max_confidence integer Maximum confidence score (0-10). Example: 10
     * @queryParam search string Search in title and description. Example: website
     * @queryParam sort_by string Sort by field (created_at, confidence_score, synced_at). Example: created_at
     * @queryParam sort_order string Sort order (asc, desc). Example: desc
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "leads": [
     *       {
     *         "id": 1,
     *         "campaign_id": 1,
     *         "campaign_name": "Web Development Services",
     *         "platform": "reddit",
     *         "platform_id": "abc123",
     *         "title": "Looking for web developer",
     *         "description": "Need someone to build a website",
     *         "url": "https://reddit.com/r/example/comments/abc123",
     *         "author": "john_doe",
     *         "subreddit": "webdev",
     *         "facebook_group": null,
     *         "comments_count": 5,
     *         "confidence_score": 8,
     *         "match_type": "strong",
     *         "status": "new",
     *         "matched_keywords": ["website", "web developer"],
     *         "synced_at": "2025-01-15T10:30:00.000000Z",
     *         "contacted_at": null,
     *         "created_at": "2025-01-15T10:30:00.000000Z",
     *         "updated_at": "2025-01-15T10:30:00.000000Z"
     *       }
     *     ],
     *     "pagination": {
     *       "current_page": 1,
     *       "per_page": 15,
     *       "total": 150,
     *       "last_page": 10
     *     }
     *   }
     * }
     */
    public function index(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 15), 100);

        $query = Lead::whereHas('campaign', function ($q) {
            $q->where('user_id', auth()->id());
        })->with('campaign');

        // Apply filters
        if ($request->has('campaign_id')) {
            $query->where('campaign_id', $request->input('campaign_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('match_type')) {
            $query->where('match_type', $request->input('match_type'));
        }

        if ($request->has('platform')) {
            $query->where('platform', $request->input('platform'));
        }

        if ($request->has('min_confidence')) {
            $query->where('confidence_score', '>=', $request->input('min_confidence'));
        }

        if ($request->has('max_confidence')) {
            $query->where('confidence_score', '<=', $request->input('max_confidence'));
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        if (in_array($sortBy, ['created_at', 'confidence_score', 'synced_at'])) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $leads = $query->paginate($perPage);

        // Add campaign name to each lead
        $leadsData = $leads->items();
        foreach ($leadsData as $lead) {
            $lead->campaign_name = $lead->campaign->name ?? null;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'leads' => $leadsData,
                'pagination' => [
                    'current_page' => $leads->currentPage(),
                    'per_page' => $leads->perPage(),
                    'total' => $leads->total(),
                    'last_page' => $leads->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * Get a single lead
     *
     * Retrieve detailed information about a specific lead including AI generations and campaign details.
     *
     * @authenticated
     * @urlParam id integer required The lead ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "campaign": {
     *       "id": 1,
     *       "name": "Web Development Services",
     *       "offering": "Custom web development"
     *     },
     *     "platform": "reddit",
     *     "platform_id": "abc123",
     *     "title": "Looking for web developer",
     *     "description": "Need someone to build a website for my startup",
     *     "url": "https://reddit.com/r/example/comments/abc123",
     *     "author": "john_doe",
     *     "subreddit": "webdev",
     *     "facebook_group": null,
     *     "comments_count": 5,
     *     "confidence_score": 8,
     *     "match_type": "strong",
     *     "status": "new",
     *     "matched_keywords": ["website", "web developer"],
     *     "ai_generations": [
     *       {
     *         "id": 1,
     *         "generated_message": "Hi! I'd love to help with your project...",
     *         "created_at": "2025-01-15T10:35:00.000000Z"
     *       }
     *     ],
     *     "synced_at": "2025-01-15T10:30:00.000000Z",
     *     "contacted_at": null,
     *     "created_at": "2025-01-15T10:30:00.000000Z",
     *     "updated_at": "2025-01-15T10:30:00.000000Z"
     *   }
     * }
     * @response 404 {
     *   "success": false,
     *   "message": "Lead not found"
     * }
     */
    public function show($id)
    {
        $lead = Lead::whereHas('campaign', function ($q) {
            $q->where('user_id', auth()->id());
        })->with(['campaign', 'aiGenerations'])->find($id);

        if (!$lead) {
            return response()->json([
                'success' => false,
                'message' => 'Lead not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $lead,
        ]);
    }

    /**
     * Delete a lead
     *
     * Permanently delete a lead from your account.
     *
     * @authenticated
     * @urlParam id integer required The lead ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "message": "Lead deleted successfully"
     * }
     * @response 404 {
     *   "success": false,
     *   "message": "Lead not found"
     * }
     */
    public function destroy($id)
    {
        $lead = Lead::whereHas('campaign', function ($q) {
            $q->where('user_id', auth()->id());
        })->find($id);

        if (!$lead) {
            return response()->json([
                'success' => false,
                'message' => 'Lead not found',
            ], 404);
        }

        $lead->delete();

        return response()->json([
            'success' => true,
            'message' => 'Lead deleted successfully',
        ]);
    }

    /**
     * Bulk delete leads
     *
     * Delete multiple leads at once by providing an array of lead IDs.
     *
     * @authenticated
     * @bodyParam lead_ids array required Array of lead IDs to delete. Example: [1, 2, 3]
     * @response 200 {
     *   "success": true,
     *   "message": "Successfully deleted 3 leads"
     * }
     * @response 422 {
     *   "success": false,
     *   "message": "Validation failed",
     *   "errors": {
     *     "lead_ids": ["The lead ids field is required."]
     *   }
     * }
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'lead_ids' => 'required|array',
            'lead_ids.*' => 'required|integer',
        ]);

        $leadIds = $request->input('lead_ids');

        $deletedCount = Lead::whereHas('campaign', function ($q) {
            $q->where('user_id', auth()->id());
        })->whereIn('id', $leadIds)->delete();

        return response()->json([
            'success' => true,
            'message' => "Successfully deleted {$deletedCount} leads",
        ]);
    }

    /**
     * Update lead status
     *
     * Update the status of a lead (new, contacted, closed).
     *
     * @authenticated
     * @urlParam id integer required The lead ID. Example: 1
     * @bodyParam status string required The new status. Example: contacted
     * @response 200 {
     *   "success": true,
     *   "message": "Lead status updated successfully",
     *   "data": {
     *     "id": 1,
     *     "status": "contacted",
     *     "contacted_at": "2025-01-15T12:00:00.000000Z"
     *   }
     * }
     * @response 404 {
     *   "success": false,
     *   "message": "Lead not found"
     * }
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:new,contacted,closed',
        ]);

        $lead = Lead::whereHas('campaign', function ($q) {
            $q->where('user_id', auth()->id());
        })->find($id);

        if (!$lead) {
            return response()->json([
                'success' => false,
                'message' => 'Lead not found',
            ], 404);
        }

        if ($request->input('status') === 'contacted') {
            $lead->markContacted();
        } elseif ($request->input('status') === 'closed') {
            $lead->markClosed();
        } else {
            $lead->update(['status' => $request->input('status')]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lead status updated successfully',
            'data' => $lead->fresh(),
        ]);
    }

    /**
     * Submit a lead from browser extension
     *
     * Create a new lead from the browser extension.
     *
     * @authenticated
     * @urlParam campaignId integer required The campaign ID. Example: 1
     * @bodyParam platform string required The platform name. Example: facebook
     * @bodyParam platform_id string required The platform-specific ID. Example: 123456789
     * @bodyParam title string required The lead title. Example: Looking for a web developer
     * @bodyParam description string The lead description.
     * @bodyParam url string required The lead URL.
     * @bodyParam author string The author/poster name.
     * @bodyParam matched_keywords array The matched keywords.
     * @bodyParam confidence_score integer The confidence score (0-10).
     *
     * @response 201 {
     *   "success": true,
     *   "message": "Lead submitted successfully",
     *   "data": {
     *     "id": 1,
     *     "campaign_id": 1,
     *     "platform": "facebook",
     *     "title": "Looking for a web developer"
     *   }
     * }
     */
    public function store(Request $request, $campaignId)
    {
        $campaign = Campaign::where('id', $campaignId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $validated = $request->validate([
            'platform' => 'required|string',
            'platform_id' => 'required|string',
            'title' => 'required|string',
            'description' => 'nullable|string',
            'url' => 'required|url',
            'author' => 'nullable|string',
            'matched_keywords' => 'nullable|array',
            'confidence_score' => 'nullable|integer|min:0|max:10',
            'facebook_group' => 'nullable|string',
            'subreddit' => 'nullable|string',
            'fiverr_gig_id' => 'nullable|string',
            'upwork_job_id' => 'nullable|string',
        ]);

        // Check for duplicates
        $existing = Lead::where('campaign_id', $campaign->id)
            ->where('platform_id', $validated['platform_id'])
            ->where('platform', $validated['platform'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Lead already exists',
                'data' => $existing,
            ], 409);
        }

        // Determine match type based on confidence score
        $confidenceScore = $validated['confidence_score'] ?? 5;
        $threshold = config('evenleads.scoring.strong_match_threshold', 8);
        $matchType = $confidenceScore >= $threshold ? 'strong' : 'partial';

        $lead = Lead::create([
            'user_id' => auth()->id(),
            'campaign_id' => $campaign->id,
            'platform' => $validated['platform'],
            'platform_id' => $validated['platform_id'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'url' => $validated['url'],
            'author' => $validated['author'] ?? null,
            'confidence_score' => $confidenceScore,
            'match_type' => $matchType,
            'status' => 'new',
            'matched_keywords' => $validated['matched_keywords'] ?? [],
            'synced_at' => now(),
            'facebook_group' => $validated['facebook_group'] ?? null,
            'subreddit' => $validated['subreddit'] ?? null,
            'fiverr_gig_id' => $validated['fiverr_gig_id'] ?? null,
            'upwork_job_id' => $validated['upwork_job_id'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Lead submitted successfully',
            'data' => $lead,
        ], 201);
    }

    /**
     * Submit multiple leads from browser extension
     *
     * Create multiple leads in a single request.
     *
     * @authenticated
     * @urlParam campaignId integer required The campaign ID. Example: 1
     * @bodyParam leads array required Array of leads to submit.
     *
     * @response 201 {
     *   "success": true,
     *   "message": "5 leads submitted successfully",
     *   "data": {
     *     "created": 5,
     *     "duplicates": 2
     *   }
     * }
     */
    public function storeBulk(Request $request, $campaignId)
    {
        $campaign = Campaign::where('id', $campaignId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $validated = $request->validate([
            'leads' => 'required|array',
            'leads.*.platform' => 'required|string',
            'leads.*.platform_id' => 'required|string',
            'leads.*.title' => 'required|string',
            'leads.*.description' => 'nullable|string',
            'leads.*.url' => 'required|url',
            'leads.*.author' => 'nullable|string',
            'leads.*.matched_keywords' => 'nullable|array',
            'leads.*.confidence_score' => 'nullable|integer|min:0|max:10',
        ]);

        $created = 0;
        $duplicates = 0;

        foreach ($validated['leads'] as $leadData) {
            // Check for duplicates
            $existing = Lead::where('campaign_id', $campaign->id)
                ->where('platform_id', $leadData['platform_id'])
                ->where('platform', $leadData['platform'])
                ->exists();

            if ($existing) {
                $duplicates++;
                continue;
            }

            $confidenceScore = $leadData['confidence_score'] ?? 5;
            $threshold = config('evenleads.scoring.strong_match_threshold', 8);
            $matchType = $confidenceScore >= $threshold ? 'strong' : 'partial';

            Lead::create([
                'user_id' => auth()->id(),
                'campaign_id' => $campaign->id,
                'platform' => $leadData['platform'],
                'platform_id' => $leadData['platform_id'],
                'title' => $leadData['title'],
                'description' => $leadData['description'] ?? null,
                'url' => $leadData['url'],
                'author' => $leadData['author'] ?? null,
                'confidence_score' => $confidenceScore,
                'match_type' => $matchType,
                'status' => 'new',
                'matched_keywords' => $leadData['matched_keywords'] ?? [],
                'synced_at' => now(),
            ]);

            $created++;
        }

        return response()->json([
            'success' => true,
            'message' => "{$created} leads submitted successfully",
            'data' => [
                'created' => $created,
                'duplicates' => $duplicates,
            ],
        ], 201);
    }
}
