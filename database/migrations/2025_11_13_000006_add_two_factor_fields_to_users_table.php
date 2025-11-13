<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // These might already exist from the earlier migration
            // We'll check before adding

            if (!Schema::hasColumn('users', 'phone_number')) {
                $table->string('phone_number', 20)->nullable()->after('email');
            }

            if (!Schema::hasColumn('users', 'two_factor_enabled')) {
                $table->boolean('two_factor_enabled')->default(false)->after('two_factor_confirmed_at');
            }

            if (!Schema::hasColumn('users', 'two_factor_method')) {
                $table->enum('two_factor_method', ['app', 'sms', 'email'])->nullable()->after('two_factor_enabled');
            }

            if (!Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('two_factor_method');
            }

            if (!Schema::hasColumn('users', 'last_login_ip')) {
                $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone_number',
                'two_factor_enabled',
                'two_factor_method',
                'last_login_at',
                'last_login_ip',
            ]);
        });
    }
};
