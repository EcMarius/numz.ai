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
        Schema::table('lead_messages', function (Blueprint $table) {
            // Channel tracking (comment vs dm)
            $table->string('channel')->default('comment')->after('platform_message_id')->comment('comment or dm');

            // Follow-up automation fields
            $table->timestamp('scheduled_send_at')->nullable()->after('sent_at');
            $table->boolean('is_follow_up')->default(false)->after('scheduled_send_at');
            $table->unsignedBigInteger('parent_message_id')->nullable()->after('is_follow_up');
            $table->boolean('response_received')->default(false)->after('parent_message_id');

            // Foreign key for parent message (follow-up chain)
            $table->foreign('parent_message_id')->references('id')->on('lead_messages')->onDelete('cascade');

            // Add indexes for performance
            $table->index('scheduled_send_at');
            $table->index(['status', 'scheduled_send_at']);
            $table->index('channel');
            $table->index(['lead_id', 'channel', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lead_messages', function (Blueprint $table) {
            $table->dropForeign(['parent_message_id']);

            $table->dropIndex(['scheduled_send_at']);
            $table->dropIndex(['status', 'scheduled_send_at']);
            $table->dropIndex(['channel']);
            $table->dropIndex(['lead_id', 'channel', 'created_at']);

            $table->dropColumn([
                'channel',
                'scheduled_send_at',
                'is_follow_up',
                'parent_message_id',
                'response_received',
            ]);
        });
    }
};
