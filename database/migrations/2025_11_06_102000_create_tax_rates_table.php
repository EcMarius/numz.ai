<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "US Sales Tax", "EU VAT", "UK VAT"
            $table->decimal('rate', 5, 2); // e.g., 20.00 for 20%
            $table->enum('type', ['percent', 'fixed'])->default('percent');

            // Geographic restrictions
            $table->string('country')->nullable(); // ISO country code (US, GB, etc.)
            $table->string('state')->nullable(); // State/province code
            $table->json('zip_codes')->nullable(); // Specific zip/postal codes

            // Product restrictions
            $table->boolean('applies_to_hosting')->default(true);
            $table->boolean('applies_to_domains')->default(true);
            $table->boolean('applies_to_addons')->default(true);
            $table->boolean('applies_to_setup_fees')->default(true);

            // EU VAT specific
            $table->boolean('is_vat')->default(false);
            $table->boolean('reverse_charge')->default(false); // B2B reverse charge
            $table->boolean('require_vat_number')->default(false);

            // Priority and status
            $table->integer('priority')->default(0); // Higher priority applies first
            $table->boolean('is_active')->default(true);
            $table->boolean('is_compound')->default(false); // Applies after other taxes

            // Date range
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            $table->timestamps();

            $table->index(['country', 'state']);
            $table->index('is_active');
            $table->index('priority');
        });

        Schema::create('tax_exemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('exemption_type'); // 'full', 'partial', 'category_specific'
            $table->string('tax_id')->nullable(); // VAT number, Tax ID, etc.
            $table->string('country')->nullable();
            $table->text('reason')->nullable();
            $table->json('exempt_categories')->nullable(); // Which tax categories are exempt
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
        });

        // Add amount_paid column to invoices if it doesn't exist
        if (!Schema::hasColumn('invoices', 'amount_paid')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->decimal('amount_paid', 10, 2)->default(0)->after('total');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_exemptions');
        Schema::dropIfExists('tax_rates');

        if (Schema::hasColumn('invoices', 'amount_paid')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropColumn('amount_paid');
            });
        }
    }
};
