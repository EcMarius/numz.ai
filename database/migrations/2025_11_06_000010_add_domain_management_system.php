<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Domain registrars
        Schema::create('domain_registrars', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Namecheap, GoDaddy, etc.
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->boolean('is_available')->default(true);
            $table->json('configuration')->nullable(); // API endpoints, etc.
            $table->json('credentials')->nullable(); // Encrypted API keys
            $table->json('supported_tlds')->nullable(); // ['.com', '.net', etc.]
            $table->json('pricing')->nullable(); // Per-TLD pricing
            $table->json('capabilities')->nullable(); // [registration, transfer, renewal, whois_privacy, dns]
            $table->boolean('test_mode')->default(false);
            $table->timestamp('last_sync_at')->nullable();
            $table->integer('domain_count')->default(0);
            $table->timestamps();

            $table->index('slug');
            $table->index('is_enabled');
        });

        // Domains
        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('domain_registrar_id')->nullable()->constrained()->onDelete('set null');
            $table->string('domain_name')->unique(); // example.com
            $table->string('tld'); // .com, .net, .org
            $table->string('sld'); // example (second-level domain)
            $table->string('status'); // active, pending, expired, cancelled, transferred
            $table->string('registration_type'); // new, transfer, internal

            // Dates
            $table->date('registered_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->date('transferred_at')->nullable();
            $table->date('cancelled_at')->nullable();

            // Auto-renewal
            $table->boolean('auto_renew')->default(true);
            $table->integer('renewal_period')->default(1); // years

            // Pricing
            $table->decimal('registration_price', 10, 2)->default(0);
            $table->decimal('renewal_price', 10, 2)->default(0);
            $table->decimal('transfer_price', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');

            // Features
            $table->boolean('whois_privacy')->default(false);
            $table->boolean('domain_lock')->default(true);
            $table->boolean('auto_renew_whois_privacy')->default(false);

            // Nameservers
            $table->json('nameservers')->nullable(); // [ns1.example.com, ns2.example.com]

            // EPP/Auth Code
            $table->string('epp_code')->nullable();

            // WHOIS Contact Info
            $table->json('registrant_contact')->nullable();
            $table->json('admin_contact')->nullable();
            $table->json('tech_contact')->nullable();
            $table->json('billing_contact')->nullable();

            // Remote data
            $table->string('remote_id')->nullable(); // ID at registrar
            $table->json('remote_data')->nullable(); // Additional data from registrar

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('status');
            $table->index('expires_at');
            $table->index('domain_name');
        });

        // Domain renewal history
        Schema::create('domain_renewals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('years_renewed');
            $table->date('previous_expiry_date');
            $table->date('new_expiry_date');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('status'); // pending, completed, failed
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('domain_id');
            $table->index('status');
        });

        // Domain transfers
        Schema::create('domain_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('from_registrar_id')->nullable()->constrained('domain_registrars')->onDelete('set null');
            $table->foreignId('to_registrar_id')->constrained('domain_registrars')->onDelete('cascade');
            $table->string('transfer_type'); // incoming, outgoing
            $table->string('status'); // pending, in_progress, completed, failed, cancelled
            $table->string('epp_code')->nullable();
            $table->decimal('transfer_fee', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->json('transfer_data')->nullable();
            $table->timestamps();

            $table->index('domain_id');
            $table->index('status');
            $table->index('transfer_type');
        });

        // DNS zones (for domains using our nameservers)
        Schema::create('dns_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('zone_name'); // example.com
            $table->string('status')->default('active'); // active, suspended
            $table->integer('serial')->default(1); // SOA serial
            $table->integer('refresh')->default(3600);
            $table->integer('retry')->default(7200);
            $table->integer('expire')->default(1209600);
            $table->integer('ttl')->default(3600); // Default TTL
            $table->string('primary_ns'); // Primary nameserver
            $table->string('admin_email'); // SOA admin email
            $table->timestamps();

            $table->index('zone_name');
            $table->index('user_id');
        });

        // DNS records
        Schema::create('dns_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dns_zone_id')->constrained()->onDelete('cascade');
            $table->string('name'); // www, mail, @, etc.
            $table->string('type'); // A, AAAA, CNAME, MX, TXT, NS, SRV, CAA
            $table->text('content'); // IP address, hostname, text content
            $table->integer('ttl')->default(3600);
            $table->integer('priority')->nullable(); // For MX, SRV records
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('dns_zone_id');
            $table->index('type');
        });

        // WHOIS privacy orders
        Schema::create('whois_privacy_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('set null');
            $table->string('status'); // pending, active, expired, cancelled
            $table->decimal('price', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('auto_renew')->default(true);
            $table->timestamps();

            $table->index('domain_id');
            $table->index('status');
        });

        // Domain price list (dynamic pricing)
        Schema::create('domain_pricing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_registrar_id')->constrained()->onDelete('cascade');
            $table->string('tld'); // .com, .net, etc.
            $table->string('currency', 3)->default('USD');

            // Registration pricing
            $table->decimal('register_price_1y', 10, 2);
            $table->decimal('register_price_2y', 10, 2)->nullable();
            $table->decimal('register_price_3y', 10, 2)->nullable();
            $table->decimal('register_price_5y', 10, 2)->nullable();
            $table->decimal('register_price_10y', 10, 2)->nullable();

            // Renewal pricing
            $table->decimal('renew_price_1y', 10, 2);
            $table->decimal('renew_price_2y', 10, 2)->nullable();
            $table->decimal('renew_price_3y', 10, 2)->nullable();
            $table->decimal('renew_price_5y', 10, 2)->nullable();
            $table->decimal('renew_price_10y', 10, 2)->nullable();

            // Transfer pricing
            $table->decimal('transfer_price', 10, 2);

            // WHOIS privacy
            $table->decimal('whois_privacy_price', 10, 2)->nullable();

            // Premium domain handling
            $table->boolean('supports_premium')->default(false);

            $table->boolean('is_active')->default(true);
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamps();

            $table->unique(['domain_registrar_id', 'tld']);
            $table->index('tld');
        });

        // Domain availability checks (cache)
        Schema::create('domain_availability_cache', function (Blueprint $table) {
            $table->id();
            $table->string('domain_name')->unique();
            $table->string('tld');
            $table->boolean('is_available');
            $table->boolean('is_premium')->default(false);
            $table->decimal('premium_price', 10, 2)->nullable();
            $table->timestamp('checked_at');
            $table->timestamp('expires_at'); // Cache expiration
            $table->timestamps();

            $table->index('domain_name');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_availability_cache');
        Schema::dropIfExists('domain_pricing');
        Schema::dropIfExists('whois_privacy_orders');
        Schema::dropIfExists('dns_records');
        Schema::dropIfExists('dns_zones');
        Schema::dropIfExists('domain_transfers');
        Schema::dropIfExists('domain_renewals');
        Schema::dropIfExists('domains');
        Schema::dropIfExists('domain_registrars');
    }
};
