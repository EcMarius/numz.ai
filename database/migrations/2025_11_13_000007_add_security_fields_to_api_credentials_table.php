<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('api_credentials')) {
            // Table doesn't exist, create it
            Schema::create('api_credentials', function (Blueprint $table) {
                $table->id();
                $table->string('service_name');
                $table->string('display_name');
                $table->string('credential_type')->default('api_key');
                $table->text('api_key')->nullable();
                $table->text('api_secret')->nullable();
                $table->text('access_token')->nullable();
                $table->text('refresh_token')->nullable();
                $table->json('additional_config')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->unsignedInteger('usage_count')->default(0);
                $table->unsignedInteger('rate_limit')->nullable();
                $table->unsignedInteger('rate_limit_remaining')->nullable();
                $table->timestamp('rate_limit_reset_at')->nullable();
                $table->timestamps();

                $table->index('service_name');
                $table->index('is_active');
            });
        } else {
            // Table exists, just add missing columns
            Schema::table('api_credentials', function (Blueprint $table) {
                if (!Schema::hasColumn('api_credentials', 'credential_type')) {
                    $table->string('credential_type')->default('api_key')->after('display_name');
                }
            });
        }
    }

    public function down(): void
    {
        // We don't drop the table as it might have been created elsewhere
        Schema::table('api_credentials', function (Blueprint $table) {
            if (Schema::hasColumn('api_credentials', 'credential_type')) {
                $table->dropColumn('credential_type');
            }
        });
    }
};
