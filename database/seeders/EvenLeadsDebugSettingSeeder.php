<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Wave\Setting;

class EvenLeadsDebugSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Setting::updateOrCreate(
            ['key' => 'sync_debug_logging'],
            [
                'display_name' => 'Enable Sync Debug Logging',
                'value' => '0', // Disabled by default
                'type' => 'checkbox',
                'group' => 'evenleads',
                'details' => json_encode([
                    'description' => 'Enable detailed logging for campaign sync debugging. Creates separate log files in storage/logs/sync-debug/. Log files include API requests/responses, post processing details, AI decisions, and complete sync statistics. Logs: storage/logs/sync-debug/campaign-{id}-{date}.log. Auto-deleted after 30 days. Disable in production to save disk space.',
                    'on' => '1',
                    'off' => '0',
                    'checked' => false,
                ]),
            ]
        );

        echo "✓ EvenLeads sync debug logging setting created\n";
    }
}
