<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if the setting already exists
        $exists = DB::table('settings')
            ->where('key', 'evenleads_enable_portfolio')
            ->exists();

        if (!$exists) {
            DB::table('settings')->insert([
                'key' => 'evenleads_enable_portfolio',
                'display_name' => 'Enable Portfolio Field in Campaigns',
                'value' => '0', // Disabled by default
                'type' => 'checkbox',
                'group' => 'evenleads',
                'details' => json_encode([
                    'description' => 'Enable the portfolio file upload field in campaign creation and editing forms. When disabled, users will not see or be able to upload portfolio files.',
                    'on' => '1',
                    'off' => '0',
                    'checked' => false,
                ]),
                'order' => 100,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the setting
        DB::table('settings')
            ->where('key', 'evenleads_enable_portfolio')
            ->delete();
    }
};
