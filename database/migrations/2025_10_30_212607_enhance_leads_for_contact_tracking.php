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
        Schema::table('evenleads_leads', function (Blueprint $table) {
            // Contact tracking fields
            $table->timestamp('last_contact_at')->nullable()->after('contacted_at');
            $table->string('last_contact_channel')->nullable()->after('last_contact_at')->comment('comment or dm');
            $table->timestamp('response_received_at')->nullable()->after('last_contact_channel');
            $table->integer('response_count')->default(0)->after('response_received_at');
            $table->integer('engagement_score')->default(0)->after('response_count')->comment('0-100 score based on interaction quality');
            $table->timestamp('last_checked_for_response_at')->nullable()->after('engagement_score');

            // Add indexes for performance
            $table->index('last_contact_at');
            $table->index(['status', 'last_contact_at']);
            $table->index(['last_checked_for_response_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evenleads_leads', function (Blueprint $table) {
            $table->dropIndex(['last_contact_at']);
            $table->dropIndex(['status', 'last_contact_at']);
            $table->dropIndex(['last_checked_for_response_at']);

            $table->dropColumn([
                'last_contact_at',
                'last_contact_channel',
                'response_received_at',
                'response_count',
                'engagement_score',
                'last_checked_for_response_at',
            ]);
        });
    }
};
