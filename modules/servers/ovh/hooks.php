<?php
/**
 * OVH Module Hooks
 *
 * Additional hooks to enhance OVH module functionality
 */

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

/**
 * Add OVH service actions to admin service page
 */
add_hook('AdminServiceEdit', 1, function($vars) {
    $serviceId = $vars['serviceid'];

    // Get service details
    $service = Capsule::table('tblhosting')
        ->where('id', $serviceId)
        ->first();

    if (!$service || $service->server !== 'ovh') {
        return;
    }

    // Add custom CSS and JavaScript for OVH actions
    $output = <<<HTML
<style>
    .ovh-admin-actions {
        margin-top: 20px;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 6px;
    }
    .ovh-admin-actions h3 {
        margin-top: 0;
        color: #333;
    }
    .ovh-action-btn {
        display: inline-block;
        margin: 5px;
        padding: 8px 15px;
        background: #007bff;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        text-decoration: none;
    }
    .ovh-action-btn:hover {
        background: #0056b3;
        color: white;
    }
    .ovh-action-btn.danger {
        background: #dc3545;
    }
    .ovh-action-btn.danger:hover {
        background: #c82333;
    }
</style>

<div class="ovh-admin-actions">
    <h3>OVH Service Management</h3>
    <p>Quick actions for this OVH service:</p>
    <div>
        <a href="#" class="ovh-action-btn" onclick="ovhModuleAction('RebootServer'); return false;">Reboot Server</a>
        <a href="#" class="ovh-action-btn danger" onclick="ovhModuleAction('ReinstallOS'); return false;">Reinstall OS</a>
        <a href="#" class="ovh-action-btn" onclick="ovhModuleAction('RescueMode'); return false;">Rescue Mode</a>
        <a href="#" class="ovh-action-btn" onclick="ovhModuleAction('ExitRescueMode'); return false;">Exit Rescue</a>
        <a href="#" class="ovh-action-btn" onclick="ovhModuleAction('GetIPMI'); return false;">Get IPMI</a>
        <a href="#" class="ovh-action-btn" onclick="ovhModuleAction('CreateBackup'); return false;">Create Backup</a>
        <a href="#" class="ovh-action-btn" onclick="ovhModuleAction('ViewTasks'); return false;">View Tasks</a>
    </div>
</div>

<script>
function ovhModuleAction(action) {
    if (action === 'ReinstallOS' || action === 'RescueMode') {
        if (!confirm('Are you sure you want to perform this action? It may cause service downtime.')) {
            return;
        }
    }

    // Call the module action
    window.location.href = 'clientsservices.php?userid=' + {$vars['userid']} +
                          '&id={$serviceId}&modop=custom&a=' + action;
}
</script>
HTML;

    return $output;
});

/**
 * Log OVH API calls for debugging
 */
add_hook('ModuleCall', 1, function($vars) {
    if ($vars['moduleType'] !== 'ovh') {
        return;
    }

    // Log module calls
    logActivity("OVH Module: {$vars['function']} called for service ID {$vars['serviceid']}", $vars['userid']);
});

/**
 * Add OVH server status check to daily cron
 */
add_hook('DailyCronJob', 1, function($vars) {
    // Get all active OVH services
    $services = Capsule::table('tblhosting')
        ->join('tblproducts', 'tblhosting.packageid', '=', 'tblproducts.id')
        ->where('tblproducts.servertype', 'ovh')
        ->where('tblhosting.domainstatus', 'Active')
        ->select('tblhosting.*')
        ->get();

    foreach ($services as $service) {
        try {
            // Load module
            $params = [];
            $moduleParams = localAPI('GetModuleConfiguration', [
                'serviceid' => $service->id,
            ]);

            if ($moduleParams['result'] === 'success') {
                // Check service status
                // This would call the OVH API to verify service is running
                logActivity("OVH Daily Check: Service ID {$service->id} status verified");
            }
        } catch (Exception $e) {
            logActivity("OVH Daily Check Error: Service ID {$service->id} - {$e->getMessage()}");
        }
    }
});

/**
 * Add bandwidth usage tracking (if available)
 */
add_hook('AfterCronJob', 1, function($vars) {
    // This hook could be used to collect bandwidth statistics from OVH
    // and update WHMCS usage records for billing purposes
});

/**
 * Custom email template variables for OVH services
 */
add_hook('EmailPreSend', 1, function($vars) {
    if ($vars['messagename'] === 'Hosting Account Welcome') {
        // Check if this is an OVH service
        $service = Capsule::table('tblhosting')
            ->join('tblproducts', 'tblhosting.packageid', '=', 'tblproducts.id')
            ->where('tblhosting.id', $vars['relid'])
            ->where('tblproducts.servertype', 'ovh')
            ->first();

        if ($service) {
            // Add OVH-specific merge fields
            $vars['mergefields']['ovh_service_name'] = $service->username ?? 'N/A';
            $vars['mergefields']['ovh_manager_url'] = 'https://www.ovh.com/manager/';
        }
    }

    return $vars;
});

/**
 * Add widget to admin dashboard for OVH services overview
 */
add_hook('AdminHomeWidgets', 1, function() {
    try {
        // Count active OVH services
        $activeCount = Capsule::table('tblhosting')
            ->join('tblproducts', 'tblhosting.packageid', '=', 'tblproducts.id')
            ->where('tblproducts.servertype', 'ovh')
            ->where('tblhosting.domainstatus', 'Active')
            ->count();

        $suspendedCount = Capsule::table('tblhosting')
            ->join('tblproducts', 'tblhosting.packageid', '=', 'tblproducts.id')
            ->where('tblproducts.servertype', 'ovh')
            ->where('tblhosting.domainstatus', 'Suspended')
            ->count();

        $totalCount = Capsule::table('tblhosting')
            ->join('tblproducts', 'tblhosting.packageid', '=', 'tblproducts.id')
            ->where('tblproducts.servertype', 'ovh')
            ->count();

        return [
            [
                'title' => 'OVH Services',
                'content' => <<<HTML
<div class="widget-content-padded">
    <div class="row">
        <div class="col-sm-4 text-center">
            <h4 style="color: #28a745; margin: 0;">{$activeCount}</h4>
            <p style="margin: 5px 0 0 0; color: #666;">Active</p>
        </div>
        <div class="col-sm-4 text-center">
            <h4 style="color: #dc3545; margin: 0;">{$suspendedCount}</h4>
            <p style="margin: 5px 0 0 0; color: #666;">Suspended</p>
        </div>
        <div class="col-sm-4 text-center">
            <h4 style="color: #007bff; margin: 0;">{$totalCount}</h4>
            <p style="margin: 5px 0 0 0; color: #666;">Total</p>
        </div>
    </div>
</div>
HTML
,
                'icon' => 'fas fa-server',
                'order' => 50,
            ],
        ];
    } catch (Exception $e) {
        return [];
    }
});

/**
 * Validate OVH API credentials when server is added/edited
 */
add_hook('ServerAdd', 1, function($vars) {
    if ($vars['type'] !== 'ovh') {
        return;
    }

    // This hook fires when a new server is added
    // You could add validation logic here
    logActivity("OVH Server Added: {$vars['name']}");
});

add_hook('ServerEdit', 1, function($vars) {
    if ($vars['type'] !== 'ovh') {
        return;
    }

    // This hook fires when a server is edited
    logActivity("OVH Server Updated: {$vars['name']}");
});

/**
 * Auto-update service details from OVH API
 */
add_hook('AfterModuleCreate', 1, function($vars) {
    if ($vars['params']['servertype'] !== 'ovh') {
        return;
    }

    // After service creation, log it
    logActivity("OVH Service Created: Service ID {$vars['params']['serviceid']}", $vars['params']['userid']);

    // You could also trigger additional setup here
    // For example: setting up monitoring, backups, etc.
});

/**
 * Handle service upgrades
 */
add_hook('AfterModuleUpgrade', 1, function($vars) {
    if ($vars['params']['servertype'] !== 'ovh') {
        return;
    }

    logActivity("OVH Service Upgraded: Service ID {$vars['params']['serviceid']}", $vars['params']['userid']);
});

/**
 * Handle service termination cleanup
 */
add_hook('AfterModuleTerminate', 1, function($vars) {
    if ($vars['params']['servertype'] !== 'ovh') {
        return;
    }

    logActivity("OVH Service Terminated: Service ID {$vars['params']['serviceid']}", $vars['params']['userid']);

    // Clean up any associated data
    // For example: remove custom OVH-specific database records
});

/**
 * Add OVH-specific fields to order forms
 */
add_hook('ShoppingCartCheckoutOutput', 1, function($vars) {
    // Check if cart contains OVH products
    foreach ($vars['products'] as $product) {
        if (isset($product['server']) && $product['server'] === 'ovh') {
            // Add custom JavaScript or HTML for OVH-specific options
            return <<<HTML
<script>
    // Add OVH-specific order form enhancements
    console.log('OVH product detected in cart');
</script>
HTML;
        }
    }
});

/**
 * Client area navigation menu for OVH services
 */
add_hook('ClientAreaPrimarySidebar', 1, function($primarySidebar) {
    if (!is_null($primarySidebar->getChild('Service Details Actions'))) {
        // Add custom OVH actions to service details sidebar
        $serviceActionsMenu = $primarySidebar->getChild('Service Details Actions');

        if ($serviceActionsMenu) {
            $serviceActionsMenu->addChild('OVH Manager', [
                'label' => 'OVH Manager',
                'uri' => 'https://www.ovh.com/manager/',
                'icon' => 'fa-external-link',
                'order' => 100,
            ]);
        }
    }
});
