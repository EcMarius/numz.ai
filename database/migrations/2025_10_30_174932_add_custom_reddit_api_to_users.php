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
            $table->text('reddit_client_id')->nullable()->after('email');
            $table->text('reddit_client_secret')->nullable()->after('reddit_client_id');
            $table->boolean('reddit_use_custom_api')->default(false)->after('reddit_client_secret');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['reddit_client_id', 'reddit_client_secret', 'reddit_use_custom_api']);
        });
    }
};
