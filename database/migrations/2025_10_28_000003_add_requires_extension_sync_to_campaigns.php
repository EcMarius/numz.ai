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
            $table->boolean('requires_extension_sync')->default(false)->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evenleads_campaigns', function (Blueprint $table) {
            $table->dropColumn('requires_extension_sync');
        });
    }
};
