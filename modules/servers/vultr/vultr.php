<?php
/**
 * Vultr Cloud Provisioning Module
 *
 * Complete integration with Vultr API v2:
 * - Cloud Compute instances
 * - Regular, High Frequency, and Bare Metal
 * - 25+ locations worldwide
 * - Block storage
 * - Snapshots
 * - Firewall groups
 * - Private networking
 * - IPv6 support
 * - Startup scripts
 *
 * @version 1.0
 */

if (!defined('WHMCS')) {
    define('WHMCS', true);
}

// ============================================================================
// VULTR API CLIENT CLASS
// ============================================================================

class VultrAPI
{
    private $apiKey;
    private $apiUrl = 'https://api.vultr.com/v2';

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * Make API request
     */
    private function request($endpoint, $method = 'GET', $data = [])
    {
        $url = $this->apiUrl . $endpoint;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'PUT' || $method === 'PATCH') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
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
            $errorMsg = 'Unknown API error';
            if (isset($result['error'])) {
                $errorMsg = $result['error'];
            } elseif (isset($result['message'])) {
                $errorMsg = $result['message'];
            }
            return ['error' => $errorMsg];
        }

        return $result;
    }

    /**
     * Create instance
     */
    public function createInstance($region, $plan, $osId, $label = '', $hostname = '', $enableIpv6 = true, $enablePrivateNetwork = true, $sshkeyIds = [], $startupScriptId = '', $userdata = '', $tags = [])
    {
        $data = [
            'region' => $region,
            'plan' => $plan,
            'os_id' => $osId,
            'enable_ipv6' => $enableIpv6,
            'enable_private_network' => $enablePrivateNetwork,
        ];

        if (!empty($label)) {
            $data['label'] = $label;
        }

        if (!empty($hostname)) {
            $data['hostname'] = $hostname;
        }

        if (!empty($sshkeyIds)) {
            $data['sshkey_id'] = $sshkeyIds;
        }

        if (!empty($startupScriptId)) {
            $data['script_id'] = $startupScriptId;
        }

        if (!empty($userdata)) {
            $data['user_data'] = base64_encode($userdata);
        }

        if (!empty($tags)) {
            $data['tags'] = $tags;
        }

        return $this->request('/instances', 'POST', $data);
    }

    /**
     * Get instance details
     */
    public function getInstance($instanceId)
    {
        return $this->request('/instances/' . $instanceId);
    }

    /**
     * List all instances
     */
    public function listInstances()
    {
        return $this->request('/instances');
    }

    /**
     * Delete instance
     */
    public function deleteInstance($instanceId)
    {
        return $this->request('/instances/' . $instanceId, 'DELETE');
    }

    /**
     * Start instance
     */
    public function startInstance($instanceId)
    {
        return $this->request('/instances/' . $instanceId . '/start', 'POST');
    }

    /**
     * Halt instance
     */
    public function haltInstance($instanceId)
    {
        return $this->request('/instances/' . $instanceId . '/halt', 'POST');
    }

    /**
     * Reboot instance
     */
    public function rebootInstance($instanceId)
    {
        return $this->request('/instances/' . $instanceId . '/reboot', 'POST');
    }

    /**
     * Reinstall instance
     */
    public function reinstallInstance($instanceId)
    {
        return $this->request('/instances/' . $instanceId . '/reinstall', 'POST');
    }

    /**
     * Update instance
     */
    public function updateInstance($instanceId, $plan = null, $label = null, $tags = null)
    {
        $data = [];

        if ($plan !== null) {
            $data['plan'] = $plan;
        }

        if ($label !== null) {
            $data['label'] = $label;
        }

        if ($tags !== null) {
            $data['tags'] = $tags;
        }

        if (empty($data)) {
            return ['error' => 'No update data provided'];
        }

        return $this->request('/instances/' . $instanceId, 'PATCH', $data);
    }

    /**
     * Create snapshot
     */
    public function createSnapshot($instanceId, $description = '')
    {
        $data = ['description' => $description ?: 'Snapshot created at ' . date('Y-m-d H:i:s')];
        return $this->request('/snapshots/create-from-instance', 'POST', ['instance_id' => $instanceId, 'description' => $data['description']]);
    }

    /**
     * List snapshots
     */
    public function listSnapshots()
    {
        return $this->request('/snapshots');
    }

    /**
     * Delete snapshot
     */
    public function deleteSnapshot($snapshotId)
    {
        return $this->request('/snapshots/' . $snapshotId, 'DELETE');
    }

    /**
     * Create instance from snapshot
     */
    public function createFromSnapshot($snapshotId, $region, $plan, $label = '')
    {
        $data = [
            'snapshot_id' => $snapshotId,
            'region' => $region,
            'plan' => $plan,
        ];

        if (!empty($label)) {
            $data['label'] = $label;
        }

        return $this->request('/instances', 'POST', $data);
    }

    /**
     * Get instance bandwidth
     */
    public function getBandwidth($instanceId)
    {
        return $this->request('/instances/' . $instanceId . '/bandwidth');
    }

    /**
     * Get instance IPv4
     */
    public function getIPv4($instanceId)
    {
        return $this->request('/instances/' . $instanceId . '/ipv4');
    }

    /**
     * Get instance IPv6
     */
    public function getIPv6($instanceId)
    {
        return $this->request('/instances/' . $instanceId . '/ipv6');
    }

    /**
     * Create reserved IP
     */
    public function createReservedIP($region, $ipType = 'v4', $label = '')
    {
        $data = [
            'region' => $region,
            'ip_type' => $ipType,
        ];

        if (!empty($label)) {
            $data['label'] = $label;
        }

        return $this->request('/reserved-ips', 'POST', $data);
    }

    /**
     * Attach reserved IP to instance
     */
    public function attachReservedIP($reservedIp, $instanceId)
    {
        $data = ['instance_id' => $instanceId];
        return $this->request('/reserved-ips/' . $reservedIp . '/attach', 'POST', $data);
    }

    /**
     * Detach reserved IP
     */
    public function detachReservedIP($reservedIp)
    {
        return $this->request('/reserved-ips/' . $reservedIp . '/detach', 'POST');
    }

    /**
     * List regions
     */
    public function listRegions()
    {
        return $this->request('/regions');
    }

    /**
     * List plans
     */
    public function listPlans($type = null)
    {
        $endpoint = '/plans';
        if ($type) {
            $endpoint .= '?type=' . $type;
        }
        return $this->request($endpoint);
    }

    /**
     * List operating systems
     */
    public function listOS()
    {
        return $this->request('/os');
    }

    /**
     * List SSH keys
     */
    public function listSSHKeys()
    {
        return $this->request('/ssh-keys');
    }

    /**
     * Create block storage
     */
    public function createBlockStorage($region, $sizeGb, $label = '')
    {
        $data = [
            'region' => $region,
            'size_gb' => $sizeGb,
        ];

        if (!empty($label)) {
            $data['label'] = $label;
        }

        return $this->request('/blocks', 'POST', $data);
    }

    /**
     * Attach block storage to instance
     */
    public function attachBlockStorage($blockId, $instanceId)
    {
        $data = ['instance_id' => $instanceId];
        return $this->request('/blocks/' . $blockId . '/attach', 'POST', $data);
    }

    /**
     * Detach block storage
     */
    public function detachBlockStorage($blockId)
    {
        return $this->request('/blocks/' . $blockId . '/detach', 'POST');
    }

    /**
     * Delete block storage
     */
    public function deleteBlockStorage($blockId)
    {
        return $this->request('/blocks/' . $blockId, 'DELETE');
    }

    /**
     * Create firewall group
     */
    public function createFirewallGroup($description = '')
    {
        $data = ['description' => $description];
        return $this->request('/firewalls', 'POST', $data);
    }

    /**
     * Create firewall rule
     */
    public function createFirewallRule($firewallGroupId, $ipType, $protocol, $subnet, $subnetSize, $port = '', $notes = '')
    {
        $data = [
            'ip_type' => $ipType,
            'protocol' => $protocol,
            'subnet' => $subnet,
            'subnet_size' => $subnetSize,
        ];

        if (!empty($port)) {
            $data['port'] = $port;
        }

        if (!empty($notes)) {
            $data['notes'] = $notes;
        }

        return $this->request('/firewalls/' . $firewallGroupId . '/rules', 'POST', $data);
    }

    /**
     * Update instance firewall group
     */
    public function updateInstanceFirewallGroup($instanceId, $firewallGroupId)
    {
        $data = ['firewall_group_id' => $firewallGroupId];
        return $this->request('/instances/' . $instanceId, 'PATCH', $data);
    }

    /**
     * Create startup script
     */
    public function createStartupScript($name, $script, $type = 'boot')
    {
        $data = [
            'name' => $name,
            'script' => base64_encode($script),
            'type' => $type,
        ];

        return $this->request('/startup-scripts', 'POST', $data);
    }

    /**
     * List startup scripts
     */
    public function listStartupScripts()
    {
        return $this->request('/startup-scripts');
    }

    /**
     * Get account information
     */
    public function getAccount()
    {
        return $this->request('/account');
    }
}

// ============================================================================
// MODULE METADATA
// ============================================================================

/**
 * Module metadata
 */
function vultr_MetaData()
{
    return [
        'DisplayName' => 'Vultr Cloud',
        'APIVersion' => '1.1',
        'RequiresServer' => false,
        'DefaultNonSSLPort' => '',
        'DefaultSSLPort' => '',
        'ServiceSingleSignOnLabel' => 'Access Console',
        'AdminSingleSignOnLabel' => 'Manage Instance',
    ];
}

// ============================================================================
// CONFIGURATION OPTIONS
// ============================================================================

/**
 * Configuration options for products
 */
function vultr_ConfigOptions()
{
    return [
        'plan' => [
            'FriendlyName' => 'Instance Plan',
            'Type' => 'dropdown',
            'Options' => [
                // Regular Performance
                'vc2-1c-1gb' => 'Regular - 1 vCPU, 1GB RAM, 25GB SSD, 1TB Bandwidth',
                'vc2-1c-2gb' => 'Regular - 1 vCPU, 2GB RAM, 55GB SSD, 2TB Bandwidth',
                'vc2-2c-4gb' => 'Regular - 2 vCPU, 4GB RAM, 80GB SSD, 3TB Bandwidth',
                'vc2-4c-8gb' => 'Regular - 4 vCPU, 8GB RAM, 160GB SSD, 4TB Bandwidth',
                'vc2-6c-16gb' => 'Regular - 6 vCPU, 16GB RAM, 320GB SSD, 5TB Bandwidth',
                'vc2-8c-32gb' => 'Regular - 8 vCPU, 32GB RAM, 640GB SSD, 6TB Bandwidth',
                // High Frequency
                'vhf-1c-1gb' => 'High Frequency - 1 vCPU, 1GB RAM, 32GB NVMe, 1TB Bandwidth',
                'vhf-1c-2gb' => 'High Frequency - 1 vCPU, 2GB RAM, 64GB NVMe, 2TB Bandwidth',
                'vhf-2c-4gb' => 'High Frequency - 2 vCPU, 4GB RAM, 128GB NVMe, 3TB Bandwidth',
                'vhf-4c-8gb' => 'High Frequency - 4 vCPU, 8GB RAM, 256GB NVMe, 4TB Bandwidth',
                'vhf-6c-16gb' => 'High Frequency - 6 vCPU, 16GB RAM, 512GB NVMe, 5TB Bandwidth',
                // High Performance AMD
                'vhp-1c-1gb-amd' => 'AMD High Perf - 1 vCPU, 1GB RAM, 32GB NVMe, 1TB Bandwidth',
                'vhp-2c-2gb-amd' => 'AMD High Perf - 2 vCPU, 2GB RAM, 64GB NVMe, 2TB Bandwidth',
                'vhp-3c-4gb-amd' => 'AMD High Perf - 3 vCPU, 4GB RAM, 128GB NVMe, 3TB Bandwidth',
            ],
            'Default' => 'vc2-1c-1gb',
            'Description' => 'Select instance plan type',
        ],
        'region' => [
            'FriendlyName' => 'Region',
            'Type' => 'dropdown',
            'Options' => [
                'ewr' => 'New Jersey, USA',
                'ord' => 'Chicago, USA',
                'dfw' => 'Dallas, USA',
                'sea' => 'Seattle, USA',
                'lax' => 'Los Angeles, USA',
                'atl' => 'Atlanta, USA',
                'mia' => 'Miami, USA',
                'ams' => 'Amsterdam, Netherlands',
                'lhr' => 'London, UK',
                'fra' => 'Frankfurt, Germany',
                'cdg' => 'Paris, France',
                'waw' => 'Warsaw, Poland',
                'nrt' => 'Tokyo, Japan',
                'icn' => 'Seoul, South Korea',
                'sgp' => 'Singapore',
                'syd' => 'Sydney, Australia',
                'mel' => 'Melbourne, Australia',
                'yto' => 'Toronto, Canada',
                'sao' => 'São Paulo, Brazil',
                'mex' => 'Mexico City, Mexico',
                'del' => 'Delhi, India',
                'bom' => 'Mumbai, India',
            ],
            'Default' => 'ewr',
            'Description' => 'Select datacenter region',
        ],
        'os_id' => [
            'FriendlyName' => 'Operating System',
            'Type' => 'dropdown',
            'Options' => [
                '1743' => 'Ubuntu 22.04 LTS x64',
                '1740' => 'Ubuntu 20.04 LTS x64',
                '2284' => 'Debian 12 x64',
                '477' => 'Debian 11 x64',
                '542' => 'CentOS Stream 9 x64',
                '1869' => 'Rocky Linux 9 x64',
                '2187' => 'Fedora 38 x64',
                '1925' => 'AlmaLinux 9 x64',
            ],
            'Default' => '1743',
            'Description' => 'Select operating system',
        ],
        'enable_ipv6' => [
            'FriendlyName' => 'Enable IPv6',
            'Type' => 'yesno',
            'Default' => 'yes',
            'Description' => 'Enable IPv6 networking',
        ],
        'enable_private_network' => [
            'FriendlyName' => 'Enable Private Network',
            'Type' => 'yesno',
            'Default' => 'yes',
            'Description' => 'Enable private networking',
        ],
        'ssh_keys' => [
            'FriendlyName' => 'SSH Key IDs',
            'Type' => 'text',
            'Size' => '50',
            'Default' => '',
            'Description' => 'Comma-separated SSH key IDs',
        ],
        'enable_firewall' => [
            'FriendlyName' => 'Enable Firewall',
            'Type' => 'yesno',
            'Default' => 'no',
            'Description' => 'Create and apply basic firewall group',
        ],
        'block_storage_size' => [
            'FriendlyName' => 'Block Storage (GB)',
            'Type' => 'text',
            'Size' => '10',
            'Default' => '0',
            'Description' => 'Additional block storage size in GB (10-10000, 0 to disable)',
        ],
    ];
}

// ============================================================================
// MODULE PARAMETERS
// ============================================================================

/**
 * Get API client from parameters
 */
function vultr_getApiClient($params)
{
    $apiKey = $params['serverapitoken'] ?? $params['configoption9'] ?? '';
    return new VultrAPI($apiKey);
}

// ============================================================================
// PROVISIONING FUNCTIONS
// ============================================================================

/**
 * Create new instance
 */
function vultr_CreateAccount(array $params)
{
    try {
        $api = vultr_getApiClient($params);

        $plan = $params['configoption1'] ?? 'vc2-1c-1gb';
        $region = $params['configoption2'] ?? 'ewr';
        $osId = intval($params['configoption3'] ?? 1743);
        $enableIpv6 = ($params['configoption4'] ?? 'yes') === 'yes';
        $enablePrivateNetwork = ($params['configoption5'] ?? 'yes') === 'yes';
        $sshKeyIds = $params['configoption6'] ?? '';
        $enableFirewall = ($params['configoption7'] ?? 'no') === 'yes';
        $blockStorageSize = intval($params['configoption8'] ?? 0);

        $label = 'instance-' . $params['serviceid'] . '-' . time();
        $hostname = $label . '.vultr.com';
        $domain = $params['domain'] ?? '';
        if (!empty($domain)) {
            $label = preg_replace('/[^a-z0-9-.]/', '-', strtolower($domain));
            $hostname = $domain;
        }

        $sshKeys = [];
        if (!empty($sshKeyIds)) {
            $keys = explode(',', $sshKeyIds);
            foreach ($keys as $key) {
                $key = trim($key);
                if (!empty($key)) {
                    $sshKeys[] = $key;
                }
            }
        }

        $tags = ['whmcs', 'service-' . $params['serviceid']];

        $result = $api->createInstance(
            $region,
            $plan,
            $osId,
            $label,
            $hostname,
            $enableIpv6,
            $enablePrivateNetwork,
            $sshKeys,
            '',
            '',
            $tags
        );

        if (isset($result['error'])) {
            logModuleCall('vultr', 'CreateAccount', $params, $result, $result['error']);
            return ['error' => $result['error']];
        }

        $instance = $result['instance'] ?? $result;
        $instanceId = $instance['id'];

        // Wait for instance to get IP address
        sleep(10);
        $instanceInfo = $api->getInstance($instanceId);
        $instanceData = $instanceInfo['instance'] ?? $instance;

        $ipAddress = $instanceData['main_ip'] ?? '';

        // Store instance ID and details
        update_query('tblhosting', [
            'dedicatedip' => $ipAddress,
            'username' => 'root',
            'password' => encrypt($instanceData['default_password'] ?? ''),
            'domain' => $instanceId, // Store instance ID
        ], ['id' => $params['serviceid']]);

        // Create block storage if requested
        if ($blockStorageSize >= 10) {
            $volumeLabel = 'volume-' . $params['serviceid'];
            $volumeResult = $api->createBlockStorage($region, $blockStorageSize, $volumeLabel);

            if (!isset($volumeResult['error']) && isset($volumeResult['block'])) {
                $blockId = $volumeResult['block']['id'];
                // Wait for volume to be created
                sleep(5);
                $api->attachBlockStorage($blockId, $instanceId);
            }
        }

        // Create firewall if enabled
        if ($enableFirewall) {
            $firewallDesc = 'Firewall for service #' . $params['serviceid'];
            $firewallResult = $api->createFirewallGroup($firewallDesc);

            if (!isset($firewallResult['error']) && isset($firewallResult['firewall_group'])) {
                $firewallGroupId = $firewallResult['firewall_group']['id'];

                // Add SSH rule
                $api->createFirewallRule($firewallGroupId, 'v4', 'tcp', '0.0.0.0', 0, '22', 'SSH');
                // Add HTTP rule
                $api->createFirewallRule($firewallGroupId, 'v4', 'tcp', '0.0.0.0', 0, '80', 'HTTP');
                // Add HTTPS rule
                $api->createFirewallRule($firewallGroupId, 'v4', 'tcp', '0.0.0.0', 0, '443', 'HTTPS');

                // Attach firewall to instance
                $api->updateInstanceFirewallGroup($instanceId, $firewallGroupId);
            }
        }

        logModuleCall('vultr', 'CreateAccount', $params, $result, 'Instance created successfully');

        return [
            'success' => true,
            'instance_id' => $instanceId,
            'ip_address' => $ipAddress,
        ];

    } catch (\Exception $e) {
        logModuleCall('vultr', 'CreateAccount', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => 'Instance creation failed: ' . $e->getMessage()];
    }
}

/**
 * Suspend instance (halt)
 */
function vultr_SuspendAccount(array $params)
{
    try {
        $api = vultr_getApiClient($params);
        $instanceId = $params['domain'];

        if (empty($instanceId)) {
            return ['error' => 'Instance ID not found'];
        }

        $result = $api->haltInstance($instanceId);

        if (isset($result['error'])) {
            logModuleCall('vultr', 'SuspendAccount', $params, $result, $result['error']);
            return ['error' => $result['error']];
        }

        logModuleCall('vultr', 'SuspendAccount', $params, $result, 'Instance halted');
        return ['success' => true];

    } catch (\Exception $e) {
        logModuleCall('vultr', 'SuspendAccount', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => 'Suspension failed: ' . $e->getMessage()];
    }
}

/**
 * Unsuspend instance (start)
 */
function vultr_UnsuspendAccount(array $params)
{
    try {
        $api = vultr_getApiClient($params);
        $instanceId = $params['domain'];

        if (empty($instanceId)) {
            return ['error' => 'Instance ID not found'];
        }

        $result = $api->startInstance($instanceId);

        if (isset($result['error'])) {
            logModuleCall('vultr', 'UnsuspendAccount', $params, $result, $result['error']);
            return ['error' => $result['error']];
        }

        logModuleCall('vultr', 'UnsuspendAccount', $params, $result, 'Instance started');
        return ['success' => true];

    } catch (\Exception $e) {
        logModuleCall('vultr', 'UnsuspendAccount', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => 'Unsuspension failed: ' . $e->getMessage()];
    }
}

/**
 * Terminate instance (delete)
 */
function vultr_TerminateAccount(array $params)
{
    try {
        $api = vultr_getApiClient($params);
        $instanceId = $params['domain'];

        if (empty($instanceId)) {
            return ['error' => 'Instance ID not found'];
        }

        $result = $api->deleteInstance($instanceId);

        if (isset($result['error'])) {
            logModuleCall('vultr', 'TerminateAccount', $params, $result, $result['error']);
            return ['error' => $result['error']];
        }

        logModuleCall('vultr', 'TerminateAccount', $params, $result, 'Instance deleted');
        return ['success' => true];

    } catch (\Exception $e) {
        logModuleCall('vultr', 'TerminateAccount', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => 'Termination failed: ' . $e->getMessage()];
    }
}

/**
 * Change package (resize instance)
 */
function vultr_ChangePackage(array $params)
{
    try {
        $api = vultr_getApiClient($params);
        $instanceId = $params['domain'];
        $newPlan = $params['configoption1'] ?? '';

        if (empty($instanceId)) {
            return ['error' => 'Instance ID not found'];
        }

        if (empty($newPlan)) {
            return ['error' => 'New plan not specified'];
        }

        $result = $api->updateInstance($instanceId, $newPlan);

        if (isset($result['error'])) {
            logModuleCall('vultr', 'ChangePackage', $params, $result, $result['error']);
            return ['error' => $result['error']];
        }

        logModuleCall('vultr', 'ChangePackage', $params, $result, 'Instance plan updated');
        return ['success' => true];

    } catch (\Exception $e) {
        logModuleCall('vultr', 'ChangePackage', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => 'Package change failed: ' . $e->getMessage()];
    }
}

// ============================================================================
// ADMIN FUNCTIONS
// ============================================================================

/**
 * Display additional fields in admin services tab
 */
function vultr_AdminServicesTabFields(array $params)
{
    try {
        $api = vultr_getApiClient($params);
        $instanceId = $params['domain'];

        if (empty($instanceId)) {
            return ['Instance ID' => 'Not found'];
        }

        $result = $api->getInstance($instanceId);

        if (isset($result['error'])) {
            return ['Error' => $result['error']];
        }

        $instance = $result['instance'] ?? $result;

        return [
            'Instance ID' => $instance['id'],
            'Label' => $instance['label'] ?? 'N/A',
            'Status' => ucfirst($instance['status'] ?? 'unknown'),
            'Power Status' => ucfirst($instance['power_status'] ?? 'unknown'),
            'Plan' => $instance['plan'] ?? 'N/A',
            'Region' => $instance['region'] ?? 'N/A',
            'Main IP' => $instance['main_ip'] ?? 'N/A',
            'IPv6' => $instance['v6_main_ip'] ?? 'N/A',
            'vCPUs' => $instance['vcpu_count'] ?? 'N/A',
            'RAM' => $instance['ram'] . ' MB',
            'Disk' => $instance['disk'] . ' GB',
            'Bandwidth' => $instance['allowed_bandwidth'] . ' GB',
            'Created' => $instance['date_created'] ?? 'N/A',
        ];

    } catch (\Exception $e) {
        return ['Error' => $e->getMessage()];
    }
}

/**
 * Custom admin button functions
 */
function vultr_AdminCustomButtonArray()
{
    return [
        'Reboot Instance' => 'reboot',
        'Reinstall OS' => 'reinstall',
        'Create Snapshot' => 'snapshot',
    ];
}

/**
 * Reboot instance
 */
function vultr_reboot(array $params)
{
    try {
        $api = vultr_getApiClient($params);
        $instanceId = $params['domain'];

        $result = $api->rebootInstance($instanceId);

        if (isset($result['error'])) {
            return $result['error'];
        }

        return 'success';

    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Reinstall instance
 */
function vultr_reinstall(array $params)
{
    try {
        $api = vultr_getApiClient($params);
        $instanceId = $params['domain'];

        $result = $api->reinstallInstance($instanceId);

        if (isset($result['error'])) {
            return $result['error'];
        }

        return 'success - Instance reinstall initiated';

    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Create snapshot
 */
function vultr_snapshot(array $params)
{
    try {
        $api = vultr_getApiClient($params);
        $instanceId = $params['domain'];

        $description = 'Snapshot - ' . date('Y-m-d H:i:s');
        $result = $api->createSnapshot($instanceId, $description);

        if (isset($result['error'])) {
            return $result['error'];
        }

        return 'success - Snapshot creation initiated';

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
function vultr_ClientArea(array $params)
{
    try {
        $api = vultr_getApiClient($params);
        $instanceId = $params['domain'];

        if (empty($instanceId)) {
            return ['error' => 'Instance information not available'];
        }

        $result = $api->getInstance($instanceId);

        if (isset($result['error'])) {
            return ['error' => $result['error']];
        }

        $instance = $result['instance'] ?? $result;

        // Get snapshots
        $snapshotsResult = $api->listSnapshots();
        $snapshots = $snapshotsResult['snapshots'] ?? [];

        return [
            'templatefile' => 'templates/clientarea',
            'vars' => [
                'instance' => $instance,
                'snapshots' => $snapshots,
                'status' => $instance['status'] ?? 'unknown',
                'power_status' => $instance['power_status'] ?? 'unknown',
                'ip_address' => $instance['main_ip'] ?? 'N/A',
                'ipv6_address' => $instance['v6_main_ip'] ?? 'N/A',
            ],
        ];

    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Custom client area buttons
 */
function vultr_ClientAreaCustomButtonArray()
{
    return [
        'Start' => 'start',
        'Halt' => 'halt',
        'Reboot' => 'clientreboot',
    ];
}

/**
 * Client start
 */
function vultr_start(array $params)
{
    try {
        $api = vultr_getApiClient($params);
        $instanceId = $params['domain'];

        $result = $api->startInstance($instanceId);

        if (isset($result['error'])) {
            return $result['error'];
        }

        return 'success';

    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Client halt
 */
function vultr_halt(array $params)
{
    try {
        $api = vultr_getApiClient($params);
        $instanceId = $params['domain'];

        $result = $api->haltInstance($instanceId);

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
function vultr_clientreboot(array $params)
{
    try {
        $api = vultr_getApiClient($params);
        $instanceId = $params['domain'];

        $result = $api->rebootInstance($instanceId);

        if (isset($result['error'])) {
            return $result['error'];
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
function vultr_TestConnection(array $params)
{
    try {
        $api = vultr_getApiClient($params);

        // Test API by getting account info
        $result = $api->getAccount();

        if (isset($result['error'])) {
            return [
                'success' => false,
                'error' => 'API connection failed: ' . $result['error'],
            ];
        }

        return [
            'success' => true,
            'version' => 'Vultr API v2',
        ];

    } catch (\Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage(),
        ];
    }
}

// ============================================================================
// ADMIN AREA OUTPUT
// ============================================================================

/**
 * Display admin area link
 */
function vultr_AdminLink(array $params)
{
    $instanceId = $params['domain'];

    if (empty($instanceId)) {
        return '';
    }

    return '<a href="https://my.vultr.com/subs/?SUBID=' . $instanceId . '" target="_blank" class="btn btn-sm btn-info">
        <i class="fas fa-external-link-alt"></i> Manage in Vultr
    </a>';
}
