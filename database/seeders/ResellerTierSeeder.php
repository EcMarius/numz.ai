<?php

namespace Database\Seeders;

use App\Models\ResellerTier;
use Illuminate\Database\Seeder;

class ResellerTierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tiers = [
            [
                'name' => 'Bronze',
                'slug' => 'bronze',
                'description' => 'Entry-level reseller tier perfect for getting started.',
                'level' => 1,
                'discount_percentage' => 10.00,
                'max_customers' => 50,
                'max_services' => 100,
                'max_domains' => 50,
                'commission_rate' => 10.00,
                'recurring_commission' => false,
                'white_label_enabled' => false,
                'custom_branding' => false,
                'custom_domain' => false,
                'api_access' => false,
                'priority_support' => false,
                'monthly_fee' => 0.00,
                'setup_fee' => 0.00,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Silver',
                'slug' => 'silver',
                'description' => 'Mid-tier reseller package with enhanced features and better margins.',
                'level' => 2,
                'discount_percentage' => 20.00,
                'max_customers' => 200,
                'max_services' => 500,
                'max_domains' => 200,
                'commission_rate' => 15.00,
                'recurring_commission' => true,
                'white_label_enabled' => true,
                'custom_branding' => true,
                'custom_domain' => false,
                'api_access' => false,
                'priority_support' => false,
                'monthly_fee' => 29.99,
                'setup_fee' => 0.00,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Gold',
                'slug' => 'gold',
                'description' => 'Professional reseller tier with custom domain and API access.',
                'level' => 3,
                'discount_percentage' => 30.00,
                'max_customers' => 500,
                'max_services' => 1500,
                'max_domains' => 500,
                'commission_rate' => 20.00,
                'recurring_commission' => true,
                'white_label_enabled' => true,
                'custom_branding' => true,
                'custom_domain' => true,
                'api_access' => true,
                'priority_support' => true,
                'monthly_fee' => 79.99,
                'setup_fee' => 0.00,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Platinum',
                'slug' => 'platinum',
                'description' => 'Enterprise reseller tier with unlimited resources and maximum commissions.',
                'level' => 4,
                'discount_percentage' => 40.00,
                'max_customers' => null, // Unlimited
                'max_services' => null,
                'max_domains' => null,
                'commission_rate' => 25.00,
                'recurring_commission' => true,
                'white_label_enabled' => true,
                'custom_branding' => true,
                'custom_domain' => true,
                'api_access' => true,
                'priority_support' => true,
                'monthly_fee' => 199.99,
                'setup_fee' => 0.00,
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($tiers as $tier) {
            ResellerTier::updateOrCreate(
                ['slug' => $tier['slug']],
                $tier
            );
        }
    }
}
