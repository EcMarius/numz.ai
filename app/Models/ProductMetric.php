<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'metric_date',
        'period_type',
        'new_orders',
        'renewals',
        'cancellations',
        'upgrades',
        'downgrades',
        'total_revenue',
        'mrr_contribution',
        'churn_rate',
        'active_subscriptions',
    ];

    protected $casts = [
        'metric_date' => 'date',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Product::class);
    }

    /**
     * Calculate metrics for a product in a specific period
     */
    public static function calculateForProduct(
        int $productId,
        \Carbon\Carbon $date,
        string $periodType = 'monthly'
    ): self {
        [$startDate, $endDate] = self::getPeriodRange($date, $periodType);

        // Get product orders for the period
        $orders = \App\Models\Order::where('product_id', $productId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'active')
            ->get();

        $newOrders = $orders->where('order_type', 'new')->count();
        $renewals = $orders->where('order_type', 'renewal')->count();
        $upgrades = $orders->where('order_type', 'upgrade')->count();
        $downgrades = $orders->where('order_type', 'downgrade')->count();

        // Get cancellations
        $cancellations = \App\Models\Order::where('product_id', $productId)
            ->whereBetween('cancelled_at', [$startDate, $endDate])
            ->count();

        // Calculate revenue
        $totalRevenue = $orders->sum('total');

        // Get active subscriptions
        $activeSubscriptions = \App\Models\Order::where('product_id', $productId)
            ->where('status', 'active')
            ->count();

        // Calculate MRR contribution (simplified)
        $mrrContribution = $activeSubscriptions > 0 ? $totalRevenue / $activeSubscriptions : 0;

        // Calculate churn rate
        $previousActive = $activeSubscriptions + $cancellations - $newOrders;
        $churnRate = $previousActive > 0 ? ($cancellations / $previousActive) * 100 : 0;

        return self::updateOrCreate(
            [
                'product_id' => $productId,
                'metric_date' => $date->format('Y-m-d'),
                'period_type' => $periodType,
            ],
            [
                'new_orders' => $newOrders,
                'renewals' => $renewals,
                'cancellations' => $cancellations,
                'upgrades' => $upgrades,
                'downgrades' => $downgrades,
                'total_revenue' => $totalRevenue,
                'mrr_contribution' => round($mrrContribution, 2),
                'churn_rate' => round($churnRate, 2),
                'active_subscriptions' => $activeSubscriptions,
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
     * Get top performing products
     */
    public static function getTopPerformers(
        \Carbon\Carbon $startDate,
        \Carbon\Carbon $endDate,
        string $periodType = 'monthly',
        int $limit = 10
    ): \Illuminate\Database\Eloquent\Collection {
        return self::with('product')
            ->where('period_type', $periodType)
            ->whereBetween('metric_date', [$startDate, $endDate])
            ->selectRaw('product_id, SUM(total_revenue) as total_revenue, SUM(new_orders) as total_orders')
            ->groupBy('product_id')
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->get();
    }

    /**
     * Get product performance trend
     */
    public static function getProductTrend(
        int $productId,
        int $periods = 12,
        string $periodType = 'monthly'
    ): array {
        $metrics = self::where('product_id', $productId)
            ->where('period_type', $periodType)
            ->orderBy('metric_date', 'desc')
            ->limit($periods)
            ->get()
            ->reverse()
            ->values();

        return [
            'labels' => $metrics->pluck('metric_date')->map(fn($date) => $date->format('M Y'))->toArray(),
            'revenue' => $metrics->pluck('total_revenue')->toArray(),
            'orders' => $metrics->pluck('new_orders')->toArray(),
            'subscriptions' => $metrics->pluck('active_subscriptions')->toArray(),
            'churn_rate' => $metrics->pluck('churn_rate')->toArray(),
        ];
    }
}
