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
        if (!Schema::hasTable('growth_hacking_campaigns')) {
            Schema::create('growth_hacking_campaigns', function (Blueprint $table) {
                $table->id();
                $table->foreignId('admin_user_id')->constrained('users')->cascadeOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->text('website_urls'); // One per line
                $table->enum('email_method', ['site_smtp', 'custom_smtp', 'external_relay'])->default('site_smtp');
                $table->foreignId('smtp_config_id')->nullable()->constrained('smtp_configs')->nullOnDelete();
                $table->json('custom_smtp_config')->nullable(); // For external relay
                $table->boolean('auto_create_accounts')->default(true);
                $table->string('email_subject_template')->nullable();
                $table->text('email_body_template')->nullable();
                $table->enum('status', ['draft', 'processing', 'review', 'sent', 'completed'])->default('draft');
                $table->integer('total_prospects')->default(0);
                $table->integer('emails_sent')->default(0);
                $table->integer('accounts_created')->default(0);
                $table->integer('emails_opened')->default(0);
                $table->integer('emails_clicked')->default(0);
                $table->integer('logged_in_count')->default(0);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('growth_hacking_campaigns');
    }
};
