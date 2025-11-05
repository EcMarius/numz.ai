<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Wave\Plugins\EvenLeads\Models\Campaign;
use Wave\Plugins\EvenLeads\Models\Lead;
use Wave\Plugins\EvenLeads\Models\AIGeneration;
use Wave\Plugins\EvenLeads\Models\SyncHistory;

class EnforcePlanLimits
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $limitType  The type of limit to check (campaigns|manual_sync|ai_generation|leads_storage)
     */
    public function handle(Request $request, Closure $next, string $limitType): Response
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        // Get active subscription
        $subscription = \Wave\Subscription::where('billable_id', $user->id)
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription. Please subscribe to a plan to use this feature.',
                'redirect' => route('pricing')
            ], 403);
        }

        // Get plan limits from snapshot (this is what user paid for!)
        $limits = $subscription->plan_limits_snapshot
            ? (is_string($subscription->plan_limits_snapshot)
                ? json_decode($subscription->plan_limits_snapshot, true)
                : $subscription->plan_limits_snapshot)
            : [];

        // Check specific limit type
        switch ($limitType) {
            case 'campaigns':
                return $this->checkCampaignLimit($user, $limits);

            case 'manual_sync':
                return $this->checkManualSyncLimit($user, $limits, $request);

            case 'ai_generation':
                return $this->checkAIGenerationLimit($user, $limits);

            case 'leads_storage':
                return $this->checkLeadsStorageLimit($user, $limits);

            default:
                return $next($request);
        }
    }

    protected function checkCampaignLimit($user, $limits)
    {
        $current = Campaign::where('user_id', $user->id)
            ->whereNotIn('status', ['disabled_by_downgrade', 'archived'])
            ->count();
        $max = $limits['campaigns'] ?? 0;

        if ($current >= $max) {
            return response()->json([
                'success' => false,
                'message' => "Campaign limit reached ({$max}/{$max}). Upgrade your plan to create more campaigns.",
                'limit' => [
                    'current' => $current,
                    'max' => $max,
                    'upgrade_url' => route('pricing')
                ]
            ], 403);
        }

        return app()->call(function(Request $request, Closure $next) {
            return $next($request);
        });
    }

    protected function checkManualSyncLimit($user, $limits, $request)
    {
        $startOfMonth = now()->startOfMonth();
        $current = SyncHistory::whereHas('campaign', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->where('sync_type', 'manual')
        ->where('created_at', '>=', $startOfMonth)
        ->count();

        $max = $limits['manual_syncs_per_month'] ?? 0;

        if ($current >= $max) {
            $resetsAt = now()->endOfMonth()->addSecond();
            return response()->json([
                'success' => false,
                'message' => "Monthly sync limit reached ({$max}/{$max}). Limit resets on {$resetsAt->format('M d, Y')}.",
                'limit' => [
                    'current' => $current,
                    'max' => $max,
                    'resets_at' => $resetsAt->toIso8601String(),
                    'upgrade_url' => route('pricing')
                ]
            ], 429);
        }

        return app()->call(function(Request $request, Closure $next) {
            return $next($request);
        });
    }

    protected function checkAIGenerationLimit($user, $limits)
    {
        $startOfMonth = now()->startOfMonth();
        $current = AIGeneration::whereHas('lead', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->where('created_at', '>=', $startOfMonth)
        ->count();

        $max = $limits['ai_replies_per_month'] ?? 0;

        if ($current >= $max) {
            $resetsAt = now()->endOfMonth()->addSecond();
            return response()->json([
                'success' => false,
                'message' => "Monthly AI generation limit reached ({$max}/{$max}). Limit resets on {$resetsAt->format('M d, Y')}.",
                'limit' => [
                    'current' => $current,
                    'max' => $max,
                    'resets_at' => $resetsAt->toIso8601String(),
                    'upgrade_url' => route('pricing')
                ]
            ], 429);
        }

        return app()->call(function(Request $request, Closure $next) {
            return $next($request);
        });
    }

    protected function checkLeadsStorageLimit($user, $limits)
    {
        $current = Lead::where('user_id', $user->id)
            ->where('archived', false)
            ->count();
        $max = $limits['leads_storage'] ?? 0;

        if ($current >= $max) {
            return response()->json([
                'success' => false,
                'message' => "Leads storage limit reached ({$max}/{$max}). Upgrade your plan or delete old leads.",
                'limit' => [
                    'current' => $current,
                    'max' => $max,
                    'upgrade_url' => route('pricing')
                ]
            ], 403);
        }

        return app()->call(function(Request $request, Closure $next) {
            return $next($request);
        });
    }
}
