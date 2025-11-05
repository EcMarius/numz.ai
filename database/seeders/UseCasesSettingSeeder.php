<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Wave\Setting;

class UseCasesSettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::updateOrCreate(
            ['key' => 'site.show_use_cases'],
            [
                'display_name' => 'Show Use Cases Section',
                'value' => '1',
                'type' => 'checkbox',
                'group' => 'Site',
                'order' => 19,
                'details' => json_encode([
                    'description' => 'Display use cases section on the homepage showing different ways to use EvenLeads. When disabled, the section will be hidden.',
                    'on' => '1',
                    'off' => '0',
                    'checked' => true,
                ]),
            ]
        );

        echo "✓ Use cases visibility setting created in Site settings\n";
    }
}
