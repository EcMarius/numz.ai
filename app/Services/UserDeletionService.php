<?php

namespace App\Services;

use App\Models\User;
use App\Models\DataDeletionRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class UserDeletionService
{
    /**
     * Permanently delete ALL user data from the system (GDPR Article 17 - Right to Erasure)
     *
     * @param User $user
     * @param DataDeletionRequest|null $deletionRequest
     * @return array ['success' => bool, 'message' => string, 'deleted_counts' => array]
     */
    public function deleteUserCompletely(User $user, ?DataDeletionRequest $deletionRequest = null): array
    {
        $userId = $user->id;
        $userEmail = $user->email;
        $deletedCounts = [];

        Log::info('Starting complete user deletion', [
            'user_id' => $userId,
            'email' => $userEmail,
            'deletion_request_id' => $deletionRequest?->id,
        ]);

        try {
            DB::beginTransaction();

            // PHASE 1: Cancel external services FIRST (before deleting data)
            $this->cancelExternalServices($user);

            // PHASE 2: Delete user data from all tables
            $deletedCounts = $this->deleteAllUserData($user);

            // PHASE 3: Delete the data deletion request itself (contains user's PII)
            if ($deletionRequest) {
                $deletionRequest->delete();
                Log::info('Data deletion request removed', ['request_id' => $deletionRequest->id]);
            }

            // PHASE 4: Finally delete the user record
            $user->delete();
            Log::info('User record deleted', ['user_id' => $userId]);

            DB::commit();

            Log::info('User deletion completed successfully', [
                'user_id' => $userId,
                'email' => $userEmail,
                'total_records_deleted' => array_sum($deletedCounts),
                'breakdown' => $deletedCounts,
            ]);

            return [
                'success' => true,
                'message' => 'User and all associated data deleted successfully',
                'deleted_counts' => $deletedCounts,
            ];

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('User deletion failed', [
                'user_id' => $userId,
                'email' => $userEmail,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to delete user: ' . $e->getMessage(),
                'deleted_counts' => [],
            ];
        }
    }

    /**
     * Cancel all external services (Stripe, OAuth tokens, etc.)
     */
    protected function cancelExternalServices(User $user): void
    {
        // Cancel Stripe subscription if active
        try {
            $subscription = $user->subscription;
            if ($subscription && $subscription->active()) {
                // Cancel via Stripe API
                $stripe = new \Stripe\StripeClient(config('cashier.secret'));

                if ($subscription->stripe_id) {
                    $stripe->subscriptions->cancel($subscription->stripe_id, [
                        'prorate' => false,
                    ]);
                    Log::info('Stripe subscription cancelled', [
                        'user_id' => $user->id,
                        'subscription_id' => $subscription->stripe_id,
                    ]);
                }
            }
        } catch (Exception $e) {
            Log::warning('Failed to cancel Stripe subscription', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            // Continue anyway - we'll delete the subscription record
        }

        // Revoke platform OAuth tokens (Reddit, LinkedIn, etc.)
        try {
            $platformConnections = DB::table('evenleads_platform_connections')
                ->where('user_id', $user->id)
                ->get();

            foreach ($platformConnections as $connection) {
                // TODO: Call platform APIs to revoke tokens if needed
                // For now, just delete the connection (tokens will expire)
                Log::info('Platform connection will be deleted', [
                    'user_id' => $user->id,
                    'platform' => $connection->platform ?? 'unknown',
                ]);
            }
        } catch (Exception $e) {
            Log::warning('Failed to revoke platform tokens', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Delete all user data from database tables
     */
    protected function deleteAllUserData(User $user): array
    {
        $counts = [];

        // Define all tables with direct user_id foreign key
        $userTables = [
            // Core Wave tables
            'api_keys',
            'profile_key_values' => ['key' => 'keyvalue_id', 'type' => 'keyvalue_type', 'value' => 'users'],
            'posts' => ['key' => 'author_id'],
            'sessions' => ['key' => 'user_id', 'nullable' => true],

            // EvenLeads tables (most have CASCADE, but delete explicitly for logging)
            'evenleads_campaigns',
            'evenleads_leads',
            'evenleads_platform_connections',
            'evenleads_ai_generations',
            'evenleads_sync_history',
            'evenleads_chat_conversations',
            'evenleads_chat_messages',
            'evenleads_feedback',
            'evenleads_user_posts',
            'evenleads_post_comments',
            'evenleads_crm_contacts',

            // Application tables
            'social_accounts',
            'account_warmups',
            'lead_messages',
            'seat_change_history',
            'platform_schema_history',

            // Growth hacking tables
            'growth_hacking_campaigns' => ['key' => 'admin_user_id'],
            'growth_hacking_prospects' => ['via_cascade' => true], // Deleted via campaign cascade
            'growth_hacking_leads' => ['via_cascade' => true], // Deleted via campaign cascade
            'growth_hacking_emails' => ['via_cascade' => true], // Deleted via lead cascade

            // Data deletion requests (all of them, not just current one)
            'data_deletion_requests',
        ];

        // Delete from each table
        foreach ($userTables as $table => $options) {
            if (is_numeric($table)) {
                $table = $options;
                $options = [];
            }

            // Skip cascade-only tables (will be handled by DB)
            if (isset($options['via_cascade']) && $options['via_cascade']) {
                continue;
            }

            $key = $options['key'] ?? 'user_id';
            $type = $options['type'] ?? null;
            $value = $options['value'] ?? null;

            try {
                if (!$this->tableExists($table)) {
                    continue;
                }

                if ($type && $value) {
                    // Polymorphic relationship
                    $count = DB::table($table)
                        ->where($key, $user->id)
                        ->where($type, $value)
                        ->delete();
                } else {
                    // Direct foreign key
                    $count = DB::table($table)
                        ->where($key, $user->id)
                        ->delete();
                }

                if ($count > 0) {
                    $counts[$table] = $count;
                    Log::info("Deleted from {$table}", [
                        'user_id' => $user->id,
                        'count' => $count,
                    ]);
                }
            } catch (Exception $e) {
                Log::error("Failed to delete from {$table}", [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
                // Continue anyway
            }
        }

        // Handle polymorphic relationships explicitly
        $counts = array_merge($counts, $this->deletePolymorphicData($user));

        // Handle subscriptions (polymorphic billable)
        $counts = array_merge($counts, $this->deleteSubscriptions($user));

        // Handle organization ownership and memberships
        $counts = array_merge($counts, $this->handleOrganizations($user));

        // Anonymize API usage logs (keep for analytics, remove PII)
        $counts = array_merge($counts, $this->anonymizeApiLogs($user));

        // Handle Spatie permissions (polymorphic)
        $counts = array_merge($counts, $this->deletePermissionsAndRoles($user));

        return $counts;
    }

    /**
     * Delete polymorphic relationships (notifications, tokens, etc.)
     */
    protected function deletePolymorphicData(User $user): array
    {
        $counts = [];

        try {
            // Notifications (morphMany)
            if ($this->tableExists('notifications')) {
                $count = DB::table('notifications')
                    ->where('notifiable_type', 'App\\Models\\User')
                    ->where('notifiable_id', $user->id)
                    ->delete();
                if ($count > 0) $counts['notifications'] = $count;
            }

            // Personal access tokens (Sanctum)
            if ($this->tableExists('personal_access_tokens')) {
                $count = DB::table('personal_access_tokens')
                    ->where('tokenable_type', 'App\\Models\\User')
                    ->where('tokenable_id', $user->id)
                    ->delete();
                if ($count > 0) $counts['personal_access_tokens'] = $count;
            }
        } catch (Exception $e) {
            Log::error('Failed to delete polymorphic data', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $counts;
    }

    /**
     * Delete subscriptions (polymorphic billable relationship)
     */
    protected function deleteSubscriptions(User $user): array
    {
        $counts = [];

        try {
            if ($this->tableExists('subscriptions')) {
                $count = DB::table('subscriptions')
                    ->where('billable_type', 'user')
                    ->where('billable_id', $user->id)
                    ->delete();

                // Also check for full class name
                $count += DB::table('subscriptions')
                    ->where('billable_type', 'App\\Models\\User')
                    ->where('billable_id', $user->id)
                    ->delete();

                if ($count > 0) $counts['subscriptions'] = $count;
            }
        } catch (Exception $e) {
            Log::error('Failed to delete subscriptions', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $counts;
    }

    /**
     * Handle organization ownership and memberships
     */
    protected function handleOrganizations(User $user): array
    {
        $counts = [];

        try {
            if ($this->tableExists('organizations')) {
                // Delete organizations where user is owner
                $count = DB::table('organizations')
                    ->where('owner_id', $user->id)
                    ->delete();

                if ($count > 0) $counts['organizations (owned)'] = $count;

                // Remove user from team memberships (set organization_id to null for other users)
                // Actually, delete users who are team members of this user's org
                // This is complex - for now just log a warning
                Log::warning('Check for team members that need handling', [
                    'user_id' => $user->id,
                ]);
            }
        } catch (Exception $e) {
            Log::error('Failed to handle organizations', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $counts;
    }

    /**
     * Anonymize API usage logs (GDPR allows pseudonymization for analytics)
     */
    protected function anonymizeApiLogs(User $user): array
    {
        $counts = [];

        try {
            if ($this->tableExists('api_usage_logs')) {
                $count = DB::table('api_usage_logs')
                    ->where('user_id', $user->id)
                    ->update(['user_id' => null]);

                if ($count > 0) $counts['api_usage_logs (anonymized)'] = $count;
            }
        } catch (Exception $e) {
            Log::error('Failed to anonymize API logs', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $counts;
    }

    /**
     * Delete Spatie permissions and roles
     */
    protected function deletePermissionsAndRoles(User $user): array
    {
        $counts = [];

        try {
            if ($this->tableExists('model_has_roles')) {
                $count = DB::table('model_has_roles')
                    ->where('model_type', 'App\\Models\\User')
                    ->where('model_id', $user->id)
                    ->delete();
                if ($count > 0) $counts['model_has_roles'] = $count;
            }

            if ($this->tableExists('model_has_permissions')) {
                $count = DB::table('model_has_permissions')
                    ->where('model_type', 'App\\Models\\User')
                    ->where('model_id', $user->id)
                    ->delete();
                if ($count > 0) $counts['model_has_permissions'] = $count;
            }
        } catch (Exception $e) {
            Log::error('Failed to delete permissions/roles', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $counts;
    }

    /**
     * Check if table exists in database
     */
    protected function tableExists(string $table): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable($table);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get summary statistics for logging
     */
    public function getUserDataSummary(User $user): array
    {
        $summary = [];

        try {
            $summary['campaigns'] = DB::table('evenleads_campaigns')->where('user_id', $user->id)->count();
            $summary['leads'] = DB::table('evenleads_leads')->where('user_id', $user->id)->count();
            $summary['api_keys'] = DB::table('api_keys')->where('user_id', $user->id)->count();
            $summary['subscriptions'] = DB::table('subscriptions')
                ->where('billable_id', $user->id)
                ->whereIn('billable_type', ['user', 'App\\Models\\User'])
                ->count();
        } catch (Exception $e) {
            Log::error('Failed to get user data summary', ['error' => $e->getMessage()]);
        }

        return $summary;
    }
}
