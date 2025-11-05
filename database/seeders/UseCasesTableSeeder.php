<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UseCase;

class UseCasesTableSeeder extends Seeder
{
    public function run(): void
    {
        // For marketing/demo data, clear and recreate to avoid duplicates
        UseCase::truncate();

        $useCases = [
            [
                'title' => 'Find Clients as a Freelancer',
                'description' => 'Automatically discover people looking for web development, design, writing, or any freelance service across multiple platforms.',
                'icon' => 'phosphor-code',
                'color' => 'blue',
                'target_audience' => 'Freelancers & Developers',
                'order' => 1,
                'is_active' => true,
            ],
            [
                'title' => 'Get Product Feedback',
                'description' => 'Find discussions where users are asking for recommendations in your product category and join the conversation.',
                'icon' => 'phosphor-chat-dots',
                'color' => 'emerald',
                'target_audience' => 'Product Managers',
                'order' => 2,
                'is_active' => true,
            ],
            [
                'title' => 'Track Brand Mentions',
                'description' => 'Monitor when people talk about your brand, competitors, or industry keywords to engage in real-time conversations.',
                'icon' => 'phosphor-megaphone',
                'color' => 'purple',
                'target_audience' => 'Marketing Teams',
                'order' => 3,
                'is_active' => true,
            ],
            [
                'title' => 'SaaS Customer Discovery',
                'description' => 'Identify businesses actively searching for software solutions in your category before your competitors find them.',
                'icon' => 'phosphor-rocket',
                'color' => 'orange',
                'target_audience' => 'SaaS Companies',
                'order' => 4,
                'is_active' => true,
            ],
            [
                'title' => 'Agency Lead Generation',
                'description' => 'Find companies looking for marketing, SEO, design, or development agencies with automated lead qualification.',
                'icon' => 'phosphor-briefcase',
                'color' => 'indigo',
                'target_audience' => 'Agencies',
                'order' => 5,
                'is_active' => true,
            ],
            [
                'title' => 'Research Market Insights',
                'description' => 'Discover what problems people are facing in your industry and validate new product ideas with real conversations.',
                'icon' => 'phosphor-lightbulb',
                'color' => 'yellow',
                'target_audience' => 'Entrepreneurs',
                'order' => 6,
                'is_active' => true,
            ],
        ];

        foreach ($useCases as $useCase) {
            UseCase::create($useCase);
        }

        $this->command->info('✓ Seeded ' . count($useCases) . ' use cases');
    }
}
