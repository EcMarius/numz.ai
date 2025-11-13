<?php
/**
 * OVH Module Installation Script
 *
 * Creates necessary database tables and initial configuration
 * Run this script once after installing the module
 */

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

/**
 * Create module database tables
 */
function ovh_install()
{
    try {
        // Create table for storing OVH service details
        if (!Capsule::schema()->hasTable('mod_ovh_services')) {
            Capsule::schema()->create('mod_ovh_services', function ($table) {
                $table->increments('id');
                $table->integer('service_id')->unsigned()->index();
                $table->string('ovh_service_name', 100)->index();
                $table->string('service_type', 20); // dedicated, vps, cloud
                $table->string('endpoint', 20); // ovh-eu, ovh-ca, ovh-us
                $table->string('datacenter', 20)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('additional_ips')->nullable();
                $table->string('operating_system', 50)->nullable();
                $table->string('status', 50)->nullable();
                $table->boolean('monitoring_enabled')->default(false);
                $table->boolean('backup_enabled')->default(false);
                $table->timestamp('last_sync')->nullable();
                $table->text('metadata')->nullable(); // JSON field for additional data
                $table->timestamps();

                $table->unique(['service_id', 'ovh_service_name']);
            });
        }

        // Create table for tracking OVH tasks
        if (!Capsule::schema()->hasTable('mod_ovh_tasks')) {
            Capsule::schema()->create('mod_ovh_tasks', function ($table) {
                $table->increments('id');
                $table->integer('service_id')->unsigned()->index();
                $table->string('ovh_service_name', 100);
                $table->string('ovh_task_id', 50)->index();
                $table->string('task_type', 50); // reboot, reinstall, rescue, etc.
                $table->string('status', 50); // init, todo, doing, done, error
                $table->text('description')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }

        // Create table for bandwidth usage tracking
        if (!Capsule::schema()->hasTable('mod_ovh_bandwidth')) {
            Capsule::schema()->create('mod_ovh_bandwidth', function ($table) {
                $table->increments('id');
                $table->integer('service_id')->unsigned()->index();
                $table->string('ovh_service_name', 100);
                $table->date('usage_date')->index();
                $table->bigInteger('bytes_in')->default(0);
                $table->bigInteger('bytes_out')->default(0);
                $table->bigInteger('bytes_total')->default(0);
                $table->timestamps();

                $table->unique(['service_id', 'usage_date']);
            });
        }

        // Create table for monitoring alerts
        if (!Capsule::schema()->hasTable('mod_ovh_alerts')) {
            Capsule::schema()->create('mod_ovh_alerts', function ($table) {
                $table->increments('id');
                $table->integer('service_id')->unsigned()->index();
                $table->string('ovh_service_name', 100);
                $table->string('alert_type', 50); // cpu, memory, disk, network, etc.
                $table->string('severity', 20); // info, warning, critical
                $table->text('message');
                $table->boolean('notified')->default(false);
                $table->boolean('resolved')->default(false);
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
            });
        }

        // Create table for API rate limiting
        if (!Capsule::schema()->hasTable('mod_ovh_api_calls')) {
            Capsule::schema()->create('mod_ovh_api_calls', function ($table) {
                $table->increments('id');
                $table->string('endpoint', 20)->index();
                $table->string('api_method', 255);
                $table->timestamp('called_at')->index();
                $table->integer('response_time')->nullable(); // in milliseconds
                $table->integer('http_code')->nullable();
                $table->boolean('success')->default(true);
                $table->timestamps();
            });
        }

        logActivity('OVH Module: Database tables created successfully');
        return ['status' => 'success', 'description' => 'Module installed successfully'];

    } catch (Exception $e) {
        logActivity('OVH Module Installation Error: ' . $e->getMessage());
        return ['status' => 'error', 'description' => $e->getMessage()];
    }
}

/**
 * Remove module database tables
 */
function ovh_uninstall()
{
    try {
        // Drop all module tables
        Capsule::schema()->dropIfExists('mod_ovh_services');
        Capsule::schema()->dropIfExists('mod_ovh_tasks');
        Capsule::schema()->dropIfExists('mod_ovh_bandwidth');
        Capsule::schema()->dropIfExists('mod_ovh_alerts');
        Capsule::schema()->dropIfExists('mod_ovh_api_calls');

        logActivity('OVH Module: Database tables removed successfully');
        return ['status' => 'success', 'description' => 'Module uninstalled successfully'];

    } catch (Exception $e) {
        logActivity('OVH Module Uninstallation Error: ' . $e->getMessage());
        return ['status' => 'error', 'description' => $e->getMessage()];
    }
}

/**
 * Upgrade module database schema
 */
function ovh_upgrade($vars)
{
    $currentVersion = $vars['version'] ?? '1.0.0';

    try {
        // Add upgrade logic for future versions
        // Example:
        // if (version_compare($currentVersion, '1.1.0', '<')) {
        //     // Upgrade to 1.1.0
        // }

        logActivity("OVH Module: Upgraded from version {$currentVersion}");
        return ['status' => 'success', 'description' => 'Module upgraded successfully'];

    } catch (Exception $e) {
        logActivity('OVH Module Upgrade Error: ' . $e->getMessage());
        return ['status' => 'error', 'description' => $e->getMessage()];
    }
}

/**
 * Get module output for configuration page
 */
function ovh_config()
{
    return [
        'name' => 'OVH Server Management',
        'description' => 'Complete integration with OVH API for Dedicated Servers, VPS, and Cloud',
        'version' => '1.0.0',
        'author' => 'numz.ai',
        'language' => 'english',
        'fields' => [
            'enable_auto_sync' => [
                'FriendlyName' => 'Enable Auto Sync',
                'Type' => 'yesno',
                'Description' => 'Automatically sync service details from OVH API',
                'Default' => 'yes',
            ],
            'sync_interval' => [
                'FriendlyName' => 'Sync Interval (hours)',
                'Type' => 'text',
                'Size' => '5',
                'Description' => 'How often to sync with OVH API',
                'Default' => '24',
            ],
            'enable_bandwidth_tracking' => [
                'FriendlyName' => 'Enable Bandwidth Tracking',
                'Type' => 'yesno',
                'Description' => 'Track bandwidth usage for billing',
                'Default' => 'no',
            ],
            'enable_monitoring_alerts' => [
                'FriendlyName' => 'Enable Monitoring Alerts',
                'Type' => 'yesno',
                'Description' => 'Send alerts for monitoring events',
                'Default' => 'yes',
            ],
            'alert_email' => [
                'FriendlyName' => 'Alert Email Address',
                'Type' => 'text',
                'Size' => '50',
                'Description' => 'Email address for critical alerts',
                'Default' => '',
            ],
            'enable_api_logging' => [
                'FriendlyName' => 'Enable API Call Logging',
                'Type' => 'yesno',
                'Description' => 'Log all API calls for debugging',
                'Default' => 'no',
            ],
            'rate_limit_enabled' => [
                'FriendlyName' => 'Enable Rate Limiting',
                'Type' => 'yesno',
                'Description' => 'Enforce API rate limits',
                'Default' => 'yes',
            ],
            'max_api_calls_per_hour' => [
                'FriendlyName' => 'Max API Calls Per Hour',
                'Type' => 'text',
                'Size' => '10',
                'Description' => 'Maximum API calls per hour',
                'Default' => '500',
            ],
        ],
    ];
}

/**
 * Initialize module after installation
 */
function ovh_initialize()
{
    try {
        // Set default configuration values
        $config = [
            'enable_auto_sync' => true,
            'sync_interval' => 24,
            'enable_bandwidth_tracking' => false,
            'enable_monitoring_alerts' => true,
            'enable_api_logging' => false,
            'rate_limit_enabled' => true,
            'max_api_calls_per_hour' => 500,
        ];

        // Save configuration
        foreach ($config as $key => $value) {
            Capsule::table('tbladdonmodules')->updateOrInsert(
                ['module' => 'ovh', 'setting' => $key],
                ['value' => $value]
            );
        }

        logActivity('OVH Module: Initialized with default configuration');
        return true;

    } catch (Exception $e) {
        logActivity('OVH Module Initialization Error: ' . $e->getMessage());
        return false;
    }
}

// Auto-run installation if accessed directly
if (basename($_SERVER['PHP_SELF']) === 'install.php') {
    require_once __DIR__ . '/../../../init.php';

    echo "Installing OVH Module...\n";

    $result = ovh_install();

    if ($result['status'] === 'success') {
        echo "Success: " . $result['description'] . "\n";

        echo "Initializing configuration...\n";
        if (ovh_initialize()) {
            echo "Configuration initialized successfully\n";
        }
    } else {
        echo "Error: " . $result['description'] . "\n";
    }
}
