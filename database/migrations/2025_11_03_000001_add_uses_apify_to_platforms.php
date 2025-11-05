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
        Schema::table('evenleads_platforms', function (Blueprint $table) {
            $table->boolean('uses_apify')->default(false)->after('requires_connection');
        });

        // Enable Apify for Facebook, LinkedIn, and Google Maps
        DB::table('evenleads_platforms')
            ->whereIn('name', ['facebook', 'linkedin', 'googlemaps'])
            ->update(['uses_apify' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evenleads_platforms', function (Blueprint $table) {
            $table->dropColumn('uses_apify');
        });
    }
};
