<?php

namespace App\Numz\Services;

use App\Models\RevenueMetric;
use App\Models\ProductMetric;
use App\Models\CustomerSegment;
use App\Models\RevenueForecast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnalyticsService
{
    /**
     * Calculate all metrics for a date
     */
    public function calculateDailyMetrics(\Carbon\Carbon $date = null): void
    {
        $date = $date ?? now();

        Log::info('Calculating daily metrics', ['date' => $date->format('Y-m-d')]);

        // Calculate revenue metrics
        RevenueMetric::calculateForPeriod($date, 'daily');

        // Calculate product metrics for all products
        $products = \App\Models\Product::where('is_active', true)->get();

        foreach ($products as $product) {
            ProductMetric::calculateForProduct($product->id, $date, 'daily');
        }

        Log::info('Daily metrics calculated successfully');
    }

    /**
     * Calculate weekly metrics
     */
    public function calculateWeeklyMetrics(\Carbon\Carbon $date = null): void
    {
        $date = $date ?? now();

        Log::info('Calculating weekly metrics', ['date' => $date->format('Y-m-d')]);

        RevenueMetric::calculateForPeriod($date, 'weekly');

        $products = \App\Models\Product::where('is_active', true)->get();

        foreach ($products as $product) {
            ProductMetric::calculateForProduct($product->id, $date, 'weekly');
        }

        Log::info('Weekly metrics calculated successfully');
    }

    /**
     * Calculate monthly metrics
     */
    public function calculateMonthlyMetrics(\Carbon\Carbon $date = null): void
    {
        $date = $date ?? now();

        Log::info('Calculating monthly metrics', ['date' => $date->format('Y-m-d')]);

        RevenueMetric::calculateForPeriod($date, 'monthly');

        $products = \App\Models\Product::where('is_active', true)->get();

        foreach ($products as $product) {
            ProductMetric::calculateForProduct($product->id, $date, 'monthly');
        }

        // Update customer segments
        $this->updateCustomerSegments();

        // Generate affiliate leaderboard
        \App\Models\AffiliateLeaderboard::generateMonthlyLeaderboard($date->year, $date->month);

        Log::info('Monthly metrics calculated successfully');
    }

    /**
     * Update all customer segments
     */
    public function updateCustomerSegments(): void
    {
        $segments = CustomerSegment::where('is_active', true)->get();

        foreach ($segments as $segment) {
            try {
                $segment->calculateMembers();
                Log::info('Segment updated', ['segment_id' => $segment->id, 'members' => $segment->member_count]);
            } catch (\Exception $e) {
                Log::error('Segment update failed', [
                    'segment_id' => $segment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Generate revenue forecast
     */
    public function generateRevenueForecast(
        int $periods = 12,
        string $periodType = 'monthly',
        string $method = 'linear_regression'
    ): array {
        $forecasts = [];

        for ($i = 1; $i <= $periods; $i++) {
            $forecastDate = now()->add($i, $periodType);

            try {
                $forecast = RevenueForecast::generateForecast(
                    $forecastDate,
                    $periodType,
                    $method,
                    12 // Use 12 historical periods
                );

                $forecasts[] = $forecast;
            } catch (\Exception $e) {
                Log::error('Forecast generation failed', [
                    'date' => $forecastDate->format('Y-m-d'),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $forecasts;
    }

    /**
     * Get dashboard overview data
     */
    public function getDashboardOverview(): array
    {
        // Get latest revenue metrics
        $latestMetric = RevenueMetric::where('period_type', 'monthly')
            ->orderBy('metric_date', 'desc')
            ->first();

        $previousMetric = RevenueMetric::where('period_type', 'monthly')
            ->orderBy('metric_date', 'desc')
            ->skip(1)
            ->first();

        // Get total customers
        $totalCustomers = \App\Models\User::where('role', 'customer')->count();

        // Get active subscriptions
        $activeSubscriptions = \App\Models\Order::where('status', 'active')->count();

        // Get pending invoices
        $pendingInvoices = \App\Models\Invoice::where('status', 'pending')->count();

        // Get affiliate stats
        $affiliateStats = $this->getAffiliateOverview();

        // Calculate growth rates
        $revenueGrowth = $previousMetric && $previousMetric->total_revenue > 0
            ? (($latestMetric->total_revenue - $previousMetric->total_revenue) / $previousMetric->total_revenue) * 100
            : 0;

        $customerGrowth = $latestMetric && $latestMetric->new_customers > 0
            ? $latestMetric->new_customers
            : 0;

        return [
            'revenue' => [
                'current' => $latestMetric?->total_revenue ?? 0,
                'previous' => $previousMetric?->total_revenue ?? 0,
                'growth' => round($revenueGrowth, 2),
            ],
            'mrr' => [
                'current' => $latestMetric?->mrr ?? 0,
                'previous' => $previousMetric?->mrr ?? 0,
                'growth' => $latestMetric?->mrr_growth ?? 0,
            ],
            'arr' => [
                'current' => $latestMetric?->arr ?? 0,
            ],
            'customers' => [
                'total' => $totalCustomers,
                'new' => $customerGrowth,
                'churn_rate' => $latestMetric?->churn_rate ?? 0,
            ],
            'subscriptions' => [
                'active' => $activeSubscriptions,
            ],
            'invoices' => [
                'pending' => $pendingInvoices,
            ],
            'affiliates' => $affiliateStats,
        ];
    }

    /**
     * Get affiliate overview stats
     */
    protected function getAffiliateOverview(): array
    {
        $startDate = now()->startOfMonth();
        $endDate = now();

        $totalAffiliates = \App\Models\Affiliate::where('status', 'active')->count();
        $clicks = \App\Models\AffiliateClick::whereBetween('clicked_at', [$startDate, $endDate])->count();
        $conversions = \App\Models\AffiliateReferral::whereBetween('confirmed_at', [$startDate, $endDate])->count();
        $commissions = \App\Models\AffiliateCommission::whereBetween('earned_date', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')
            ->sum('commission_amount');

        return [
            'total' => $totalAffiliates,
            'clicks' => $clicks,
            'conversions' => $conversions,
            'commissions' => $commissions,
            'conversion_rate' => $clicks > 0 ? round(($conversions / $clicks) * 100, 2) : 0,
        ];
    }

    /**
     * Get revenue trends
     */
    public function getRevenueTrends(int $periods = 12, string $periodType = 'monthly'): array
    {
        return RevenueMetric::getRevenueTrend($periods, $periodType);
    }

    /**
     * Get product performance
     */
    public function getProductPerformance(
        \Carbon\Carbon $startDate = null,
        \Carbon\Carbon $endDate = null,
        int $limit = 10
    ): array {
        $startDate = $startDate ?? now()->subMonth();
        $endDate = $endDate ?? now();

        $topProducts = ProductMetric::getTopPerformers($startDate, $endDate, 'monthly', $limit);

        return $topProducts->map(function ($metric) {
            return [
                'product_id' => $metric->product_id,
                'product_name' => $metric->product->name ?? 'N/A',
                'revenue' => $metric->total_revenue,
                'orders' => $metric->total_orders,
                'avg_order_value' => $metric->total_orders > 0
                    ? round($metric->total_revenue / $metric->total_orders, 2)
                    : 0,
            ];
        })->toArray();
    }

    /**
     * Get customer cohort analysis
     */
    public function getCohortAnalysis(int $months = 6): array
    {
        $cohorts = [];

        for ($i = 0; $i < $months; $i++) {
            $cohortDate = now()->subMonths($i)->startOfMonth();

            // Get customers who signed up in this month
            $newCustomers = \App\Models\User::where('role', 'customer')
                ->whereBetween('created_at', [$cohortDate, $cohortDate->copy()->endOfMonth()])
                ->pluck('id');

            // Calculate retention for each subsequent month
            $retention = [];

            for ($m = 0; $m <= $i; $m++) {
                $checkDate = $cohortDate->copy()->addMonths($m);

                // Count how many made a purchase in this month
                $activeCustomers = \App\Models\Invoice::whereIn('user_id', $newCustomers)
                    ->whereBetween('created_at', [$checkDate->copy()->startOfMonth(), $checkDate->copy()->endOfMonth()])
                    ->where('status', 'paid')
                    ->distinct('user_id')
                    ->count();

                $retention[$m] = $newCustomers->count() > 0
                    ? round(($activeCustomers / $newCustomers->count()) * 100, 2)
                    : 0;
            }

            $cohorts[] = [
                'cohort' => $cohortDate->format('M Y'),
                'size' => $newCustomers->count(),
                'retention' => $retention,
            ];
        }

        return array_reverse($cohorts);
    }

    /**
     * Get customer lifetime value
     */
    public function getCustomerLifetimeValue(): array
    {
        $customers = \App\Models\User::where('role', 'customer')->get();

        $ltvData = $customers->map(function ($customer) {
            $totalRevenue = \App\Models\Invoice::where('user_id', $customer->id)
                ->where('status', 'paid')
                ->sum('total');

            $orderCount = \App\Models\Order::where('user_id', $customer->id)
                ->where('status', 'active')
                ->count();

            $firstOrder = \App\Models\Order::where('user_id', $customer->id)
                ->orderBy('created_at')
                ->first();

            $customerAge = $firstOrder
                ? now()->diffInMonths($firstOrder->created_at)
                : 0;

            return [
                'customer_id' => $customer->id,
                'total_revenue' => $totalRevenue,
                'order_count' => $orderCount,
                'avg_order_value' => $orderCount > 0 ? round($totalRevenue / $orderCount, 2) : 0,
                'customer_age_months' => $customerAge,
                'monthly_value' => $customerAge > 0 ? round($totalRevenue / $customerAge, 2) : $totalRevenue,
            ];
        });

        return [
            'avg_ltv' => $ltvData->avg('total_revenue'),
            'median_ltv' => $ltvData->median('total_revenue'),
            'avg_order_count' => $ltvData->avg('order_count'),
            'avg_order_value' => $ltvData->avg('avg_order_value'),
            'top_customers' => $ltvData->sortByDesc('total_revenue')->take(10)->values()->toArray(),
        ];
    }

    /**
     * Get churn analysis
     */
    public function getChurnAnalysis(): array
    {
        // Get customers who churned in the last 6 months
        $churnedCustomers = \App\Models\Order::where('status', 'cancelled')
            ->where('cancelled_at', '>=', now()->subMonths(6))
            ->with('user')
            ->get();

        // Calculate churn reasons (if tracked)
        $churnReasons = $churnedCustomers->countBy('cancellation_reason');

        // Calculate average customer lifetime before churn
        $avgLifetime = $churnedCustomers->map(function ($order) {
            return $order->created_at->diffInMonths($order->cancelled_at);
        })->avg();

        // Get revenue lost from churn
        $lostRevenue = $churnedCustomers->sum('total');

        return [
            'total_churned' => $churnedCustomers->count(),
            'churn_reasons' => $churnReasons->toArray(),
            'avg_lifetime_months' => round($avgLifetime, 2),
            'lost_revenue' => $lostRevenue,
            'monthly_churn_rate' => RevenueMetric::where('period_type', 'monthly')
                ->orderBy('metric_date', 'desc')
                ->first()?->churn_rate ?? 0,
        ];
    }
}
