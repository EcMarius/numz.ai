<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Quotes/Proposals
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->string('quote_number')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status'); // draft, sent, viewed, accepted, declined, expired, converted
            $table->string('type')->default('standard'); // standard, proforma, estimate

            // Pricing
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');

            // Discount handling
            $table->string('discount_type')->nullable(); // percentage, fixed
            $table->decimal('discount_value', 10, 2)->nullable();
            $table->foreignId('coupon_id')->nullable()->constrained()->onDelete('set null');

            // Validity
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->integer('validity_days')->default(30);

            // Terms & Notes
            $table->text('terms_and_conditions')->nullable();
            $table->text('internal_notes')->nullable(); // Not visible to customer
            $table->text('customer_notes')->nullable(); // Visible to customer

            // Tracking
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->string('declined_reason')->nullable();

            // Converted order
            $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('set null');

            // Email tracking
            $table->integer('email_sent_count')->default(0);
            $table->integer('view_count')->default(0);

            // Follow-up
            $table->timestamp('follow_up_date')->nullable();
            $table->boolean('follow_up_sent')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('quote_number');
            $table->index('status');
            $table->index('valid_until');
        });

        // Quote items/line items
        Schema::create('quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('hosting_products')->onDelete('set null');
            $table->string('item_type'); // product, service, custom, domain
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->string('billing_cycle')->nullable(); // one-time, monthly, annually, etc.
            $table->integer('setup_fee')->default(0);
            $table->json('metadata')->nullable(); // Additional data
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('quote_id');
        });

        // Quote templates
        Schema::create('quote_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('content'); // HTML template
            $table->string('type')->default('standard'); // standard, professional, minimal
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('sections')->nullable(); // Which sections to include
            $table->json('styling')->nullable(); // Colors, fonts, etc.
            $table->timestamps();
        });

        // Quote activities/history
        Schema::create('quote_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('action'); // created, sent, viewed, accepted, declined, updated, etc.
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // Additional context
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index('quote_id');
            $table->index('action');
        });

        // Quote attachments
        Schema::create('quote_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->onDelete('cascade');
            $table->string('filename');
            $table->string('original_filename');
            $table->string('mime_type');
            $table->integer('file_size'); // in bytes
            $table->string('storage_path');
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index('quote_id');
        });

        // Quote signatures (for accepted quotes)
        Schema::create('quote_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('signature_data'); // Base64 signature image or text
            $table->string('signature_type'); // drawn, typed, uploaded
            $table->string('signer_name');
            $table->string('signer_email');
            $table->string('signer_title')->nullable();
            $table->string('signer_company')->nullable();
            $table->string('ip_address');
            $table->string('user_agent')->nullable();
            $table->timestamp('signed_at');
            $table->timestamps();

            $table->index('quote_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_signatures');
        Schema::dropIfExists('quote_attachments');
        Schema::dropIfExists('quote_activities');
        Schema::dropIfExists('quote_templates');
        Schema::dropIfExists('quote_items');
        Schema::dropIfExists('quotes');
    }
};
