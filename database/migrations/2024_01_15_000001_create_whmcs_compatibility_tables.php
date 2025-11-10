<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * WHMCS Database Compatibility Layer
     * Creates views and aliases for WHMCS-compatible table access
     */
    public function up(): void
    {
        // tblclients - Map to users table
        if (!Schema::hasTable('tblclients')) {
            DB::statement('CREATE VIEW tblclients AS SELECT
                id,
                id as userid,
                first_name as firstname,
                last_name as lastname,
                company as companyname,
                email,
                address1,
                address2,
                city,
                state,
                postcode,
                country,
                phone as phonenumber,
                created_at as datecreated,
                status,
                currency,
                credit as creditbalance
            FROM users');
        }

        // tblhosting - Map to services table (if not exists, create actual table)
        if (!Schema::hasTable('tblhosting')) {
            Schema::create('tblhosting', function (Blueprint $table) {
                $table->id();
                $table->foreignId('userid')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('orderid')->nullable()->constrained('orders')->nullOnDelete();
                $table->foreignId('packageid')->nullable()->constrained('products')->nullOnDelete();
                $table->foreignId('serverid')->nullable()->constrained('servers')->nullOnDelete();
                $table->string('domain')->nullable();
                $table->string('username')->nullable();
                $table->text('password')->nullable();
                $table->enum('domainstatus', ['pending', 'active', 'suspended', 'terminated', 'cancelled', 'fraud'])->default('pending');
                $table->date('regdate');
                $table->date('nextduedate')->nullable();
                $table->date('nextinvoicedate')->nullable();
                $table->date('termination_date')->nullable();
                $table->decimal('amount', 10, 2)->default(0);
                $table->enum('billingcycle', ['Free', 'One Time', 'Monthly', 'Quarterly', 'Semi-Annually', 'Annually', 'Biennially', 'Triennially'])->default('Monthly');
                $table->text('notes')->nullable();
                $table->text('subscriptionid')->nullable();
                $table->string('promoid')->nullable();
                $table->string('suspendreason')->nullable();
                $table->boolean('overideautosuspend')->default(false);
                $table->boolean('overidesuspenduntil')->default(false());
                $table->string('dedicatedip')->nullable();
                $table->string('assignedips')->nullable();
                $table->string('ns1')->nullable();
                $table->string('ns2')->nullable();
                $table->timestamps();

                $table->index('userid');
                $table->index('packageid');
                $table->index('serverid');
                $table->index('domainstatus');
            });
        }

        // tblproducts - Map to products table
        if (!Schema::hasTable('tblproducts')) {
            DB::statement('CREATE VIEW tblproducts AS SELECT
                id,
                id as pid,
                product_group_id as gid,
                name,
                description,
                is_hidden as hidden,
                display_order as order,
                created_at as created,
                updated_at as updated,
                server_module as servertype,
                welcome_email,
                stock_control as stockcontrol,
                stock_quantity as qty
            FROM products');
        }

        // tblinvoices - Map to invoices table
        if (!Schema::hasTable('tblinvoices')) {
            DB::statement('CREATE VIEW tblinvoices AS SELECT
                id,
                user_id as userid,
                invoice_number as invoicenum,
                created_at as date,
                due_date as duedate,
                paid_at as datepaid,
                subtotal,
                tax,
                tax2 as tax2,
                total,
                status,
                payment_method as paymentmethod,
                notes
            FROM invoices');
        }

        // tbldomains - Domain registration compatibility
        if (!Schema::hasTable('tbldomains')) {
            Schema::create('tbldomains', function (Blueprint $table) {
                $table->id();
                $table->foreignId('userid')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('orderid')->nullable()->constrained('orders')->nullOnDelete();
                $table->string('domain');
                $table->string('registrar')->nullable();
                $table->integer('registrationperiod')->default(1);
                $table->date('registrationdate')->nullable();
                $table->date('expirydate')->nullable();
                $table->date('nextduedate')->nullable();
                $table->enum('status', ['Pending', 'Pending Transfer', 'Active', 'Expired', 'Transferred Away', 'Cancelled', 'Fraud'])->default('Pending');
                $table->string('subscriptionid')->nullable();
                $table->boolean('is_premium_domain')->default(false);
                $table->boolean('additionaldomain')->default(false);
                $table->string('idprotection')->default('off');
                $table->string('donotrenew')->default('');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index('userid');
                $table->index('domain');
                $table->index('registrar');
                $table->index('status');
            });
        }

        // tbltickets - Support ticket compatibility
        if (!Schema::hasTable('tbltickets')) {
            DB::statement('CREATE VIEW tbltickets AS SELECT
                id,
                id as tid,
                user_id as userid,
                department_id as did,
                title as subject,
                status,
                priority as urgency,
                created_at as date,
                updated_at as lastreply
            FROM tickets');
        }

        // tblservers - Server details for provisioning
        if (!Schema::hasTable('tblservers')) {
            Schema::create('tblservers', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('ipaddress')->nullable();
                $table->string('hostname')->nullable();
                $table->integer('maxaccounts')->default(0);
                $table->string('type')->nullable(); // server module type
                $table->string('username')->nullable();
                $table->text('password')->nullable();
                $table->text('accesshash')->nullable();
                $table->boolean('secure')->default(true);
                $table->integer('port')->nullable();
                $table->text('statusaddress')->nullable();
                $table->text('nameserver1')->nullable();
                $table->text('nameserver2')->nullable();
                $table->text('nameserver3')->nullable();
                $table->text('nameserver4')->nullable();
                $table->text('nameserver5')->nullable();
                $table->boolean('active')->default(true);
                $table->boolean('disabled')->default(false);
                $table->timestamps();

                $table->index('type');
                $table->index('active');
            });
        }

        // tblconfiguration - System configuration (key-value store)
        if (!Schema::hasTable('tblconfiguration')) {
            Schema::create('tblconfiguration', function (Blueprint $table) {
                $table->id();
                $table->string('setting')->unique();
                $table->text('value')->nullable();
                $table->timestamps();

                $table->index('setting');
            });
        }

        // tbladdonmodules - Addon module configuration
        if (!Schema::hasTable('tbladdonmodules')) {
            Schema::create('tbladdonmodules', function (Blueprint $table) {
                $table->id();
                $table->string('module');
                $table->string('setting');
                $table->text('value')->nullable();
                $table->timestamps();

                $table->unique(['module', 'setting']);
                $table->index('module');
            });
        }

        // tblcustomfields - Product/service custom fields
        if (!Schema::hasTable('tblcustomfields')) {
            Schema::create('tblcustomfields', function (Blueprint $table) {
                $table->id();
                $table->enum('type', ['client', 'product', 'domain'])->default('product');
                $table->integer('relid')->default(0); // product/group ID
                $table->string('fieldname');
                $table->string('fieldtype')->default('text');
                $table->text('description')->nullable();
                $table->text('fieldoptions')->nullable();
                $table->string('regexpr')->nullable();
                $table->boolean('adminonly')->default(false);
                $table->boolean('required')->default(false);
                $table->boolean('showorder')->default(true);
                $table->boolean('showinvoice')->default(false);
                $table->integer('sortorder')->default(0);
                $table->timestamps();

                $table->index(['type', 'relid']);
            });
        }

        // tblcustomfieldsvalues - Custom field values
        if (!Schema::hasTable('tblcustomfieldsvalues')) {
            Schema::create('tblcustomfieldsvalues', function (Blueprint $table) {
                $table->id();
                $table->foreignId('fieldid')->constrained('tblcustomfields')->cascadeOnDelete();
                $table->integer('relid'); // service/client/domain ID
                $table->text('value')->nullable();
                $table->timestamps();

                $table->index(['fieldid', 'relid']);
            });
        }

        // tblproductconfigoptions - Configurable options for products
        if (!Schema::hasTable('tblproductconfigoptions')) {
            Schema::create('tblproductconfigoptions', function (Blueprint $table) {
                $table->id();
                $table->integer('gid'); // config option group ID
                $table->string('optionname');
                $table->enum('optiontype', ['1', '2', '3', '4'])->default('1'); // 1=dropdown, 2=radio, 3=yesno, 4=quantity
                $table->integer('qtyminimum')->default(0);
                $table->integer('qtymaximum')->default(0);
                $table->integer('order')->default(0);
                $table->boolean('hidden')->default(false);
                $table->timestamps();

                $table->index('gid');
            });
        }

        // tblhostingconfigoptions - Config option values for services
        if (!Schema::hasTable('tblhostingconfigoptions')) {
            Schema::create('tblhostingconfigoptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('relid')->constrained('tblhosting')->cascadeOnDelete(); // service ID
                $table->integer('configid'); // config option ID
                $table->integer('optionid'); // sub-option ID
                $table->integer('qty')->default(0);
                $table->timestamps();

                $table->index('relid');
            });
        }

        // tblmodulelog - Module execution log
        if (!Schema::hasTable('tblmodulelog')) {
            Schema::create('tblmodulelog', function (Blueprint $table) {
                $table->id();
                $table->dateTime('date');
                $table->string('module');
                $table->string('action');
                $table->text('request')->nullable();
                $table->text('response')->nullable();
                $table->text('arrdata')->nullable();
                $table->timestamps();

                $table->index(['module', 'date']);
            });
        }

        // tblactivitylog - System activity log
        if (!Schema::hasTable('tblactivitylog')) {
            Schema::create('tblactivitylog', function (Blueprint $table) {
                $table->id();
                $table->foreignId('userid')->nullable()->constrained('users')->nullOnDelete();
                $table->string('description');
                $table->ipAddress('ipaddr')->nullable();
                $table->dateTime('date');
                $table->timestamps();

                $table->index(['userid', 'date']);
                $table->index('date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop views
        DB::statement('DROP VIEW IF EXISTS tblclients');
        DB::statement('DROP VIEW IF EXISTS tblproducts');
        DB::statement('DROP VIEW IF EXISTS tblinvoices');
        DB::statement('DROP VIEW IF EXISTS tbltickets');

        // Drop tables
        Schema::dropIfExists('tblhostingconfigoptions');
        Schema::dropIfExists('tblproductconfigoptions');
        Schema::dropIfExists('tblcustomfieldsvalues');
        Schema::dropIfExists('tblcustomfields');
        Schema::dropIfExists('tbladdonmodules');
        Schema::dropIfExists('tblconfiguration');
        Schema::dropIfExists('tblservers');
        Schema::dropIfExists('tbldomains');
        Schema::dropIfExists('tblhosting');
        Schema::dropIfExists('tblmodulelog');
        Schema::dropIfExists('tblactivitylog');
    }
};
