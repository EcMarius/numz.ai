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
        Schema::table('evenleads_sync_history', function (Blueprint $table) {
            $table->string('sync_mode', 50)->default('fast')->after('sync_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evenleads_sync_history', function (Blueprint $table) {
            $table->dropColumn('sync_mode');
        });
    }
};
