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
        if (Schema::hasTable('evenleads_platforms')) {
            Schema::table('evenleads_platforms', function (Blueprint $table) {
                if (!Schema::hasColumn('evenleads_platforms', 'require_group_selection')) {
                    $table->boolean('require_group_selection')->default(false)->after('allow_group_selection');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('evenleads_platforms')) {
            Schema::table('evenleads_platforms', function (Blueprint $table) {
                if (Schema::hasColumn('evenleads_platforms', 'require_group_selection')) {
                    $table->dropColumn('require_group_selection');
                }
            });
        }
    }
};
