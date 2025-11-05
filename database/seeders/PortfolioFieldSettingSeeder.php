<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Wave\Setting;

class PortfolioFieldSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Setting::updateOrCreate(
            ['key' => 'evenleads_enable_portfolio'],
            [
                'display_name' => 'Enable Portfolio Field in Campaigns',
                'value' => '0', // Disabled by default
                'type' => 'checkbox',
                'group' => 'evenleads',
                'details' => json_encode([
                    'description' => 'Enable the portfolio file upload field in campaign creation and editing forms. When disabled, users will not see or be able to upload portfolio files.',
                    'on' => '1',
                    'off' => '0',
                    'checked' => false,
                ]),
                'order' => 100,
            ]
        );

        echo "✓ EvenLeads portfolio field setting created\n";
    }
}
