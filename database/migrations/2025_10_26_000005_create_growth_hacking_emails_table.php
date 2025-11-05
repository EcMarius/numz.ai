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
        if (!Schema::hasTable('growth_hacking_emails')) {
            Schema::create('growth_hacking_emails', function (Blueprint $table) {
                $table->id();
                $table->foreignId('campaign_id')->constrained('growth_hacking_campaigns')->cascadeOnDelete();
                $table->foreignId('prospect_id')->constrained('growth_hacking_prospects')->cascadeOnDelete();
                $table->string('email_address');
                $table->string('subject');
                $table->text('body');
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('opened_at')->nullable();
                $table->timestamp('clicked_at')->nullable();
                $table->enum('status', ['pending', 'sent', 'delivered', 'bounced', 'unsubscribed'])->default('pending');
                $table->string('unsubscribe_token')->unique();
                $table->text('bounce_reason')->nullable();
                $table->timestamps();

                $table->index('email_address');
                $table->index('unsubscribe_token');
                $table->index('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('growth_hacking_emails');
    }
};
