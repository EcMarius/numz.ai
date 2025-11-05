<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlatformCommunicationSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if evenleads_platforms table exists
        if (!DB::getSchemaBuilder()->hasTable('evenleads_platforms')) {
            $this->command->warn('Table evenleads_platforms does not exist. Skipping PlatformCommunicationSettingsSeeder.');
            return;
        }

        // Platform communication settings
        $platformSettings = [
            'reddit' => [
                'allow_direct_messaging' => true,
                'allow_comments' => true,
            ],
            'facebook' => [
                'allow_direct_messaging' => true,
                'allow_comments' => false,
            ],
            'x' => [
                'allow_direct_messaging' => true,
                'allow_comments' => true,
            ],
            'linkedin' => [
                'allow_direct_messaging' => true,
                'allow_comments' => false,
            ],
        ];

        foreach ($platformSettings as $platformName => $settings) {
            // Get the platform
            $platform = DB::table('evenleads_platforms')
                ->where('name', $platformName)
                ->first();

            if (!$platform) {
                $this->command->warn("Platform '{$platformName}' not found. Skipping.");
                continue;
            }

            // Decode the plugin_config JSON
            $pluginConfig = json_decode($platform->plugin_config, true) ?? [];

            // Update the apify plugin config with communication settings
            if (!isset($pluginConfig['apify'])) {
                $pluginConfig['apify'] = [];
            }

            // Set the communication settings
            $pluginConfig['apify']['allow_direct_messaging'] = $settings['allow_direct_messaging'];
            $pluginConfig['apify']['allow_comments'] = $settings['allow_comments'];

            // Update the platform
            DB::table('evenleads_platforms')
                ->where('name', $platformName)
                ->update([
                    'plugin_config' => json_encode($pluginConfig),
                    'updated_at' => now(),
                ]);

            $dmStatus = $settings['allow_direct_messaging'] ? 'enabled' : 'disabled';
            $commentStatus = $settings['allow_comments'] ? 'enabled' : 'disabled';
            $this->command->info("Platform '{$platformName}' updated: DM {$dmStatus}, Comments {$commentStatus}");
        }

        $this->command->info('Platform communication settings seeded successfully.');
    }
}
