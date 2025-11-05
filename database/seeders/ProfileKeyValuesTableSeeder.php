<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProfileKeyValuesTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     */
    public function run(): void
    {
        // Use updateOrInsert for idempotency instead of delete + insert
        $profileKeyValues = [
            0 => [
                'id' => 10,
                'type' => 'text_area',
                'keyvalue_id' => 1,
                'keyvalue_type' => 'users',
                'key' => 'about',
                'value' => 'Hello I am the admin user. You can update this information in the edit profile section. Hope you enjoy using Wave.',
            ],
        ];

        foreach ($profileKeyValues as $profileData) {
            DB::table('profile_key_values')->updateOrInsert(
                [
                    'keyvalue_id' => $profileData['keyvalue_id'],
                    'keyvalue_type' => $profileData['keyvalue_type'],
                    'key' => $profileData['key']
                ],
                $profileData
            );
        }
    }
}
