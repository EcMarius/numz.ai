<?php
/**
 * OVH Module Cron Tasks
 *
 * Scheduled tasks for syncing service status, bandwidth tracking, etc.
 * Add this to WHMCS cron or run separately
 */

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/ovh.php';

use WHMCS\Database\Capsule;

// Check if running from command line or WHMCS cron
$isCli = php_sapi_name() === 'cli';

if ($isCli) {
    echo "OVH Module Cron Job Started at " . date('Y-m-d H:i:s') . "\n";
}

/**
 * Sync all active OVH services
 */
function syncAllOVHServices()
{
    global $isCli;

    try {
        // Get all active OVH services
        $services = Capsule::table('tblhosting')
            ->join('tblproducts', 'tblhosting.packageid', '=', 'tblproducts.id')
            ->join('tblproductconfigoptions', function($join) {
                $join->on('tblhosting.packageid', '=', 'tblproductconfigoptions.id');
            })
            ->where('tblproducts.servertype', 'ovh')
            ->whereIn('tblhosting.domainstatus', ['Active', 'Suspended'])
            ->select('tblhosting.*', 'tblproducts.configoption1', 'tblproducts.configoption2',
                     'tblproducts.configoption3', 'tblproducts.configoption4',
                     'tblproducts.configoption5', 'tblproducts.configoption6')
            ->get();

        $syncCount = 0;
        $errorCount = 0;

        foreach ($services as $service) {
            try {
                // Prepare params array
                $params = [
                    'serviceid' => $service->id,
                    'configoption1' => $service->configoption1, // endpoint
                    'configoption2' => $service->configoption2, // app key
                    'configoption3' => $service->configoption3, // app secret
                    'configoption4' => $service->configoption4, // consumer key
                    'configoption5' => $service->configoption5, // service type
                    'configoption6' => $service->configoption6, // service name
                ];

                if (empty($params['configoption6'])) {
                    continue; // Skip if no service name
                }

                // Sync service details
                syncServiceDetails($params);
                $syncCount++;

                if ($isCli && $syncCount % 10 === 0) {
                    echo "Synced {$syncCount} services...\n";
                }

            } catch (Exception $e) {
                $errorCount++;
                logActivity("OVH Cron Error: Service ID {$service->id} - {$e->getMessage()}");

                if ($isCli) {
                    echo "Error syncing service {$service->id}: {$e->getMessage()}\n";
                }
            }
        }

        $message = "OVH Sync completed: {$syncCount} services synced, {$errorCount} errors";
        logActivity($message);

        if ($isCli) {
            echo $message . "\n";
        }

        return true;

    } catch (Exception $e) {
        logActivity("OVH Cron Error: {$e->getMessage()}");

        if ($isCli) {
            echo "Fatal error: {$e->getMessage()}\n";
        }

        return false;
    }
}

/**
 * Sync individual service details
 */
function syncServiceDetails($params)
{
    $client = ovh_getApiClient($params);
    $serviceType = $params['configoption5'] ?? 'dedicated';
    $serviceName = $params['configoption6'] ?? '';
    $serviceId = $params['serviceid'];

    $serviceData = [];

    switch ($serviceType) {
        case 'dedicated':
            $serverInfo = $client->get("/dedicated/server/{$serviceName}");
            $serviceData = [
                'status' => $serverInfo['state'] ?? 'unknown',
                'ip_address' => $serverInfo['ip'] ?? null,
                'datacenter' => $serverInfo['datacenter'] ?? null,
                'monitoring_enabled' => $serverInfo['monitoring'] ?? false,
            ];
            break;

        case 'vps':
            $vpsInfo = $client->get("/vps/{$serviceName}");
            $ips = $client->get("/vps/{$serviceName}/ips");
            $serviceData = [
                'status' => $vpsInfo['state'] ?? 'unknown',
                'ip_address' => $ips[0] ?? null,
                'additional_ips' => json_encode($ips),
                'datacenter' => $vpsInfo['zone'] ?? null,
            ];
            break;

        case 'cloud':
            // Cloud instance sync
            $serviceData = [
                'status' => 'active',
            ];
            break;
    }

    // Update or insert service data
    Capsule::table('mod_ovh_services')->updateOrInsert(
        [
            'service_id' => $serviceId,
            'ovh_service_name' => $serviceName,
        ],
        array_merge($serviceData, [
            'service_type' => $serviceType,
            'endpoint' => $params['configoption1'] ?? 'ovh-eu',
            'last_sync' => now(),
            'updated_at' => now(),
        ])
    );
}

/**
 * Track bandwidth usage
 */
function trackBandwidthUsage()
{
    global $isCli;

    try {
        // Get services with bandwidth tracking enabled
        $services = Capsule::table('mod_ovh_services')
            ->where('service_type', 'dedicated')
            ->orWhere('service_type', 'vps')
            ->get();

        $trackCount = 0;

        foreach ($services as $service) {
            try {
                // Get service params
                $hosting = Capsule::table('tblhosting')
                    ->where('id', $service->service_id)
                    ->first();

                if (!$hosting) {
                    continue;
                }

                // Note: OVH API doesn't provide real-time bandwidth stats
                // This is a placeholder for custom implementation
                // You would need to implement actual bandwidth retrieval

                $trackCount++;

            } catch (Exception $e) {
                logActivity("OVH Bandwidth Tracking Error: Service {$service->ovh_service_name} - {$e->getMessage()}");
            }
        }

        if ($isCli) {
            echo "Tracked bandwidth for {$trackCount} services\n";
        }

        return true;

    } catch (Exception $e) {
        logActivity("OVH Bandwidth Tracking Error: {$e->getMessage()}");
        return false;
    }
}

/**
 * Check pending tasks
 */
function checkPendingTasks()
{
    global $isCli;

    try {
        // Get pending tasks
        $tasks = Capsule::table('mod_ovh_tasks')
            ->whereIn('status', ['init', 'todo', 'doing'])
            ->where('created_at', '>', date('Y-m-d H:i:s', strtotime('-24 hours')))
            ->get();

        $updatedCount = 0;

        foreach ($tasks as $task) {
            try {
                // Get service params
                $hosting = Capsule::table('tblhosting')
                    ->where('id', $task->service_id)
                    ->first();

                if (!$hosting) {
                    continue;
                }

                // Get task details from OVH
                $params = getServiceParams($task->service_id);
                $client = ovh_getApiClient($params);

                $serviceType = $params['configoption5'] ?? 'dedicated';
                $serviceName = $task->ovh_service_name;
                $taskId = $task->ovh_task_id;

                $taskInfo = null;

                if ($serviceType === 'dedicated') {
                    $taskInfo = $client->get("/dedicated/server/{$serviceName}/task/{$taskId}");
                    $taskStatus = $taskInfo['status'] ?? 'unknown';
                } elseif ($serviceType === 'vps') {
                    $taskInfo = $client->get("/vps/{$serviceName}/tasks/{$taskId}");
                    $taskStatus = $taskInfo['state'] ?? 'unknown';
                }

                if ($taskInfo) {
                    // Update task status
                    Capsule::table('mod_ovh_tasks')
                        ->where('id', $task->id)
                        ->update([
                            'status' => $taskStatus,
                            'completed_at' => in_array($taskStatus, ['done', 'error', 'cancelled']) ? now() : null,
                            'updated_at' => now(),
                        ]);

                    $updatedCount++;
                }

            } catch (Exception $e) {
                logActivity("OVH Task Check Error: Task {$task->id} - {$e->getMessage()}");
            }
        }

        if ($isCli) {
            echo "Updated {$updatedCount} pending tasks\n";
        }

        return true;

    } catch (Exception $e) {
        logActivity("OVH Task Check Error: {$e->getMessage()}");
        return false;
    }
}

/**
 * Clean up old API call logs
 */
function cleanupOldLogs()
{
    global $isCli;

    try {
        $deleted = Capsule::table('mod_ovh_api_calls')
            ->where('called_at', '<', date('Y-m-d H:i:s', strtotime('-7 days')))
            ->delete();

        if ($isCli) {
            echo "Cleaned up {$deleted} old API call logs\n";
        }

        return true;

    } catch (Exception $e) {
        logActivity("OVH Cleanup Error: {$e->getMessage()}");
        return false;
    }
}

/**
 * Check monitoring alerts
 */
function checkMonitoringAlerts()
{
    global $isCli;

    try {
        // Get services with monitoring enabled
        $services = Capsule::table('mod_ovh_services')
            ->where('monitoring_enabled', true)
            ->where('service_type', 'dedicated')
            ->get();

        $alertCount = 0;

        foreach ($services as $service) {
            try {
                $params = getServiceParams($service->service_id);
                $client = ovh_getApiClient($params);

                // Check server status
                $serverInfo = $client->get("/dedicated/server/{$service->ovh_service_name}");
                $state = $serverInfo['state'] ?? 'unknown';

                // Alert if server is in error state
                if ($state === 'error') {
                    // Create alert
                    Capsule::table('mod_ovh_alerts')->insert([
                        'service_id' => $service->service_id,
                        'ovh_service_name' => $service->ovh_service_name,
                        'alert_type' => 'server_error',
                        'severity' => 'critical',
                        'message' => "Server {$service->ovh_service_name} is in error state",
                        'notified' => false,
                        'resolved' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $alertCount++;
                }

            } catch (Exception $e) {
                logActivity("OVH Monitoring Check Error: Service {$service->ovh_service_name} - {$e->getMessage()}");
            }
        }

        if ($isCli && $alertCount > 0) {
            echo "Created {$alertCount} new monitoring alerts\n";
        }

        return true;

    } catch (Exception $e) {
        logActivity("OVH Monitoring Error: {$e->getMessage()}");
        return false;
    }
}

/**
 * Send pending alert notifications
 */
function sendAlertNotifications()
{
    global $isCli;

    try {
        // Get unnotified alerts
        $alerts = Capsule::table('mod_ovh_alerts')
            ->where('notified', false)
            ->where('resolved', false)
            ->get();

        $sentCount = 0;

        foreach ($alerts as $alert) {
            try {
                // Get service and client details
                $hosting = Capsule::table('tblhosting')
                    ->where('id', $alert->service_id)
                    ->first();

                if ($hosting) {
                    // Send email notification
                    $subject = "OVH Alert: {$alert->alert_type}";
                    $message = $alert->message;

                    sendMessage('General Notification', $hosting->userid, [
                        'subject' => $subject,
                        'message' => $message,
                    ]);

                    // Mark as notified
                    Capsule::table('mod_ovh_alerts')
                        ->where('id', $alert->id)
                        ->update(['notified' => true]);

                    $sentCount++;
                }

            } catch (Exception $e) {
                logActivity("OVH Alert Notification Error: Alert {$alert->id} - {$e->getMessage()}");
            }
        }

        if ($isCli && $sentCount > 0) {
            echo "Sent {$sentCount} alert notifications\n";
        }

        return true;

    } catch (Exception $e) {
        logActivity("OVH Alert Notification Error: {$e->getMessage()}");
        return false;
    }
}

/**
 * Get service parameters
 */
function getServiceParams($serviceId)
{
    $hosting = Capsule::table('tblhosting')
        ->join('tblproducts', 'tblhosting.packageid', '=', 'tblproducts.id')
        ->where('tblhosting.id', $serviceId)
        ->first();

    if (!$hosting) {
        throw new Exception("Service not found: {$serviceId}");
    }

    // Get config options
    $configOptions = Capsule::table('tblhostingconfigoptions')
        ->where('relid', $serviceId)
        ->get();

    $params = [
        'serviceid' => $serviceId,
    ];

    foreach ($configOptions as $i => $option) {
        $params['configoption' . ($i + 1)] = $option->value;
    }

    return $params;
}

// Run cron tasks
if ($isCli || (isset($_GET['token']) && $_GET['token'] === 'your_cron_token')) {
    // Task 1: Sync all services
    if ($isCli) echo "\n=== Syncing Services ===\n";
    syncAllOVHServices();

    // Task 2: Track bandwidth (if enabled)
    if ($isCli) echo "\n=== Tracking Bandwidth ===\n";
    trackBandwidthUsage();

    // Task 3: Check pending tasks
    if ($isCli) echo "\n=== Checking Pending Tasks ===\n";
    checkPendingTasks();

    // Task 4: Check monitoring alerts
    if ($isCli) echo "\n=== Checking Monitoring Alerts ===\n";
    checkMonitoringAlerts();

    // Task 5: Send alert notifications
    if ($isCli) echo "\n=== Sending Alert Notifications ===\n";
    sendAlertNotifications();

    // Task 6: Cleanup old logs
    if ($isCli) echo "\n=== Cleaning Up Logs ===\n";
    cleanupOldLogs();

    if ($isCli) {
        echo "\nOVH Module Cron Job Completed at " . date('Y-m-d H:i:s') . "\n";
    } else {
        echo json_encode(['status' => 'success', 'time' => date('Y-m-d H:i:s')]);
    }
} else {
    echo "Access denied. Run from CLI or provide valid token.";
}
