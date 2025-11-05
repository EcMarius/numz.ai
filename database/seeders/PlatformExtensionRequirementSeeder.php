<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlatformExtensionRequirementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Platforms that require browser extension for lead generation
        $platformsRequiringExtension = [
            'linkedin',
            'upwork',
            'fiverr',
            'facebook',
        ];

        foreach ($platformsRequiringExtension as $platformName) {
            DB::table('evenleads_platforms')
                ->where('name', $platformName)
                ->update([
                    'requires_extension_sync' => true,
                    'requires_extension_dm' => true,
                    'requires_extension_comment' => true,
                    'updated_at' => now(),
                ]);
        }

        $this->command->info('Updated platforms with extension requirements (sync, DM, comment): ' . implode(', ', $platformsRequiringExtension));
    }
}
