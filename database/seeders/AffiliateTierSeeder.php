<?php

namespace Database\Seeders;

use App\Models\AffiliateTier;
use Illuminate\Database\Seeder;

class AffiliateTierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tiers = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Entry-level affiliate tier for beginners.',
                'level' => 1,
                'commission_percentage' => 15.00,
                'recurring_percentage' => 5.00,
                'cookie_lifetime_days' => 30,
                'commission_lifetime_months' => 6, // 6 months of recurring
                'min_referrals' => 0,
                'min_sales' => 0,
                'signup_bonus' => 25.00,
                'monthly_bonus_threshold' => 500.00,
                'monthly_bonus_amount' => 50.00,
                'minimum_payout' => 50.00,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'description' => 'Professional tier with higher commissions and longer cookie lifetime.',
                'level' => 2,
                'commission_percentage' => 20.00,
                'recurring_percentage' => 10.00,
                'cookie_lifetime_days' => 60,
                'commission_lifetime_months' => 12, // 1 year of recurring
                'min_referrals' => 10,
                'min_sales' => 1000.00,
                'signup_bonus' => 0.00,
                'monthly_bonus_threshold' => 1000.00,
                'monthly_bonus_amount' => 100.00,
                'minimum_payout' => 50.00,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Elite',
                'slug' => 'elite',
                'description' => 'Top-tier affiliates with maximum commissions and lifetime recurring.',
                'level' => 3,
                'commission_percentage' => 30.00,
                'recurring_percentage' => 15.00,
                'cookie_lifetime_days' => 90,
                'commission_lifetime_months' => null, // Lifetime recurring
                'min_referrals' => 50,
                'min_sales' => 10000.00,
                'signup_bonus' => 0.00,
                'monthly_bonus_threshold' => 2500.00,
                'monthly_bonus_amount' => 250.00,
                'minimum_payout' => 100.00,
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($tiers as $tier) {
            AffiliateTier::updateOrCreate(
                ['slug' => $tier['slug']],
                $tier
            );
        }
    }
}
