<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class AnalyticsDashboard extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'created_by',
        'name',
        'slug',
        'description',
        'layout',
        'widgets',
        'refresh_interval',
        'is_public',
        'shared_with_users',
        'shared_with_roles',
        'is_default',
        'view_count',
    ];

    protected $casts = [
        'layout' => 'array',
        'widgets' => 'array',
        'shared_with_users' => 'array',
        'shared_with_roles' => 'array',
        'is_public' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Generate unique slug
     */
    public static function generateSlug(string $name): string
    {
        $slug = \Illuminate\Support\Str::slug($name);
        $count = 1;
        $originalSlug = $slug;

        while (self::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count++;
        }

        return $slug;
    }

    /**
     * Check if user can access dashboard
     */
    public function canAccess(User $user): bool
    {
        if ($this->is_public) {
            return true;
        }

        if ($this->created_by === $user->id) {
            return true;
        }

        if ($this->shared_with_users && in_array($user->id, $this->shared_with_users)) {
            return true;
        }

        if ($this->shared_with_roles && method_exists($user, 'hasAnyRole')) {
            return $user->hasAnyRole($this->shared_with_roles);
        }

        return false;
    }

    /**
     * Increment view count
     */
    public function incrementViews(): void
    {
        $this->increment('view_count');
    }

    /**
     * Add widget to dashboard
     */
    public function addWidget(array $widgetConfig): void
    {
        $widgets = $this->widgets ?? [];
        $widgets[] = array_merge($widgetConfig, [
            'id' => \Illuminate\Support\Str::uuid(),
            'created_at' => now()->toIso8601String(),
        ]);

        $this->update(['widgets' => $widgets]);
    }

    /**
     * Update widget
     */
    public function updateWidget(string $widgetId, array $widgetConfig): void
    {
        $widgets = $this->widgets ?? [];

        foreach ($widgets as $index => $widget) {
            if ($widget['id'] === $widgetId) {
                $widgets[$index] = array_merge($widget, $widgetConfig);
                break;
            }
        }

        $this->update(['widgets' => $widgets]);
    }

    /**
     * Remove widget
     */
    public function removeWidget(string $widgetId): void
    {
        $widgets = $this->widgets ?? [];

        $widgets = array_filter($widgets, fn($widget) => $widget['id'] !== $widgetId);

        $this->update(['widgets' => array_values($widgets)]);
    }

    /**
     * Get available widget types
     */
    public static function getWidgetTypes(): array
    {
        return [
            'revenue_chart' => [
                'name' => 'Revenue Chart',
                'description' => 'Display revenue trends over time',
                'icon' => 'heroicon-o-chart-bar',
                'config' => [
                    'period' => 'monthly',
                    'chart_type' => 'line',
                ],
            ],
            'mrr_metric' => [
                'name' => 'MRR Metric',
                'description' => 'Monthly Recurring Revenue',
                'icon' => 'heroicon-o-currency-dollar',
                'config' => [
                    'show_growth' => true,
                ],
            ],
            'customer_count' => [
                'name' => 'Customer Count',
                'description' => 'Total active customers',
                'icon' => 'heroicon-o-users',
                'config' => [
                    'show_new' => true,
                    'show_churned' => true,
                ],
            ],
            'churn_rate' => [
                'name' => 'Churn Rate',
                'description' => 'Customer churn rate',
                'icon' => 'heroicon-o-arrow-trending-down',
                'config' => [
                    'period' => 'monthly',
                ],
            ],
            'top_products' => [
                'name' => 'Top Products',
                'description' => 'Best performing products',
                'icon' => 'heroicon-o-trophy',
                'config' => [
                    'limit' => 10,
                    'sort_by' => 'revenue',
                ],
            ],
            'recent_invoices' => [
                'name' => 'Recent Invoices',
                'description' => 'Latest invoices',
                'icon' => 'heroicon-o-document-text',
                'config' => [
                    'limit' => 5,
                ],
            ],
            'affiliate_stats' => [
                'name' => 'Affiliate Stats',
                'description' => 'Affiliate program performance',
                'icon' => 'heroicon-o-link',
                'config' => [
                    'period' => 'monthly',
                ],
            ],
            'custom_report' => [
                'name' => 'Custom Report',
                'description' => 'Embed a custom report',
                'icon' => 'heroicon-o-document-chart-bar',
                'config' => [
                    'report_id' => null,
                ],
            ],
        ];
    }

    /**
     * Get dashboard data
     */
    public function getData(): array
    {
        $data = [];

        foreach ($this->widgets ?? [] as $widget) {
            $data[$widget['id']] = $this->getWidgetData($widget);
        }

        return $data;
    }

    /**
     * Get data for a specific widget
     */
    protected function getWidgetData(array $widget): array
    {
        $type = $widget['type'];
        $config = $widget['config'] ?? [];

        return match($type) {
            'revenue_chart' => $this->getRevenueChartData($config),
            'mrr_metric' => $this->getMrrMetricData($config),
            'customer_count' => $this->getCustomerCountData($config),
            'churn_rate' => $this->getChurnRateData($config),
            'top_products' => $this->getTopProductsData($config),
            'recent_invoices' => $this->getRecentInvoicesData($config),
            'affiliate_stats' => $this->getAffiliateStatsData($config),
            'custom_report' => $this->getCustomReportData($config),
            default => [],
        };
    }

    /**
     * Get revenue chart data
     */
    protected function getRevenueChartData(array $config): array
    {
        $period = $config['period'] ?? 'monthly';
        return RevenueMetric::getRevenueTrend(12, $period);
    }

    /**
     * Get MRR metric data
     */
    protected function getMrrMetricData(array $config): array
    {
        $latest = RevenueMetric::where('period_type', 'monthly')
            ->orderBy('metric_date', 'desc')
            ->first();

        $previous = RevenueMetric::where('period_type', 'monthly')
            ->orderBy('metric_date', 'desc')
            ->skip(1)
            ->first();

        return [
            'current' => $latest?->mrr ?? 0,
            'previous' => $previous?->mrr ?? 0,
            'growth' => $latest?->mrr_growth ?? 0,
        ];
    }

    /**
     * Get customer count data
     */
    protected function getCustomerCountData(array $config): array
    {
        $total = User::where('role', 'customer')->count();
        $latest = RevenueMetric::where('period_type', 'monthly')
            ->orderBy('metric_date', 'desc')
            ->first();

        return [
            'total' => $total,
            'new' => $latest?->new_customers ?? 0,
            'churned' => $latest?->churned_customers ?? 0,
        ];
    }

    /**
     * Get churn rate data
     */
    protected function getChurnRateData(array $config): array
    {
        $latest = RevenueMetric::where('period_type', 'monthly')
            ->orderBy('metric_date', 'desc')
            ->first();

        return [
            'rate' => $latest?->churn_rate ?? 0,
        ];
    }

    /**
     * Get top products data
     */
    protected function getTopProductsData(array $config): array
    {
        $limit = $config['limit'] ?? 10;
        $startDate = now()->subMonth();
        $endDate = now();

        return ProductMetric::getTopPerformers($startDate, $endDate, 'monthly', $limit)
            ->map(fn($metric) => [
                'product' => $metric->product->name ?? 'N/A',
                'revenue' => $metric->total_revenue,
                'orders' => $metric->total_orders,
            ])
            ->toArray();
    }

    /**
     * Get recent invoices data
     */
    protected function getRecentInvoicesData(array $config): array
    {
        $limit = $config['limit'] ?? 5;

        return Invoice::with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn($invoice) => [
                'id' => $invoice->id,
                'customer' => $invoice->user->name ?? 'N/A',
                'total' => $invoice->total,
                'status' => $invoice->status,
                'date' => $invoice->created_at->format('M d, Y'),
            ])
            ->toArray();
    }

    /**
     * Get affiliate stats data
     */
    protected function getAffiliateStatsData(array $config): array
    {
        $period = $config['period'] ?? 'monthly';
        $startDate = now()->startOfMonth();
        $endDate = now();

        $clicks = AffiliateClick::whereBetween('clicked_at', [$startDate, $endDate])->count();
        $conversions = AffiliateReferral::whereBetween('confirmed_at', [$startDate, $endDate])->count();
        $commissions = AffiliateCommission::whereBetween('earned_date', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')
            ->sum('commission_amount');

        return [
            'clicks' => $clicks,
            'conversions' => $conversions,
            'commissions' => $commissions,
            'conversion_rate' => $clicks > 0 ? round(($conversions / $clicks) * 100, 2) : 0,
        ];
    }

    /**
     * Get custom report data
     */
    protected function getCustomReportData(array $config): array
    {
        $reportId = $config['report_id'] ?? null;

        if (!$reportId) {
            return ['error' => 'No report selected'];
        }

        $report = CustomReport::find($reportId);

        if (!$report) {
            return ['error' => 'Report not found'];
        }

        return $report->execute();
    }

    /**
     * Set as default dashboard
     */
    public function setAsDefault(): void
    {
        // Unset other default dashboards for this user
        self::where('created_by', $this->created_by)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }

    /**
     * Get default dashboard for user
     */
    public static function getDefault(int $userId): ?self
    {
        return self::where('created_by', $userId)
            ->where('is_default', true)
            ->first();
    }
}
