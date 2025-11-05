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
        if (!Schema::hasTable('growth_hacking_prospects')) {
            Schema::create('growth_hacking_prospects', function (Blueprint $table) {
                $table->id();
                $table->foreignId('campaign_id')->constrained('growth_hacking_campaigns')->cascadeOnDelete();
                $table->string('website_url');
                $table->string('business_name')->nullable();
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->string('contact_person_name')->nullable();
                $table->string('contact_person_email')->nullable();
                $table->json('inbound_links')->nullable(); // Array of 1-depth links
                $table->longText('website_content')->nullable(); // Scraped content
                $table->json('ai_analysis')->nullable(); // Business type, industry, pain points, etc.
                $table->enum('status', ['pending', 'analyzed', 'account_created', 'email_sent', 'logged_in', 'skipped'])->default('pending');
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // Created account
                $table->string('secure_token')->nullable()->unique(); // For password setup
                $table->timestamp('token_expires_at')->nullable();
                $table->integer('leads_found')->default(0);
                $table->timestamps();

                $table->index('email');
                $table->index('secure_token');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('growth_hacking_prospects');
    }
};
