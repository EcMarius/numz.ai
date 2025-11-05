<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Wave\Plugins\EvenLeads\Models\Platform;

class PlatformPostManagementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Use updateOrCreate for idempotency
        // Enable post management for Reddit only (for now)
        $reddit = Platform::where('name', 'reddit')->first();
        if ($reddit) {
            $reddit->update(['post_management_enabled' => true]);
            $this->command->info('✓ Reddit: Post Management ENABLED');
        }

        // Disable for all others (including X/Twitter and LinkedIn)
        $platforms = Platform::whereIn('name', ['facebook', 'x', 'twitter', 'linkedin', 'fiverr', 'upwork'])->get();
        foreach ($platforms as $platform) {
            $platform->update(['post_management_enabled' => false]);
            $this->command->info('✗ ' . $platform->display_name . ': Post Management DISABLED');
        }

        if ($platforms->count() > 0) {
            $this->command->info('✗ Other platforms: Post Management DISABLED (Coming Soon)');
        }

        $this->command->info('Platform post management settings updated!');
    }
}
