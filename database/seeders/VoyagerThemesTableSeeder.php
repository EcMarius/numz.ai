<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VoyagerThemesTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     */
    public function run(): void
    {
        // Use updateOrInsert for idempotency instead of delete + insert
        $themes = [
            0 => [
                'id' => 1,
                'name' => 'Tailwind Theme',
                'folder' => 'tailwind',
                'active' => 1,
                'version' => '1.0',
                'created_at' => '2020-08-23 08:06:45',
                'updated_at' => '2020-08-23 08:06:45',
            ],
        ];

        foreach ($themes as $themeData) {
            DB::table('themes')->updateOrInsert(
                ['folder' => $themeData['folder']],
                $themeData
            );
        }
    }
}
