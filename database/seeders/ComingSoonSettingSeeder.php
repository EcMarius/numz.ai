<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Wave\Setting;

class ComingSoonSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Setting::updateOrCreate(
            ['key' => 'site.coming_soon_mode'],
            [
                'display_name' => 'Coming Soon Mode',
                'value' => '0',
                'type' => 'checkbox',
                'group' => 'Site',
                'order' => 99,
                'details' => json_encode([
                    'description' => 'Enable coming soon mode. When enabled, non-logged-in users will see the coming-soon.html page. Logged-in users and admin can still access the site.',
                    'on' => '1',
                    'off' => '0',
                    'checked' => false,
                ]),
            ]
        );

        echo "✓ Coming Soon Mode setting created in Site settings\n";
    }
}
