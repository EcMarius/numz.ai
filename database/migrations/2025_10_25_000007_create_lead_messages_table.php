<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('lead_messages')) {
            Schema::create('lead_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('lead_id')->constrained('evenleads_leads')->onDelete('cascade');
                $table->text('message_text');
                $table->timestamp('sent_at')->nullable();
                $table->boolean('is_ai_generated')->default(false);
                $table->string('ai_model_used', 50)->nullable();
                $table->enum('direction', ['outgoing', 'incoming'])->default('outgoing');
                $table->string('platform_message_id')->nullable();
                $table->enum('status', ['draft', 'sent', 'delivered', 'failed'])->default('draft');
                $table->timestamps();

                $table->index(['lead_id', 'created_at']);
                $table->index(['user_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_messages');
    }
};
