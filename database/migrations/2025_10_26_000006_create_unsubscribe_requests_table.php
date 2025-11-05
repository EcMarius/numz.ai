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
        if (!Schema::hasTable('unsubscribe_requests')) {
            Schema::create('unsubscribe_requests', function (Blueprint $table) {
                $table->id();
                $table->string('email_address');
                $table->string('token')->unique();
                $table->text('reason')->nullable();
                $table->timestamps();

                $table->index('email_address');
                $table->index('token');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unsubscribe_requests');
    }
};
