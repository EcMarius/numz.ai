<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Wave\Plugins\EvenLeads\Models\Campaign;
use Wave\Plugins\SocialAuth\Models\SocialAccount;

/**
 * @group Campaigns
 *
 * API endpoints for managing campaigns
 */
class CampaignController extends Controller
{
    /**
     * List all campaigns
     *
     * Get a paginated list of all campaigns for the authenticated user.
     *
     * @authenticated
     * @queryParam page integer Page number for pagination. Example: 1
     * @queryParam per_page integer Number of items per page (max 100). Example: 15
     * @queryParam status string Filter by status (active, paused, completed). Example: active
     * @queryParam platform string Filter by platform (reddit, facebook, etc). Example: reddit
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "campaigns": [
     *       {
     *         "id": 1,
     *         "name": "Web Development Services",
     *         "offering": "Custom web development",
     *         "website_url": "https://example.com",
     *         "platforms": ["reddit", "facebook"],
     *         "status": "active",
     *         "keywords": ["web development", "website"],
     *         "strong_matches_count": 15,
     *         "partial_matches_count": 8,
     *         "new_leads_count": 5,
     *         "last_sync_at": "2025-01-15T10:30:00.000000Z",
     *         "next_sync_at": "2025-01-15T11:30:00.000000Z",
     *         "created_at": "2025-01-01T00:00:00.000000Z",
     *         "updated_at": "2025-01-15T10:30:00.000000Z"
     *       }
     *     ],
     *     "pagination": {
     *       "current_page": 1,
     *       "per_page": 15,
     *       "total": 25,
     *       "last_page": 2
     *     }
     *   }
     * }
     */
    public function index(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 15), 100);

        $query = Campaign::where('user_id', auth()->id());

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('platform')) {
            $platform = $request->input('platform');
            $query->whereJsonContains('platforms', $platform);
        }

        $campaigns = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'campaigns' => $campaigns->items(),
                'pagination' => [
                    'current_page' => $campaigns->currentPage(),
                    'per_page' => $campaigns->perPage(),
                    'total' => $campaigns->total(),
                    'last_page' => $campaigns->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * Get a single campaign
     *
     * Retrieve detailed information about a specific campaign including all settings, matches, and sync history.
     *
     * @authenticated
     * @urlParam id integer required The campaign ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "name": "Web Development Services",
     *     "offering": "Custom web development",
     *     "website_url": "https://example.com",
     *     "portfolio_path": "/portfolio",
     *     "platforms": ["reddit", "facebook"],
     *     "facebook_groups": ["group1", "group2"],
     *     "keywords": ["web development", "website"],
     *     "include_keywords": ["need", "looking for"],
     *     "ai_settings": {
     *       "tone": "professional",
     *       "length": "medium"
     *     },
     *     "include_call_to_action": true,
     *     "status": "active",
     *     "strong_matches_count": 15,
     *     "partial_matches_count": 8,
     *     "new_leads_count": 5,
     *     "last_sync_at": "2025-01-15T10:30:00.000000Z",
     *     "next_sync_at": "2025-01-15T11:30:00.000000Z",
     *     "created_at": "2025-01-01T00:00:00.000000Z",
     *     "updated_at": "2025-01-15T10:30:00.000000Z"
     *   }
     * }
     * @response 404 {
     *   "success": false,
     *   "message": "Campaign not found"
     * }
     */
    public function show(Request $request, $id)
    {
        $campaign = Campaign::where('user_id', auth()->id())
            ->with('syncHistory')
            ->find($id);

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $campaign,
        ]);
    }

    /**
     * Create a new campaign
     *
     * Create a new campaign. You can only create campaigns for social platforms your account is connected with.
     *
     * @authenticated
     * @bodyParam name string required The campaign name. Example: Web Development Services
     * @bodyParam offering string required Description of what you're offering. Example: Custom web development and design services
     * @bodyParam website_url string The website URL. Example: https://example.com
     * @bodyParam portfolio_path string Portfolio path. Example: /portfolio
     * @bodyParam platforms array required Array of platforms (must be connected). Example: ["reddit", "facebook"]
     * @bodyParam facebook_groups array Facebook groups to monitor (required if facebook in platforms). Example: ["group1", "group2"]
     * @bodyParam keywords array required Keywords to search for. Example: ["web development", "website"]
     * @bodyParam include_keywords array Additional filter keywords. Example: ["need", "looking for"]
     * @bodyParam ai_settings object AI generation settings. Example: {"tone": "professional", "length": "medium"}
     * @bodyParam include_call_to_action boolean Include call to action. Example: true
     * @bodyParam status string Campaign status (active, paused). Example: active
     * @response 201 {
     *   "success": true,
     *   "message": "Campaign created successfully",
     *   "data": {
     *     "id": 1,
     *     "name": "Web Development Services",
     *     "status": "active",
     *     "created_at": "2025-01-15T12:00:00.000000Z"
     *   }
     * }
     * @response 422 {
     *   "success": false,
     *   "message": "Validation failed",
     *   "errors": {
     *     "platforms": ["You are not connected to facebook. Please connect your account first."]
     *   }
     * }
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'offering' => 'required|string',
            'website_url' => 'nullable|url',
            'portfolio_path' => 'nullable|string',
            'platforms' => 'required|array',
            'platforms.*' => 'required|string|in:reddit,facebook,twitter,linkedin',
            'facebook_groups' => 'required_if:platforms,facebook|array',
            'keywords' => 'required|array|min:1',
            'keywords.*' => 'required|string',
            'negative_keywords' => 'nullable|array',
            'negative_keywords.*' => 'nullable|string',
            'include_keywords' => 'nullable|array',
            'include_keywords.*' => 'nullable|string',
            'ai_settings' => 'nullable|array',
            'include_call_to_action' => 'nullable|boolean',
            'status' => 'nullable|string|in:active,paused',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Validate that user is connected to the requested platforms
        $connectedPlatforms = SocialAccount::where('user_id', auth()->id())
            ->pluck('provider')
            ->toArray();

        $requestedPlatforms = $request->input('platforms', []);
        $notConnected = array_diff($requestedPlatforms, $connectedPlatforms);

        if (!empty($notConnected)) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => [
                    'platforms' => [
                        'You are not connected to: ' . implode(', ', $notConnected) . '. Please connect your account first.',
                    ],
                ],
            ], 422);
        }

        $campaign = Campaign::create([
            'user_id' => auth()->id(),
            'name' => $request->input('name'),
            'offering' => $request->input('offering'),
            'website_url' => $request->input('website_url'),
            'portfolio_path' => $request->input('portfolio_path'),
            'platforms' => $request->input('platforms'),
            'facebook_groups' => $request->input('facebook_groups', []),
            'keywords' => $request->input('keywords'),
            'negative_keywords' => $request->input('negative_keywords', []),
            'include_keywords' => $request->input('include_keywords', []),
            'ai_settings' => $request->input('ai_settings', []),
            'include_call_to_action' => $request->input('include_call_to_action', true),
            'status' => $request->input('status', 'active'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Campaign created successfully',
            'data' => $campaign,
        ], 201);
    }

    /**
     * Update a campaign
     *
     * Update an existing campaign. Same validation rules as creation apply.
     *
     * @authenticated
     * @urlParam id integer required The campaign ID. Example: 1
     * @bodyParam name string The campaign name. Example: Updated Campaign Name
     * @bodyParam offering string Description of what you're offering. Example: Updated offering description
     * @bodyParam website_url string The website URL. Example: https://example.com
     * @bodyParam portfolio_path string Portfolio path. Example: /portfolio
     * @bodyParam platforms array Array of platforms (must be connected). Example: ["reddit"]
     * @bodyParam facebook_groups array Facebook groups to monitor. Example: ["group1"]
     * @bodyParam keywords array Keywords to search for. Example: ["web dev", "website"]
     * @bodyParam include_keywords array Additional filter keywords. Example: ["need"]
     * @bodyParam ai_settings object AI generation settings. Example: {"tone": "casual"}
     * @bodyParam include_call_to_action boolean Include call to action. Example: false
     * @bodyParam status string Campaign status. Example: paused
     * @response 200 {
     *   "success": true,
     *   "message": "Campaign updated successfully",
     *   "data": {
     *     "id": 1,
     *     "name": "Updated Campaign Name",
     *     "updated_at": "2025-01-15T12:30:00.000000Z"
     *   }
     * }
     * @response 404 {
     *   "success": false,
     *   "message": "Campaign not found"
     * }
     */
    public function update(Request $request, $id)
    {
        $campaign = Campaign::where('user_id', auth()->id())->find($id);

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'offering' => 'sometimes|string',
            'website_url' => 'nullable|url',
            'portfolio_path' => 'nullable|string',
            'platforms' => 'sometimes|array',
            'platforms.*' => 'required|string|in:reddit,facebook,twitter,linkedin',
            'facebook_groups' => 'required_if:platforms,facebook|array',
            'keywords' => 'sometimes|array|min:1',
            'keywords.*' => 'required|string',
            'negative_keywords' => 'nullable|array',
            'negative_keywords.*' => 'nullable|string',
            'include_keywords' => 'nullable|array',
            'include_keywords.*' => 'nullable|string',
            'ai_settings' => 'nullable|array',
            'include_call_to_action' => 'nullable|boolean',
            'status' => 'nullable|string|in:active,paused,completed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Validate platform connections if platforms are being updated
        if ($request->has('platforms')) {
            $connectedPlatforms = SocialAccount::where('user_id', auth()->id())
                ->pluck('provider')
                ->toArray();

            $requestedPlatforms = $request->input('platforms', []);
            $notConnected = array_diff($requestedPlatforms, $connectedPlatforms);

            if (!empty($notConnected)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => [
                        'platforms' => [
                            'You are not connected to: ' . implode(', ', $notConnected) . '. Please connect your account first.',
                        ],
                    ],
                ], 422);
            }
        }

        $campaign->update($request->only([
            'name',
            'offering',
            'website_url',
            'portfolio_path',
            'platforms',
            'facebook_groups',
            'keywords',
            'negative_keywords',
            'include_keywords',
            'ai_settings',
            'include_call_to_action',
            'status',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Campaign updated successfully',
            'data' => $campaign->fresh(),
        ]);
    }

    /**
     * Delete a campaign
     *
     * Soft delete a campaign and all its associated data.
     *
     * @authenticated
     * @urlParam id integer required The campaign ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "message": "Campaign deleted successfully"
     * }
     * @response 404 {
     *   "success": false,
     *   "message": "Campaign not found"
     * }
     */
    public function destroy($id)
    {
        $campaign = Campaign::where('user_id', auth()->id())->find($id);

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found',
            ], 404);
        }

        $campaign->delete();

        return response()->json([
            'success' => true,
            'message' => 'Campaign deleted successfully',
        ]);
    }
}
