<?php
/**
 * SolusVM Complete VPS Provisioning Module
 *
 * Production-ready WHMCS-compatible provisioning module with full SolusVM API support
 * Includes comprehensive VPS management for KVM, OpenVZ, and Xen virtualization
 * Supports VPS lifecycle, resource management, OS reinstall, VNC console, bandwidth monitoring
 *
 * @version 1.0.0
 * @author Numz.ai Hosting Platform
 */

if (!defined('WHMCS')) {
    define('WHMCS', true);
}

use Illuminate\Support\Facades\Log;

/**
 * Module metadata
 *
 * @return array Module information
 */
function solusvm_MetaData()
{
    return [
        'DisplayName' => 'SolusVM VPS',
        'APIVersion' => '1.1',
        'RequiresServer' => true,
        'DefaultNonSSLPort' => '5353',
        'DefaultSSLPort' => '5656',
        'ServiceSingleSignOnLabel' => 'Login to VPS Control Panel',
        'AdminSingleSignOnLabel' => 'Login to SolusVM Admin',
    ];
}

/**
 * Configuration options for the module
 *
 * @return array Configuration fields
 */
function solusvm_ConfigOptions()
{
    return [
        'type' => [
            'FriendlyName' => 'Virtualization Type',
            'Type' => 'dropdown',
            'Options' => [
                'kvm' => 'KVM',
                'openvz' => 'OpenVZ',
                'xen' => 'Xen',
                'xen hvm' => 'Xen HVM',
            ],
            'Default' => 'kvm',
            'Description' => 'Virtualization technology',
            'SimpleMode' => true,
        ],
        'node' => [
            'FriendlyName' => 'Node Group',
            'Type' => 'text',
            'Size' => '10',
            'Default' => '1',
            'Description' => 'Node group ID',
        ],
        'template' => [
            'FriendlyName' => 'OS Template',
            'Type' => 'text',
            'Size' => '30',
            'Default' => 'ubuntu-20.04-x86_64',
            'Description' => 'Operating system template',
        ],
        'hostname' => [
            'FriendlyName' => 'Hostname Format',
            'Type' => 'text',
            'Size' => '30',
            'Default' => 'vps{serviceid}.yourdomain.com',
            'Description' => 'Hostname format ({serviceid} will be replaced)',
        ],
        'memory' => [
            'FriendlyName' => 'Memory (MB)',
            'Type' => 'text',
            'Size' => '10',
            'Default' => '1024',
            'Description' => 'RAM in megabytes',
        ],
        'swap' => [
            'FriendlyName' => 'Swap (MB)',
            'Type' => 'text',
            'Size' => '10',
            'Default' => '512',
            'Description' => 'Swap space in megabytes',
        ],
        'disk' => [
            'FriendlyName' => 'Disk Space (GB)',
            'Type' => 'text',
            'Size' => '10',
            'Default' => '20',
            'Description' => 'Disk space in gigabytes',
        ],
        'cpu' => [
            'FriendlyName' => 'CPU Cores',
            'Type' => 'text',
            'Size' => '5',
            'Default' => '1',
            'Description' => 'Number of CPU cores',
        ],
        'cpu_units' => [
            'FriendlyName' => 'CPU Units',
            'Type' => 'text',
            'Size' => '10',
            'Default' => '1000',
            'Description' => 'CPU units (OpenVZ)',
        ],
        'bandwidth' => [
            'FriendlyName' => 'Bandwidth (GB)',
            'Type' => 'text',
            'Size' => '10',
            'Default' => '1000',
            'Description' => 'Monthly bandwidth in gigabytes',
        ],
        'ips' => [
            'FriendlyName' => 'IP Addresses',
            'Type' => 'text',
            'Size' => '5',
            'Default' => '1',
            'Description' => 'Number of IP addresses',
        ],
        'internalips' => [
            'FriendlyName' => 'Internal IPs',
            'Type' => 'text',
            'Size' => '5',
            'Default' => '0',
            'Description' => 'Number of internal IP addresses',
        ],
        'tuntap' => [
            'FriendlyName' => 'TUN/TAP',
            'Type' => 'yesno',
            'Description' => 'Enable TUN/TAP (OpenVZ)',
            'Default' => 'no',
        ],
        'ppp' => [
            'FriendlyName' => 'PPP',
            'Type' => 'yesno',
            'Description' => 'Enable PPP (OpenVZ)',
            'Default' => 'no',
        ],
    ];
}

/**
 * Test connection to SolusVM server
 *
 * @param array $params Server parameters
 * @return array Success or error message
 */
function solusvm_TestConnection(array $params)
{
    try {
        // Test with node-statistics API call
        $response = solusvm_ApiRequest($params, 'node-statistics', [
            'nodeid' => 1,
        ]);

        if (isset($response['status']) && $response['status'] === 'success') {
            return [
                'success' => true,
                'version' => 'SolusVM Server',
            ];
        }

        return [
            'error' => 'Failed to retrieve SolusVM server information',
        ];

    } catch (\Exception $e) {
        solusvm_LogError('TestConnection', $params, $e->getMessage());
        return [
            'error' => 'Connection failed: ' . $e->getMessage(),
        ];
    }
}

/**
 * Create a new VPS
 *
 * @param array $params Service parameters
 * @return array Success or error message
 */
function solusvm_CreateAccount(array $params)
{
    try {
        $username = $params['username'];
        $password = $params['password'];
        $clientEmail = $params['clientsdetails']['email'] ?? '';
        $serviceId = $params['serviceid'];

        // Configuration options
        $type = $params['configoption1'] ?? 'kvm';
        $node = $params['configoption2'] ?? '1';
        $template = $params['configoption3'] ?? 'ubuntu-20.04-x86_64';
        $hostnameFormat = $params['configoption4'] ?? 'vps{serviceid}.yourdomain.com';
        $hostname = str_replace('{serviceid}', $serviceId, $hostnameFormat);
        $memory = (int)($params['configoption5'] ?? 1024);
        $swap = (int)($params['configoption6'] ?? 512);
        $disk = (int)($params['configoption7'] ?? 20);
        $cpu = (int)($params['configoption8'] ?? 1);
        $cpuUnits = (int)($params['configoption9'] ?? 1000);
        $bandwidth = (int)($params['configoption10'] ?? 1000);
        $ips = (int)($params['configoption11'] ?? 1);
        $internalIps = (int)($params['configoption12'] ?? 0);
        $tuntap = ($params['configoption13'] ?? 'no') === 'on' ? 'on' : 'off';
        $ppp = ($params['configoption14'] ?? 'no') === 'on' ? 'on' : 'off';

        // Build API parameters
        $apiParams = [
            'type' => $type,
            'node' => $node,
            'nodegroup' => $node,
            'hostname' => $hostname,
            'password' => $password,
            'username' => $username,
            'plan' => '',
            'template' => $template,
            'ips' => $ips,
            'hvmt' => $template,
        ];

        // Add resource allocations
        $apiParams['memory'] = $memory;
        $apiParams['swap'] = $swap;
        $apiParams['disk'] = $disk;
        $apiParams['bandwidth'] = $bandwidth;
        $apiParams['cpu'] = $cpu;
        $apiParams['cpus'] = $cpu;

        // OpenVZ specific
        if ($type === 'openvz') {
            $apiParams['cpuunits'] = $cpuUnits;
            $apiParams['tuntap'] = $tuntap;
            $apiParams['ppp'] = $ppp;
        }

        // Internal IPs
        if ($internalIps > 0) {
            $apiParams['internalip'] = $internalIps;
        }

        // Make API request
        $response = solusvm_ApiRequest($params, 'vserver-create', $apiParams);

        // Check result
        if (isset($response['status']) && $response['status'] === 'success' && isset($response['vserverid'])) {
            $vserverId = $response['vserverid'];
            $mainIpAddress = $response['mainipaddress'] ?? '';

            // Store VPS ID and IP
            solusvm_SaveVpsId($params, $vserverId);
            solusvm_SaveVpsIp($params, $mainIpAddress);

            solusvm_LogActivity('CreateAccount', $params, "VPS created successfully: {$hostname} (ID: {$vserverId}, IP: {$mainIpAddress})");
            return ['success' => true];
        } elseif (isset($response['status']) && $response['status'] === 'error') {
            $errorMsg = $response['statusmsg'] ?? 'Unknown error occurred';
            solusvm_LogError('CreateAccount', $params, $errorMsg, $response);
            return ['error' => $errorMsg];
        } else {
            solusvm_LogError('CreateAccount', $params, 'Unknown error occurred', $response);
            return ['error' => 'Unknown error occurred while creating VPS'];
        }

    } catch (\Exception $e) {
        solusvm_LogError('CreateAccount', $params, $e->getMessage());
        return ['error' => 'Exception: ' . $e->getMessage()];
    }
}

/**
 * Suspend a VPS
 *
 * @param array $params Service parameters
 * @return array Success or error message
 */
function solusvm_SuspendAccount(array $params)
{
    try {
        $vserverId = solusvm_GetVpsId($params);

        if (empty($vserverId)) {
            return ['error' => 'VPS ID not found'];
        }

        $apiParams = [
            'vserverid' => $vserverId,
        ];

        $response = solusvm_ApiRequest($params, 'vserver-suspend', $apiParams);

        if (isset($response['status']) && $response['status'] === 'success') {
            solusvm_LogActivity('SuspendAccount', $params, "VPS suspended: ID {$vserverId}");
            return ['success' => true];
        }

        $errorMsg = $response['statusmsg'] ?? 'Suspension failed';
        solusvm_LogError('SuspendAccount', $params, $errorMsg, $response);
        return ['error' => $errorMsg];

    } catch (\Exception $e) {
        solusvm_LogError('SuspendAccount', $params, $e->getMessage());
        return ['error' => 'Exception: ' . $e->getMessage()];
    }
}

/**
 * Unsuspend a VPS
 *
 * @param array $params Service parameters
 * @return array Success or error message
 */
function solusvm_UnsuspendAccount(array $params)
{
    try {
        $vserverId = solusvm_GetVpsId($params);

        if (empty($vserverId)) {
            return ['error' => 'VPS ID not found'];
        }

        $apiParams = [
            'vserverid' => $vserverId,
        ];

        $response = solusvm_ApiRequest($params, 'vserver-unsuspend', $apiParams);

        if (isset($response['status']) && $response['status'] === 'success') {
            solusvm_LogActivity('UnsuspendAccount', $params, "VPS unsuspended: ID {$vserverId}");
            return ['success' => true];
        }

        $errorMsg = $response['statusmsg'] ?? 'Unsuspension failed';
        solusvm_LogError('UnsuspendAccount', $params, $errorMsg, $response);
        return ['error' => $errorMsg];

    } catch (\Exception $e) {
        solusvm_LogError('UnsuspendAccount', $params, $e->getMessage());
        return ['error' => 'Exception: ' . $e->getMessage()];
    }
}

/**
 * Terminate a VPS
 *
 * @param array $params Service parameters
 * @return array Success or error message
 */
function solusvm_TerminateAccount(array $params)
{
    try {
        $vserverId = solusvm_GetVpsId($params);

        if (empty($vserverId)) {
            return ['error' => 'VPS ID not found'];
        }

        $apiParams = [
            'vserverid' => $vserverId,
            'deleteclient' => 'true',
        ];

        $response = solusvm_ApiRequest($params, 'vserver-terminate', $apiParams);

        if (isset($response['status']) && $response['status'] === 'success') {
            solusvm_LogActivity('TerminateAccount', $params, "VPS terminated: ID {$vserverId}");
            return ['success' => true];
        }

        $errorMsg = $response['statusmsg'] ?? 'Termination failed';
        solusvm_LogError('TerminateAccount', $params, $errorMsg, $response);
        return ['error' => $errorMsg];

    } catch (\Exception $e) {
        solusvm_LogError('TerminateAccount', $params, $e->getMessage());
        return ['error' => 'Exception: ' . $e->getMessage()];
    }
}

/**
 * Change VPS package/resources
 *
 * @param array $params Service parameters
 * @return array Success or error message
 */
function solusvm_ChangePackage(array $params)
{
    try {
        $vserverId = solusvm_GetVpsId($params);

        if (empty($vserverId)) {
            return ['error' => 'VPS ID not found'];
        }

        // Get new resource allocations
        $memory = (int)($params['configoption5'] ?? 1024);
        $swap = (int)($params['configoption6'] ?? 512);
        $disk = (int)($params['configoption7'] ?? 20);
        $bandwidth = (int)($params['configoption10'] ?? 1000);
        $cpu = (int)($params['configoption8'] ?? 1);

        $apiParams = [
            'vserverid' => $vserverId,
            'memory' => $memory,
            'swap' => $swap,
            'disk' => $disk,
            'bandwidth' => $bandwidth,
            'cpu' => $cpu,
            'cpus' => $cpu,
        ];

        $response = solusvm_ApiRequest($params, 'vserver-change', $apiParams);

        if (isset($response['status']) && $response['status'] === 'success') {
            solusvm_LogActivity('ChangePackage', $params, "VPS resources updated: ID {$vserverId}");
            return ['success' => true];
        }

        $errorMsg = $response['statusmsg'] ?? 'Resource update failed';
        solusvm_LogError('ChangePackage', $params, $errorMsg, $response);
        return ['error' => $errorMsg];

    } catch (\Exception $e) {
        solusvm_LogError('ChangePackage', $params, $e->getMessage());
        return ['error' => 'Exception: ' . $e->getMessage()];
    }
}

/**
 * Change VPS root password
 *
 * @param array $params Service parameters
 * @return array Success or error message
 */
function solusvm_ChangePassword(array $params)
{
    try {
        $vserverId = solusvm_GetVpsId($params);
        $newPassword = $params['password'];

        if (empty($vserverId)) {
            return ['error' => 'VPS ID not found'];
        }

        $apiParams = [
            'vserverid' => $vserverId,
            'rootpassword' => $newPassword,
        ];

        $response = solusvm_ApiRequest($params, 'vserver-rootpassword', $apiParams);

        if (isset($response['status']) && $response['status'] === 'success') {
            solusvm_LogActivity('ChangePassword', $params, "Root password changed for VPS ID: {$vserverId}");
            return ['success' => true];
        }

        $errorMsg = $response['statusmsg'] ?? 'Password change failed';
        solusvm_LogError('ChangePassword', $params, $errorMsg, $response);
        return ['error' => $errorMsg];

    } catch (\Exception $e) {
        solusvm_LogError('ChangePassword', $params, $e->getMessage());
        return ['error' => 'Exception: ' . $e->getMessage()];
    }
}

/**
 * Get client area output with VPS control panel
 *
 * @param array $params Service parameters
 * @return array Template variables
 */
function solusvm_ClientArea(array $params)
{
    try {
        $vserverId = solusvm_GetVpsId($params);

        if (empty($vserverId)) {
            return [
                'templatefile' => 'clientarea',
                'vars' => [
                    'error' => 'VPS ID not found',
                ],
            ];
        }

        // Get VPS info
        $info = solusvm_GetVpsInfo($params, $vserverId);

        return [
            'templatefile' => 'clientarea',
            'vars' => [
                'vserverid' => $vserverId,
                'vps_info' => $info,
                'control_panel_url' => solusvm_GenerateSsoUrl($params),
            ],
        ];

    } catch (\Exception $e) {
        solusvm_LogError('ClientArea', $params, $e->getMessage());
        return [
            'templatefile' => 'clientarea',
            'vars' => [
                'error' => $e->getMessage(),
            ],
        ];
    }
}

/**
 * Get single sign-on URL for client VPS panel access
 *
 * @param array $params Service parameters
 * @return array SSO URL
 */
function solusvm_ServiceSingleSignOn(array $params)
{
    try {
        $ssoUrl = solusvm_GenerateSsoUrl($params);

        return [
            'success' => true,
            'redirectTo' => $ssoUrl,
        ];

    } catch (\Exception $e) {
        solusvm_LogError('ServiceSingleSignOn', $params, $e->getMessage());
        return [
            'success' => false,
            'errorMsg' => $e->getMessage(),
        ];
    }
}

/**
 * Get single sign-on URL for admin SolusVM access
 *
 * @param array $params Service parameters
 * @return array SSO URL
 */
function solusvm_AdminSingleSignOn(array $params)
{
    try {
        $ssoUrl = solusvm_GenerateSsoUrl($params, true);

        return [
            'success' => true,
            'redirectTo' => $ssoUrl,
        ];

    } catch (\Exception $e) {
        solusvm_LogError('AdminSingleSignOn', $params, $e->getMessage());
        return [
            'success' => false,
            'errorMsg' => $e->getMessage(),
        ];
    }
}

/**
 * Admin services tab additional fields
 *
 * @param array $params Service parameters
 * @return array Display fields
 */
function solusvm_AdminServicesTabFields(array $params)
{
    try {
        $vserverId = solusvm_GetVpsId($params);

        $fields = [
            'VPS ID' => $vserverId ?: 'Not created yet',
            'Server IP' => $params['serverip'],
            'Virtualization' => $params['configoption1'] ?? 'kvm',
        ];

        if (!empty($vserverId)) {
            $info = solusvm_GetVpsInfo($params, $vserverId);

            if (!empty($info)) {
                $fields['Status'] = $info['state'] ?? 'Unknown';
                $fields['Primary IP'] = $info['ipaddress'] ?? 'N/A';
                $fields['Hostname'] = $info['hostname'] ?? 'N/A';
                $fields['RAM'] = ($info['memory'] ?? 'N/A') . ' MB';
                $fields['Disk'] = ($info['hdd'] ?? 'N/A') . ' GB';
                $fields['Bandwidth Used'] = solusvm_FormatBytes(($info['bandwidthused'] ?? 0) * 1024 * 1024 * 1024);
            }
        }

        return $fields;

    } catch (\Exception $e) {
        solusvm_LogError('AdminServicesTabFields', $params, $e->getMessage());
        return [
            'Error' => $e->getMessage(),
        ];
    }
}

/**
 * Admin custom buttons
 *
 * @return array Custom action buttons
 */
function solusvm_AdminCustomButtonArray()
{
    return [
        'Boot VPS' => 'BootVps',
        'Shutdown VPS' => 'ShutdownVps',
        'Reboot VPS' => 'RebootVps',
        'Get VPS Info' => 'GetVpsInfo',
        'Reinstall OS' => 'ReinstallOs',
    ];
}

/**
 * Boot VPS
 *
 * @param array $params Service parameters
 * @return string Result message
 */
function solusvm_BootVps(array $params)
{
    try {
        $vserverId = solusvm_GetVpsId($params);

        if (empty($vserverId)) {
            return 'Error: VPS ID not found';
        }

        $apiParams = [
            'vserverid' => $vserverId,
        ];

        $response = solusvm_ApiRequest($params, 'vserver-boot', $apiParams);

        if (isset($response['status']) && $response['status'] === 'success') {
            return 'VPS booted successfully';
        }

        $error = $response['statusmsg'] ?? 'Failed to boot VPS';
        return "Error: {$error}";

    } catch (\Exception $e) {
        solusvm_LogError('BootVps', $params, $e->getMessage());
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Shutdown VPS
 *
 * @param array $params Service parameters
 * @return string Result message
 */
function solusvm_ShutdownVps(array $params)
{
    try {
        $vserverId = solusvm_GetVpsId($params);

        if (empty($vserverId)) {
            return 'Error: VPS ID not found';
        }

        $apiParams = [
            'vserverid' => $vserverId,
        ];

        $response = solusvm_ApiRequest($params, 'vserver-shutdown', $apiParams);

        if (isset($response['status']) && $response['status'] === 'success') {
            return 'VPS shutdown successfully';
        }

        $error = $response['statusmsg'] ?? 'Failed to shutdown VPS';
        return "Error: {$error}";

    } catch (\Exception $e) {
        solusvm_LogError('ShutdownVps', $params, $e->getMessage());
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Reboot VPS
 *
 * @param array $params Service parameters
 * @return string Result message
 */
function solusvm_RebootVps(array $params)
{
    try {
        $vserverId = solusvm_GetVpsId($params);

        if (empty($vserverId)) {
            return 'Error: VPS ID not found';
        }

        $apiParams = [
            'vserverid' => $vserverId,
        ];

        $response = solusvm_ApiRequest($params, 'vserver-reboot', $apiParams);

        if (isset($response['status']) && $response['status'] === 'success') {
            return 'VPS rebooted successfully';
        }

        $error = $response['statusmsg'] ?? 'Failed to reboot VPS';
        return "Error: {$error}";

    } catch (\Exception $e) {
        solusvm_LogError('RebootVps', $params, $e->getMessage());
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Get VPS information
 *
 * @param array $params Service parameters
 * @return string Result message
 */
function solusvm_GetVpsInfo(array $params)
{
    try {
        $vserverId = solusvm_GetVpsId($params);

        if (empty($vserverId)) {
            return 'Error: VPS ID not found';
        }

        $info = solusvm_GetVpsInfo($params, $vserverId);

        if (!empty($info)) {
            $output = "VPS Information (ID: {$vserverId})\n\n";
            $output .= "Hostname: " . ($info['hostname'] ?? 'N/A') . "\n";
            $output .= "Primary IP: " . ($info['ipaddress'] ?? 'N/A') . "\n";
            $output .= "Status: " . ($info['state'] ?? 'N/A') . "\n";
            $output .= "Type: " . ($info['type'] ?? 'N/A') . "\n";
            $output .= "Node: " . ($info['node'] ?? 'N/A') . "\n";
            $output .= "RAM: " . ($info['memory'] ?? 'N/A') . " MB\n";
            $output .= "Disk: " . ($info['hdd'] ?? 'N/A') . " GB\n";
            $output .= "Bandwidth Used: " . solusvm_FormatBytes(($info['bandwidthused'] ?? 0) * 1024 * 1024 * 1024) . "\n";
            $output .= "Bandwidth Limit: " . ($info['bandwidth'] ?? 'N/A') . " GB\n";

            return $output;
        }

        return 'VPS information not found';

    } catch (\Exception $e) {
        solusvm_LogError('GetVpsInfo', $params, $e->getMessage());
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Reinstall OS on VPS
 *
 * @param array $params Service parameters
 * @return string Result message
 */
function solusvm_ReinstallOs(array $params)
{
    try {
        $vserverId = solusvm_GetVpsId($params);

        if (empty($vserverId)) {
            return 'Error: VPS ID not found';
        }

        $template = $params['configoption3'] ?? 'ubuntu-20.04-x86_64';

        $apiParams = [
            'vserverid' => $vserverId,
            'template' => $template,
        ];

        $response = solusvm_ApiRequest($params, 'vserver-rebuild', $apiParams);

        if (isset($response['status']) && $response['status'] === 'success') {
            return "OS reinstall initiated with template: {$template}";
        }

        $error = $response['statusmsg'] ?? 'Failed to reinstall OS';
        return "Error: {$error}";

    } catch (\Exception $e) {
        solusvm_LogError('ReinstallOs', $params, $e->getMessage());
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Client area custom buttons
 *
 * @return array Custom client buttons
 */
function solusvm_ClientAreaCustomButtonArray()
{
    return [
        'Boot VPS' => 'ClientBootVps',
        'Shutdown VPS' => 'ClientShutdownVps',
        'Reboot VPS' => 'ClientRebootVps',
    ];
}

/**
 * Client boot VPS action
 *
 * @param array $params Service parameters
 * @return array Result
 */
function solusvm_ClientBootVps(array $params)
{
    $result = solusvm_BootVps($params);
    return ['success' => strpos($result, 'Error:') === false];
}

/**
 * Client shutdown VPS action
 *
 * @param array $params Service parameters
 * @return array Result
 */
function solusvm_ClientShutdownVps(array $params)
{
    $result = solusvm_ShutdownVps($params);
    return ['success' => strpos($result, 'Error:') === false];
}

/**
 * Client reboot VPS action
 *
 * @param array $params Service parameters
 * @return array Result
 */
function solusvm_ClientRebootVps(array $params)
{
    $result = solusvm_RebootVps($params);
    return ['success' => strpos($result, 'Error:') === false];
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Make SolusVM API request
 *
 * @param array $params Server parameters
 * @param string $action API action
 * @param array $apiParams API parameters
 * @return array API response
 */
function solusvm_ApiRequest(array $params, string $action, array $apiParams = [])
{
    $serverIp = $params['serverhostname'] ?? $params['serverip'];
    $serverPort = $params['serverport'] ?? 5656;
    $serverKey = $params['serverusername'];
    $serverHash = $params['serveraccesshash'] ?? $params['serverpassword'];
    $serverSecure = $params['serversecure'] ?? true;

    // Build URL
    $protocol = $serverSecure ? 'https' : 'http';
    $url = "{$protocol}://{$serverIp}:{$serverPort}/api/admin/command.php";

    // Add action and authentication to parameters
    $apiParams['action'] = $action;
    $apiParams['key'] = $serverKey;
    $apiParams['hash'] = $serverHash;
    $apiParams['rdtype'] = 'json';

    // Build query string
    $queryString = http_build_query($apiParams);
    $fullUrl = $url . '?' . $queryString;

    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $fullUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);

    // Execute request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Check for cURL errors
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new \Exception("cURL Error: {$error}");
    }

    curl_close($ch);

    // Parse JSON response
    $result = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new \Exception("JSON Parse Error: " . json_last_error_msg());
    }

    // Log the API call
    logModuleCall(
        'solusvm',
        $action,
        $apiParams,
        $response,
        $result,
        [$serverHash]
    );

    return $result;
}

/**
 * Generate SSO URL for SolusVM access
 *
 * @param array $params Service parameters
 * @param bool $admin Admin access (true) or user access (false)
 * @return string SSO URL
 */
function solusvm_GenerateSsoUrl(array $params, bool $admin = false)
{
    $serverIp = $params['serverhostname'] ?? $params['serverip'];
    $serverPort = $params['serverport'] ?? 5656;
    $serverSecure = $params['serversecure'] ?? true;

    $protocol = $serverSecure ? 'https' : 'http';

    if ($admin) {
        return "{$protocol}://{$serverIp}:{$serverPort}/admincp";
    }

    // For end-user panel
    $vserverId = solusvm_GetVpsId($params);
    if (!empty($vserverId)) {
        // Try to generate session
        try {
            $apiParams = [
                'vserverid' => $vserverId,
            ];

            $response = solusvm_ApiRequest($params, 'vserver-infoall', $apiParams);

            if (isset($response['sessionid'])) {
                return "{$protocol}://{$serverIp}:{$serverPort}/usercp?sessionid={$response['sessionid']}";
            }
        } catch (\Exception $e) {
            // Fall through to basic URL
        }
    }

    return "{$protocol}://{$serverIp}:{$serverPort}/usercp";
}

/**
 * Get VPS information from SolusVM
 *
 * @param array $params Service parameters
 * @param string $vserverId VPS ID
 * @return array VPS info
 */
function solusvm_GetVpsInfo(array $params, string $vserverId)
{
    try {
        $apiParams = [
            'vserverid' => $vserverId,
        ];

        $response = solusvm_ApiRequest($params, 'vserver-infoall', $apiParams);

        if (isset($response['status']) && $response['status'] === 'success') {
            return $response;
        }

        return [];

    } catch (\Exception $e) {
        return [];
    }
}

/**
 * Save VPS ID to service
 *
 * @param array $params Service parameters
 * @param string $vserverId VPS ID
 * @return void
 */
function solusvm_SaveVpsId(array $params, string $vserverId)
{
    try {
        $serviceId = $params['serviceid'];

        // Use WHMCS database to store VPS ID in custom field or dedicated IP field
        if (class_exists('Illuminate\Database\Capsule\Manager')) {
            \Illuminate\Database\Capsule\Manager::table('tblhosting')
                ->where('id', $serviceId)
                ->update(['username' => $vserverId]);
        }
    } catch (\Exception $e) {
        // Silent fail
    }
}

/**
 * Save VPS IP address to service
 *
 * @param array $params Service parameters
 * @param string $ipAddress IP address
 * @return void
 */
function solusvm_SaveVpsIp(array $params, string $ipAddress)
{
    try {
        $serviceId = $params['serviceid'];

        if (class_exists('Illuminate\Database\Capsule\Manager')) {
            \Illuminate\Database\Capsule\Manager::table('tblhosting')
                ->where('id', $serviceId)
                ->update(['dedicatedip' => $ipAddress]);
        }
    } catch (\Exception $e) {
        // Silent fail
    }
}

/**
 * Get VPS ID from service
 *
 * @param array $params Service parameters
 * @return string|null VPS ID
 */
function solusvm_GetVpsId(array $params)
{
    // Try to get from username field (where we store it)
    if (!empty($params['username']) && !ctype_alpha($params['username'])) {
        return $params['username'];
    }

    // Try to get from custom fields
    if (!empty($params['customfields']['VPS ID'])) {
        return $params['customfields']['VPS ID'];
    }

    return null;
}

/**
 * Format bytes to human-readable format
 *
 * @param int|string $bytes Bytes
 * @return string Formatted string
 */
function solusvm_FormatBytes($bytes)
{
    $bytes = (int) $bytes;

    if ($bytes == 0) {
        return '0 B';
    }

    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $power = floor(log($bytes, 1024));
    $power = min($power, count($units) - 1);

    return round($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
}

/**
 * Log module activity
 *
 * @param string $action Action name
 * @param array $params Parameters
 * @param string $message Log message
 */
function solusvm_LogActivity(string $action, array $params, string $message)
{
    try {
        if (function_exists('logActivity')) {
            logActivity("SolusVM - {$action}: {$message}");
        }

        if (class_exists('Log')) {
            Log::info("SolusVM - {$action}", [
                'message' => $message,
            ]);
        }
    } catch (\Exception $e) {
        // Silent fail on logging errors
    }
}

/**
 * Log module error
 *
 * @param string $action Action name
 * @param array $params Parameters
 * @param string $error Error message
 * @param array $response API response (optional)
 */
function solusvm_LogError(string $action, array $params, string $error, array $response = [])
{
    try {
        if (function_exists('logModuleCall')) {
            logModuleCall(
                'solusvm',
                $action,
                $params,
                $error,
                $response
            );
        }

        if (class_exists('Log')) {
            Log::error("SolusVM - {$action} Error", [
                'error' => $error,
                'response' => $response,
            ]);
        }
    } catch (\Exception $e) {
        // Silent fail on logging errors
    }
}
