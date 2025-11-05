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
            $table->boolean('follow_up_enabled')->default(false)->after('include_call_to_action');
            $table->integer('follow_up_days')->default(3)->after('follow_up_enabled');
            $table->string('follow_up_mode')->default('ai')->after('follow_up_days')->comment('ai or template');
            $table->text('follow_up_template')->nullable()->after('follow_up_mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evenleads_campaigns', function (Blueprint $table) {
            $table->dropColumn([
                'follow_up_enabled',
                'follow_up_days',
                'follow_up_mode',
                'follow_up_template',
            ]);
        });
    }
};
