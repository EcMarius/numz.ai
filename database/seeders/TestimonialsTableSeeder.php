<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Testimonial;

class TestimonialsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // For marketing/demo data, clear and recreate to avoid duplicates
        Testimonial::truncate();

        $testimonials = [
            [
                'name' => 'Vlad Popa',
                'position' => 'Web Developer',
                'company' => 'ExpertCoder',
                'content' => "Honestly didn't think it'd work this well. I set it up for Reddit and it's been finding me actual projects. Way better than cold emailing random people.",
                'avatar' => null,
                'avatar_fallback' => 'VP',
                'gradient_from' => 'blue-500',
                'gradient_to' => 'indigo-600',
                'order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Marian Malahin',
                'position' => 'Digital Marketing Specialist',
                'company' => 'AgileMedia',
                'content' => "I was skeptical at first but it's been pretty useful. Saves me from manually going through LinkedIn and Reddit posts. Not perfect but definitely worth it for the time saved.",
                'avatar' => null,
                'avatar_fallback' => 'MM',
                'gradient_from' => 'purple-500',
                'gradient_to' => 'pink-600',
                'order' => 2,
                'is_active' => true,
            ],
        ];

        foreach ($testimonials as $testimonial) {
            Testimonial::create($testimonial);
        }

        $this->command->info('✓ Seeded ' . count($testimonials) . ' testimonials');
    }
}
