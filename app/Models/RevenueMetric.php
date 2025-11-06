<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RevenueMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'metric_date',
        'period_type',
        'new_sales',
        'renewals',
        'upgrades',
        'refunds',
        'total_revenue',
        'mrr',
        'arr',
        'mrr_growth',
        'new_customers',
        'churned_customers',
        'churn_rate',
        'customer_ltv',
    ];

    protected $casts = [
        'metric_date' => 'date',
    ];

    /**
     * Calculate metrics for a specific date and period
     */
    public static function calculateForPeriod(\Carbon\Carbon $date, string $periodType = 'daily'): self
    {
        [$startDate, $endDate] = self::getPeriodRange($date, $periodType);

        // Get invoices for the period
        $invoices = \App\Models\Invoice::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'paid')
            ->get();

        // Calculate revenue breakdown
        $newSales = $invoices->where('invoice_type', 'new')->sum('total');
        $renewals = $invoices->where('invoice_type', 'renewal')->sum('total');
        $upgrades = $invoices->where('invoice_type', 'upgrade')->sum('total');

        // Get refunds
        $refunds = \App\Models\Invoice::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'refunded')
            ->sum('total');

        $totalRevenue = $newSales + $renewals + $upgrades - $refunds;

        // Calculate MRR/ARR (simplified - should use subscription data)
        $mrr = $renewals; // This should be calculated based on active subscriptions
        $arr = $mrr * 12;

        // Get previous period MRR for growth calculation
        $previousDate = $date->copy()->sub(self::getPeriodInterval($periodType));
        $previousMetric = self::where('metric_date', $previousDate->format('Y-m-d'))
            ->where('period_type', $periodType)
            ->first();

        $mrrGrowth = $previousMetric ? (($mrr - $previousMetric->mrr) / $previousMetric->mrr) * 100 : 0;

        // Calculate customer metrics
        $newCustomers = \App\Models\User::whereBetween('created_at', [$startDate, $endDate])
            ->where('role', 'customer')
            ->count();

        // Churned customers (simplified - should check subscription cancellations)
        $churned = 0;
        $totalCustomers = \App\Models\User::where('role', 'customer')->count();
        $churnRate = $totalCustomers > 0 ? ($churned / $totalCustomers) * 100 : 0;

        // Calculate LTV (simplified)
        $avgRevenue = $totalCustomers > 0 ? $totalRevenue / $totalCustomers : 0;
        $customerLtv = $churnRate > 0 ? $avgRevenue / ($churnRate / 100) : $avgRevenue * 12;

        return self::updateOrCreate(
            [
                'metric_date' => $date->format('Y-m-d'),
                'period_type' => $periodType,
            ],
            [
                'new_sales' => $newSales,
                'renewals' => $renewals,
                'upgrades' => $upgrades,
                'refunds' => $refunds,
                'total_revenue' => $totalRevenue,
                'mrr' => $mrr,
                'arr' => $arr,
                'mrr_growth' => round($mrrGrowth, 2),
                'new_customers' => $newCustomers,
                'churned_customers' => $churned,
                'churn_rate' => round($churnRate, 2),
                'customer_ltv' => round($customerLtv, 2),
            ]
        );
    }

    /**
     * Get period range based on type
     */
    protected static function getPeriodRange(\Carbon\Carbon $date, string $periodType): array
    {
        return match($periodType) {
            'daily' => [$date->copy()->startOfDay(), $date->copy()->endOfDay()],
            'weekly' => [$date->copy()->startOfWeek(), $date->copy()->endOfWeek()],
            'monthly' => [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()],
            'quarterly' => [$date->copy()->startOfQuarter(), $date->copy()->endOfQuarter()],
            'yearly' => [$date->copy()->startOfYear(), $date->copy()->endOfYear()],
            default => [$date->copy()->startOfDay(), $date->copy()->endOfDay()],
        };
    }

    /**
     * Get period interval for calculations
     */
    protected static function getPeriodInterval(string $periodType): \DateInterval
    {
        return match($periodType) {
            'daily' => new \DateInterval('P1D'),
            'weekly' => new \DateInterval('P1W'),
            'monthly' => new \DateInterval('P1M'),
            'quarterly' => new \DateInterval('P3M'),
            'yearly' => new \DateInterval('P1Y'),
            default => new \DateInterval('P1D'),
        };
    }

    /**
     * Get metrics for date range
     */
    public static function getMetricsForRange(
        \Carbon\Carbon $startDate,
        \Carbon\Carbon $endDate,
        string $periodType = 'daily'
    ): \Illuminate\Database\Eloquent\Collection {
        return self::where('period_type', $periodType)
            ->whereBetween('metric_date', [$startDate, $endDate])
            ->orderBy('metric_date')
            ->get();
    }

    /**
     * Get revenue trend
     */
    public static function getRevenueTrend(int $periods = 12, string $periodType = 'monthly'): array
    {
        $metrics = self::where('period_type', $periodType)
            ->orderBy('metric_date', 'desc')
            ->limit($periods)
            ->get()
            ->reverse()
            ->values();

        return [
            'labels' => $metrics->pluck('metric_date')->map(fn($date) => $date->format('M Y'))->toArray(),
            'revenue' => $metrics->pluck('total_revenue')->toArray(),
            'mrr' => $metrics->pluck('mrr')->toArray(),
            'new_customers' => $metrics->pluck('new_customers')->toArray(),
        ];
    }
}
