<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Wave\Setting;

class FeatureShowcaseSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Setting::updateOrCreate(
            ['key' => 'site.show_feature_showcase'],
            [
                'display_name' => 'Show Feature Showcase Section',
                'value' => '1',
                'type' => 'checkbox',
                'group' => 'Site',
                'order' => 19,
                'details' => json_encode([
                    'description' => 'Display interactive feature showcase section on the homepage with media demonstrations. When disabled, the section will be hidden from visitors.',
                    'on' => '1',
                    'off' => '0',
                    'checked' => true,
                ]),
            ]
        );

        $this->command->info('Feature showcase setting created successfully!');
    }
}
