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
        // System updates tracking
        Schema::create('system_updates', function (Blueprint $table) {
            $table->id();
            $table->string('version', 20);
            $table->string('previous_version', 20)->nullable();
            $table->string('update_type')->default('minor'); // major, minor, patch, hotfix
            $table->string('status')->default('pending'); // pending, downloading, installing, completed, failed, rolled_back
            $table->text('changelog')->nullable();
            $table->string('download_url')->nullable();
            $table->string('checksum')->nullable();
            $table->integer('download_size')->nullable(); // in bytes
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('backup_info')->nullable(); // Information about created backup
            $table->boolean('auto_update')->default(false);
            $table->integer('progress_percentage')->default(0);
            $table->json('update_steps')->nullable(); // Track individual steps
            $table->timestamps();

            $table->index('version');
            $table->index('status');
            $table->index(['status', 'created_at']);
        });

        // Version check log
        Schema::create('version_checks', function (Blueprint $table) {
            $table->id();
            $table->string('current_version', 20);
            $table->string('latest_version', 20)->nullable();
            $table->boolean('update_available')->default(false);
            $table->string('check_status')->default('success'); // success, failed
            $table->text('error_message')->nullable();
            $table->json('release_info')->nullable(); // Full release information
            $table->timestamp('checked_at');
            $table->timestamps();

            $table->index('checked_at');
            $table->index(['update_available', 'checked_at']);
        });

        // Update backups
        Schema::create('update_backups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('system_update_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('version', 20); // Version being backed up
            $table->string('backup_type')->default('full'); // full, database_only, files_only
            $table->string('database_backup_path')->nullable();
            $table->string('files_backup_path')->nullable();
            $table->bigInteger('backup_size')->nullable(); // in bytes
            $table->boolean('is_restorable')->default(true);
            $table->timestamp('created_at');
            $table->timestamp('expires_at')->nullable();
            $table->text('notes')->nullable();

            $table->index('version');
            $table->index(['created_at', 'is_restorable']);
        });

        // Update notifications
        Schema::create('update_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('version', 20);
            $table->string('notification_type')->default('new_version'); // new_version, update_started, update_completed, update_failed
            $table->text('message');
            $table->json('metadata')->nullable();
            $table->boolean('is_read')->default(false);
            $table->boolean('is_dismissed')->default(false);
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_read']);
            $table->index(['version', 'notification_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('update_notifications');
        Schema::dropIfExists('update_backups');
        Schema::dropIfExists('version_checks');
        Schema::dropIfExists('system_updates');
    }
};
