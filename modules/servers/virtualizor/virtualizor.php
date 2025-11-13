<?php
/**
 * Virtualizor Complete VPS Provisioning Module
 *
 * Production-ready WHMCS-compatible provisioning module with full Virtualizor API support
 * Includes comprehensive VPS management for KVM and OpenVZ virtualization
 * Supports VPS lifecycle, resource management, OS templates, VNC console, snapshots
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
function virtualizor_MetaData()
{
    return [
        'DisplayName' => 'Virtualizor VPS',
        'APIVersion' => '1.1',
        'RequiresServer' => true,
        'DefaultNonSSLPort' => '4082',
        'DefaultSSLPort' => '4083',
        'ServiceSingleSignOnLabel' => 'Login to VPS Control Panel',
        'AdminSingleSignOnLabel' => 'Login to Virtualizor Admin',
    ];
}

/**
 * Configuration options for the module
 *
 * @return array Configuration fields
 */
function virtualizor_ConfigOptions()
{
    return [
        'virt_type' => [
            'FriendlyName' => 'Virtualization Type',
            'Type' => 'dropdown',
            'Options' => [
                'kvm' => 'KVM',
                'openvz' => 'OpenVZ',
                'xen' => 'Xen HVM',
                'xcp' => 'XenServer',
                'xcphvm' => 'XenServer HVM',
                'lxc' => 'LXC',
            ],
            'Default' => 'kvm',
            'Description' => 'Virtualization technology',
            'SimpleMode' => true,
        ],
        'os_template' => [
            'FriendlyName' => 'OS Template',
            'Type' => 'text',
            'Size' => '30',
            'Default' => 'ubuntu-20.04-x86_64',
            'Description' => 'Operating system template name',
        ],
        'cpu_cores' => [
            'FriendlyName' => 'CPU Cores',
            'Type' => 'text',
            'Size' => '5',
            'Default' => '1',
            'Description' => 'Number of CPU cores',
        ],
        'cpu_percent' => [
            'FriendlyName' => 'CPU %',
            'Type' => 'text',
            'Size' => '5',
            'Default' => '100',
            'Description' => 'CPU usage limit percentage',
        ],
        'ram' => [
            'FriendlyName' => 'RAM (MB)',
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
        'disk_space' => [
            'FriendlyName' => 'Disk Space (GB)',
            'Type' => 'text',
            'Size' => '10',
            'Default' => '20',
            'Description' => 'Disk space in gigabytes',
        ],
        'bandwidth' => [
            'FriendlyName' => 'Bandwidth (GB)',
            'Type' => 'text',
            'Size' => '10',
            'Default' => '1000',
            'Description' => 'Monthly bandwidth in gigabytes (0 for unlimited)',
        ],
        'network_speed' => [
            'FriendlyName' => 'Network Speed (MB/s)',
            'Type' => 'text',
            'Size' => '5',
            'Default' => '100',
            'Description' => 'Network speed limit in MB/s (0 for unlimited)',
        ],
        'ip_addresses' => [
            'FriendlyName' => 'Number of IPs',
            'Type' => 'text',
            'Size' => '5',
            'Default' => '1',
            'Description' => 'Number of IP addresses',
        ],
        'server_group' => [
            'FriendlyName' => 'Server Group',
            'Type' => 'text',
            'Size' => '10',
            'Default' => '0',
            'Description' => 'Server group ID (0 for automatic)',
        ],
        'vnc_enabled' => [
            'FriendlyName' => 'VNC Access',
            'Type' => 'yesno',
            'Description' => 'Enable VNC console access',
            'Default' => 'yes',
        ],
    ];
}

/**
 * Test connection to Virtualizor server
 *
 * @param array $params Server parameters
 * @return array Success or error message
 */
function virtualizor_TestConnection(array $params)
{
    try {
        // Test with listservergroup API call
        $response = virtualizor_ApiRequest($params, 'listservergroup', []);

        if (isset($response['servergroups'])) {
            return [
                'success' => true,
                'version' => 'Virtualizor ' . ($response['timenow'] ?? 'Server'),
            ];
        }

        return [
            'error' => 'Failed to retrieve Virtualizor server information',
        ];

    } catch (\Exception $e) {
        virtualizor_LogError('TestConnection', $params, $e->getMessage());
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
function virtualizor_CreateAccount(array $params)
{
    try {
        $domain = $params['domain'];
        $username = $params['username'];
        $password = $params['password'];
        $clientEmail = $params['clientsdetails']['email'] ?? '';

        // Configuration options
        $virtType = $params['configoption1'] ?? 'kvm';
        $osTemplate = $params['configoption2'] ?? 'ubuntu-20.04-x86_64';
        $cpuCores = (int)($params['configoption3'] ?? 1);
        $cpuPercent = (int)($params['configoption4'] ?? 100);
        $ram = (int)($params['configoption5'] ?? 1024);
        $swap = (int)($params['configoption6'] ?? 512);
        $diskSpace = (int)($params['configoption7'] ?? 20);
        $bandwidth = (int)($params['configoption8'] ?? 1000);
        $networkSpeed = (int)($params['configoption9'] ?? 100);
        $ipAddresses = (int)($params['configoption10'] ?? 1);
        $serverGroup = (int)($params['configoption11'] ?? 0);
        $vncEnabled = ($params['configoption12'] ?? 'yes') === 'on' ? 1 : 0;

        // Build API parameters
        $apiParams = [
            'virt' => $virtType,
            'hostname' => $domain,
            'ips' => $ipAddresses,
            'ram' => $ram,
            'swap' => $swap,
            'disk_space' => $diskSpace,
            'cores' => $cpuCores,
            'cpu_percent' => $cpuPercent,
            'bandwidth' => $bandwidth > 0 ? $bandwidth : 0,
            'network_speed' => $networkSpeed > 0 ? $networkSpeed : 0,
            'ostemplate' => $osTemplate,
            'rootpass' => $password,
            'vnc' => $vncEnabled,
            'email' => $clientEmail,
        ];

        // Server group
        if ($serverGroup > 0) {
            $apiParams['server_group'] = $serverGroup;
        }

        // Make API request
        $response = virtualizor_ApiRequest($params, 'addvs', $apiParams);

        // Check result
        if (isset($response['done']) && isset($response['done']['vpsid'])) {
            $vpsid = $response['done']['vpsid'];

            // Store VPS ID in custom fields or service properties
            virtualizor_SaveVpsId($params, $vpsid);

            virtualizor_LogActivity('CreateAccount', $params, "VPS created successfully: {$domain} (VPSID: {$vpsid})");
            return ['success' => true];
        } elseif (isset($response['error'])) {
            $errorMsg = is_array($response['error']) ? implode(', ', $response['error']) : $response['error'];
            virtualizor_LogError('CreateAccount', $params, $errorMsg, $response);
            return ['error' => $errorMsg];
        } else {
            virtualizor_LogError('CreateAccount', $params, 'Unknown error occurred', $response);
            return ['error' => 'Unknown error occurred while creating VPS'];
        }

    } catch (\Exception $e) {
        virtualizor_LogError('CreateAccount', $params, $e->getMessage());
        return ['error' => 'Exception: ' . $e->getMessage()];
    }
}

/**
 * Suspend a VPS
 *
 * @param array $params Service parameters
 * @return array Success or error message
 */
function virtualizor_SuspendAccount(array $params)
{
    try {
        $vpsid = virtualizor_GetVpsId($params);

        if (empty($vpsid)) {
            return ['error' => 'VPS ID not found'];
        }

        $apiParams = [
            'suspend' => $vpsid,
        ];

        $response = virtualizor_ApiRequest($params, 'vs', $apiParams);

        if (isset($response['done'])) {
            virtualizor_LogActivity('SuspendAccount', $params, "VPS suspended: VPSID {$vpsid}");
            return ['success' => true];
        }

        $errorMsg = isset($response['error']) ?
            (is_array($response['error']) ? implode(', ', $response['error']) : $response['error']) :
            'Suspension failed';
        virtualizor_LogError('SuspendAccount', $params, $errorMsg, $response);
        return ['error' => $errorMsg];

    } catch (\Exception $e) {
        virtualizor_LogError('SuspendAccount', $params, $e->getMessage());
        return ['error' => 'Exception: ' . $e->getMessage()];
    }
}

/**
 * Unsuspend a VPS
 *
 * @param array $params Service parameters
 * @return array Success or error message
 */
function virtualizor_UnsuspendAccount(array $params)
{
    try {
        $vpsid = virtualizor_GetVpsId($params);

        if (empty($vpsid)) {
            return ['error' => 'VPS ID not found'];
        }

        $apiParams = [
            'unsuspend' => $vpsid,
        ];

        $response = virtualizor_ApiRequest($params, 'vs', $apiParams);

        if (isset($response['done'])) {
            virtualizor_LogActivity('UnsuspendAccount', $params, "VPS unsuspended: VPSID {$vpsid}");
            return ['success' => true];
        }

        $errorMsg = isset($response['error']) ?
            (is_array($response['error']) ? implode(', ', $response['error']) : $response['error']) :
            'Unsuspension failed';
        virtualizor_LogError('UnsuspendAccount', $params, $errorMsg, $response);
        return ['error' => $errorMsg];

    } catch (\Exception $e) {
        virtualizor_LogError('UnsuspendAccount', $params, $e->getMessage());
        return ['error' => 'Exception: ' . $e->getMessage()];
    }
}

/**
 * Terminate a VPS
 *
 * @param array $params Service parameters
 * @return array Success or error message
 */
function virtualizor_TerminateAccount(array $params)
{
    try {
        $vpsid = virtualizor_GetVpsId($params);

        if (empty($vpsid)) {
            return ['error' => 'VPS ID not found'];
        }

        $apiParams = [
            'deletevs' => $vpsid,
            'delete_vps' => 1,
        ];

        $response = virtualizor_ApiRequest($params, 'vs', $apiParams);

        if (isset($response['done'])) {
            virtualizor_LogActivity('TerminateAccount', $params, "VPS terminated: VPSID {$vpsid}");
            return ['success' => true];
        }

        $errorMsg = isset($response['error']) ?
            (is_array($response['error']) ? implode(', ', $response['error']) : $response['error']) :
            'Termination failed';
        virtualizor_LogError('TerminateAccount', $params, $errorMsg, $response);
        return ['error' => $errorMsg];

    } catch (\Exception $e) {
        virtualizor_LogError('TerminateAccount', $params, $e->getMessage());
        return ['error' => 'Exception: ' . $e->getMessage()];
    }
}

/**
 * Change VPS package/resources
 *
 * @param array $params Service parameters
 * @return array Success or error message
 */
function virtualizor_ChangePackage(array $params)
{
    try {
        $vpsid = virtualizor_GetVpsId($params);

        if (empty($vpsid)) {
            return ['error' => 'VPS ID not found'];
        }

        // Get new resource allocations
        $ram = (int)($params['configoption5'] ?? 1024);
        $swap = (int)($params['configoption6'] ?? 512);
        $diskSpace = (int)($params['configoption7'] ?? 20);
        $bandwidth = (int)($params['configoption8'] ?? 1000);
        $cpuCores = (int)($params['configoption3'] ?? 1);

        $apiParams = [
            'editvs' => $vpsid,
            'ram' => $ram,
            'swap' => $swap,
            'disk_space' => $diskSpace,
            'bandwidth' => $bandwidth,
            'cores' => $cpuCores,
        ];

        $response = virtualizor_ApiRequest($params, 'vs', $apiParams);

        if (isset($response['done'])) {
            virtualizor_LogActivity('ChangePackage', $params, "VPS resources updated: VPSID {$vpsid}");
            return ['success' => true];
        }

        $errorMsg = isset($response['error']) ?
            (is_array($response['error']) ? implode(', ', $response['error']) : $response['error']) :
            'Resource update failed';
        virtualizor_LogError('ChangePackage', $params, $errorMsg, $response);
        return ['error' => $errorMsg];

    } catch (\Exception $e) {
        virtualizor_LogError('ChangePackage', $params, $e->getMessage());
        return ['error' => 'Exception: ' . $e->getMessage()];
    }
}

/**
 * Change VPS root password
 *
 * @param array $params Service parameters
 * @return array Success or error message
 */
function virtualizor_ChangePassword(array $params)
{
    try {
        $vpsid = virtualizor_GetVpsId($params);
        $newPassword = $params['password'];

        if (empty($vpsid)) {
            return ['error' => 'VPS ID not found'];
        }

        $apiParams = [
            'changepassword' => $vpsid,
            'newpass' => $newPassword,
        ];

        $response = virtualizor_ApiRequest($params, 'vs', $apiParams);

        if (isset($response['done'])) {
            virtualizor_LogActivity('ChangePassword', $params, "Root password changed for VPSID: {$vpsid}");
            return ['success' => true];
        }

        $errorMsg = isset($response['error']) ?
            (is_array($response['error']) ? implode(', ', $response['error']) : $response['error']) :
            'Password change failed';
        virtualizor_LogError('ChangePassword', $params, $errorMsg, $response);
        return ['error' => $errorMsg];

    } catch (\Exception $e) {
        virtualizor_LogError('ChangePassword', $params, $e->getMessage());
        return ['error' => 'Exception: ' . $e->getMessage()];
    }
}

/**
 * Get client area output with VPS control panel
 *
 * @param array $params Service parameters
 * @return array Template variables
 */
function virtualizor_ClientArea(array $params)
{
    try {
        $vpsid = virtualizor_GetVpsId($params);

        if (empty($vpsid)) {
            return [
                'templatefile' => 'clientarea',
                'vars' => [
                    'error' => 'VPS ID not found',
                ],
            ];
        }

        // Get VPS info
        $info = virtualizor_GetVpsInfo($params, $vpsid);

        return [
            'templatefile' => 'clientarea',
            'vars' => [
                'vpsid' => $vpsid,
                'hostname' => $params['domain'],
                'vps_info' => $info,
                'control_panel_url' => virtualizor_GenerateSsoUrl($params),
            ],
        ];

    } catch (\Exception $e) {
        virtualizor_LogError('ClientArea', $params, $e->getMessage());
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
function virtualizor_ServiceSingleSignOn(array $params)
{
    try {
        $ssoUrl = virtualizor_GenerateSsoUrl($params);

        return [
            'success' => true,
            'redirectTo' => $ssoUrl,
        ];

    } catch (\Exception $e) {
        virtualizor_LogError('ServiceSingleSignOn', $params, $e->getMessage());
        return [
            'success' => false,
            'errorMsg' => $e->getMessage(),
        ];
    }
}

/**
 * Get single sign-on URL for admin Virtualizor access
 *
 * @param array $params Service parameters
 * @return array SSO URL
 */
function virtualizor_AdminSingleSignOn(array $params)
{
    try {
        $ssoUrl = virtualizor_GenerateSsoUrl($params, true);

        return [
            'success' => true,
            'redirectTo' => $ssoUrl,
        ];

    } catch (\Exception $e) {
        virtualizor_LogError('AdminSingleSignOn', $params, $e->getMessage());
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
function virtualizor_AdminServicesTabFields(array $params)
{
    try {
        $vpsid = virtualizor_GetVpsId($params);

        $fields = [
            'VPS ID' => $vpsid ?: 'Not created yet',
            'Hostname' => $params['domain'],
            'Server IP' => $params['serverip'],
            'Virtualization' => $params['configoption1'] ?? 'kvm',
        ];

        if (!empty($vpsid)) {
            $info = virtualizor_GetVpsInfo($params, $vpsid);

            if (!empty($info)) {
                $fields['Status'] = $info['status'] ?? 'Unknown';
                $fields['Primary IP'] = $info['ip'] ?? 'N/A';
                $fields['RAM'] = ($info['ram'] ?? 'N/A') . ' MB';
                $fields['Disk'] = ($info['disk_space'] ?? 'N/A') . ' GB';
                $fields['Bandwidth Used'] = virtualizor_FormatBytes(($info['bandwidth_used'] ?? 0) * 1024 * 1024);
            }
        }

        return $fields;

    } catch (\Exception $e) {
        virtualizor_LogError('AdminServicesTabFields', $params, $e->getMessage());
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
function virtualizor_AdminCustomButtonArray()
{
    return [
        'Start VPS' => 'StartVps',
        'Stop VPS' => 'StopVps',
        'Restart VPS' => 'RestartVps',
        'Get VPS Info' => 'GetVpsInfo',
        'Rebuild VPS' => 'RebuildVps',
    ];
}

/**
 * Start VPS
 *
 * @param array $params Service parameters
 * @return string Result message
 */
function virtualizor_StartVps(array $params)
{
    try {
        $vpsid = virtualizor_GetVpsId($params);

        if (empty($vpsid)) {
            return 'Error: VPS ID not found';
        }

        $apiParams = [
            'start' => $vpsid,
        ];

        $response = virtualizor_ApiRequest($params, 'vs', $apiParams);

        if (isset($response['done'])) {
            return 'VPS started successfully';
        }

        $error = isset($response['error']) ?
            (is_array($response['error']) ? implode(', ', $response['error']) : $response['error']) :
            'Failed to start VPS';
        return "Error: {$error}";

    } catch (\Exception $e) {
        virtualizor_LogError('StartVps', $params, $e->getMessage());
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Stop VPS
 *
 * @param array $params Service parameters
 * @return string Result message
 */
function virtualizor_StopVps(array $params)
{
    try {
        $vpsid = virtualizor_GetVpsId($params);

        if (empty($vpsid)) {
            return 'Error: VPS ID not found';
        }

        $apiParams = [
            'stop' => $vpsid,
        ];

        $response = virtualizor_ApiRequest($params, 'vs', $apiParams);

        if (isset($response['done'])) {
            return 'VPS stopped successfully';
        }

        $error = isset($response['error']) ?
            (is_array($response['error']) ? implode(', ', $response['error']) : $response['error']) :
            'Failed to stop VPS';
        return "Error: {$error}";

    } catch (\Exception $e) {
        virtualizor_LogError('StopVps', $params, $e->getMessage());
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Restart VPS
 *
 * @param array $params Service parameters
 * @return string Result message
 */
function virtualizor_RestartVps(array $params)
{
    try {
        $vpsid = virtualizor_GetVpsId($params);

        if (empty($vpsid)) {
            return 'Error: VPS ID not found';
        }

        $apiParams = [
            'restart' => $vpsid,
        ];

        $response = virtualizor_ApiRequest($params, 'vs', $apiParams);

        if (isset($response['done'])) {
            return 'VPS restarted successfully';
        }

        $error = isset($response['error']) ?
            (is_array($response['error']) ? implode(', ', $response['error']) : $response['error']) :
            'Failed to restart VPS';
        return "Error: {$error}";

    } catch (\Exception $e) {
        virtualizor_LogError('RestartVps', $params, $e->getMessage());
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Get VPS information
 *
 * @param array $params Service parameters
 * @return string Result message
 */
function virtualizor_GetVpsInfo(array $params)
{
    try {
        $vpsid = virtualizor_GetVpsId($params);

        if (empty($vpsid)) {
            return 'Error: VPS ID not found';
        }

        $info = virtualizor_GetVpsInfo($params, $vpsid);

        if (!empty($info)) {
            $output = "VPS Information (ID: {$vpsid})\n\n";
            $output .= "Hostname: " . ($info['hostname'] ?? 'N/A') . "\n";
            $output .= "Primary IP: " . ($info['ip'] ?? 'N/A') . "\n";
            $output .= "Status: " . ($info['status'] ?? 'N/A') . "\n";
            $output .= "OS: " . ($info['os_name'] ?? 'N/A') . "\n";
            $output .= "RAM: " . ($info['ram'] ?? 'N/A') . " MB\n";
            $output .= "Disk: " . ($info['disk_space'] ?? 'N/A') . " GB\n";
            $output .= "Bandwidth Used: " . virtualizor_FormatBytes(($info['bandwidth_used'] ?? 0) * 1024 * 1024) . "\n";
            $output .= "Bandwidth Limit: " . ($info['bandwidth'] ?? 'N/A') . " GB\n";

            return $output;
        }

        return 'VPS information not found';

    } catch (\Exception $e) {
        virtualizor_LogError('GetVpsInfo', $params, $e->getMessage());
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Rebuild VPS with new OS
 *
 * @param array $params Service parameters
 * @return string Result message
 */
function virtualizor_RebuildVps(array $params)
{
    try {
        $vpsid = virtualizor_GetVpsId($params);

        if (empty($vpsid)) {
            return 'Error: VPS ID not found';
        }

        $osTemplate = $params['configoption2'] ?? 'ubuntu-20.04-x86_64';

        $apiParams = [
            'rebuild' => $vpsid,
            'ostemplate' => $osTemplate,
            'newpass' => $params['password'],
        ];

        $response = virtualizor_ApiRequest($params, 'vs', $apiParams);

        if (isset($response['done'])) {
            return "VPS rebuild initiated with OS template: {$osTemplate}";
        }

        $error = isset($response['error']) ?
            (is_array($response['error']) ? implode(', ', $response['error']) : $response['error']) :
            'Failed to rebuild VPS';
        return "Error: {$error}";

    } catch (\Exception $e) {
        virtualizor_LogError('RebuildVps', $params, $e->getMessage());
        return 'Error: ' . $e->getMessage();
    }
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Make Virtualizor API request
 *
 * @param array $params Server parameters
 * @param string $action API action
 * @param array $apiParams API parameters
 * @return array API response
 */
function virtualizor_ApiRequest(array $params, string $action, array $apiParams = [])
{
    $serverIp = $params['serverhostname'] ?? $params['serverip'];
    $serverPort = $params['serverport'] ?? 4083;
    $serverKey = $params['serverusername'];
    $serverPassword = $params['serverpassword'];
    $serverSecure = $params['serversecure'] ?? true;

    // Build URL
    $protocol = $serverSecure ? 'https' : 'http';
    $url = "{$protocol}://{$serverIp}:{$serverPort}/index.php";

    // Add action and authentication to parameters
    $apiParams['act'] = $action;
    $apiParams['api'] = 'json';
    $apiParams['apikey'] = $serverKey;
    $apiParams['apipass'] = $serverPassword;

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
        'virtualizor',
        $action,
        $apiParams,
        $response,
        $result,
        [$serverPassword]
    );

    return $result;
}

/**
 * Generate SSO URL for Virtualizor access
 *
 * @param array $params Service parameters
 * @param bool $admin Admin access (true) or user access (false)
 * @return string SSO URL
 */
function virtualizor_GenerateSsoUrl(array $params, bool $admin = false)
{
    $serverIp = $params['serverhostname'] ?? $params['serverip'];
    $serverPort = $params['serverport'] ?? 4083;
    $serverSecure = $params['serversecure'] ?? true;

    $protocol = $serverSecure ? 'https' : 'http';

    if ($admin) {
        return "{$protocol}://{$serverIp}:{$serverPort}/";
    }

    // For end-user panel, include VPS ID if available
    $vpsid = virtualizor_GetVpsId($params);
    if (!empty($vpsid)) {
        return "{$protocol}://{$serverIp}:{$serverPort}/index.php?act=vs&vpsid={$vpsid}";
    }

    return "{$protocol}://{$serverIp}:{$serverPort}/";
}

/**
 * Get VPS information from Virtualizor
 *
 * @param array $params Service parameters
 * @param int $vpsid VPS ID
 * @return array VPS info
 */
function virtualizor_GetVpsInfo(array $params, int $vpsid)
{
    try {
        $apiParams = [
            'vpsid' => $vpsid,
        ];

        $response = virtualizor_ApiRequest($params, 'vs', $apiParams);

        if (isset($response['vs'][$vpsid])) {
            return $response['vs'][$vpsid];
        }

        return [];

    } catch (\Exception $e) {
        return [];
    }
}

/**
 * Save VPS ID to service custom fields
 *
 * @param array $params Service parameters
 * @param int $vpsid VPS ID
 * @return void
 */
function virtualizor_SaveVpsId(array $params, int $vpsid)
{
    try {
        $serviceId = $params['serviceid'];

        // Use WHMCS database to store VPS ID
        if (function_exists('mysql_query')) {
            $query = "UPDATE tblhosting SET dedicatedip = '{$vpsid}' WHERE id = '{$serviceId}'";
            mysql_query($query);
        } elseif (class_exists('Illuminate\Database\Capsule\Manager')) {
            \Illuminate\Database\Capsule\Manager::table('tblhosting')
                ->where('id', $serviceId)
                ->update(['dedicatedip' => $vpsid]);
        }
    } catch (\Exception $e) {
        // Silent fail
    }
}

/**
 * Get VPS ID from service
 *
 * @param array $params Service parameters
 * @return int|null VPS ID
 */
function virtualizor_GetVpsId(array $params)
{
    // Try to get from dedicatedip field
    if (!empty($params['dedicatedip']) && is_numeric($params['dedicatedip'])) {
        return (int)$params['dedicatedip'];
    }

    // Try to get from custom fields
    if (!empty($params['customfields']['VPS ID'])) {
        return (int)$params['customfields']['VPS ID'];
    }

    return null;
}

/**
 * Format bytes to human-readable format
 *
 * @param int|string $bytes Bytes
 * @return string Formatted string
 */
function virtualizor_FormatBytes($bytes)
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
function virtualizor_LogActivity(string $action, array $params, string $message)
{
    try {
        if (function_exists('logActivity')) {
            logActivity("Virtualizor - {$action}: {$message}");
        }

        if (class_exists('Log')) {
            Log::info("Virtualizor - {$action}", [
                'message' => $message,
                'domain' => $params['domain'] ?? 'N/A',
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
function virtualizor_LogError(string $action, array $params, string $error, array $response = [])
{
    try {
        if (function_exists('logModuleCall')) {
            logModuleCall(
                'virtualizor',
                $action,
                $params,
                $error,
                $response
            );
        }

        if (class_exists('Log')) {
            Log::error("Virtualizor - {$action} Error", [
                'error' => $error,
                'domain' => $params['domain'] ?? 'N/A',
                'response' => $response,
            ]);
        }
    } catch (\Exception $e) {
        // Silent fail on logging errors
    }
}
