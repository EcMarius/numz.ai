<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('evenleads_platform_connections')) {
            return;
        }

        Schema::table('evenleads_platform_connections', function (Blueprint $table) {
            // Add account_name field to support multiple accounts per platform
            if (!Schema::hasColumn('evenleads_platform_connections', 'account_name')) {
                $table->string('account_name')->nullable()->after('platform');
            }
        });

        // Drop old unique constraint and add new one
        // Use raw SQL to check if index exists
        $connection = Schema::getConnection();
        $tableName = 'evenleads_platform_connections';

        // Get all indexes on the table
        $indexes = $connection->select(
            "SHOW INDEX FROM {$tableName} WHERE Key_name LIKE '%user%platform%unique%' OR Key_name LIKE '%user_id_platform%'"
        );

        // Drop old unique constraint if it exists
        if (!empty($indexes)) {
            foreach ($indexes as $index) {
                try {
                    $connection->statement("ALTER TABLE {$tableName} DROP INDEX `{$index->Key_name}`");
                    \Log::info("Dropped old index: {$index->Key_name}");
                    break; // Only drop the first matching index
                } catch (\Exception $e) {
                    \Log::info("Could not drop index {$index->Key_name}: " . $e->getMessage());
                }
            }
        }

        // Add new composite unique index
        try {
            Schema::table('evenleads_platform_connections', function (Blueprint $table) {
                $table->unique(['user_id', 'platform', 'account_name'], 'platform_connections_user_platform_account_unique');
            });
        } catch (\Exception $e) {
            // Index might already exist
            \Log::info('Unique index might already exist: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('evenleads_platform_connections')) {
            return;
        }

        Schema::table('evenleads_platform_connections', function (Blueprint $table) {
            // Drop the new unique index
            try {
                $table->dropUnique('platform_connections_user_platform_account_unique');
            } catch (\Exception $e) {
                \Log::info('Could not drop unique index: ' . $e->getMessage());
            }

            // Remove account_name column
            if (Schema::hasColumn('evenleads_platform_connections', 'account_name')) {
                $table->dropColumn('account_name');
            }
        });

        // Restore old unique constraint
        Schema::table('evenleads_platform_connections', function (Blueprint $table) {
            try {
                $table->unique(['user_id', 'platform']);
            } catch (\Exception $e) {
                \Log::info('Could not add back old unique index: ' . $e->getMessage());
            }
        });
    }
};
