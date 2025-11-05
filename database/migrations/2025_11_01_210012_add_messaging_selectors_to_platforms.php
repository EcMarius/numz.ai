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
                // Add messaging selector configuration fields
                if (!Schema::hasColumn('evenleads_platforms', 'message_input_selectors')) {
                    $table->json('message_input_selectors')->nullable();
                }

                if (!Schema::hasColumn('evenleads_platforms', 'message_send_button_selectors')) {
                    $table->json('message_send_button_selectors')->nullable();
                }

                if (!Schema::hasColumn('evenleads_platforms', 'supports_enter_to_send')) {
                    $table->boolean('supports_enter_to_send')->default(true);
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
                if (Schema::hasColumn('evenleads_platforms', 'supports_enter_to_send')) {
                    $table->dropColumn('supports_enter_to_send');
                }

                if (Schema::hasColumn('evenleads_platforms', 'message_send_button_selectors')) {
                    $table->dropColumn('message_send_button_selectors');
                }

                if (Schema::hasColumn('evenleads_platforms', 'message_input_selectors')) {
                    $table->dropColumn('message_input_selectors');
                }
            });
        }
    }
};
