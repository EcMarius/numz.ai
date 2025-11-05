<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModelHasRolesTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     */
    public function run(): void
    {
        // Use updateOrInsert for idempotency instead of delete + insert
        $modelHasRoles = [
            0 => [
                'role_id' => 1,
                'model_type' => 'users',
                'model_id' => 1,
            ],
        ];

        foreach ($modelHasRoles as $modelHasRole) {
            DB::table('model_has_roles')->updateOrInsert(
                [
                    'role_id' => $modelHasRole['role_id'],
                    'model_type' => $modelHasRole['model_type'],
                    'model_id' => $modelHasRole['model_id']
                ],
                $modelHasRole
            );
        }
    }
}
