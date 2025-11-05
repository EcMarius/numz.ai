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
        Schema::table('evenleads_campaigns', function (Blueprint $table) {
            $table->json('twitter_communities')->nullable()->after('linkedin_groups');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evenleads_campaigns', function (Blueprint $table) {
            if (Schema::hasColumn('evenleads_campaigns', 'twitter_communities')) {
                $table->dropColumn('twitter_communities');
            }
        });
    }
};
