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
        Schema::table('plans', function (Blueprint $table) {
            $table->string('currency', 3)->default('EUR')->after('monthly_price');
            $table->boolean('custom_plan')->default(false)->after('default');
            $table->text('custom_plan_description')->nullable()->after('custom_plan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['currency', 'custom_plan', 'custom_plan_description']);
        });
    }
};
