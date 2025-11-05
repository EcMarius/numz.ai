<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Wave\ApiKey;
use App\Models\User;
use Wave\Subscription;

/**
 * @group Authentication
 *
 * API endpoints for authentication
 */
class AuthController extends Controller
{
    /**
     * Login (for browser extension)
     *
     * Authenticates a user and returns a token for API access.
     *
     * @bodyParam email string required The user's email address. Example: user@example.com
     * @bodyParam password string required The user's password. Example: password123
     *
     * @response 200 {
     *   "token": "1|abc123...",
     *   "user": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "email": "john@example.com",
     *     "avatar": "https://..."
     *   }
     * }
     * @response 422 {
     *   "message": "The provided credentials are incorrect."
     * }
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Create a Sanctum token
        $token = $user->createToken('browser-extension')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar(), // avatar() method already returns full URL
                'role_id' => $user->role_id,
                'roles' => $user->getRoleNames()->toArray(), // Array of role names (e.g., ['admin', 'editor'])
            ],
        ]);
    }

    /**
     * Get Current User
     *
     * Returns the authenticated user's information.
     *
     * @authenticated
     *
     * @response 200 {
     *   "id": 1,
     *   "name": "John Doe",
     *   "email": "john@example.com",
     *   "avatar": "https://..."
     * }
     */
    public function user(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar(), // avatar() method already returns full URL
            'role_id' => $user->role_id,
            'roles' => $user->getRoleNames()->toArray(), // Array of role names (e.g., ['admin', 'editor'])
        ]);
    }

    /**
     * Get User Subscription
     *
     * Returns the authenticated user's subscription details.
     *
     * @authenticated
     *
     * @response 200 {
     *   "id": 1,
     *   "user_id": 1,
     *   "plan_id": 2,
     *   "status": "active",
     *   "trial_ends_at": null,
     *   "ends_at": null,
     *   "plan": {
     *     "id": 2,
     *     "name": "Pro",
     *     "features": ["feature1", "feature2"],
     *     "campaigns_limit": 10,
     *     "leads_per_sync": 100,
     *     "manual_syncs_limit": 30,
     *     "ai_replies_limit": 500
     *   }
     * }
     */
    public function subscription(Request $request)
    {
        $user = $request->user();
        $subscription = $user->subscription()->with('plan')->first();

        if (!$subscription) {
            return response()->json([
                'id' => null,
                'user_id' => $user->id,
                'plan_id' => null,
                'status' => 'none',
                'plan' => null,
                'used_campaigns' => 0,
                'used_manual_syncs' => 0,
                'used_ai_replies' => 0,
                'used_leads' => 0,
                'used_crm_contacts' => 0,
            ]);
        }

        // Extract EvenLeads plan limits from custom_properties (direct JSON decode)
        $evenleadsProps = $subscription->plan ? (json_decode($subscription->plan->custom_properties, true)['evenleads'] ?? []) : [];

        // Get usage stats (same logic as validatePlan)
        $campaignsUsed = $user->campaigns()->count();
        $syncsUsed = $user->sync_history()
            ->where('created_at', '>=', now()->startOfMonth())
            ->where('sync_type', 'manual')
            ->count();
        $leadsUsed = \Wave\Plugins\EvenLeads\Models\Lead::where('user_id', $user->id)->count();

        // AI replies - use App\Models namespace
        $aiRepliesUsed = \App\Models\LeadMessage::where('user_id', $user->id)
            ->where('created_at', '>=', now()->startOfMonth())
            ->where('is_ai_generated', true)
            ->count();

        // CRM Contacts - check if model exists
        $crmContactsUsed = class_exists('\Wave\Plugins\EvenLeads\Models\CrmContact')
            ? \Wave\Plugins\EvenLeads\Models\CrmContact::where('user_id', $user->id)->count()
            : 0;

        return response()->json([
            'id' => $subscription->id,
            'user_id' => $subscription->user_id,
            'plan_id' => $subscription->plan_id,
            'status' => $subscription->status,
            'trial_ends_at' => $subscription->trial_ends_at,
            'ends_at' => $subscription->ends_at,
            'plan' => $subscription->plan ? [
                'id' => $subscription->plan->id,
                'name' => $subscription->plan->name,
                'features' => $subscription->plan->features ?? [],
                'campaigns_limit' => $evenleadsProps['campaigns'] ?? 0,
                'leads_per_sync' => $subscription->plan->leads_per_sync ?? 0,
                'manual_syncs_limit' => $evenleadsProps['manual_syncs_per_month'] ?? 0,
                'ai_replies_limit' => $evenleadsProps['ai_replies_per_month'] ?? 0,
                'crm_contacts_limit' => $evenleadsProps['crm_contacts'] ?? 100,
                'leads_limit' => $evenleadsProps['leads_storage'] ?? 0,
                'active' => $subscription->plan->active ?? false,
            ] : null,
            // Include usage data
            'used_campaigns' => $campaignsUsed,
            'used_manual_syncs' => $syncsUsed,
            'used_ai_replies' => $aiRepliesUsed,
            'used_leads' => $leadsUsed,
            'used_crm_contacts' => $crmContactsUsed,
        ]);
    }

    /**
     * Validate Plan
     *
     * Validates the user's subscription and returns current usage limits.
     *
     * @authenticated
     *
     * @response 200 {
     *   "valid": true,
     *   "subscription": {...},
     *   "limits": {
     *     "campaigns": {"used": 3, "limit": 10},
     *     "syncs": {"used": 5, "limit": 30}
     *   }
     * }
     */
    public function validatePlan(Request $request)
    {
        $user = $request->user();
        $subscription = $user->subscription()->with('plan')->first();

        if (!$subscription || !$subscription->plan || !$subscription->plan->active) {
            return response()->json([
                'valid' => false,
                'subscription' => null,
                'limits' => null,
                'message' => 'No active subscription found.',
            ]);
        }

        // Check if subscription is active or trialing
        $isValid = in_array($subscription->status, ['active', 'trialing']);

        // Extract EvenLeads plan limits from custom_properties (direct JSON decode)
        $evenleadsProps = json_decode($subscription->plan->custom_properties, true)['evenleads'] ?? [];

        // Get usage stats
        $campaignsUsed = $user->campaigns()->count();
        $syncsUsed = $user->sync_history()
            ->where('created_at', '>=', now()->startOfMonth())
            ->where('sync_type', 'manual')
            ->count();
        $leadsUsed = \Wave\Plugins\EvenLeads\Models\Lead::where('user_id', $user->id)->count();

        // AI replies - use App\Models namespace
        $aiRepliesUsed = \App\Models\LeadMessage::where('user_id', $user->id)
            ->where('created_at', '>=', now()->startOfMonth())
            ->where('is_ai_generated', true)
            ->count();

        // CRM Contacts - check if model exists
        $crmContactsUsed = class_exists('\Wave\Plugins\EvenLeads\Models\CrmContact')
            ? \Wave\Plugins\EvenLeads\Models\CrmContact::where('user_id', $user->id)->count()
            : 0;

        return response()->json([
            'valid' => $isValid,
            'subscription' => [
                'id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'plan_id' => $subscription->plan_id,
                'status' => $subscription->status,
                'trial_ends_at' => $subscription->trial_ends_at,
                'ends_at' => $subscription->ends_at,
                'plan' => [
                    'id' => $subscription->plan->id,
                    'name' => $subscription->plan->name,
                    'features' => $subscription->plan->features ?? [],
                    'campaigns_limit' => $evenleadsProps['campaigns'] ?? 0,
                    'leads_per_sync' => $subscription->plan->leads_per_sync,
                    'manual_syncs_limit' => $evenleadsProps['manual_syncs_per_month'] ?? 0,
                    'ai_replies_limit' => $evenleadsProps['ai_replies_per_month'] ?? 0,
                    'crm_contacts_limit' => $evenleadsProps['crm_contacts'] ?? 100,
                    'leads_limit' => $evenleadsProps['leads_storage'] ?? 0,
                    'active' => $subscription->plan->active ?? false,
                ],
                // Include usage data directly in subscription object (matches getSubscription structure)
                'used_campaigns' => $campaignsUsed,
                'used_manual_syncs' => $syncsUsed,
                'used_ai_replies' => $aiRepliesUsed,
                'used_leads' => $leadsUsed,
                'used_crm_contacts' => $crmContactsUsed,
            ],
            'limits' => [
                'campaigns' => [
                    'used' => $campaignsUsed,
                    'limit' => $evenleadsProps['campaigns'] ?? 0,
                ],
                'syncs' => [
                    'used' => $syncsUsed,
                    'limit' => $evenleadsProps['manual_syncs_per_month'] ?? 0,
                ],
                'ai_replies' => [
                    'used' => $aiRepliesUsed,
                    'limit' => $evenleadsProps['ai_replies_per_month'] ?? 0,
                ],
                'leads' => [
                    'used' => $leadsUsed,
                    'limit' => $evenleadsProps['leads'] ?? 0,
                ],
                'crm_contacts' => [
                    'used' => $crmContactsUsed,
                    'limit' => $evenleadsProps['crm_contacts'] ?? 100,
                ],
            ],
        ]);
    }

    /**
     * Get User Stats
     *
     * Returns stats for the dashboard including total leads, leads by platform, active campaigns, and recent activity.
     *
     * @authenticated
     *
     * @response 200 {
     *   "totalLeads": 45,
     *   "leadsByPlatform": {
     *     "facebook": 20,
     *     "linkedin": 15,
     *     "reddit": 10
     *   },
     *   "activeCampaigns": 3,
     *   "recentActivity": []
     * }
     */
    public function stats(Request $request)
    {
        $user = $request->user();

        // Get total leads count
        $totalLeads = \Wave\Plugins\EvenLeads\Models\Lead::where('user_id', $user->id)->count();

        // Get leads by platform
        $leadsByPlatform = \Wave\Plugins\EvenLeads\Models\Lead::where('user_id', $user->id)
            ->selectRaw('platform, COUNT(*) as count')
            ->groupBy('platform')
            ->pluck('count', 'platform')
            ->toArray();

        // Get ALL campaigns count (not just active - user wants to see total campaigns)
        $activeCampaigns = \Wave\Plugins\EvenLeads\Models\Campaign::where('user_id', $user->id)->count();

        // Get recent activity with pagination support
        $activityPage = (int) $request->input('activity_page', 1);
        $activityPerPage = min((int) $request->input('activity_per_page', 5), 50); // Default 5, max 50

        $recentActivityQuery = \Wave\Plugins\EvenLeads\Models\Lead::where('user_id', $user->id)
            ->with('campaign:id,name')
            ->orderBy('created_at', 'desc');

        // Get total count for pagination
        $totalActivity = $recentActivityQuery->count();

        // Paginate
        $recentActivityLeads = $recentActivityQuery
            ->skip(($activityPage - 1) * $activityPerPage)
            ->take($activityPerPage)
            ->get();

        $recentActivity = $recentActivityLeads->map(function ($lead) {
            return [
                'id' => $lead->id,
                'type' => 'lead_collected',
                'message' => sprintf('New lead from %s: %s', ucfirst($lead->platform), $lead->title),
                'timestamp' => $lead->created_at->toISOString(),
            ];
        });

        return response()->json([
            'totalLeads' => $totalLeads,
            'leadsByPlatform' => $leadsByPlatform,
            'activeCampaigns' => $activeCampaigns,
            'recentActivity' => [
                'data' => $recentActivity,
                'current_page' => $activityPage,
                'per_page' => $activityPerPage,
                'total' => $totalActivity,
                'last_page' => (int) ceil($totalActivity / $activityPerPage),
            ],
        ]);
    }

    /**
     * Logout
     *
     * Revokes the current authentication token.
     *
     * @authenticated
     *
     * @response 200 {
     *   "message": "Successfully logged out"
     * }
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Successfully logged out',
        ]);
    }
    /**
     * Validate API Key
     *
     * Validates if an API key is valid and returns associated user information.
     *
     * @authenticated
     * @response 200 {
     *   "success": true,
     *   "message": "API key is valid",
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "name": "John Doe",
     *       "email": "john@example.com"
     *     },
     *     "api_key": {
     *       "name": "My API Key",
     *       "created_at": "2025-01-01T00:00:00.000000Z",
     *       "last_used_at": "2025-01-15T12:30:00.000000Z"
     *     }
     *   }
     * }
     * @response 401 {
     *   "success": false,
     *   "message": "Invalid API key."
     * }
     */
    public function validate(Request $request)
    {
        $apiKey = $request->header('X-API-Key') ?? $request->input('api_key');

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'API key is required.',
            ], 401);
        }

        $key = ApiKey::where('key', $apiKey)->with('user')->first();

        if (!$key) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid API key.',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'API key is valid',
            'data' => [
                'user' => [
                    'id' => $key->user->id,
                    'name' => $key->user->name,
                    'email' => $key->user->email,
                ],
                'api_key' => [
                    'name' => $key->name,
                    'created_at' => $key->created_at,
                    'last_used_at' => $key->last_used_at,
                ],
            ],
        ]);
    }
}
