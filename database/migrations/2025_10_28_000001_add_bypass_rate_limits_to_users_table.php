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
            $table->boolean('bypass_post_sync_limit')->default(false)->after('has_smart_search');
            $table->boolean('bypass_campaign_sync_limit')->default(false)->after('bypass_post_sync_limit');
            $table->boolean('bypass_ai_reply_limit')->default(false)->after('bypass_campaign_sync_limit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['bypass_post_sync_limit', 'bypass_campaign_sync_limit', 'bypass_ai_reply_limit']);
        });
    }
};
