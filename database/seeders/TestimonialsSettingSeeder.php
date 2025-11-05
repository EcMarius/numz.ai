<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Wave\Setting;

class TestimonialsSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Setting::updateOrCreate(
            ['key' => 'site.show_testimonials'],
            [
                'display_name' => 'Show Testimonials Section',
                'value' => '1',
                'type' => 'checkbox',
                'group' => 'Site',
                'order' => 17,
                'details' => json_encode([
                    'description' => 'Display testimonials section on the homepage. When disabled, the testimonials section will be hidden from visitors.',
                    'on' => '1',
                    'off' => '0',
                    'checked' => true,
                ]),
            ]
        );

        echo "✓ Testimonials visibility setting created in Site settings\n";
    }
}
