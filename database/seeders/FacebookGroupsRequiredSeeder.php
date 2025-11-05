<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FacebookGroupsRequiredSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if evenleads_platforms table exists
        if (!DB::getSchemaBuilder()->hasTable('evenleads_platforms')) {
            $this->command->warn('Table evenleads_platforms does not exist. Skipping FacebookGroupsRequiredSeeder.');
            return;
        }

        // Get the Facebook platform
        $facebookPlatform = DB::table('evenleads_platforms')
            ->where('name', 'facebook')
            ->first();

        if (!$facebookPlatform) {
            $this->command->warn('Facebook platform not found. Skipping FacebookGroupsRequiredSeeder.');
            return;
        }

        // Decode the plugin_config JSON
        $pluginConfig = json_decode($facebookPlatform->plugin_config, true) ?? [];

        // Update the apify plugin config to require group selection
        if (isset($pluginConfig['apify'])) {
            $pluginConfig['apify']['require_group_selection'] = true;

            // Also ensure allow_group_selection is enabled
            if (!isset($pluginConfig['apify']['allow_group_selection'])) {
                $pluginConfig['apify']['allow_group_selection'] = true;
            }
        } else {
            // If apify config doesn't exist, create it
            $pluginConfig['apify'] = [
                'allow_group_selection' => true,
                'require_group_selection' => true,
            ];
        }

        // Update the platform
        DB::table('evenleads_platforms')
            ->where('name', 'facebook')
            ->update([
                'plugin_config' => json_encode($pluginConfig),
                'updated_at' => now(),
            ]);

        $this->command->info('Facebook platform updated to require group selection.');
    }
}
