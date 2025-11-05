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
        if (Schema::hasTable('evenleads_campaigns')) {
            Schema::table('evenleads_campaigns', function (Blueprint $table) {
                if (!Schema::hasColumn('evenleads_campaigns', 'selected_accounts')) {
                    // Store selected social account IDs per platform
                    // Format: {"reddit": 5, "facebook": 3, "twitter": 7}
                    $table->json('selected_accounts')->nullable()->after('platforms');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evenleads_campaigns', function (Blueprint $table) {
            $table->dropColumn('selected_accounts');
        });
    }
};
