<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LinkedInOROperatorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Enable OR operator for LinkedIn platform
        DB::table('evenleads_platforms')
            ->where('name', 'linkedin')
            ->update([
                'or_operator_allowed' => true,
                'updated_at' => now(),
            ]);

        $this->command->info('✅ Enabled OR operator for LinkedIn platform');

        // Set LinkedIn Apify limit to 10 per sync
        $linkedin = DB::table('evenleads_platforms')
            ->where('name', 'linkedin')
            ->first();

        if ($linkedin) {
            $pluginConfig = json_decode($linkedin->plugin_config, true) ?? [];

            // Ensure apify config exists
            if (!isset($pluginConfig['apify'])) {
                $pluginConfig['apify'] = [];
            }

            // Set limit_per_sync to 10
            $pluginConfig['apify']['limit_per_sync'] = 10;

            DB::table('evenleads_platforms')
                ->where('name', 'linkedin')
                ->update([
                    'plugin_config' => json_encode($pluginConfig),
                    'updated_at' => now(),
                ]);

            $this->command->info('✅ Set LinkedIn Apify limit_per_sync to 10');
        } else {
            $this->command->warn('⚠️  LinkedIn platform not found in database');
        }

        $this->command->info('LinkedIn OR operator and Apify configuration completed!');
    }
}
