<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Wave\Setting;

class FaqSettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::updateOrCreate(
            ['key' => 'site.show_faq'],
            [
                'display_name' => 'Show FAQ Section',
                'value' => '1',
                'type' => 'checkbox',
                'group' => 'Site',
                'order' => 19,
                'details' => json_encode([
                    'description' => 'Display FAQ section on the homepage. When disabled, the FAQ section will be hidden from visitors.',
                    'on' => '1',
                    'off' => '0',
                    'checked' => true,
                ]),
            ]
        );

        echo "✓ FAQ visibility setting created in Site settings\n";
    }
}
