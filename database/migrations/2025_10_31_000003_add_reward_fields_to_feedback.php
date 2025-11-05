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
        if (Schema::hasTable('evenleads_feedback')) {
            Schema::table('evenleads_feedback', function (Blueprint $table) {
                if (!Schema::hasColumn('evenleads_feedback', 'reward_given')) {
                    $table->boolean('reward_given')->default(false)->after('responded_at');
                }
                if (!Schema::hasColumn('evenleads_feedback', 'reward_type')) {
                    $table->string('reward_type')->nullable()->after('reward_given');
                }
                if (!Schema::hasColumn('evenleads_feedback', 'reward_details')) {
                    $table->text('reward_details')->nullable()->after('reward_type');
                }
                if (!Schema::hasColumn('evenleads_feedback', 'reward_given_at')) {
                    $table->timestamp('reward_given_at')->nullable()->after('reward_details');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('evenleads_feedback')) {
            Schema::table('evenleads_feedback', function (Blueprint $table) {
                $columns = ['reward_given', 'reward_type', 'reward_details', 'reward_given_at'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('evenleads_feedback', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
