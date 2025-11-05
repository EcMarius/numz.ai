<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Wave\Setting;

class CookieBannerSettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::updateOrCreate(
            ['key' => 'site.cookie_banner_enabled'],
            [
                'display_name' => 'Enable Cookie Banner',
                'value' => '1',
                'type' => 'checkbox',
                'group' => 'Site',
                'order' => 20,
                'details' => json_encode([
                    'description' => 'Display GDPR-compliant cookie consent banner to visitors',
                    'on' => '1',
                    'off' => '0',
                    'checked' => true,
                ]),
            ]
        );

        echo "✓ Cookie banner setting created\n";
    }
}
