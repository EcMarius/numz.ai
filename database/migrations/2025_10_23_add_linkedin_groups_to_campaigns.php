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
            $table->json('linkedin_groups')->nullable()->after('reddit_subreddits');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evenleads_campaigns', function (Blueprint $table) {
            if (Schema::hasColumn('evenleads_campaigns', 'linkedin_groups')) {
                $table->dropColumn('linkedin_groups');
            }
        });
    }
};
