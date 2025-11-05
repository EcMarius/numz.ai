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
        Schema::table('evenleads_ai_generations', function (Blueprint $table) {
            $table->string('model_used')->after('type')->default('gpt-3.5-turbo')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evenleads_ai_generations', function (Blueprint $table) {
            $table->dropColumn('model_used');
        });
    }
};
