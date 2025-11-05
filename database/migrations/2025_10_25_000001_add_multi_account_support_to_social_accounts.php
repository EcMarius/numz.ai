<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Only add columns if they don't exist
        if (Schema::hasTable('social_accounts')) {
            Schema::table('social_accounts', function (Blueprint $table) {
                if (!Schema::hasColumn('social_accounts', 'account_name')) {
                    $table->string('account_name')->nullable()->after('provider_id');
                }
                if (!Schema::hasColumn('social_accounts', 'is_primary')) {
                    $table->boolean('is_primary')->default(false)->after('account_name');
                }
            });

            // Drop the old unique constraint (user_id, provider)
            try {
                DB::statement('ALTER TABLE social_accounts DROP INDEX social_accounts_user_id_provider_unique');
            } catch (\Exception $e) {
                // Constraint might not exist or have a different name, that's okay
            }

            // Add new unique constraint on (user_id, provider, provider_id)
            // Check if constraint doesn't already exist
            try {
                Schema::table('social_accounts', function (Blueprint $table) {
                    $table->unique(['user_id', 'provider', 'provider_id'], 'social_accounts_user_provider_id_unique');
                });
            } catch (\Exception $e) {
                // Constraint might already exist
            }

            // Set existing accounts as primary only if is_primary column was just added
            if (Schema::hasColumn('social_accounts', 'is_primary')) {
                DB::table('social_accounts')
                    ->whereNull('is_primary')
                    ->orWhere('is_primary', false)
                    ->update(['is_primary' => true]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the new unique constraint
        Schema::table('social_accounts', function (Blueprint $table) {
            $table->dropUnique('social_accounts_user_provider_id_unique');
        });

        // Recreate old unique constraint
        Schema::table('social_accounts', function (Blueprint $table) {
            $table->unique(['user_id', 'provider'], 'social_accounts_user_id_provider_unique');
        });

        // Drop new columns
        Schema::table('social_accounts', function (Blueprint $table) {
            $table->dropColumn(['account_name', 'is_primary']);
        });
    }
};
