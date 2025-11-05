<?php

namespace App\Services;

use App\Models\User;
use Wave\Subscription;
use Wave\Plan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SaasMetricsService
{
    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Get Monthly Recurring Revenue (MRR)
     */
    public function getMRR(): array
    {
        $activeSubscriptions = Subscription::where('status', 'active')
            ->whereNull('ends_at')
            ->with('plan')
            ->get();

        $mrr = 0;
        foreach ($activeSubscriptions as $subscription) {
            if ($subscription->cycle === 'month') {
                $mrr += floatval($subscription->plan->monthly_price ?? 0);
            } elseif ($subscription->cycle === 'year') {
                // Convert yearly to monthly
                $mrr += floatval($subscription->plan->yearly_price ?? 0) / 12;
            }
        }

        // Get previous month MRR for growth calculation
        $lastMonthMrr = $this->getLastMonthMRR();
        $growth = $lastMonthMrr > 0 ? (($mrr - $lastMonthMrr) / $lastMonthMrr) * 100 : 0;

        return [
            'value' => round($mrr, 2),
            'formatted' => '$' . number_format($mrr, 2),
            'growth' => round($growth, 2),
            'growth_formatted' => ($growth >= 0 ? '+' : '') . number_format($growth, 2) . '%'
        ];
    }

    /**
     * Get Annual Recurring Revenue (ARR)
     */
    public function getARR(): array
    {
        $mrr = $this->getMRR();
        $arr = $mrr['value'] * 12;

        return [
            'value' => round($arr, 2),
            'formatted' => '$' . number_format($arr, 2),
        ];
    }

    /**
     * Get total revenue this month
     */
    public function getMonthlyRevenue(): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $revenue = Subscription::where('status', 'active')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->with('plan')
            ->get()
            ->sum(function ($subscription) {
                if ($subscription->cycle === 'month') {
                    return floatval($subscription->plan->monthly_price ?? 0);
                } elseif ($subscription->cycle === 'year') {
                    return floatval($subscription->plan->yearly_price ?? 0);
                }
                return 0;
            });

        $lastMonthRevenue = $this->getLastMonthRevenue();
        $growth = $lastMonthRevenue > 0 ? (($revenue - $lastMonthRevenue) / $lastMonthRevenue) * 100 : 0;

        return [
            'value' => round($revenue, 2),
            'formatted' => '$' . number_format($revenue, 2),
            'growth' => round($growth, 2),
            'growth_formatted' => ($growth >= 0 ? '+' : '') . number_format($growth, 2) . '%'
        ];
    }

    /**
     * Get active subscriptions count
     */
    public function getActiveSubscriptions(): array
    {
        $active = Subscription::where('status', 'active')
            ->whereNull('ends_at')
            ->count();

        $lastMonthActive = Subscription::where('status', 'active')
            ->whereNull('ends_at')
            ->where('created_at', '<', Carbon::now()->subMonth())
            ->count();

        $growth = $lastMonthActive > 0 ? (($active - $lastMonthActive) / $lastMonthActive) * 100 : 0;

        return [
            'value' => $active,
            'formatted' => number_format($active),
            'growth' => round($growth, 2),
            'growth_formatted' => ($growth >= 0 ? '+' : '') . number_format($growth, 2) . '%'
        ];
    }

    /**
     * Get churn rate (monthly)
     */
    public function getChurnRate(): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $startSubscribers = Subscription::where('status', 'active')
            ->where('created_at', '<', $startOfMonth)
            ->count();

        $churned = Subscription::where('status', 'cancelled')
            ->whereBetween('ends_at', [$startOfMonth, Carbon::now()])
            ->count();

        $churnRate = $startSubscribers > 0 ? ($churned / $startSubscribers) * 100 : 0;

        return [
            'value' => round($churnRate, 2),
            'formatted' => number_format($churnRate, 2) . '%',
            'churned_count' => $churned,
        ];
    }

    /**
     * Get new subscriptions this month
     */
    public function getNewSubscriptions(): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();

        $newSubs = Subscription::where('status', 'active')
            ->whereBetween('created_at', [$startOfMonth, Carbon::now()])
            ->count();

        $lastMonthNew = Subscription::where('status', 'active')
            ->whereBetween('created_at', [
                Carbon::now()->subMonth()->startOfMonth(),
                Carbon::now()->subMonth()->endOfMonth()
            ])
            ->count();

        $growth = $lastMonthNew > 0 ? (($newSubs - $lastMonthNew) / $lastMonthNew) * 100 : 0;

        return [
            'value' => $newSubs,
            'formatted' => number_format($newSubs),
            'growth' => round($growth, 2),
            'growth_formatted' => ($growth >= 0 ? '+' : '') . number_format($growth, 2) . '%'
        ];
    }

    /**
     * Get total users
     */
    public function getTotalUsers(): array
    {
        $total = User::count();
        $subscribers = User::whereHas('subscription', function($query) {
            $query->where('status', 'active')->whereNull('ends_at');
        })->count();

        $conversionRate = $total > 0 ? ($subscribers / $total) * 100 : 0;

        return [
            'total' => $total,
            'total_formatted' => number_format($total),
            'subscribers' => $subscribers,
            'subscribers_formatted' => number_format($subscribers),
            'conversion_rate' => round($conversionRate, 2),
            'conversion_formatted' => number_format($conversionRate, 2) . '%',
        ];
    }

    /**
     * Get average revenue per user (ARPU)
     */
    public function getARPU(): array
    {
        $mrr = $this->getMRR()['value'];
        $activeSubscriptions = $this->getActiveSubscriptions()['value'];

        $arpu = $activeSubscriptions > 0 ? $mrr / $activeSubscriptions : 0;

        return [
            'value' => round($arpu, 2),
            'formatted' => '$' . number_format($arpu, 2),
        ];
    }

    /**
     * Get customer lifetime value (LTV) - simplified calculation
     */
    public function getLTV(): array
    {
        $arpu = $this->getARPU()['value'];
        $churnRate = $this->getChurnRate()['value'];

        // LTV = ARPU / Churn Rate (monthly)
        $ltv = $churnRate > 0 ? ($arpu / ($churnRate / 100)) : $arpu * 12;

        return [
            'value' => round($ltv, 2),
            'formatted' => '$' . number_format($ltv, 2),
        ];
    }

    /**
     * Get plan distribution
     */
    public function getPlanDistribution(): array
    {
        $distribution = Subscription::where('status', 'active')
            ->whereNull('ends_at')
            ->select('plan_id', DB::raw('count(*) as count'))
            ->groupBy('plan_id')
            ->with('plan')
            ->get()
            ->map(function($item) {
                return [
                    'plan_name' => $item->plan->name ?? 'Unknown',
                    'count' => $item->count,
                    'percentage' => 0, // Will calculate after
                ];
            });

        $total = $distribution->sum('count');

        return $distribution->map(function($item) use ($total) {
            $item['percentage'] = $total > 0 ? round(($item['count'] / $total) * 100, 2) : 0;
            return $item;
        })->toArray();
    }

    /**
     * Get revenue by plan
     */
    public function getRevenueByPlan(): array
    {
        $revenueByPlan = Subscription::where('status', 'active')
            ->whereNull('ends_at')
            ->with('plan')
            ->get()
            ->groupBy('plan_id')
            ->map(function($subscriptions, $planId) {
                $revenue = $subscriptions->sum(function($sub) {
                    if ($sub->cycle === 'month') {
                        return floatval($sub->plan->monthly_price ?? 0);
                    } elseif ($sub->cycle === 'year') {
                        return floatval($sub->plan->yearly_price ?? 0) / 12;
                    }
                    return 0;
                });

                return [
                    'plan_name' => $subscriptions->first()->plan->name ?? 'Unknown',
                    'mrr' => round($revenue, 2),
                    'mrr_formatted' => '$' . number_format($revenue, 2),
                    'count' => $subscriptions->count(),
                ];
            })->values()->toArray();

        return $revenueByPlan;
    }

    /**
     * Get trial conversion rate
     */
    public function getTrialConversion(): array
    {
        $trialDays = (int) setting('trial_days', 0);

        if ($trialDays === 0) {
            return [
                'value' => 0,
                'formatted' => 'N/A',
                'message' => 'Trials not configured'
            ];
        }

        // This is a simplified calculation - in production you'd track trial starts
        $recentSubscriptions = Subscription::where('status', 'active')
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->count();

        return [
            'value' => $recentSubscriptions,
            'formatted' => number_format($recentSubscriptions),
            'message' => 'New paying customers (last 30 days)'
        ];
    }

    /**
     * Helper: Get last month MRR
     */
    protected function getLastMonthMRR(): float
    {
        $lastMonth = Carbon::now()->subMonth();

        $subscriptions = Subscription::where('status', 'active')
            ->where('created_at', '<=', $lastMonth->endOfMonth())
            ->whereNull('ends_at')
            ->with('plan')
            ->get();

        $mrr = 0;
        foreach ($subscriptions as $subscription) {
            if ($subscription->cycle === 'month') {
                $mrr += floatval($subscription->plan->monthly_price ?? 0);
            } elseif ($subscription->cycle === 'year') {
                $mrr += floatval($subscription->plan->yearly_price ?? 0) / 12;
            }
        }

        return $mrr;
    }

    /**
     * Helper: Get last month revenue
     */
    protected function getLastMonthRevenue(): float
    {
        $startOfLastMonth = Carbon::now()->subMonth()->startOfMonth();
        $endOfLastMonth = Carbon::now()->subMonth()->endOfMonth();

        return Subscription::where('status', 'active')
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
            ->with('plan')
            ->get()
            ->sum(function ($subscription) {
                if ($subscription->cycle === 'month') {
                    return floatval($subscription->plan->monthly_price ?? 0);
                } elseif ($subscription->cycle === 'year') {
                    return floatval($subscription->plan->yearly_price ?? 0);
                }
                return 0;
            });
    }
}
