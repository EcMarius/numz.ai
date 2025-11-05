<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Wave\Plugins\EvenLeads\Models\Campaign;
use Wave\Plugins\EvenLeads\Models\Lead;
use Wave\Plugins\EvenLeads\Models\AIGeneration;
use Wave\Plugins\EvenLeads\Models\SyncHistory;

/**
 * @group Account
 *
 * API endpoints for account information and usage
 */
class AccountController extends Controller
{
    /**
     * Get account usage and limits
     *
     * Retrieve current usage statistics and plan limits for EvenLeads features.
     *
     * @authenticated
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "name": "John Doe",
     *       "email": "john@example.com"
     *     },
     *     "plan": {
     *       "name": "Professional",
     *       "interval": "monthly"
     *     },
     *     "limits": {
     *       "campaigns": {
     *         "current": 3,
     *         "max": 5,
     *         "percentage": 60
     *       },
     *       "leads_storage": {
     *         "current": 1250,
     *         "max": 5000,
     *         "percentage": 25
     *       },
     *       "leads_per_sync": {
     *         "max": 100
     *       },
     *       "ai_replies_per_month": {
     *         "current": 45,
     *         "max": 500,
     *         "percentage": 9,
     *         "resets_at": "2025-02-01T00:00:00.000000Z"
     *       },
     *       "manual_syncs_per_month": {
     *         "current": 12,
     *         "max": 100,
     *         "percentage": 12,
     *         "resets_at": "2025-02-01T00:00:00.000000Z"
     *       },
     *       "keywords_per_campaign": {
     *         "max": 20
     *       },
     *       "automated_sync_interval_minutes": {
     *         "value": 60
     *       }
     *     }
     *   }
     * }
     */
    public function usage(Request $request)
    {
        $user = auth()->user();

        // Get subscription and plan info
        $subscription = $user->subscription;
        $plan = null;
        $limits = [];
        $planInterval = null;

        if ($subscription && $subscription->plan) {
            // User has an active paid subscription
            $plan = $subscription->plan;
            $limits = $plan->custom_properties['evenleads'] ?? [];
            $planName = $plan->name;
            $planInterval = $subscription->cycle === 'month' ? 'monthly' : 'yearly';
        } else {
            // No paid subscription (could be trial, free, or nothing)
            $plan = (object) ['name' => 'No Active Subscription'];
            $limits = [];
            $planName = 'No Active Subscription';
            $planInterval = 'none';

            // Add trial info if on trial
            if ($user->onTrial() && $user->trial_ends_at) {
                $planInterval = 'trial';
            }
        }

        // Get current usage
        $campaignsCount = Campaign::where('user_id', $user->id)->count();
        $leadsCount = Lead::where('user_id', $user->id)->count();

        // Get monthly usage (current month)
        $startOfMonth = now()->startOfMonth();
        $aiRepliesThisMonth = AIGeneration::whereHas('lead', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->where('created_at', '>=', $startOfMonth)->count();

        $manualSyncsThisMonth = SyncHistory::whereHas('campaign', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->where('sync_type', 'manual')
        ->where('created_at', '>=', $startOfMonth)
        ->count();

        // Build response
        $response = [
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'plan' => [
                    'name' => $planName,
                    'interval' => $planInterval,
                ],
                'limits' => [],
            ],
        ];

        // Campaigns
        if (isset($limits['campaigns'])) {
            $max = (int) $limits['campaigns'];
            $response['data']['limits']['campaigns'] = [
                'current' => $campaignsCount,
                'max' => $max,
                'percentage' => $max > 0 ? round(($campaignsCount / $max) * 100) : 0,
            ];
        }

        // Leads storage
        if (isset($limits['leads_storage'])) {
            $max = (int) $limits['leads_storage'];
            $response['data']['limits']['leads_storage'] = [
                'current' => $leadsCount,
                'max' => $max,
                'percentage' => $max > 0 ? round(($leadsCount / $max) * 100) : 0,
            ];
        }

        // Leads per sync
        if (isset($limits['leads_per_sync'])) {
            $response['data']['limits']['leads_per_sync'] = [
                'max' => (int) $limits['leads_per_sync'],
            ];
        }

        // AI replies per month
        if (isset($limits['ai_replies_per_month'])) {
            $max = (int) $limits['ai_replies_per_month'];
            $response['data']['limits']['ai_replies_per_month'] = [
                'current' => $aiRepliesThisMonth,
                'max' => $max,
                'percentage' => $max > 0 ? round(($aiRepliesThisMonth / $max) * 100) : 0,
                'resets_at' => now()->endOfMonth()->addSecond(),
            ];
        }

        // Manual syncs per month
        if (isset($limits['manual_syncs_per_month'])) {
            $max = (int) $limits['manual_syncs_per_month'];
            $response['data']['limits']['manual_syncs_per_month'] = [
                'current' => $manualSyncsThisMonth,
                'max' => $max,
                'percentage' => $max > 0 ? round(($manualSyncsThisMonth / $max) * 100) : 0,
                'resets_at' => now()->endOfMonth()->addSecond(),
            ];
        }

        // Keywords per campaign
        if (isset($limits['keywords_per_campaign'])) {
            $response['data']['limits']['keywords_per_campaign'] = [
                'max' => (int) $limits['keywords_per_campaign'],
            ];
        }

        // Automated sync interval
        if (isset($limits['automated_sync_interval_minutes'])) {
            $response['data']['limits']['automated_sync_interval_minutes'] = [
                'value' => (int) $limits['automated_sync_interval_minutes'],
            ];
        }

        return response()->json($response);
    }
}
