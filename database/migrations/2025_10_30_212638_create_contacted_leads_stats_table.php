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
        Schema::create('contacted_leads_stats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('platform'); // reddit, x, facebook, etc.
            $table->string('channel'); // comment or dm
            $table->integer('total_contacted')->default(0);
            $table->integer('total_responded')->default(0);
            $table->decimal('response_rate', 5, 2)->default(0)->comment('Percentage: 0-100');
            $table->decimal('avg_response_time_hours', 8, 2)->nullable()->comment('Average time to get response in hours');
            $table->timestamp('period_start');
            $table->timestamp('period_end');
            $table->timestamps();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Indexes for performance
            $table->index(['user_id', 'platform', 'channel']);
            $table->index(['period_start', 'period_end']);
            $table->unique(['user_id', 'platform', 'channel', 'period_start'], 'stats_unique_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacted_leads_stats');
    }
};
