<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Wave\Plugins\EvenLeads\Models\Platform;

class FollowUpPlatformSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Enable follow-up for Reddit and X (Twitter)
        $reddit = Platform::where('name', 'reddit')->first();
        if ($reddit) {
            $reddit->update(['follow_up_enabled' => true]);
            $this->command->info('✓ Reddit: Follow-Up ENABLED');
        }

        $x = Platform::where('name', 'x')->first();
        if ($x) {
            $x->update(['follow_up_enabled' => true]);
            $this->command->info('✓ X (Twitter): Follow-Up ENABLED');
        }

        // Disable for all others
        $platforms = Platform::whereNotIn('name', ['reddit', 'x'])->get();
        foreach ($platforms as $platform) {
            $platform->update(['follow_up_enabled' => false]);
            $this->command->info('✗ ' . $platform->display_name . ': Follow-Up DISABLED');
        }

        $this->command->info('Platform follow-up settings updated!');
    }
}
