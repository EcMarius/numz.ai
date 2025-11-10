<?php
/**
 * Example Addon Module
 *
 * Demonstrates WHMCS addon module compatibility
 */

if (!defined('WHMCS')) {
    define('WHMCS', true);
}

/**
 * Module configuration
 */
function example_addon_config()
{
    return [
        'name' => 'Example Addon Module',
        'description' => 'This is an example addon module demonstrating WHMCS compatibility',
        'version' => '1.0',
        'author' => 'Your Company',
        'language' => 'english',
        'fields' => [
            'api_key' => [
                'FriendlyName' => 'API Key',
                'Type' => 'text',
                'Size' => '50',
                'Description' => 'Enter your API key',
                'Default' => '',
            ],
            'api_secret' => [
                'FriendlyName' => 'API Secret',
                'Type' => 'password',
                'Size' => '50',
                'Description' => 'Enter your API secret',
                'Default' => '',
            ],
            'enable_feature' => [
                'FriendlyName' => 'Enable Feature X',
                'Type' => 'yesno',
                'Description' => 'Tick to enable this feature',
                'Default' => 'yes',
            ],
            'notification_email' => [
                'FriendlyName' => 'Notification Email',
                'Type' => 'text',
                'Size' => '50',
                'Description' => 'Email address for notifications',
                'Default' => '',
            ],
        ],
    ];
}

/**
 * Activate addon module
 */
function example_addon_activate()
{
    try {
        // Create custom tables if needed
        $query = "CREATE TABLE IF NOT EXISTS `mod_example_addon_data` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `user_id` int(10) unsigned NOT NULL,
            `setting_name` varchar(100) NOT NULL,
            `setting_value` text,
            `created_at` datetime DEFAULT NULL,
            `updated_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        Capsule::connection()->getPdo()->exec($query);

        // Log activation
        logActivity('Example Addon Module activated');

        return [
            'status' => 'success',
            'description' => 'Example Addon Module has been activated successfully.',
        ];

    } catch (\Exception $e) {
        return [
            'status' => 'error',
            'description' => 'Activation failed: ' . $e->getMessage(),
        ];
    }
}

/**
 * Deactivate addon module
 */
function example_addon_deactivate()
{
    try {
        // Note: Usually we don't drop tables on deactivation
        // Just log the deactivation
        logActivity('Example Addon Module deactivated');

        return [
            'status' => 'success',
            'description' => 'Example Addon Module has been deactivated successfully.',
        ];

    } catch (\Exception $e) {
        return [
            'status' => 'error',
            'description' => 'Deactivation failed: ' . $e->getMessage(),
        ];
    }
}

/**
 * Upgrade addon module
 */
function example_addon_upgrade($vars)
{
    $currentVersion = $vars['version'];

    // Perform upgrade tasks based on version
    if (version_compare($currentVersion, '1.1', '<')) {
        // Upgrade to 1.1
        try {
            $query = "ALTER TABLE `mod_example_addon_data`
                      ADD COLUMN `extra_field` varchar(255) DEFAULT NULL";
            Capsule::connection()->getPdo()->exec($query);
        } catch (\Exception $e) {
            // Column might already exist
        }
    }

    return;
}

/**
 * Output main admin page
 */
function example_addon_output($vars)
{
    $modulelink = $vars['modulelink'];
    $version = $vars['version'];
    $apiKey = $vars['api_key'];
    $apiSecret = $vars['api_secret'];

    // Check if action is set
    $action = $_REQUEST['action'] ?? 'dashboard';

    echo '<div class="container-fluid">';
    echo '<h2>Example Addon Module</h2>';
    echo '<p class="text-muted">Version ' . $version . '</p>';

    // Navigation tabs
    echo '<ul class="nav nav-tabs" role="tablist">';
    echo '<li class="nav-item"><a class="nav-link ' . ($action == 'dashboard' ? 'active' : '') . '" href="' . $modulelink . '&action=dashboard">Dashboard</a></li>';
    echo '<li class="nav-item"><a class="nav-link ' . ($action == 'settings' ? 'active' : '') . '" href="' . $modulelink . '&action=settings">Settings</a></li>';
    echo '<li class="nav-item"><a class="nav-link ' . ($action == 'logs' ? 'active' : '') . '" href="' . $modulelink . '&action=logs">Logs</a></li>';
    echo '</ul>';

    echo '<div class="tab-content">';

    // Handle different actions
    switch ($action) {
        case 'dashboard':
            example_addon_dashboard($vars);
            break;
        case 'settings':
            example_addon_settings_page($vars);
            break;
        case 'logs':
            example_addon_logs_page($vars);
            break;
        default:
            example_addon_dashboard($vars);
    }

    echo '</div>';
    echo '</div>';
}

/**
 * Dashboard page
 */
function example_addon_dashboard($vars)
{
    echo '<div class="panel panel-default mt-3">';
    echo '<div class="panel-heading">Dashboard</div>';
    echo '<div class="panel-body">';

    // Get some statistics
    $totalUsers = Capsule::table('users')->count();
    $totalServices = Capsule::table('services')->count();
    $totalData = Capsule::table('mod_example_addon_data')->count();

    echo '<div class="row">';
    echo '<div class="col-md-4">';
    echo '<div class="panel panel-info">';
    echo '<div class="panel-heading">Total Users</div>';
    echo '<div class="panel-body text-center"><h1>' . $totalUsers . '</h1></div>';
    echo '</div>';
    echo '</div>';

    echo '<div class="col-md-4">';
    echo '<div class="panel panel-success">';
    echo '<div class="panel-heading">Total Services</div>';
    echo '<div class="panel-body text-center"><h1>' . $totalServices . '</h1></div>';
    echo '</div>';
    echo '</div>';

    echo '<div class="col-md-4">';
    echo '<div class="panel panel-warning">';
    echo '<div class="panel-heading">Addon Data Records</div>';
    echo '<div class="panel-body text-center"><h1>' . $totalData . '</h1></div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';

    echo '<div class="alert alert-info mt-3">';
    echo '<strong>Info:</strong> This is an example addon module demonstrating WHMCS compatibility.';
    echo '</div>';

    echo '</div>';
    echo '</div>';
}

/**
 * Settings page
 */
function example_addon_settings_page($vars)
{
    echo '<div class="panel panel-default mt-3">';
    echo '<div class="panel-heading">Module Settings</div>';
    echo '<div class="panel-body">';

    echo '<table class="table table-bordered">';
    echo '<tr><th>Setting</th><th>Value</th></tr>';
    echo '<tr><td>API Key</td><td>' . (empty($vars['api_key']) ? '<em>Not configured</em>' : '••••••••' . substr($vars['api_key'], -4)) . '</td></tr>';
    echo '<tr><td>API Secret</td><td>' . (empty($vars['api_secret']) ? '<em>Not configured</em>' : '••••••••') . '</td></tr>';
    echo '<tr><td>Enable Feature</td><td>' . ($vars['enable_feature'] ? 'Yes' : 'No') . '</td></tr>';
    echo '<tr><td>Notification Email</td><td>' . ($vars['notification_email'] ?: '<em>Not configured</em>') . '</td></tr>';
    echo '</table>';

    echo '<p class="text-muted">Configure these settings in Setup > Addon Modules</p>';

    echo '</div>';
    echo '</div>';
}

/**
 * Logs page
 */
function example_addon_logs_page($vars)
{
    echo '<div class="panel panel-default mt-3">';
    echo '<div class="panel-heading">Activity Logs</div>';
    echo '<div class="panel-body">';

    // Get recent activity logs
    $logs = Capsule::table('tblactivitylog')
        ->where('description', 'LIKE', '%Example Addon%')
        ->orderBy('date', 'DESC')
        ->limit(20)
        ->get();

    if (count($logs) > 0) {
        echo '<table class="table table-striped">';
        echo '<thead><tr><th>Date</th><th>User</th><th>Description</th><th>IP Address</th></tr></thead>';
        echo '<tbody>';

        foreach ($logs as $log) {
            echo '<tr>';
            echo '<td>' . $log->date . '</td>';
            echo '<td>' . $log->user . '</td>';
            echo '<td>' . $log->description . '</td>';
            echo '<td>' . $log->ipaddr . '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<div class="alert alert-info">No activity logs found.</div>';
    }

    echo '</div>';
    echo '</div>';
}

/**
 * Client area output (optional)
 */
function example_addon_clientarea($vars)
{
    // This function can be used to display content in the client area
    // Return array with template file and variables

    return [
        'pagetitle' => 'Example Addon',
        'breadcrumb' => ['index.php?m=example_addon' => 'Example Addon'],
        'templatefile' => 'clientarea',
        'requirelogin' => true,
        'vars' => [
            'testvar' => 'Demo value',
        ],
    ];
}

/**
 * Sidebar output (optional)
 */
function example_addon_sidebar($vars)
{
    $sidebar = '<div class="panel panel-default">';
    $sidebar .= '<div class="panel-heading">Example Addon</div>';
    $sidebar .= '<div class="list-group">';
    $sidebar .= '<a href="index.php?m=example_addon" class="list-group-item">Dashboard</a>';
    $sidebar .= '<a href="index.php?m=example_addon&action=settings" class="list-group-item">Settings</a>';
    $sidebar .= '</div>';
    $sidebar .= '</div>';

    return $sidebar;
}
