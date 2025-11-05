<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Wave\Setting;

class BYOAPIRequirementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Add setting for requiring users to bring their own API keys
        // When enabled, users must configure their own Reddit/X API credentials
        // to create campaigns or connect accounts
        Setting::updateOrCreate(
            ['key' => 'site.bring_your_api_key_required'],
            [
                'value' => '0', // Disabled by default
                'type' => 'checkbox',
                'display_name' => 'Require Users to Bring Their Own API Keys',
                'details' => 'When enabled, users must configure their own Reddit and X (Twitter) API credentials before creating campaigns or syncing. This prevents platform API rate limit issues by distributing API usage across user-owned applications.',
                'order' => 999,
            ]
        );
    }
}
