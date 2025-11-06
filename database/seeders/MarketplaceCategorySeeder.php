<?php

namespace Database\Seeders;

use App\Models\Marketplace\MarketplaceCategory;
use Illuminate\Database\Seeder;

class MarketplaceCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Themes',
                'slug' => 'themes',
                'description' => 'Beautiful themes for your application',
                'icon' => 'heroicon-o-paint-brush',
                'sort_order' => 1,
            ],
            [
                'name' => 'Plugins',
                'slug' => 'plugins',
                'description' => 'Extend functionality with powerful plugins',
                'icon' => 'heroicon-o-puzzle-piece',
                'sort_order' => 2,
            ],
            [
                'name' => 'Components',
                'slug' => 'components',
                'description' => 'Reusable UI components',
                'icon' => 'heroicon-o-cube',
                'sort_order' => 3,
            ],
            [
                'name' => 'Templates',
                'slug' => 'templates',
                'description' => 'Ready-to-use templates',
                'icon' => 'heroicon-o-document-duplicate',
                'sort_order' => 4,
            ],
            [
                'name' => 'Integrations',
                'slug' => 'integrations',
                'description' => 'Third-party service integrations',
                'icon' => 'heroicon-o-link',
                'sort_order' => 5,
            ],
            [
                'name' => 'Tools',
                'slug' => 'tools',
                'description' => 'Developer tools and utilities',
                'icon' => 'heroicon-o-wrench-screwdriver',
                'sort_order' => 6,
            ],
            [
                'name' => 'Admin Panels',
                'slug' => 'admin-panels',
                'description' => 'Admin dashboard templates and components',
                'icon' => 'heroicon-o-shield-check',
                'sort_order' => 7,
            ],
            [
                'name' => 'Payment Gateways',
                'slug' => 'payment-gateways',
                'description' => 'Payment processing integrations',
                'icon' => 'heroicon-o-credit-card',
                'sort_order' => 8,
            ],
            [
                'name' => 'Marketing',
                'slug' => 'marketing',
                'description' => 'Marketing and analytics tools',
                'icon' => 'heroicon-o-megaphone',
                'sort_order' => 9,
            ],
            [
                'name' => 'E-commerce',
                'slug' => 'ecommerce',
                'description' => 'E-commerce plugins and themes',
                'icon' => 'heroicon-o-shopping-cart',
                'sort_order' => 10,
            ],
        ];

        foreach ($categories as $category) {
            MarketplaceCategory::updateOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}
