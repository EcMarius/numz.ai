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
        Schema::table('users', function (Blueprint $table) {
            $table->string('occupation')->nullable()->after('avatar');
            $table->string('referral_source')->nullable()->after('occupation');
            $table->string('company_name')->nullable()->after('referral_source');
            $table->string('company_size')->nullable()->after('company_name');
            $table->text('goals')->nullable()->after('company_size');
            $table->boolean('onboarding_completed')->default(false)->after('goals');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'occupation',
                'referral_source',
                'company_name',
                'company_size',
                'goals',
                'onboarding_completed'
            ]);
        });
    }
};
