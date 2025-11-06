<?php

namespace Database\Seeders;

use App\Models\AnalyticsDashboard;
use Illuminate\Database\Seeder;

class AnalyticsDashboardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Executive Dashboard
        AnalyticsDashboard::create([
            'created_by' => 1, // Admin user
            'name' => 'Executive Dashboard',
            'slug' => 'executive-dashboard',
            'description' => 'High-level overview of business performance for executives',
            'layout' => [
                'columns' => 12,
                'rows' => 'auto',
                'gap' => 4,
            ],
            'widgets' => [
                [
                    'id' => \Illuminate\Support\Str::uuid(),
                    'type' => 'mrr_metric',
                    'title' => 'Monthly Recurring Revenue',
                    'position' => ['x' => 0, 'y' => 0, 'w' => 3, 'h' => 2],
                    'config' => [
                        'show_growth' => true,
                    ],
                ],
                [
                    'id' => \Illuminate\Support\Str::uuid(),
                    'type' => 'customer_count',
                    'title' => 'Total Customers',
                    'position' => ['x' => 3, 'y' => 0, 'w' => 3, 'h' => 2],
                    'config' => [
                        'show_new' => true,
                        'show_churned' => true,
                    ],
                ],
                [
                    'id' => \Illuminate\Support\Str::uuid(),
                    'type' => 'revenue_chart',
                    'title' => 'Revenue Trend',
                    'position' => ['x' => 0, 'y' => 2, 'w' => 6, 'h' => 4],
                    'config' => [
                        'period' => 'monthly',
                        'chart_type' => 'line',
                    ],
                ],
                [
                    'id' => \Illuminate\Support\Str::uuid(),
                    'type' => 'churn_rate',
                    'title' => 'Churn Rate',
                    'position' => ['x' => 6, 'y' => 0, 'w' => 3, 'h' => 2],
                    'config' => [
                        'period' => 'monthly',
                    ],
                ],
                [
                    'id' => \Illuminate\Support\Str::uuid(),
                    'type' => 'top_products',
                    'title' => 'Top Products',
                    'position' => ['x' => 6, 'y' => 2, 'w' => 6, 'h' => 4],
                    'config' => [
                        'limit' => 10,
                        'sort_by' => 'revenue',
                    ],
                ],
            ],
            'refresh_interval' => 300, // 5 minutes
            'is_public' => false,
            'shared_with_roles' => ['admin', 'manager'],
            'is_default' => true,
        ]);

        // Sales Dashboard
        AnalyticsDashboard::create([
            'created_by' => 1,
            'name' => 'Sales Dashboard',
            'slug' => 'sales-dashboard',
            'description' => 'Detailed sales and revenue analytics',
            'layout' => [
                'columns' => 12,
                'rows' => 'auto',
                'gap' => 4,
            ],
            'widgets' => [
                [
                    'id' => \Illuminate\Support\Str::uuid(),
                    'type' => 'revenue_chart',
                    'title' => 'Monthly Revenue',
                    'position' => ['x' => 0, 'y' => 0, 'w' => 8, 'h' => 4],
                    'config' => [
                        'period' => 'monthly',
                        'chart_type' => 'bar',
                    ],
                ],
                [
                    'id' => \Illuminate\Support\Str::uuid(),
                    'type' => 'recent_invoices',
                    'title' => 'Recent Invoices',
                    'position' => ['x' => 8, 'y' => 0, 'w' => 4, 'h' => 4],
                    'config' => [
                        'limit' => 10,
                    ],
                ],
                [
                    'id' => \Illuminate\Support\Str::uuid(),
                    'type' => 'top_products',
                    'title' => 'Best Selling Products',
                    'position' => ['x' => 0, 'y' => 4, 'w' => 6, 'h' => 4],
                    'config' => [
                        'limit' => 10,
                        'sort_by' => 'revenue',
                    ],
                ],
                [
                    'id' => \Illuminate\Support\Str::uuid(),
                    'type' => 'customer_count',
                    'title' => 'Customer Growth',
                    'position' => ['x' => 6, 'y' => 4, 'w' => 6, 'h' => 4],
                    'config' => [
                        'show_new' => true,
                        'show_churned' => true,
                    ],
                ],
            ],
            'refresh_interval' => 300,
            'is_public' => false,
            'shared_with_roles' => ['admin', 'sales'],
            'is_default' => false,
        ]);

        // Customer Success Dashboard
        AnalyticsDashboard::create([
            'created_by' => 1,
            'name' => 'Customer Success Dashboard',
            'slug' => 'customer-success-dashboard',
            'description' => 'Customer retention and satisfaction metrics',
            'layout' => [
                'columns' => 12,
                'rows' => 'auto',
                'gap' => 4,
            ],
            'widgets' => [
                [
                    'id' => \Illuminate\Support\Str::uuid(),
                    'type' => 'churn_rate',
                    'title' => 'Monthly Churn Rate',
                    'position' => ['x' => 0, 'y' => 0, 'w' => 4, 'h' => 2],
                    'config' => [
                        'period' => 'monthly',
                    ],
                ],
                [
                    'id' => \Illuminate\Support\Str::uuid(),
                    'type' => 'customer_count',
                    'title' => 'Active Customers',
                    'position' => ['x' => 4, 'y' => 0, 'w' => 4, 'h' => 2],
                    'config' => [
                        'show_new' => true,
                        'show_churned' => true,
                    ],
                ],
                [
                    'id' => \Illuminate\Support\Str::uuid(),
                    'type' => 'mrr_metric',
                    'title' => 'MRR',
                    'position' => ['x' => 8, 'y' => 0, 'w' => 4, 'h' => 2],
                    'config' => [
                        'show_growth' => true,
                    ],
                ],
                [
                    'id' => \Illuminate\Support\Str::uuid(),
                    'type' => 'revenue_chart',
                    'title' => 'Customer Lifetime Value Trend',
                    'position' => ['x' => 0, 'y' => 2, 'w' => 12, 'h' => 4],
                    'config' => [
                        'period' => 'monthly',
                        'chart_type' => 'line',
                    ],
                ],
            ],
            'refresh_interval' => 600,
            'is_public' => false,
            'shared_with_roles' => ['admin', 'support'],
            'is_default' => false,
        ]);

        // Affiliate Dashboard
        AnalyticsDashboard::create([
            'created_by' => 1,
            'name' => 'Affiliate Marketing Dashboard',
            'slug' => 'affiliate-dashboard',
            'description' => 'Affiliate program performance and tracking',
            'layout' => [
                'columns' => 12,
                'rows' => 'auto',
                'gap' => 4,
            ],
            'widgets' => [
                [
                    'id' => \Illuminate\Support\Str::uuid(),
                    'type' => 'affiliate_stats',
                    'title' => 'Affiliate Overview',
                    'position' => ['x' => 0, 'y' => 0, 'w' => 12, 'h' => 3],
                    'config' => [
                        'period' => 'monthly',
                    ],
                ],
                [
                    'id' => \Illuminate\Support\Str::uuid(),
                    'type' => 'revenue_chart',
                    'title' => 'Commission Trends',
                    'position' => ['x' => 0, 'y' => 3, 'w' => 12, 'h' => 4],
                    'config' => [
                        'period' => 'monthly',
                        'chart_type' => 'line',
                    ],
                ],
            ],
            'refresh_interval' => 300,
            'is_public' => false,
            'shared_with_roles' => ['admin', 'marketing'],
            'is_default' => false,
        ]);

        // Product Performance Dashboard
        AnalyticsDashboard::create([
            'created_by' => 1,
            'name' => 'Product Performance Dashboard',
            'slug' => 'product-performance-dashboard',
            'description' => 'Detailed product and service metrics',
            'layout' => [
                'columns' => 12,
                'rows' => 'auto',
                'gap' => 4,
            ],
            'widgets' => [
                [
                    'id' => \Illuminate\Support\Str::uuid(),
                    'type' => 'top_products',
                    'title' => 'Top Performing Products',
                    'position' => ['x' => 0, 'y' => 0, 'w' => 6, 'h' => 6],
                    'config' => [
                        'limit' => 15,
                        'sort_by' => 'revenue',
                    ],
                ],
                [
                    'id' => \Illuminate\Support\Str::uuid(),
                    'type' => 'revenue_chart',
                    'title' => 'Product Revenue Trend',
                    'position' => ['x' => 6, 'y' => 0, 'w' => 6, 'h' => 6],
                    'config' => [
                        'period' => 'monthly',
                        'chart_type' => 'bar',
                    ],
                ],
            ],
            'refresh_interval' => 600,
            'is_public' => false,
            'shared_with_roles' => ['admin', 'product'],
            'is_default' => false,
        ]);

        $this->command->info('✓ Created 5 sample analytics dashboards');
    }
}
