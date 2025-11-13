<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consent_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('consent_type', 50)->index();
            $table->text('consent_text');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('consented_at');
            $table->timestamp('withdrawn_at')->nullable();
            $table->string('version', 20)->default('1.0');
            $table->timestamps();

            $table->index(['user_id', 'consent_type']);
            $table->index('consented_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consent_logs');
    }
};
