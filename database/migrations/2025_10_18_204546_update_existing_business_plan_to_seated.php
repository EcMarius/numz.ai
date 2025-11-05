<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Wave\Plan;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update Business plan to be seated and set new prices
        $businessPlan = Plan::where('name', 'Business')->first();

        if ($businessPlan) {
            $businessPlan->is_seated_plan = true;
            $businessPlan->monthly_price = '50';
            $businessPlan->yearly_price = '500';
            $businessPlan->description = 'Advanced features for teams with per-seat pricing';
            $businessPlan->save();

            echo "✓ Updated Business plan to seated plan with €50/seat pricing\n";
        }

        // Add requires_organization column to subscriptions
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->boolean('requires_organization')->default(true)->after('seats_used');
        });

        // Mark all existing Business plan subscriptions as NOT requiring organization
        // This grandfathers in existing users as individual subscribers
        \DB::table('subscriptions')
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->where('plans.name', 'Business')
            ->update(['subscriptions.requires_organization' => false]);

        echo "✓ Grandfathered existing Business plan users as individual subscribers\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('requires_organization');
        });

        $businessPlan = Plan::where('name', 'Business')->first();

        if ($businessPlan) {
            $businessPlan->is_seated_plan = false;
            $businessPlan->monthly_price = '99';
            $businessPlan->yearly_price = '990';
            $businessPlan->save();
        }
    }
};
