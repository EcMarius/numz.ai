<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FeatureShowcase;

class FeatureShowcaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // For marketing/demo data, clear and recreate to avoid duplicates
        FeatureShowcase::truncate();

        $showcases = [
            [
                'title' => 'Find Perfect Leads',
                'description' => 'Discover leads actively looking for your services, track what competitors are doing, understand customer pain points, and gather valuable feedback from potential customers. Our smart algorithm finds opportunities you would miss manually.',
                'media_path' => '/storage/features/find-leads.gif',
                'media_type' => 'gif',
                'order' => 1,
                'is_active' => true,
            ],
            [
                'title' => 'AI Relevance Filtering',
                'description' => 'Our advanced AI analyzes every lead to determine relevance to your business. Filter out noise and focus only on high-quality opportunities that match your ideal customer profile with intelligent scoring and ranking.',
                'media_path' => '/storage/features/ai-filtering.gif',
                'media_type' => 'gif',
                'order' => 2,
                'is_active' => true,
            ],
            [
                'title' => 'Post Management',
                'description' => 'Manage all your social media posts from one central dashboard. Track engagement, analyze performance, respond to comments, and turn conversations into leads. Support for Reddit, Facebook, X (Twitter), and LinkedIn.',
                'media_path' => '/storage/features/post-management.gif',
                'media_type' => 'gif',
                'order' => 3,
                'is_active' => true,
            ],
            [
                'title' => 'AI Guided Replies',
                'description' => 'Generate natural, contextual responses with AI that sound authentic and helpful. Our AI understands your business and creates personalized replies that engage prospects and build relationships without sounding robotic.',
                'media_path' => '/storage/features/ai-replies.gif',
                'media_type' => 'gif',
                'order' => 4,
                'is_active' => true,
            ],
            [
                'title' => 'Track Competitors',
                'description' => 'Monitor what your competitors are doing, see where they\'re active, understand their messaging strategy, and identify gaps in the market. Stay one step ahead with competitive intelligence.',
                'media_path' => '/storage/features/track-competitors.gif',
                'media_type' => 'gif',
                'order' => 5,
                'is_active' => true,
            ],
            [
                'title' => 'Track Mentions',
                'description' => 'Never miss a mention of your brand, product, or industry keywords. Get real-time notifications when people are talking about topics relevant to your business across all supported platforms.',
                'media_path' => '/storage/features/track-mentions.gif',
                'media_type' => 'gif',
                'order' => 6,
                'is_active' => true,
            ],
        ];

        foreach ($showcases as $showcase) {
            FeatureShowcase::create($showcase);
        }

        $this->command->info('Feature showcases seeded successfully!');
    }
}
