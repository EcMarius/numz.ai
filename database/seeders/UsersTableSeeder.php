<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class UsersTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     */
    public function run(): void
    {
        // Use updateOrCreate for idempotency
        $user = User::updateOrCreate(
            ['email' => 'contact@evenleads.com'],
            [
                'id' => 1,
                'name' => 'EvenLeads Admin',
                'username' => 'contact',
                'avatar' => 'demo/default.png',
                'password' => bcrypt('CoEvenLeads1!@aA'),
                'remember_token' => null,
                'trial_ends_at' => null,
                'verification_code' => null,
                'verified' => 1,
            ]
        );

        if ($user->wasRecentlyCreated) {
            $this->command->info('✅ New user created successfully! Email: ' . $user->email);
        } else {
            $this->command->info('✅ User updated successfully! Email: ' . $user->email);
        }
    }
}
