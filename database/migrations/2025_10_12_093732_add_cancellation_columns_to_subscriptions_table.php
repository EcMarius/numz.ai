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
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->timestamp('cancelled_at')->nullable()->after('ends_at');
            $table->string('cancellation_reason')->nullable()->after('cancelled_at');
            $table->text('cancellation_details')->nullable()->after('cancellation_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['cancelled_at', 'cancellation_reason', 'cancellation_details']);
        });
    }
};
