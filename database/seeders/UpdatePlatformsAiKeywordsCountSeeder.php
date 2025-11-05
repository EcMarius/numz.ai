<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Wave\Plugins\EvenLeads\Models\Platform;

class UpdatePlatformsAiKeywordsCountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Sets ai_keywords_count = 4 in platform metadata for all platforms
     */
    public function run(): void
    {
        $platforms = Platform::all();

        foreach ($platforms as $platform) {
            // Set ai_keywords_count in metadata (default: 4)
            $platform->setMetadata('ai_keywords_count', 4);
            $platform->save();

            $this->command->info("Set ai_keywords_count = 4 for platform: {$platform->name}");
        }

        $this->command->info("Updated {$platforms->count()} platforms with ai_keywords_count metadata");
    }
}
