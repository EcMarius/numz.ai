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
        // Support Tickets table
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('ticket_number')->unique();
            $table->string('subject');
            $table->string('department')->default('general'); // general, technical, billing, sales
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->enum('status', ['open', 'in_progress', 'waiting_customer', 'waiting_staff', 'closed'])->default('open');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('related_service_id')->nullable()->constrained('hosting_services')->nullOnDelete();
            $table->foreignId('related_domain_id')->nullable()->constrained('domain_registrations')->nullOnDelete();
            $table->foreignId('related_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->timestamp('last_reply_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('ticket_number');
            $table->index('status');
            $table->index('assigned_to');
        });

        // Ticket Replies table
        Schema::create('support_ticket_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('message');
            $table->boolean('is_staff_reply')->default(false);
            $table->boolean('is_internal_note')->default(false); // Only visible to staff
            $table->json('attachments')->nullable();
            $table->timestamps();

            $table->index('ticket_id');
            $table->index(['ticket_id', 'created_at']);
        });

        // Ticket Attachments table
        Schema::create('support_ticket_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('reply_id')->nullable()->constrained('support_ticket_replies')->cascadeOnDelete();
            $table->string('filename');
            $table->string('original_filename');
            $table->string('mime_type');
            $table->integer('file_size'); // in bytes
            $table->string('storage_path');
            $table->timestamps();

            $table->index('ticket_id');
            $table->index('reply_id');
        });

        // Canned Responses table (predefined responses for staff)
        Schema::create('support_canned_responses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('category')->default('general');
            $table->text('content');
            $table->boolean('is_active')->default(true);
            $table->integer('usage_count')->default(0);
            $table->timestamps();

            $table->index('category');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_canned_responses');
        Schema::dropIfExists('support_ticket_attachments');
        Schema::dropIfExists('support_ticket_replies');
        Schema::dropIfExists('support_tickets');
    }
};
