<?php
/**
 * OVH Webhook Handler
 *
 * Receives webhook notifications from OVH API
 * This allows real-time updates for server status, tasks, etc.
 */

require_once __DIR__ . '/../../../init.php';

use WHMCS\Database\Capsule;

// Get raw POST data
$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

// Log the webhook receipt
logActivity('OVH Webhook Received: ' . $rawData);

// Verify webhook signature if configured
$secret = '';
try {
    $config = require __DIR__ . '/config.php';
    $secret = $config['webhooks']['secret'] ?? '';
} catch (Exception $e) {
    // Config file doesn't exist, continue without verification
}

if (!empty($secret)) {
    $signature = $_SERVER['HTTP_X_OVH_SIGNATURE'] ?? '';
    $expectedSignature = hash_hmac('sha256', $rawData, $secret);

    if (!hash_equals($expectedSignature, $signature)) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid signature']);
        logActivity('OVH Webhook: Invalid signature');
        exit;
    }
}

// Process webhook based on event type
try {
    $eventType = $data['event'] ?? '';
    $serviceName = $data['serviceName'] ?? '';

    switch ($eventType) {
        case 'server.state.changed':
            handleServerStateChange($data);
            break;

        case 'server.task.completed':
            handleTaskCompleted($data);
            break;

        case 'server.task.failed':
            handleTaskFailed($data);
            break;

        case 'vps.state.changed':
            handleVPSStateChange($data);
            break;

        case 'vps.task.completed':
            handleTaskCompleted($data);
            break;

        case 'backup.completed':
            handleBackupCompleted($data);
            break;

        case 'backup.failed':
            handleBackupFailed($data);
            break;

        case 'monitoring.alert':
            handleMonitoringAlert($data);
            break;

        default:
            logActivity("OVH Webhook: Unknown event type: {$eventType}");
            break;
    }

    http_response_code(200);
    echo json_encode(['status' => 'processed']);

} catch (Exception $e) {
    logActivity("OVH Webhook Error: {$e->getMessage()}");
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

/**
 * Handle server state change
 */
function handleServerStateChange($data)
{
    $serviceName = $data['serviceName'] ?? '';
    $newState = $data['newState'] ?? '';
    $oldState = $data['oldState'] ?? '';

    // Find WHMCS service by service name
    $service = findServiceByName($serviceName);

    if ($service) {
        logActivity("OVH Server State Changed: Service ID {$service->id} - {$oldState} -> {$newState}", $service->userid);

        // Update service status if needed
        if ($newState === 'error') {
            // Optionally suspend the service
            localAPI('ModuleSuspend', ['accountid' => $service->id]);
        }

        // Send email notification to client
        sendClientEmail($service, 'OVH Server State Changed', "Your server {$serviceName} state changed from {$oldState} to {$newState}");
    }
}

/**
 * Handle VPS state change
 */
function handleVPSStateChange($data)
{
    handleServerStateChange($data); // Same logic as server
}

/**
 * Handle task completion
 */
function handleTaskCompleted($data)
{
    $serviceName = $data['serviceName'] ?? '';
    $taskType = $data['taskType'] ?? '';
    $taskId = $data['taskId'] ?? '';

    $service = findServiceByName($serviceName);

    if ($service) {
        logActivity("OVH Task Completed: Service ID {$service->id} - Task #{$taskId} ({$taskType})", $service->userid);

        $message = "Your {$taskType} task has completed successfully.";

        switch ($taskType) {
            case 'reinstall':
                $message = "Your server has been successfully reinstalled. You should have received the new credentials via email.";
                break;
            case 'reboot':
                $message = "Your server has been rebooted successfully.";
                break;
            case 'rescue':
                $message = "Your server is now in rescue mode. You should have received rescue credentials via email.";
                break;
        }

        sendClientEmail($service, "Task Completed: {$taskType}", $message);
    }
}

/**
 * Handle task failure
 */
function handleTaskFailed($data)
{
    $serviceName = $data['serviceName'] ?? '';
    $taskType = $data['taskType'] ?? '';
    $taskId = $data['taskId'] ?? '';
    $reason = $data['reason'] ?? 'Unknown error';

    $service = findServiceByName($serviceName);

    if ($service) {
        logActivity("OVH Task Failed: Service ID {$service->id} - Task #{$taskId} ({$taskType}) - {$reason}", $service->userid);

        $message = "Your {$taskType} task has failed. Reason: {$reason}\n\nPlease contact support if you need assistance.";

        sendClientEmail($service, "Task Failed: {$taskType}", $message);
    }
}

/**
 * Handle backup completion
 */
function handleBackupCompleted($data)
{
    $serviceName = $data['serviceName'] ?? '';
    $backupId = $data['backupId'] ?? '';

    $service = findServiceByName($serviceName);

    if ($service) {
        logActivity("OVH Backup Completed: Service ID {$service->id} - Backup #{$backupId}", $service->userid);

        sendClientEmail($service, 'Backup Completed', "Your server backup has been completed successfully. Backup ID: {$backupId}");
    }
}

/**
 * Handle backup failure
 */
function handleBackupFailed($data)
{
    $serviceName = $data['serviceName'] ?? '';
    $reason = $data['reason'] ?? 'Unknown error';

    $service = findServiceByName($serviceName);

    if ($service) {
        logActivity("OVH Backup Failed: Service ID {$service->id} - {$reason}", $service->userid);

        sendClientEmail($service, 'Backup Failed', "Your server backup has failed. Reason: {$reason}\n\nPlease check your backup configuration or contact support.");
    }
}

/**
 * Handle monitoring alert
 */
function handleMonitoringAlert($data)
{
    $serviceName = $data['serviceName'] ?? '';
    $alertType = $data['alertType'] ?? '';
    $message = $data['message'] ?? '';

    $service = findServiceByName($serviceName);

    if ($service) {
        logActivity("OVH Monitoring Alert: Service ID {$service->id} - {$alertType}: {$message}", $service->userid);

        // Send urgent notification
        sendClientEmail($service, "Server Alert: {$alertType}", "Your server {$serviceName} has triggered a monitoring alert:\n\n{$message}\n\nPlease check your server immediately.");

        // Optionally send admin notification
        sendAdminNotification("OVH Monitoring Alert", "Service {$serviceName} (ID: {$service->id}) - {$alertType}: {$message}");
    }
}

/**
 * Find WHMCS service by OVH service name
 */
function findServiceByName($serviceName)
{
    if (empty($serviceName)) {
        return null;
    }

    // Search in hosting services
    $service = Capsule::table('tblhosting')
        ->join('tblproducts', 'tblhosting.packageid', '=', 'tblproducts.id')
        ->where('tblproducts.servertype', 'ovh')
        ->where(function ($query) use ($serviceName) {
            $query->where('tblhosting.username', $serviceName)
                  ->orWhere('tblhosting.domain', $serviceName)
                  ->orWhere('tblhosting.dedicatedip', 'LIKE', '%' . $serviceName . '%');
        })
        ->select('tblhosting.*')
        ->first();

    return $service;
}

/**
 * Send email to client
 */
function sendClientEmail($service, $subject, $message)
{
    if (!$service) {
        return false;
    }

    try {
        // Get client details
        $client = Capsule::table('tblclients')
            ->where('id', $service->userid)
            ->first();

        if (!$client) {
            return false;
        }

        // Send email using WHMCS
        sendMessage('General Notification', $service->userid, [
            'subject' => $subject,
            'message' => $message,
        ]);

        return true;
    } catch (Exception $e) {
        logActivity("Failed to send client email: {$e->getMessage()}");
        return false;
    }
}

/**
 * Send notification to admin
 */
function sendAdminNotification($subject, $message)
{
    try {
        // Get admin email
        $adminEmail = Capsule::table('tbladmins')
            ->where('roleid', 1)
            ->value('email');

        if ($adminEmail) {
            sendAdminMessage($subject, $message);
        }

        return true;
    } catch (Exception $e) {
        logActivity("Failed to send admin notification: {$e->getMessage()}");
        return false;
    }
}
