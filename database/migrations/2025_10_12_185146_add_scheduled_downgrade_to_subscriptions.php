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
        Schema::table('subscriptions', function (Blueprint $table) {
            // Scheduled downgrade fields
            $table->bigInteger('scheduled_plan_id')->nullable()->after('plan_limits_snapshot');
            $table->timestamp('scheduled_plan_date')->nullable()->after('scheduled_plan_id');
            $table->json('scheduled_plan_limits')->nullable()->after('scheduled_plan_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['scheduled_plan_id']);
            $table->dropColumn(['scheduled_plan_id', 'scheduled_plan_date', 'scheduled_plan_limits']);
        });
    }
};
