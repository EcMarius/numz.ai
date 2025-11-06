<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Enhance hosting_servers table
        Schema::table('hosting_servers', function (Blueprint $table) {
            $table->string('location')->nullable()->after('ip_address'); // US-East, EU-West, etc.
            $table->string('datacenter')->nullable()->after('location');
            $table->json('server_specs')->nullable()->after('type'); // CPU, RAM, Storage specs
            $table->decimal('monthly_cost', 10, 2)->nullable()->after('server_specs');
            $table->integer('cpu_cores')->nullable()->after('monthly_cost');
            $table->integer('ram_mb')->nullable()->after('cpu_cores');
            $table->bigInteger('disk_gb')->nullable()->after('ram_mb');
            $table->bigInteger('bandwidth_gb')->nullable()->after('disk_gb');
            $table->json('monitoring_config')->nullable()->after('bandwidth_gb');
            $table->timestamp('last_monitored_at')->nullable()->after('monitoring_config');
            $table->enum('health_status', ['healthy', 'warning', 'critical', 'offline'])->default('healthy')->after('last_monitored_at');

            $table->index('location');
            $table->index('health_status');
        });

        // Server monitoring metrics
        Schema::create('server_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hosting_server_id')->constrained()->onDelete('cascade');
            $table->timestamp('recorded_at');
            $table->decimal('cpu_usage', 5, 2); // percentage
            $table->decimal('memory_usage', 5, 2); // percentage
            $table->bigInteger('disk_used'); // in bytes
            $table->bigInteger('disk_total'); // in bytes
            $table->bigInteger('bandwidth_used'); // in bytes
            $table->integer('active_connections')->default(0);
            $table->decimal('load_average', 5, 2)->nullable();
            $table->integer('response_time')->nullable(); // in ms
            $table->json('additional_metrics')->nullable();
            $table->timestamps();

            $table->index(['hosting_server_id', 'recorded_at']);
        });

        // Server groups for organization
        Schema::create('server_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Server group assignments
        Schema::create('server_group_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_group_id')->constrained()->onDelete('cascade');
            $table->foreignId('hosting_server_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['server_group_id', 'hosting_server_id']);
        });

        // VPS plans
        Schema::create('vps_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->integer('cpu_cores');
            $table->integer('ram_mb');
            $table->bigInteger('disk_gb');
            $table->bigInteger('bandwidth_gb')->nullable(); // null for unlimited
            $table->string('virtualization_type'); // KVM, OpenVZ, VMware
            $table->decimal('monthly_price', 10, 2);
            $table->decimal('yearly_price', 10, 2)->nullable();
            $table->decimal('setup_fee', 10, 2)->default(0);
            $table->boolean('ipv6_included')->default(false);
            $table->integer('additional_ips')->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        // Cloud server plans
        Schema::create('cloud_server_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('provider'); // aws, gcp, azure, digitalocean, vultr
            $table->string('instance_type'); // t2.micro, n1-standard-1, etc.
            $table->string('region');
            $table->integer('cpu_cores');
            $table->integer('ram_mb');
            $table->bigInteger('disk_gb');
            $table->string('disk_type')->default('ssd'); // ssd, nvme, hdd
            $table->decimal('hourly_price', 10, 4);
            $table->decimal('monthly_price', 10, 2);
            $table->boolean('auto_scaling')->default(false);
            $table->boolean('load_balancing')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Kubernetes clusters
        Schema::create('kubernetes_clusters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('cluster_id')->unique();
            $table->string('version');
            $table->string('provider'); // managed or self-hosted
            $table->string('region')->nullable();
            $table->enum('status', ['creating', 'active', 'upgrading', 'deleting', 'failed'])->default('creating');
            $table->integer('node_count')->default(3);
            $table->string('node_type')->nullable();
            $table->text('kubeconfig')->nullable();
            $table->string('api_endpoint')->nullable();
            $table->decimal('monthly_cost', 10, 2)->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
        });

        // Container deployments
        Schema::create('container_deployments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('kubernetes_cluster_id')->nullable()->constrained()->onDelete('set null');
            $table->string('name');
            $table->string('image');
            $table->string('tag')->default('latest');
            $table->integer('replicas')->default(1);
            $table->json('environment_variables')->nullable();
            $table->json('ports')->nullable();
            $table->json('volumes')->nullable();
            $table->integer('cpu_limit')->nullable(); // in millicores
            $table->integer('memory_limit')->nullable(); // in MB
            $table->enum('status', ['pending', 'running', 'stopped', 'failed'])->default('pending');
            $table->timestamps();

            $table->index('user_id');
        });

        // SSL certificates
        Schema::create('ssl_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hosting_service_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('domain');
            $table->enum('type', ['lets_encrypt', 'sectigo', 'digicert', 'custom'])->default('lets_encrypt');
            $table->enum('status', ['pending', 'active', 'expired', 'failed'])->default('pending');
            $table->text('certificate')->nullable();
            $table->text('private_key')->nullable();
            $table->text('ca_bundle')->nullable();
            $table->date('issued_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->boolean('auto_renew')->default(true);
            $table->text('validation_method')->nullable(); // http, dns, email
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('expires_at');
        });

        // DNS zones
        Schema::create('dns_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('domain')->unique();
            $table->string('type')->default('master'); // master, slave
            $table->integer('serial')->default(1);
            $table->integer('refresh')->default(3600);
            $table->integer('retry')->default(1800);
            $table->integer('expire')->default(604800);
            $table->integer('ttl')->default(86400);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('domain');
        });

        // DNS records
        Schema::create('dns_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dns_zone_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->enum('type', ['A', 'AAAA', 'CNAME', 'MX', 'TXT', 'NS', 'SRV', 'CAA']);
            $table->text('content');
            $table->integer('ttl')->default(3600);
            $table->integer('priority')->nullable(); // for MX, SRV records
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['dns_zone_id', 'type']);
        });

        // Backups
        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hosting_service_id')->constrained()->onDelete('cascade');
            $table->string('backup_type'); // full, incremental, differential
            $table->enum('status', ['pending', 'in_progress', 'completed', 'failed'])->default('pending');
            $table->bigInteger('size_bytes')->nullable();
            $table->string('storage_path')->nullable();
            $table->string('storage_provider')->default('local'); // local, s3, backblaze
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->text('error_message')->nullable();
            $table->boolean('auto_generated')->default(false);
            $table->date('expires_at')->nullable();
            $table->timestamps();

            $table->index(['hosting_service_id', 'status']);
            $table->index('expires_at');
        });

        // Backup schedules
        Schema::create('backup_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hosting_service_id')->constrained()->onDelete('cascade');
            $table->enum('frequency', ['daily', 'weekly', 'monthly'])->default('daily');
            $table->time('schedule_time')->default('02:00:00');
            $table->integer('retention_days')->default(30);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->timestamps();

            $table->index('hosting_service_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_schedules');
        Schema::dropIfExists('backups');
        Schema::dropIfExists('dns_records');
        Schema::dropIfExists('dns_zones');
        Schema::dropIfExists('ssl_certificates');
        Schema::dropIfExists('container_deployments');
        Schema::dropIfExists('kubernetes_clusters');
        Schema::dropIfExists('cloud_server_plans');
        Schema::dropIfExists('vps_plans');
        Schema::dropIfExists('server_group_assignments');
        Schema::dropIfExists('server_groups');
        Schema::dropIfExists('server_metrics');

        Schema::table('hosting_servers', function (Blueprint $table) {
            $table->dropColumn([
                'location',
                'datacenter',
                'server_specs',
                'monthly_cost',
                'cpu_cores',
                'ram_mb',
                'disk_gb',
                'bandwidth_gb',
                'monitoring_config',
                'last_monitored_at',
                'health_status',
            ]);
        });
    }
};
