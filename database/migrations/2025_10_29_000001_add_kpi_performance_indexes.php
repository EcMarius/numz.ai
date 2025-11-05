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
        // Add indexes for KPI dashboard performance
        // Using raw SQL to avoid errors if indexes already exist

        try {
            DB::statement('CREATE INDEX users_created_at_index ON users(created_at)');
        } catch (\Exception $e) {
            // Index already exists, skip
        }

        try {
            DB::statement('CREATE INDEX users_country_index ON users(country)');
        } catch (\Exception $e) {
            // Index already exists, skip
        }

        try {
            DB::statement('CREATE INDEX subscriptions_created_at_index ON subscriptions(created_at)');
        } catch (\Exception $e) {
            // Index already exists, skip
        }

        try {
            DB::statement('CREATE INDEX subscriptions_cancelled_at_index ON subscriptions(cancelled_at)');
        } catch (\Exception $e) {
            // Index already exists, skip
        }

        try {
            DB::statement('CREATE INDEX subscriptions_trial_ends_at_index ON subscriptions(trial_ends_at)');
        } catch (\Exception $e) {
            // Index already exists, skip
        }

        try {
            DB::statement('CREATE INDEX subscriptions_status_index ON subscriptions(status)');
        } catch (\Exception $e) {
            // Index already exists, skip
        }

        try {
            DB::statement('CREATE INDEX subscriptions_status_created_at_index ON subscriptions(status, created_at)');
        } catch (\Exception $e) {
            // Index already exists, skip
        }

        try {
            DB::statement('CREATE INDEX subscriptions_billable_id_index ON subscriptions(billable_id)');
        } catch (\Exception $e) {
            // Index already exists, skip
        }

        try {
            DB::statement('CREATE INDEX subscriptions_billable_type_index ON subscriptions(billable_type)');
        } catch (\Exception $e) {
            // Index already exists, skip
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            DB::statement('DROP INDEX users_created_at_index ON users');
        } catch (\Exception $e) {
            // Index doesn't exist, skip
        }

        try {
            DB::statement('DROP INDEX users_country_index ON users');
        } catch (\Exception $e) {
            // Index doesn't exist, skip
        }

        try {
            DB::statement('DROP INDEX subscriptions_created_at_index ON subscriptions');
        } catch (\Exception $e) {
            // Index doesn't exist, skip
        }

        try {
            DB::statement('DROP INDEX subscriptions_cancelled_at_index ON subscriptions');
        } catch (\Exception $e) {
            // Index doesn't exist, skip
        }

        try {
            DB::statement('DROP INDEX subscriptions_trial_ends_at_index ON subscriptions');
        } catch (\Exception $e) {
            // Index doesn't exist, skip
        }

        try {
            DB::statement('DROP INDEX subscriptions_status_index ON subscriptions');
        } catch (\Exception $e) {
            // Index doesn't exist, skip
        }

        try {
            DB::statement('DROP INDEX subscriptions_status_created_at_index ON subscriptions');
        } catch (\Exception $e) {
            // Index doesn't exist, skip
        }
    }
};
