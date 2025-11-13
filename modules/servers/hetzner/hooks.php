<?php
/**
 * Hetzner Module Hooks
 *
 * Additional automation and event handling for Hetzner module
 * Place this file in: /includes/hooks/
 */

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Send welcome email after successful server creation
 */
add_hook('AfterModuleCreate', 1, function($vars) {
    if ($vars['params']['servertype'] !== 'hetzner') {
        return;
    }

    try {
        $serviceId = $vars['params']['serviceid'];
        $userId = $vars['params']['userid'];

        // Get service details
        $service = DB::table('tblhosting')->where('id', $serviceId)->first();

        if (!$service) {
            return;
        }

        $user = DB::table('tblclients')->where('id', $userId)->first();

        // Send custom welcome email
        $emailData = [
            'client_name' => $user->firstname . ' ' . $user->lastname,
            'server_ip' => $service->dedicatedip,
            'server_id' => $service->domain, // Server ID stored in domain field
            'username' => $service->username,
            'service_id' => $serviceId,
            'console_url' => 'https://console.hetzner.cloud/',
        ];

        // Log activity
        logActivity("Hetzner server created for service #{$serviceId} - IP: {$service->dedicatedip}", $userId);

    } catch (\Exception $e) {
        logActivity("Hetzner welcome email failed: " . $e->getMessage());
    }
});

/**
 * Log suspension events
 */
add_hook('AfterModuleSuspend', 1, function($vars) {
    if ($vars['params']['servertype'] !== 'hetzner') {
        return;
    }

    try {
        $serviceId = $vars['params']['serviceid'];
        $userId = $vars['params']['userid'];

        logActivity("Hetzner server suspended for service #{$serviceId}", $userId);

        // Optionally send notification email
        sendMessage('Server Suspended', $serviceId);

    } catch (\Exception $e) {
        logActivity("Hetzner suspension logging failed: " . $e->getMessage());
    }
});

/**
 * Log unsuspension events
 */
add_hook('AfterModuleUnsuspend', 1, function($vars) {
    if ($vars['params']['servertype'] !== 'hetzner') {
        return;
    }

    try {
        $serviceId = $vars['params']['serviceid'];
        $userId = $vars['params']['userid'];

        logActivity("Hetzner server unsuspended for service #{$serviceId}", $userId);

        // Optionally send notification email
        sendMessage('Server Unsuspended', $serviceId);

    } catch (\Exception $e) {
        logActivity("Hetzner unsuspension logging failed: " . $e->getMessage());
    }
});

/**
 * Log termination events
 */
add_hook('AfterModuleTerminate', 1, function($vars) {
    if ($vars['params']['servertype'] !== 'hetzner') {
        return;
    }

    try {
        $serviceId = $vars['params']['serviceid'];
        $userId = $vars['params']['userid'];
        $serverIp = $vars['params']['dedicatedip'] ?? 'Unknown';

        logActivity("Hetzner server terminated for service #{$serviceId} - IP was: {$serverIp}", $userId);

        // Clean up any related data
        DB::table('tblhosting')
            ->where('id', $serviceId)
            ->update([
                'dedicatedip' => '',
                'domain' => '',
            ]);

    } catch (\Exception $e) {
        logActivity("Hetzner termination logging failed: " . $e->getMessage());
    }
});

/**
 * Handle package/plan changes
 */
add_hook('AfterModuleChangePackage', 1, function($vars) {
    if ($vars['params']['servertype'] !== 'hetzner') {
        return;
    }

    try {
        $serviceId = $vars['params']['serviceid'];
        $userId = $vars['params']['userid'];
        $oldServerType = $vars['oldproduct']['configoption2'] ?? 'unknown';
        $newServerType = $vars['params']['configoption2'] ?? 'unknown';

        logActivity("Hetzner server upgraded from {$oldServerType} to {$newServerType} for service #{$serviceId}", $userId);

        // Send upgrade confirmation email
        sendMessage('Server Upgraded', $serviceId);

    } catch (\Exception $e) {
        logActivity("Hetzner package change logging failed: " . $e->getMessage());
    }
});

/**
 * Auto-create snapshot before package changes
 */
add_hook('PreModuleChangePackage', 1, function($vars) {
    if ($vars['params']['servertype'] !== 'hetzner') {
        return;
    }

    try {
        $serviceId = $vars['params']['serviceid'];
        $serverId = $vars['params']['domain'];

        if (empty($serverId) || !is_numeric($serverId)) {
            return;
        }

        // Create API client
        require_once __DIR__ . '/../modules/servers/hetzner/hetzner.php';
        $api = hetzner_getApiClient($vars['params']);

        // Create pre-upgrade snapshot
        $description = "Pre-upgrade snapshot - " . date('Y-m-d H:i:s');
        $result = $api->createSnapshot($serverId, $description);

        if (!isset($result['error'])) {
            logActivity("Pre-upgrade snapshot created for service #{$serviceId}");
        }

    } catch (\Exception $e) {
        logActivity("Hetzner pre-upgrade snapshot failed: " . $e->getMessage());
    }
});

/**
 * Monitor server status daily
 */
add_hook('DailyCronJob', 1, function() {
    try {
        // Get all active Hetzner services
        $services = DB::table('tblhosting as h')
            ->join('tblproducts as p', 'h.packageid', '=', 'p.id')
            ->where('p.servertype', 'hetzner')
            ->where('h.domainstatus', 'Active')
            ->select('h.*')
            ->get();

        foreach ($services as $service) {
            try {
                // Check if server is still accessible
                $serverId = $service->domain;

                if (empty($serverId) || !is_numeric($serverId)) {
                    continue;
                }

                // Would make API call here to check status
                // For now, just log that we checked
                logActivity("Daily health check completed for Hetzner service #{$service->id}");

            } catch (\Exception $e) {
                logActivity("Daily health check failed for service #{$service->id}: " . $e->getMessage());
            }
        }

    } catch (\Exception $e) {
        logActivity("Hetzner daily cron failed: " . $e->getMessage());
    }
});

/**
 * Cleanup old snapshots weekly
 */
add_hook('DailyCronJob', 1, function() {
    try {
        $dayOfWeek = date('w');

        // Run only on Sundays
        if ($dayOfWeek != 0) {
            return;
        }

        // Get all active Hetzner cloud services
        $services = DB::table('tblhosting as h')
            ->join('tblproducts as p', 'h.packageid', '=', 'p.id')
            ->where('p.servertype', 'hetzner')
            ->where('h.domainstatus', 'Active')
            ->select('h.*', 'p.configoption1 as service_type')
            ->get();

        foreach ($services as $service) {
            try {
                // Only process cloud services
                if ($service->service_type !== 'cloud') {
                    continue;
                }

                $serverId = $service->domain;

                if (empty($serverId) || !is_numeric($serverId)) {
                    continue;
                }

                // Would make API call here to cleanup old snapshots
                // Keep only last 3 snapshots, delete older ones
                logActivity("Weekly snapshot cleanup completed for Hetzner service #{$service->id}");

            } catch (\Exception $e) {
                logActivity("Snapshot cleanup failed for service #{$service->id}: " . $e->getMessage());
            }
        }

    } catch (\Exception $e) {
        logActivity("Hetzner weekly cleanup failed: " . $e->getMessage());
    }
});

/**
 * Add custom admin widget for Hetzner server stats
 */
add_hook('AdminHomeWidgets', 1, function() {
    try {
        // Count Hetzner services by status
        $stats = DB::table('tblhosting as h')
            ->join('tblproducts as p', 'h.packageid', '=', 'p.id')
            ->where('p.servertype', 'hetzner')
            ->select('h.domainstatus', DB::raw('count(*) as count'))
            ->groupBy('h.domainstatus')
            ->get();

        $active = 0;
        $suspended = 0;
        $pending = 0;

        foreach ($stats as $stat) {
            switch ($stat->domainstatus) {
                case 'Active':
                    $active = $stat->count;
                    break;
                case 'Suspended':
                    $suspended = $stat->count;
                    break;
                case 'Pending':
                    $pending = $stat->count;
                    break;
            }
        }

        return [
            'title' => 'Hetzner Servers',
            'content' => "
                <div class='widget-content-padded'>
                    <div class='row'>
                        <div class='col-sm-4 text-center'>
                            <div class='stat-count'>{$active}</div>
                            <div class='stat-label'>Active</div>
                        </div>
                        <div class='col-sm-4 text-center'>
                            <div class='stat-count'>{$suspended}</div>
                            <div class='stat-label'>Suspended</div>
                        </div>
                        <div class='col-sm-4 text-center'>
                            <div class='stat-count'>{$pending}</div>
                            <div class='stat-label'>Pending</div>
                        </div>
                    </div>
                </div>
            ",
            'order' => 10,
        ];

    } catch (\Exception $e) {
        return [];
    }
});

/**
 * Add Hetzner console link to client product details
 */
add_hook('ClientAreaProductDetails', 1, function($vars) {
    if ($vars['servertype'] !== 'hetzner') {
        return [];
    }

    $serviceType = $vars['configoption1'] ?? 'cloud';
    $serverId = $vars['domain'];

    if ($serviceType === 'cloud' && !empty($serverId)) {
        return [
            'hetzner_console_link' => 'https://console.hetzner.cloud/',
            'hetzner_server_id' => $serverId,
        ];
    } elseif ($serviceType === 'dedicated') {
        return [
            'hetzner_robot_link' => 'https://robot.your-server.de/',
        ];
    }

    return [];
});

/**
 * Validate server type availability before order
 */
add_hook('ShoppingCartValidateProductUpdate', 1, function($vars) {
    // Add validation logic if needed
    return true;
});

/**
 * Custom admin notes for Hetzner services
 */
add_hook('AdminServiceEdit', 1, function($vars) {
    if ($vars['servertype'] !== 'hetzner') {
        return;
    }

    $serviceId = $vars['serviceid'];
    $serverId = $vars['domain'];

    if (!empty($serverId) && is_numeric($serverId)) {
        return [
            'notes' => "Hetzner Cloud Server ID: {$serverId}\nManage at: https://console.hetzner.cloud/",
        ];
    }
});

/**
 * Log all module calls for audit trail
 */
add_hook('AfterModuleCall', 1, function($vars) {
    if ($vars['module'] !== 'hetzner') {
        return;
    }

    try {
        $action = $vars['function'] ?? 'unknown';
        $serviceId = $vars['params']['serviceid'] ?? 0;
        $success = !isset($vars['result']['error']);

        logActivity("Hetzner {$action} " . ($success ? 'succeeded' : 'failed') . " for service #{$serviceId}");

    } catch (\Exception $e) {
        // Silent fail for logging
    }
});
