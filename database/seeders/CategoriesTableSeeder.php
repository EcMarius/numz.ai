<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriesTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     */
    public function run(): void
    {
        // Delete all existing categories first
        DB::table('categories')->delete();

        // Insert new categories
        $categories = [
            [
                'id' => 1,
                'parent_id' => null,
                'order' => 1,
                'name' => 'Marketing',
                'slug' => 'marketing',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'parent_id' => null,
                'order' => 2,
                'name' => 'SEO',
                'slug' => 'seo',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($categories as $categoryData) {
            DB::table('categories')->insert($categoryData);
        }
    }
}
