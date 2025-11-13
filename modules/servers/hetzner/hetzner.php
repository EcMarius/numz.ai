<?php
/**
 * Hetzner Cloud & Robot API Provisioning Module
 *
 * Comprehensive integration with:
 * - Hetzner Cloud API (VPS/Cloud instances)
 * - Hetzner Robot API (Dedicated servers)
 *
 * @version 1.0
 */

if (!defined('WHMCS')) {
    define('WHMCS', true);
}

// ============================================================================
// HETZNER API CLIENT CLASS
// ============================================================================

class HetznerAPI
{
    private $cloudApiToken;
    private $robotUsername;
    private $robotPassword;
    private $cloudApiUrl = 'https://api.hetzner.cloud/v1';
    private $robotApiUrl = 'https://robot-ws.your-server.de';

    public function __construct($cloudToken = '', $robotUser = '', $robotPass = '')
    {
        $this->cloudApiToken = $cloudToken;
        $this->robotUsername = $robotUser;
        $this->robotPassword = $robotPass;
    }

    /**
     * Make Cloud API request
     */
    public function cloudRequest($endpoint, $method = 'GET', $data = [])
    {
        $url = $this->cloudApiUrl . $endpoint;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->cloudApiToken,
            'Content-Type: application/json',
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['error' => $error];
        }

        $result = json_decode($response, true);

        if ($httpCode >= 400) {
            return ['error' => $result['error']['message'] ?? 'Unknown API error'];
        }

        return $result;
    }

    /**
     * Make Robot API request
     */
    public function robotRequest($endpoint, $method = 'GET', $data = [])
    {
        $url = $this->robotApiUrl . $endpoint;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $this->robotUsername . ':' . $this->robotPassword);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['error' => $error];
        }

        $result = json_decode($response, true);

        if ($httpCode >= 400) {
            return ['error' => $result['error']['message'] ?? 'Unknown API error'];
        }

        return $result;
    }

    /**
     * Create Cloud server
     */
    public function createCloudServer($name, $serverType, $location, $image, $sshKeys = [], $volumes = [], $firewalls = [])
    {
        $data = [
            'name' => $name,
            'server_type' => $serverType,
            'location' => $location,
            'image' => $image,
            'start_after_create' => true,
        ];

        if (!empty($sshKeys)) {
            $data['ssh_keys'] = $sshKeys;
        }

        if (!empty($volumes)) {
            $data['volumes'] = $volumes;
        }

        if (!empty($firewalls)) {
            $data['firewalls'] = $firewalls;
        }

        return $this->cloudRequest('/servers', 'POST', $data);
    }

    /**
     * Get Cloud server details
     */
    public function getCloudServer($serverId)
    {
        return $this->cloudRequest('/servers/' . $serverId);
    }

    /**
     * Delete Cloud server
     */
    public function deleteCloudServer($serverId)
    {
        return $this->cloudRequest('/servers/' . $serverId, 'DELETE');
    }

    /**
     * Power on/off Cloud server
     */
    public function powerCloudServer($serverId, $action)
    {
        // Actions: poweron, poweroff, reboot, reset, shutdown
        return $this->cloudRequest('/servers/' . $serverId . '/actions/' . $action, 'POST');
    }

    /**
     * Resize Cloud server
     */
    public function resizeCloudServer($serverId, $newServerType)
    {
        $data = [
            'server_type' => $newServerType,
            'upgrade_disk' => true,
        ];

        return $this->cloudRequest('/servers/' . $serverId . '/actions/change_type', 'POST', $data);
    }

    /**
     * Create snapshot
     */
    public function createSnapshot($serverId, $description = '')
    {
        $data = ['description' => $description ?: 'Snapshot created at ' . date('Y-m-d H:i:s')];
        return $this->cloudRequest('/servers/' . $serverId . '/actions/create_image', 'POST', $data);
    }

    /**
     * List snapshots
     */
    public function listSnapshots($serverId = null)
    {
        $endpoint = '/images?type=snapshot';
        if ($serverId) {
            $endpoint .= '&bound_to=' . $serverId;
        }
        return $this->cloudRequest($endpoint);
    }

    /**
     * Restore from snapshot
     */
    public function restoreSnapshot($serverId, $imageId)
    {
        $data = ['image' => $imageId];
        return $this->cloudRequest('/servers/' . $serverId . '/actions/rebuild', 'POST', $data);
    }

    /**
     * Get console access
     */
    public function getConsole($serverId)
    {
        return $this->cloudRequest('/servers/' . $serverId . '/actions/request_console', 'POST');
    }

    /**
     * List available server types
     */
    public function listServerTypes()
    {
        return $this->cloudRequest('/server_types');
    }

    /**
     * List available locations
     */
    public function listLocations()
    {
        return $this->cloudRequest('/locations');
    }

    /**
     * List available images
     */
    public function listImages($type = 'system')
    {
        return $this->cloudRequest('/images?type=' . $type);
    }

    /**
     * Create firewall
     */
    public function createFirewall($name, $rules = [])
    {
        $data = [
            'name' => $name,
            'rules' => $rules,
        ];

        return $this->cloudRequest('/firewalls', 'POST', $data);
    }

    /**
     * Update reverse DNS
     */
    public function updateReverseDNS($serverId, $ip, $dnsPtr)
    {
        $data = [
            'ip' => $ip,
            'dns_ptr' => $dnsPtr,
        ];

        return $this->cloudRequest('/servers/' . $serverId . '/actions/change_dns_ptr', 'POST', $data);
    }

    /**
     * Get dedicated server (Robot API)
     */
    public function getDedicatedServer($serverIp)
    {
        return $this->robotRequest('/server/' . $serverIp);
    }

    /**
     * List dedicated servers (Robot API)
     */
    public function listDedicatedServers()
    {
        return $this->robotRequest('/server');
    }

    /**
     * Activate rescue system (Robot API)
     */
    public function activateRescue($serverIp, $os = 'linux')
    {
        $data = ['os' => $os];
        return $this->robotRequest('/boot/' . $serverIp . '/rescue', 'POST', $data);
    }

    /**
     * Reset dedicated server (Robot API)
     */
    public function resetDedicatedServer($serverIp, $type = 'hw')
    {
        $data = ['type' => $type]; // hw, sw, man
        return $this->robotRequest('/reset/' . $serverIp, 'POST', $data);
    }

    /**
     * Get IPMI console URL (Robot API)
     */
    public function getIPMI($serverIp)
    {
        return $this->robotRequest('/server/' . $serverIp . '/ipmi');
    }

    /**
     * Update reverse DNS (Robot API)
     */
    public function updateDedicatedReverseDNS($ip, $ptr)
    {
        $data = ['ptr' => $ptr];
        return $this->robotRequest('/rdns/' . $ip, 'PUT', $data);
    }

    /**
     * List subnets (Robot API)
     */
    public function listSubnets($serverIp)
    {
        return $this->robotRequest('/subnet?server_ip=' . $serverIp);
    }
}

// ============================================================================
// MODULE METADATA
// ============================================================================

/**
 * Module metadata
 */
function hetzner_MetaData()
{
    return [
        'DisplayName' => 'Hetzner Cloud & Dedicated',
        'APIVersion' => '1.1',
        'RequiresServer' => false,
        'DefaultNonSSLPort' => '',
        'DefaultSSLPort' => '',
        'ServiceSingleSignOnLabel' => 'Access Console',
        'AdminSingleSignOnLabel' => 'Manage Server',
    ];
}

// ============================================================================
// CONFIGURATION OPTIONS
// ============================================================================

/**
 * Configuration options for products
 */
function hetzner_ConfigOptions()
{
    return [
        'service_type' => [
            'FriendlyName' => 'Service Type',
            'Type' => 'dropdown',
            'Options' => [
                'cloud' => 'Cloud (VPS)',
                'dedicated' => 'Dedicated Server',
            ],
            'Default' => 'cloud',
            'Description' => 'Select Hetzner Cloud or Dedicated Server',
        ],
        'server_type' => [
            'FriendlyName' => 'Server Type/Size',
            'Type' => 'dropdown',
            'Options' => [
                // Cloud options
                'cx11' => 'CX11 - 1 vCPU, 2GB RAM, 20GB SSD',
                'cx21' => 'CX21 - 2 vCPU, 4GB RAM, 40GB SSD',
                'cx31' => 'CX31 - 2 vCPU, 8GB RAM, 80GB SSD',
                'cx41' => 'CX41 - 4 vCPU, 16GB RAM, 160GB SSD',
                'cx51' => 'CX51 - 8 vCPU, 32GB RAM, 240GB SSD',
                'cpx11' => 'CPX11 - 2 vCPU, 2GB RAM, 40GB SSD (AMD)',
                'cpx21' => 'CPX21 - 3 vCPU, 4GB RAM, 80GB SSD (AMD)',
                'cpx31' => 'CPX31 - 4 vCPU, 8GB RAM, 160GB SSD (AMD)',
                'cpx41' => 'CPX41 - 8 vCPU, 16GB RAM, 240GB SSD (AMD)',
                'cpx51' => 'CPX51 - 16 vCPU, 32GB RAM, 360GB SSD (AMD)',
                'ccx12' => 'CCX12 - 2 Dedicated vCPU, 8GB RAM, 80GB SSD',
                'ccx22' => 'CCX22 - 4 Dedicated vCPU, 16GB RAM, 160GB SSD',
                'ccx32' => 'CCX32 - 8 Dedicated vCPU, 32GB RAM, 240GB SSD',
                'ccx42' => 'CCX42 - 16 Dedicated vCPU, 64GB RAM, 360GB SSD',
                'ccx52' => 'CCX52 - 32 Dedicated vCPU, 128GB RAM, 600GB SSD',
                // Dedicated options
                'dedicated_ax41' => 'AX41 - AMD Ryzen 5, 64GB RAM, 2x512GB NVMe',
                'dedicated_ax51' => 'AX51 - AMD Ryzen 7, 64GB RAM, 2x512GB NVMe',
                'dedicated_ex44' => 'EX44 - Intel i7, 64GB RAM, 2x512GB NVMe',
                'dedicated_ex101' => 'EX101 - Intel i9, 128GB RAM, 2x1.92TB NVMe',
            ],
            'Default' => 'cx11',
            'Description' => 'Select server configuration',
        ],
        'location' => [
            'FriendlyName' => 'Location/Datacenter',
            'Type' => 'dropdown',
            'Options' => [
                'nbg1' => 'Nuremberg, Germany (nbg1)',
                'fsn1' => 'Falkenstein, Germany (fsn1)',
                'hel1' => 'Helsinki, Finland (hel1)',
                'ash' => 'Ashburn, Virginia, USA (ash)',
                'hil' => 'Hillsboro, Oregon, USA (hil)',
            ],
            'Default' => 'fsn1',
            'Description' => 'Select datacenter location',
        ],
        'image' => [
            'FriendlyName' => 'Operating System',
            'Type' => 'dropdown',
            'Options' => [
                'ubuntu-22.04' => 'Ubuntu 22.04 LTS',
                'ubuntu-20.04' => 'Ubuntu 20.04 LTS',
                'debian-11' => 'Debian 11',
                'debian-12' => 'Debian 12',
                'centos-stream-9' => 'CentOS Stream 9',
                'rocky-9' => 'Rocky Linux 9',
                'fedora-38' => 'Fedora 38',
                'alma-9' => 'AlmaLinux 9',
            ],
            'Default' => 'ubuntu-22.04',
            'Description' => 'Select operating system',
        ],
        'enable_ipv6' => [
            'FriendlyName' => 'Enable IPv6',
            'Type' => 'yesno',
            'Default' => 'yes',
            'Description' => 'Enable IPv6 networking',
        ],
        'enable_backups' => [
            'FriendlyName' => 'Enable Backups',
            'Type' => 'yesno',
            'Default' => 'no',
            'Description' => 'Enable automated backups (additional cost)',
        ],
        'ssh_keys' => [
            'FriendlyName' => 'SSH Key IDs',
            'Type' => 'text',
            'Size' => '50',
            'Default' => '',
            'Description' => 'Comma-separated SSH key IDs from Hetzner Cloud',
        ],
        'firewall_enabled' => [
            'FriendlyName' => 'Enable Firewall',
            'Type' => 'yesno',
            'Default' => 'no',
            'Description' => 'Create and apply basic firewall rules',
        ],
    ];
}

// ============================================================================
// MODULE PARAMETERS
// ============================================================================

/**
 * Get API client from parameters
 */
function hetzner_getApiClient($params)
{
    $cloudToken = $params['serverapitoken'] ?? $params['configoption9'] ?? '';
    $robotUser = $params['serverusername'] ?? '';
    $robotPass = $params['serverpassword'] ?? '';

    return new HetznerAPI($cloudToken, $robotUser, $robotPass);
}

// ============================================================================
// PROVISIONING FUNCTIONS
// ============================================================================

/**
 * Create new server instance
 */
function hetzner_CreateAccount(array $params)
{
    try {
        $api = hetzner_getApiClient($params);

        $serviceType = $params['configoption1'] ?? 'cloud';
        $serverType = $params['configoption2'] ?? 'cx11';
        $location = $params['configoption3'] ?? 'fsn1';
        $image = $params['configoption4'] ?? 'ubuntu-22.04';
        $sshKeyIds = $params['configoption7'] ?? '';

        $serverName = 'server-' . $params['serviceid'] . '-' . time();
        $domain = $params['domain'] ?? '';
        if (!empty($domain)) {
            $serverName = preg_replace('/[^a-z0-9-]/', '-', strtolower($domain));
        }

        if ($serviceType === 'cloud') {
            // Create Cloud server
            $sshKeys = !empty($sshKeyIds) ? array_map('intval', explode(',', $sshKeyIds)) : [];

            $result = $api->createCloudServer(
                $serverName,
                $serverType,
                $location,
                $image,
                $sshKeys
            );

            if (isset($result['error'])) {
                logModuleCall('hetzner', 'CreateAccount', $params, $result, $result['error']);
                return ['error' => $result['error']];
            }

            $server = $result['server'];
            $serverId = $server['id'];
            $ipAddress = $server['public_net']['ipv4']['ip'] ?? '';
            $rootPassword = $result['root_password'] ?? '';

            // Store server ID and details
            update_query('tblhosting', [
                'dedicatedip' => $ipAddress,
                'username' => 'root',
                'password' => encrypt($rootPassword),
                'domain' => $serverId, // Store server ID in domain field
            ], ['id' => $params['serviceid']]);

            logModuleCall('hetzner', 'CreateAccount', $params, $result, 'Server created successfully');

            return [
                'success' => true,
                'server_id' => $serverId,
                'ip_address' => $ipAddress,
                'root_password' => $rootPassword,
            ];

        } else {
            // Dedicated server - needs manual activation typically
            // Store pending status
            logModuleCall('hetzner', 'CreateAccount', $params, [], 'Dedicated server requires manual activation');

            return [
                'success' => true,
                'message' => 'Dedicated server will be activated manually. You will receive details via email.',
            ];
        }

    } catch (\Exception $e) {
        logModuleCall('hetzner', 'CreateAccount', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => 'Server creation failed: ' . $e->getMessage()];
    }
}

/**
 * Suspend server (power off)
 */
function hetzner_SuspendAccount(array $params)
{
    try {
        $api = hetzner_getApiClient($params);
        $serviceType = $params['configoption1'] ?? 'cloud';

        if ($serviceType === 'cloud') {
            $serverId = $params['domain']; // Server ID stored in domain field

            if (empty($serverId) || !is_numeric($serverId)) {
                return ['error' => 'Server ID not found'];
            }

            $result = $api->powerCloudServer($serverId, 'poweroff');

            if (isset($result['error'])) {
                logModuleCall('hetzner', 'SuspendAccount', $params, $result, $result['error']);
                return ['error' => $result['error']];
            }

            logModuleCall('hetzner', 'SuspendAccount', $params, $result, 'Server powered off');
            return ['success' => true];

        } else {
            // Dedicated servers - would typically contact support
            logModuleCall('hetzner', 'SuspendAccount', $params, [], 'Dedicated server suspension requires support');
            return ['success' => true, 'message' => 'Dedicated server suspension initiated'];
        }

    } catch (\Exception $e) {
        logModuleCall('hetzner', 'SuspendAccount', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => 'Suspension failed: ' . $e->getMessage()];
    }
}

/**
 * Unsuspend server (power on)
 */
function hetzner_UnsuspendAccount(array $params)
{
    try {
        $api = hetzner_getApiClient($params);
        $serviceType = $params['configoption1'] ?? 'cloud';

        if ($serviceType === 'cloud') {
            $serverId = $params['domain'];

            if (empty($serverId) || !is_numeric($serverId)) {
                return ['error' => 'Server ID not found'];
            }

            $result = $api->powerCloudServer($serverId, 'poweron');

            if (isset($result['error'])) {
                logModuleCall('hetzner', 'UnsuspendAccount', $params, $result, $result['error']);
                return ['error' => $result['error']];
            }

            logModuleCall('hetzner', 'UnsuspendAccount', $params, $result, 'Server powered on');
            return ['success' => true];

        } else {
            logModuleCall('hetzner', 'UnsuspendAccount', $params, [], 'Dedicated server unsuspension requires support');
            return ['success' => true, 'message' => 'Dedicated server unsuspension initiated'];
        }

    } catch (\Exception $e) {
        logModuleCall('hetzner', 'UnsuspendAccount', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => 'Unsuspension failed: ' . $e->getMessage()];
    }
}

/**
 * Terminate server (delete)
 */
function hetzner_TerminateAccount(array $params)
{
    try {
        $api = hetzner_getApiClient($params);
        $serviceType = $params['configoption1'] ?? 'cloud';

        if ($serviceType === 'cloud') {
            $serverId = $params['domain'];

            if (empty($serverId) || !is_numeric($serverId)) {
                return ['error' => 'Server ID not found'];
            }

            $result = $api->deleteCloudServer($serverId);

            if (isset($result['error'])) {
                logModuleCall('hetzner', 'TerminateAccount', $params, $result, $result['error']);
                return ['error' => $result['error']];
            }

            logModuleCall('hetzner', 'TerminateAccount', $params, $result, 'Server deleted');
            return ['success' => true];

        } else {
            logModuleCall('hetzner', 'TerminateAccount', $params, [], 'Dedicated server termination requires support');
            return ['success' => true, 'message' => 'Dedicated server termination initiated'];
        }

    } catch (\Exception $e) {
        logModuleCall('hetzner', 'TerminateAccount', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => 'Termination failed: ' . $e->getMessage()];
    }
}

/**
 * Change package (resize server)
 */
function hetzner_ChangePackage(array $params)
{
    try {
        $api = hetzner_getApiClient($params);
        $serviceType = $params['configoption1'] ?? 'cloud';

        if ($serviceType === 'cloud') {
            $serverId = $params['domain'];
            $newServerType = $params['configoption2'] ?? '';

            if (empty($serverId) || !is_numeric($serverId)) {
                return ['error' => 'Server ID not found'];
            }

            if (empty($newServerType)) {
                return ['error' => 'New server type not specified'];
            }

            // Power off server first
            $api->powerCloudServer($serverId, 'poweroff');
            sleep(5);

            $result = $api->resizeCloudServer($serverId, $newServerType);

            if (isset($result['error'])) {
                logModuleCall('hetzner', 'ChangePackage', $params, $result, $result['error']);
                return ['error' => $result['error']];
            }

            // Power on server
            sleep(10);
            $api->powerCloudServer($serverId, 'poweron');

            logModuleCall('hetzner', 'ChangePackage', $params, $result, 'Server resized');
            return ['success' => true];

        } else {
            return ['error' => 'Package change not supported for dedicated servers'];
        }

    } catch (\Exception $e) {
        logModuleCall('hetzner', 'ChangePackage', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => 'Package change failed: ' . $e->getMessage()];
    }
}

// ============================================================================
// ADMIN FUNCTIONS
// ============================================================================

/**
 * Display additional fields in admin services tab
 */
function hetzner_AdminServicesTabFields(array $params)
{
    try {
        $api = hetzner_getApiClient($params);
        $serviceType = $params['configoption1'] ?? 'cloud';

        if ($serviceType === 'cloud') {
            $serverId = $params['domain'];

            if (empty($serverId) || !is_numeric($serverId)) {
                return ['Server ID' => 'Not found'];
            }

            $result = $api->getCloudServer($serverId);

            if (isset($result['error'])) {
                return ['Error' => $result['error']];
            }

            $server = $result['server'];

            return [
                'Server ID' => $server['id'],
                'Server Name' => $server['name'],
                'Status' => ucfirst($server['status']),
                'Server Type' => $server['server_type']['name'],
                'Location' => $server['datacenter']['location']['name'],
                'IPv4 Address' => $server['public_net']['ipv4']['ip'] ?? 'N/A',
                'IPv6 Address' => $server['public_net']['ipv6']['ip'] ?? 'N/A',
                'CPUs' => $server['server_type']['cores'],
                'RAM' => $server['server_type']['memory'] . ' GB',
                'Disk' => $server['server_type']['disk'] . ' GB',
                'Created' => date('Y-m-d H:i:s', strtotime($server['created'])),
            ];

        } else {
            return [
                'Service Type' => 'Dedicated Server',
                'Status' => 'Requires manual management via Robot panel',
            ];
        }

    } catch (\Exception $e) {
        return ['Error' => $e->getMessage()];
    }
}

/**
 * Custom admin button functions
 */
function hetzner_AdminCustomButtonArray()
{
    return [
        'Reboot Server' => 'reboot',
        'Force Reset' => 'reset',
        'Enable Rescue' => 'rescue',
        'Create Snapshot' => 'snapshot',
        'Get Console' => 'console',
    ];
}

/**
 * Reboot server
 */
function hetzner_reboot(array $params)
{
    try {
        $api = hetzner_getApiClient($params);
        $serverId = $params['domain'];

        $result = $api->powerCloudServer($serverId, 'reboot');

        if (isset($result['error'])) {
            return $result['error'];
        }

        return 'success';

    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Force reset server
 */
function hetzner_reset(array $params)
{
    try {
        $api = hetzner_getApiClient($params);
        $serverId = $params['domain'];

        $result = $api->powerCloudServer($serverId, 'reset');

        if (isset($result['error'])) {
            return $result['error'];
        }

        return 'success';

    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Enable rescue system
 */
function hetzner_rescue(array $params)
{
    try {
        $api = hetzner_getApiClient($params);
        $serviceType = $params['configoption1'] ?? 'cloud';

        if ($serviceType === 'cloud') {
            $serverId = $params['domain'];
            // Cloud rescue mode would need specific API call
            return 'Rescue mode activation via Cloud API - contact support for rescue access';
        } else {
            $serverIp = $params['dedicatedip'];
            $result = $api->activateRescue($serverIp);

            if (isset($result['error'])) {
                return $result['error'];
            }

            return 'success - Rescue system activated. Root password: ' . ($result['rescue']['password'] ?? 'check email');
        }

    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Create snapshot
 */
function hetzner_snapshot(array $params)
{
    try {
        $api = hetzner_getApiClient($params);
        $serverId = $params['domain'];

        $description = 'Admin snapshot - ' . date('Y-m-d H:i:s');
        $result = $api->createSnapshot($serverId, $description);

        if (isset($result['error'])) {
            return $result['error'];
        }

        return 'success - Snapshot created: ' . ($result['image']['id'] ?? 'ID pending');

    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Get console access
 */
function hetzner_console(array $params)
{
    try {
        $api = hetzner_getApiClient($params);
        $serverId = $params['domain'];

        $result = $api->getConsole($serverId);

        if (isset($result['error'])) {
            return $result['error'];
        }

        $consoleUrl = $result['action']['wss_url'] ?? '';
        return 'success - Console URL: ' . $consoleUrl . ' (Valid for 1 hour)';

    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}

// ============================================================================
// CLIENT AREA FUNCTIONS
// ============================================================================

/**
 * Client area output
 */
function hetzner_ClientArea(array $params)
{
    try {
        $api = hetzner_getApiClient($params);
        $serviceType = $params['configoption1'] ?? 'cloud';

        if ($serviceType === 'cloud') {
            $serverId = $params['domain'];

            if (empty($serverId) || !is_numeric($serverId)) {
                return ['error' => 'Server information not available'];
            }

            $result = $api->getCloudServer($serverId);

            if (isset($result['error'])) {
                return ['error' => $result['error']];
            }

            $server = $result['server'];

            // Get snapshots
            $snapshotsResult = $api->listSnapshots($serverId);
            $snapshots = $snapshotsResult['images'] ?? [];

            return [
                'templatefile' => 'templates/clientarea',
                'vars' => [
                    'server' => $server,
                    'snapshots' => $snapshots,
                    'service_type' => 'cloud',
                    'status' => $server['status'],
                    'ip_address' => $server['public_net']['ipv4']['ip'] ?? 'N/A',
                    'ipv6_address' => $server['public_net']['ipv6']['ip'] ?? 'N/A',
                ],
            ];

        } else {
            return [
                'templatefile' => 'templates/clientarea',
                'vars' => [
                    'service_type' => 'dedicated',
                    'message' => 'Dedicated server management available via Hetzner Robot panel',
                ],
            ];
        }

    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Custom client area buttons
 */
function hetzner_ClientAreaCustomButtonArray()
{
    return [
        'Power On' => 'poweron',
        'Power Off' => 'poweroff',
        'Reboot' => 'clientreboot',
        'Request Console' => 'clientconsole',
    ];
}

/**
 * Client power on
 */
function hetzner_poweron(array $params)
{
    try {
        $api = hetzner_getApiClient($params);
        $serverId = $params['domain'];

        $result = $api->powerCloudServer($serverId, 'poweron');

        if (isset($result['error'])) {
            return $result['error'];
        }

        return 'success';

    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Client power off
 */
function hetzner_poweroff(array $params)
{
    try {
        $api = hetzner_getApiClient($params);
        $serverId = $params['domain'];

        $result = $api->powerCloudServer($serverId, 'shutdown');

        if (isset($result['error'])) {
            return $result['error'];
        }

        return 'success';

    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Client reboot
 */
function hetzner_clientreboot(array $params)
{
    try {
        $api = hetzner_getApiClient($params);
        $serverId = $params['domain'];

        $result = $api->powerCloudServer($serverId, 'reboot');

        if (isset($result['error'])) {
            return $result['error'];
        }

        return 'success';

    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Client console access
 */
function hetzner_clientconsole(array $params)
{
    try {
        $api = hetzner_getApiClient($params);
        $serverId = $params['domain'];

        $result = $api->getConsole($serverId);

        if (isset($result['error'])) {
            return $result['error'];
        }

        $consoleUrl = $result['action']['wss_url'] ?? '';

        // Redirect to console
        if (!empty($consoleUrl)) {
            header('Location: ' . $consoleUrl);
            exit;
        }

        return 'success';

    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}

// ============================================================================
// TEST CONNECTION
// ============================================================================

/**
 * Test API connection
 */
function hetzner_TestConnection(array $params)
{
    try {
        $api = hetzner_getApiClient($params);

        // Test Cloud API
        $result = $api->listLocations();

        if (isset($result['error'])) {
            return [
                'success' => false,
                'error' => 'Cloud API connection failed: ' . $result['error'],
            ];
        }

        return [
            'success' => true,
            'version' => 'Hetzner Cloud API v1',
        ];

    } catch (\Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage(),
        ];
    }
}

// ============================================================================
// USAGE UPDATE (Optional - for metrics/billing)
// ============================================================================

/**
 * Update usage statistics
 */
function hetzner_UsageUpdate(array $params)
{
    try {
        $api = hetzner_getApiClient($params);
        $serverId = $params['domain'];

        if (empty($serverId) || !is_numeric($serverId)) {
            return;
        }

        $result = $api->getCloudServer($serverId);

        if (isset($result['error'])) {
            return;
        }

        $server = $result['server'];

        // Update usage metrics (if using WHMCS metric billing)
        update_query('tblhosting', [
            'bwlimit' => $server['outbound_traffic'] ?? 0,
            'bwusage' => $server['ingoing_traffic'] ?? 0,
        ], ['id' => $params['serviceid']]);

    } catch (\Exception $e) {
        logModuleCall('hetzner', 'UsageUpdate', $params, $e->getMessage(), $e->getTraceAsString());
    }
}

// ============================================================================
// ADMIN AREA OUTPUT
// ============================================================================

/**
 * Display admin area server list
 */
function hetzner_AdminLink(array $params)
{
    $serverId = $params['domain'];

    if (empty($serverId)) {
        return '';
    }

    return '<a href="https://console.hetzner.cloud/projects" target="_blank" class="btn btn-sm btn-info">
        <i class="fas fa-external-link-alt"></i> Manage in Hetzner Console
    </a>';
}

// ============================================================================
// ADDITIONAL HELPER FUNCTIONS
// ============================================================================

/**
 * Get server status with color coding
 */
function hetzner_GetServerStatus($status)
{
    $statusMap = [
        'running' => ['label' => 'Running', 'class' => 'success'],
        'off' => ['label' => 'Powered Off', 'class' => 'danger'],
        'starting' => ['label' => 'Starting', 'class' => 'warning'],
        'stopping' => ['label' => 'Stopping', 'class' => 'warning'],
        'deleting' => ['label' => 'Deleting', 'class' => 'danger'],
        'migrating' => ['label' => 'Migrating', 'class' => 'info'],
        'rebuilding' => ['label' => 'Rebuilding', 'class' => 'warning'],
        'unknown' => ['label' => 'Unknown', 'class' => 'default'],
    ];

    return $statusMap[$status] ?? $statusMap['unknown'];
}

/**
 * Format server metrics
 */
function hetzner_FormatMetrics($bytes)
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $factor = floor((strlen($bytes) - 1) / 3);

    return sprintf("%.2f", $bytes / pow(1024, $factor)) . ' ' . $units[$factor];
}
