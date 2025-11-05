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
        if (!Schema::hasTable('growth_hacking_leads')) {
            Schema::create('growth_hacking_leads', function (Blueprint $table) {
                $table->id();
                $table->foreignId('prospect_id')->constrained('growth_hacking_prospects')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete(); // Prospect's account
                $table->unsignedBigInteger('campaign_id')->nullable(); // Their EvenLeads campaign ID
                $table->json('lead_data'); // Full lead details (title, description, platform, author, etc.)
                $table->decimal('confidence_score', 5, 2)->default(0);
                $table->boolean('copied_to_account')->default(false); // Copied to evenleads_leads on first login
                $table->timestamp('added_at')->useCurrent();
                $table->timestamps();

                $table->index('prospect_id');
                $table->index('user_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('growth_hacking_leads');
    }
};
