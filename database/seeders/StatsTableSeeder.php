<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Stat;

class StatsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // For marketing/demo data, clear and recreate to avoid duplicates
        Stat::truncate();

        $stats = [
            [
                'label' => 'Posts Scanned Daily',
                'value' => '25,000+',
                'icon' => 'phosphor-magnifying-glass',
                'color' => 'blue',
                'order' => 1,
                'is_active' => true,
            ],
            [
                'label' => 'Leads Generated Daily',
                'value' => '4,500+',
                'icon' => 'phosphor-users',
                'color' => 'emerald',
                'order' => 2,
                'is_active' => true,
            ],
            [
                'label' => 'Campaigns Created',
                'value' => '50+',
                'icon' => 'phosphor-target',
                'color' => 'purple',
                'order' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($stats as $stat) {
            Stat::create($stat);
        }

        $this->command->info('✓ Seeded ' . count($stats) . ' stats');
    }
}
