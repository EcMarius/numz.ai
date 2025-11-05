<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Wave\Setting;

class StatsSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Setting::updateOrCreate(
            ['key' => 'site.show_stats'],
            [
                'display_name' => 'Show Stats Section',
                'value' => '1',
                'type' => 'checkbox',
                'group' => 'Site',
                'order' => 18,
                'details' => json_encode([
                    'description' => 'Display stats section on the homepage showing key platform metrics. When disabled, the stats section will be hidden from visitors.',
                    'on' => '1',
                    'off' => '0',
                    'checked' => true,
                ]),
            ]
        );

        echo "✓ Stats visibility setting created in Site settings\n";
    }
}
