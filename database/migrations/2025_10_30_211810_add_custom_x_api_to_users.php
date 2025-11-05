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
        Schema::table('users', function (Blueprint $table) {
            $table->text('x_client_id')->nullable()->after('reddit_use_custom_api');
            $table->text('x_client_secret')->nullable()->after('x_client_id');
            $table->boolean('x_use_custom_api')->default(false)->after('x_client_secret');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['x_client_id', 'x_client_secret', 'x_use_custom_api']);
        });
    }
};
