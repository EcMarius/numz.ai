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
                if (!Schema::hasColumn('evenleads_campaigns', 'follow_up_use_variations')) {
                    $table->boolean('follow_up_use_variations')->default(false)->after('follow_up_template');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('evenleads_campaigns')) {
            Schema::table('evenleads_campaigns', function (Blueprint $table) {
                if (Schema::hasColumn('evenleads_campaigns', 'follow_up_use_variations')) {
                    $table->dropColumn('follow_up_use_variations');
                }
            });
        }
    }
};
