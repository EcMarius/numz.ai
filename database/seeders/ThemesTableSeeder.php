<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ThemesTableSeeder extends Seeder
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
                'name' => 'Anchor Theme',
                'folder' => 'anchor',
                'active' => 1,
                'version' => 1.0,
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
