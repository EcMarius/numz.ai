<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->timestamp('current_period_start')->nullable()->after('ends_at');
            $table->timestamp('current_period_end')->nullable()->after('current_period_start');
        });
    }

    public function down()
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['current_period_start', 'current_period_end']);
        });
    }
};
