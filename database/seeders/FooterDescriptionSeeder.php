<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Wave\Setting;

class FooterDescriptionSeeder extends Seeder
{
    public function run(): void
    {
        Setting::updateOrCreate(
            ['key' => 'site.footer_description'],
            [
                'display_name' => 'Footer Description',
                'value' => 'EvenLeads is an AI-powered lead generation platform that automatically discovers qualified prospects from Reddit, Twitter/X, Facebook, and LinkedIn. Find customers actively seeking your services before your competitors do.',
                'details' => 'SEO-optimized description text displayed in the footer under the logo',
                'type' => 'textarea',
                'order' => 16,
                'group' => 'Site',
            ]
        );

        echo "✓ Footer description updated with SEO-optimized text\n";
    }
}
