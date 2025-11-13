<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            if (!Schema::hasColumn('roles', 'is_system_role')) {
                $table->boolean('is_system_role')->default(false)->after('guard_name');
            }
            if (!Schema::hasColumn('roles', 'priority')) {
                $table->integer('priority')->default(999)->after('is_system_role');
            }
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['is_system_role', 'priority']);
        });
    }
};
