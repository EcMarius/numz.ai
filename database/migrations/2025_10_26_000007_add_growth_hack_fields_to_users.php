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
            if (!Schema::hasColumn('users', 'growth_hack_prospect_id')) {
                $table->foreignId('growth_hack_prospect_id')->nullable()->constrained('growth_hacking_prospects')->nullOnDelete();
            }
            if (!Schema::hasColumn('users', 'trial_activated_at')) {
                $table->timestamp('trial_activated_at')->nullable();
            }
            if (!Schema::hasColumn('users', 'is_growth_hack_account')) {
                $table->boolean('is_growth_hack_account')->default(false);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'growth_hack_prospect_id')) {
                $table->dropForeign(['growth_hack_prospect_id']);
                $table->dropColumn('growth_hack_prospect_id');
            }
            if (Schema::hasColumn('users', 'trial_activated_at')) {
                $table->dropColumn('trial_activated_at');
            }
            if (Schema::hasColumn('users', 'is_growth_hack_account')) {
                $table->dropColumn('is_growth_hack_account');
            }
        });
    }
};
