<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Wave\Plugins\EvenLeads\Models\Setting;

class AutomatedSyncDefaultSeeder extends Seeder
{
    public function run(): void
    {
        Setting::updateOrCreate(
            ['key' => 'automated_sync_default_interval'],
            [
                'value' => '1440', // 24 hours in minutes
                'type' => 'number',
                'description' => 'Default interval for automated campaign syncs if not specified in plan (in minutes). 1440 = 24 hours, 720 = 12 hours, 360 = 6 hours.',
            ]
        );

        echo "✓ Automated sync default interval setting created (1440 minutes = 24 hours)\n";
    }
}
