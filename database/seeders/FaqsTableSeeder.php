<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Faq;

class FaqsTableSeeder extends Seeder
{
    public function run(): void
    {
        // For marketing/demo data, clear and recreate to avoid duplicates
        Faq::truncate();

        $faqs = [
            [
                'question' => 'What is EvenLeads?',
                'answer' => 'EvenLeads is an AI-powered lead generation platform that monitors social media discussions on Reddit and X (Twitter) to find potential customers for your business. We use advanced AI algorithms to match posts with your product or service, helping you discover high-quality leads automatically.',
                'order' => 1,
                'is_active' => true,
            ],
            [
                'question' => 'How does the free trial work?',
                'answer' => 'New users get a 7-day free trial with full access to all features of their selected plan. You can cancel anytime during the trial period without being charged. If you don\'t cancel before the trial ends, your subscription will automatically convert to a paid plan and you\'ll be charged the monthly fee.',
                'order' => 2,
                'is_active' => true,
            ],
            [
                'question' => 'Which platforms do you support?',
                'answer' => 'Currently we support Reddit and X (Twitter). We scan these platforms daily to find relevant posts and discussions where your target audience is actively looking for solutions. We\'re constantly working on expanding to additional platforms.',
                'order' => 3,
                'is_active' => true,
            ],
            [
                'question' => 'Can I export my leads?',
                'answer' => 'Yes! All plans include CSV export functionality. You can download your leads anytime from your dashboard. Keep in mind that lead data is retained for 60 days, so make sure to export any data you want to keep before that period ends.',
                'order' => 4,
                'is_active' => true,
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::create($faq);
        }

        $this->command->info('✓ Seeded ' . count($faqs) . ' FAQs');
    }
}
