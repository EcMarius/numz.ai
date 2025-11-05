<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Models\User;

class ClearUserCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:clear-cache {user_id? : The ID of the user to clear cache for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear cache for a specific user or all users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user_id');

        if ($userId) {
            // Clear cache for specific user
            $user = User::find($userId);

            if (!$user) {
                $this->error("User with ID {$userId} not found.");
                return 1;
            }

            $this->clearUserCache($user);
            $this->info("Cache cleared for user: {$user->name} (ID: {$user->id})");
        } else {
            // Clear cache for all users
            if (!$this->confirm('Do you want to clear cache for ALL users?')) {
                $this->info('Operation cancelled.');
                return 0;
            }

            $users = User::all();
            $count = 0;

            foreach ($users as $user) {
                $this->clearUserCache($user);
                $count++;
            }

            $this->info("Cache cleared for {$count} users.");
        }

        return 0;
    }

    /**
     * Clear cache for a specific user
     */
    protected function clearUserCache(User $user)
    {
        // Clear subscription-related cache
        Cache::forget("user_subscriber_{$user->id}");
        Cache::forget("user_subscribed_plan_{$user->id}");

        // Clear user model cache if method exists
        if (method_exists($user, 'clearUserCache')) {
            $user->clearUserCache();
        }

        // Clear any other common user caches
        Cache::forget("user_{$user->id}_subscription");
        Cache::forget("user_{$user->id}_plan");
    }
}
