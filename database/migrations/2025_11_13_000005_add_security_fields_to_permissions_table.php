<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            if (!Schema::hasColumn('permissions', 'description')) {
                $table->text('description')->nullable()->after('guard_name');
            }
            if (!Schema::hasColumn('permissions', 'group')) {
                $table->string('group', 50)->nullable()->after('description')->index();
            }
            if (!Schema::hasColumn('permissions', 'is_system_permission')) {
                $table->boolean('is_system_permission')->default(false)->after('group');
            }
        });
    }

    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn(['description', 'group', 'is_system_permission']);
        });
    }
};
