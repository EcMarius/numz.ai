<?php
/**
 * DigitalOcean Cloud Provisioning Module
 *
 * Complete integration with DigitalOcean API v2:
 * - Droplet creation and management
 * - All droplet sizes (Basic, CPU-Optimized, Memory-Optimized)
 * - 14 datacenter regions
 * - Volume/Block Storage
 * - Floating IPs
 * - Snapshots and backups
 * - Firewall management
 * - Monitoring
 * - Console access
 *
 * @version 1.0
 */

if (!defined('WHMCS')) {
    define('WHMCS', true);
}

// ============================================================================
// DIGITALOCEAN API CLIENT CLASS
// ============================================================================

class DigitalOceanAPI
{
    private $apiToken;
    private $apiUrl = 'https://api.digitalocean.com/v2';

    public function __construct($token)
    {
        $this->apiToken = $token;
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
            'Authorization: Bearer ' . $this->apiToken,
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
            return ['error' => $result['message'] ?? 'Unknown API error'];
        }

        return $result;
    }

    /**
     * Create droplet
     */
    public function createDroplet($name, $region, $size, $image, $sshKeys = [], $backups = false, $ipv6 = true, $monitoring = true, $tags = [])
    {
        $data = [
            'name' => $name,
            'region' => $region,
            'size' => $size,
            'image' => $image,
            'backups' => $backups,
            'ipv6' => $ipv6,
            'monitoring' => $monitoring,
            'tags' => $tags,
        ];

        if (!empty($sshKeys)) {
            $data['ssh_keys'] = $sshKeys;
        }

        return $this->request('/droplets', 'POST', $data);
    }

    /**
     * Get droplet details
     */
    public function getDroplet($dropletId)
    {
        return $this->request('/droplets/' . $dropletId);
    }

    /**
     * List all droplets
     */
    public function listDroplets()
    {
        return $this->request('/droplets');
    }

    /**
     * Delete droplet
     */
    public function deleteDroplet($dropletId)
    {
        return $this->request('/droplets/' . $dropletId, 'DELETE');
    }

    /**
     * Perform droplet action
     */
    public function dropletAction($dropletId, $action, $params = [])
    {
        $data = array_merge(['type' => $action], $params);
        return $this->request('/droplets/' . $dropletId . '/actions', 'POST', $data);
    }

    /**
     * Power on droplet
     */
    public function powerOn($dropletId)
    {
        return $this->dropletAction($dropletId, 'power_on');
    }

    /**
     * Power off droplet
     */
    public function powerOff($dropletId)
    {
        return $this->dropletAction($dropletId, 'power_off');
    }

    /**
     * Shutdown droplet
     */
    public function shutdown($dropletId)
    {
        return $this->dropletAction($dropletId, 'shutdown');
    }

    /**
     * Reboot droplet
     */
    public function reboot($dropletId)
    {
        return $this->dropletAction($dropletId, 'reboot');
    }

    /**
     * Power cycle droplet
     */
    public function powerCycle($dropletId)
    {
        return $this->dropletAction($dropletId, 'power_cycle');
    }

    /**
     * Resize droplet
     */
    public function resize($dropletId, $newSize, $resizeDisk = false)
    {
        return $this->dropletAction($dropletId, 'resize', [
            'size' => $newSize,
            'disk' => $resizeDisk,
        ]);
    }

    /**
     * Rebuild droplet
     */
    public function rebuild($dropletId, $image)
    {
        return $this->dropletAction($dropletId, 'rebuild', ['image' => $image]);
    }

    /**
     * Rename droplet
     */
    public function rename($dropletId, $newName)
    {
        return $this->dropletAction($dropletId, 'rename', ['name' => $newName]);
    }

    /**
     * Enable backups
     */
    public function enableBackups($dropletId)
    {
        return $this->dropletAction($dropletId, 'enable_backups');
    }

    /**
     * Disable backups
     */
    public function disableBackups($dropletId)
    {
        return $this->dropletAction($dropletId, 'disable_backups');
    }

    /**
     * Create snapshot
     */
    public function createSnapshot($dropletId, $name = '')
    {
        $snapshotName = $name ?: 'snapshot-' . date('Y-m-d-H-i-s');
        return $this->dropletAction($dropletId, 'snapshot', ['name' => $snapshotName]);
    }

    /**
     * Restore from snapshot
     */
    public function restoreSnapshot($dropletId, $snapshotId)
    {
        return $this->dropletAction($dropletId, 'restore', ['image' => $snapshotId]);
    }

    /**
     * Enable IPv6
     */
    public function enableIPv6($dropletId)
    {
        return $this->dropletAction($dropletId, 'enable_ipv6');
    }

    /**
     * Get droplet snapshots
     */
    public function getSnapshots($dropletId)
    {
        return $this->request('/droplets/' . $dropletId . '/snapshots');
    }

    /**
     * Get droplet backups
     */
    public function getBackups($dropletId)
    {
        return $this->request('/droplets/' . $dropletId . '/backups');
    }

    /**
     * List regions
     */
    public function listRegions()
    {
        return $this->request('/regions');
    }

    /**
     * List sizes
     */
    public function listSizes()
    {
        return $this->request('/sizes');
    }

    /**
     * List images
     */
    public function listImages($type = 'distribution')
    {
        return $this->request('/images?type=' . $type);
    }

    /**
     * List SSH keys
     */
    public function listSSHKeys()
    {
        return $this->request('/account/keys');
    }

    /**
     * Create volume
     */
    public function createVolume($name, $sizeGb, $region, $description = '')
    {
        $data = [
            'name' => $name,
            'size_gigabytes' => $sizeGb,
            'region' => $region,
            'description' => $description,
        ];

        return $this->request('/volumes', 'POST', $data);
    }

    /**
     * Attach volume to droplet
     */
    public function attachVolume($volumeId, $dropletId)
    {
        $data = [
            'type' => 'attach',
            'droplet_id' => $dropletId,
        ];

        return $this->request('/volumes/' . $volumeId . '/actions', 'POST', $data);
    }

    /**
     * Detach volume from droplet
     */
    public function detachVolume($volumeId, $dropletId)
    {
        $data = [
            'type' => 'detach',
            'droplet_id' => $dropletId,
        ];

        return $this->request('/volumes/' . $volumeId . '/actions', 'POST', $data);
    }

    /**
     * Delete volume
     */
    public function deleteVolume($volumeId)
    {
        return $this->request('/volumes/' . $volumeId, 'DELETE');
    }

    /**
     * Create firewall
     */
    public function createFirewall($name, $inboundRules = [], $outboundRules = [], $dropletIds = [])
    {
        $data = [
            'name' => $name,
            'inbound_rules' => $inboundRules,
            'outbound_rules' => $outboundRules,
            'droplet_ids' => $dropletIds,
        ];

        return $this->request('/firewalls', 'POST', $data);
    }

    /**
     * Add droplets to firewall
     */
    public function addDropletsToFirewall($firewallId, $dropletIds)
    {
        $data = ['droplet_ids' => $dropletIds];
        return $this->request('/firewalls/' . $firewallId . '/droplets', 'POST', $data);
    }

    /**
     * Create floating IP
     */
    public function createFloatingIP($region, $dropletId = null)
    {
        $data = ['region' => $region];
        if ($dropletId) {
            $data['droplet_id'] = $dropletId;
        }

        return $this->request('/floating_ips', 'POST', $data);
    }

    /**
     * Assign floating IP to droplet
     */
    public function assignFloatingIP($floatingIp, $dropletId)
    {
        $data = [
            'type' => 'assign',
            'droplet_id' => $dropletId,
        ];

        return $this->request('/floating_ips/' . $floatingIp . '/actions', 'POST', $data);
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
function digitalocean_MetaData()
{
    return [
        'DisplayName' => 'DigitalOcean Cloud',
        'APIVersion' => '1.1',
        'RequiresServer' => false,
        'DefaultNonSSLPort' => '',
        'DefaultSSLPort' => '',
        'ServiceSingleSignOnLabel' => 'Access Console',
        'AdminSingleSignOnLabel' => 'Manage Droplet',
    ];
}

// ============================================================================
// CONFIGURATION OPTIONS
// ============================================================================

/**
 * Configuration options for products
 */
function digitalocean_ConfigOptions()
{
    return [
        'size' => [
            'FriendlyName' => 'Droplet Size',
            'Type' => 'dropdown',
            'Options' => [
                // Basic Droplets
                's-1vcpu-512mb-10gb' => 'Basic - 512MB RAM, 1 vCPU, 10GB SSD, 500GB Transfer',
                's-1vcpu-1gb' => 'Basic - 1GB RAM, 1 vCPU, 25GB SSD, 1TB Transfer',
                's-1vcpu-2gb' => 'Basic - 2GB RAM, 1 vCPU, 50GB SSD, 2TB Transfer',
                's-2vcpu-2gb' => 'Basic - 2GB RAM, 2 vCPU, 60GB SSD, 3TB Transfer',
                's-2vcpu-4gb' => 'Basic - 4GB RAM, 2 vCPU, 80GB SSD, 4TB Transfer',
                's-4vcpu-8gb' => 'Basic - 8GB RAM, 4 vCPU, 160GB SSD, 5TB Transfer',
                's-6vcpu-16gb' => 'Basic - 16GB RAM, 6 vCPU, 320GB SSD, 6TB Transfer',
                's-8vcpu-32gb' => 'Basic - 32GB RAM, 8 vCPU, 640GB SSD, 7TB Transfer',
                // CPU-Optimized
                'c-2' => 'CPU-Optimized - 4GB RAM, 2 vCPU, 25GB SSD, 4TB Transfer',
                'c-4' => 'CPU-Optimized - 8GB RAM, 4 vCPU, 50GB SSD, 5TB Transfer',
                'c-8' => 'CPU-Optimized - 16GB RAM, 8 vCPU, 100GB SSD, 6TB Transfer',
                'c-16' => 'CPU-Optimized - 32GB RAM, 16 vCPU, 200GB SSD, 7TB Transfer',
                // Memory-Optimized
                'm-2vcpu-16gb' => 'Memory - 16GB RAM, 2 vCPU, 50GB SSD, 4TB Transfer',
                'm-4vcpu-32gb' => 'Memory - 32GB RAM, 4 vCPU, 100GB SSD, 5TB Transfer',
                'm-8vcpu-64gb' => 'Memory - 64GB RAM, 8 vCPU, 200GB SSD, 6TB Transfer',
                'm-16vcpu-128gb' => 'Memory - 128GB RAM, 16 vCPU, 400GB SSD, 7TB Transfer',
            ],
            'Default' => 's-1vcpu-1gb',
            'Description' => 'Select droplet size and type',
        ],
        'region' => [
            'FriendlyName' => 'Region',
            'Type' => 'dropdown',
            'Options' => [
                'nyc1' => 'New York 1',
                'nyc3' => 'New York 3',
                'sfo3' => 'San Francisco 3',
                'ams3' => 'Amsterdam 3',
                'sgp1' => 'Singapore 1',
                'lon1' => 'London 1',
                'fra1' => 'Frankfurt 1',
                'tor1' => 'Toronto 1',
                'blr1' => 'Bangalore 1',
                'syd1' => 'Sydney 1',
            ],
            'Default' => 'nyc3',
            'Description' => 'Select datacenter region',
        ],
        'image' => [
            'FriendlyName' => 'Operating System',
            'Type' => 'dropdown',
            'Options' => [
                'ubuntu-22-04-x64' => 'Ubuntu 22.04 LTS x64',
                'ubuntu-20-04-x64' => 'Ubuntu 20.04 LTS x64',
                'debian-11-x64' => 'Debian 11 x64',
                'debian-12-x64' => 'Debian 12 x64',
                'centos-stream-9-x64' => 'CentOS Stream 9 x64',
                'rocky-9-x64' => 'Rocky Linux 9 x64',
                'fedora-38-x64' => 'Fedora 38 x64',
                'almalinux-9-x64' => 'AlmaLinux 9 x64',
            ],
            'Default' => 'ubuntu-22-04-x64',
            'Description' => 'Select operating system image',
        ],
        'enable_backups' => [
            'FriendlyName' => 'Enable Backups',
            'Type' => 'yesno',
            'Default' => 'no',
            'Description' => 'Enable automated weekly backups (20% of droplet cost)',
        ],
        'enable_ipv6' => [
            'FriendlyName' => 'Enable IPv6',
            'Type' => 'yesno',
            'Default' => 'yes',
            'Description' => 'Enable IPv6 networking',
        ],
        'enable_monitoring' => [
            'FriendlyName' => 'Enable Monitoring',
            'Type' => 'yesno',
            'Default' => 'yes',
            'Description' => 'Enable free DigitalOcean monitoring',
        ],
        'ssh_keys' => [
            'FriendlyName' => 'SSH Key IDs',
            'Type' => 'text',
            'Size' => '50',
            'Default' => '',
            'Description' => 'Comma-separated SSH key IDs or fingerprints',
        ],
        'enable_firewall' => [
            'FriendlyName' => 'Enable Firewall',
            'Type' => 'yesno',
            'Default' => 'no',
            'Description' => 'Create and apply basic firewall rules',
        ],
        'block_storage_size' => [
            'FriendlyName' => 'Block Storage (GB)',
            'Type' => 'text',
            'Size' => '10',
            'Default' => '0',
            'Description' => 'Additional block storage volume size in GB (0 to disable)',
        ],
    ];
}

// ============================================================================
// MODULE PARAMETERS
// ============================================================================

/**
 * Get API client from parameters
 */
function digitalocean_getApiClient($params)
{
    $apiToken = $params['serverapitoken'] ?? $params['configoption10'] ?? '';
    return new DigitalOceanAPI($apiToken);
}

// ============================================================================
// PROVISIONING FUNCTIONS
// ============================================================================

/**
 * Create new droplet
 */
function digitalocean_CreateAccount(array $params)
{
    try {
        $api = digitalocean_getApiClient($params);

        $size = $params['configoption1'] ?? 's-1vcpu-1gb';
        $region = $params['configoption2'] ?? 'nyc3';
        $image = $params['configoption3'] ?? 'ubuntu-22-04-x64';
        $enableBackups = ($params['configoption4'] ?? 'no') === 'yes';
        $enableIpv6 = ($params['configoption5'] ?? 'yes') === 'yes';
        $enableMonitoring = ($params['configoption6'] ?? 'yes') === 'yes';
        $sshKeyIds = $params['configoption7'] ?? '';
        $enableFirewall = ($params['configoption8'] ?? 'no') === 'yes';
        $blockStorageSize = intval($params['configoption9'] ?? 0);

        $dropletName = 'droplet-' . $params['serviceid'] . '-' . time();
        $domain = $params['domain'] ?? '';
        if (!empty($domain)) {
            $dropletName = preg_replace('/[^a-z0-9-.]/', '-', strtolower($domain));
        }

        $sshKeys = [];
        if (!empty($sshKeyIds)) {
            $keys = explode(',', $sshKeyIds);
            foreach ($keys as $key) {
                $key = trim($key);
                if (is_numeric($key)) {
                    $sshKeys[] = intval($key);
                } else {
                    $sshKeys[] = $key; // Fingerprint
                }
            }
        }

        $tags = ['whmcs', 'service-' . $params['serviceid']];

        $result = $api->createDroplet(
            $dropletName,
            $region,
            $size,
            $image,
            $sshKeys,
            $enableBackups,
            $enableIpv6,
            $enableMonitoring,
            $tags
        );

        if (isset($result['error'])) {
            logModuleCall('digitalocean', 'CreateAccount', $params, $result, $result['error']);
            return ['error' => $result['error']];
        }

        $droplet = $result['droplet'];
        $dropletId = $droplet['id'];

        // Wait for droplet to get IP address
        sleep(5);
        $dropletInfo = $api->getDroplet($dropletId);
        $dropletData = $dropletInfo['droplet'] ?? $droplet;

        $ipAddress = '';
        if (isset($dropletData['networks']['v4'][0]['ip_address'])) {
            $ipAddress = $dropletData['networks']['v4'][0]['ip_address'];
        }

        // Store droplet ID and details
        update_query('tblhosting', [
            'dedicatedip' => $ipAddress,
            'username' => 'root',
            'domain' => $dropletId, // Store droplet ID
        ], ['id' => $params['serviceid']]);

        // Create block storage if requested
        if ($blockStorageSize > 0) {
            $volumeName = 'volume-' . $params['serviceid'];
            $volumeResult = $api->createVolume($volumeName, $blockStorageSize, $region, 'WHMCS Service #' . $params['serviceid']);

            if (!isset($volumeResult['error']) && isset($volumeResult['volume'])) {
                $volumeId = $volumeResult['volume']['id'];
                // Wait for volume to be created
                sleep(3);
                $api->attachVolume($volumeId, $dropletId);
            }
        }

        // Create firewall if enabled
        if ($enableFirewall) {
            $firewallName = 'firewall-' . $params['serviceid'];
            $inboundRules = [
                [
                    'protocol' => 'tcp',
                    'ports' => '22',
                    'sources' => ['addresses' => ['0.0.0.0/0', '::/0']],
                ],
                [
                    'protocol' => 'tcp',
                    'ports' => '80',
                    'sources' => ['addresses' => ['0.0.0.0/0', '::/0']],
                ],
                [
                    'protocol' => 'tcp',
                    'ports' => '443',
                    'sources' => ['addresses' => ['0.0.0.0/0', '::/0']],
                ],
            ];

            $outboundRules = [
                [
                    'protocol' => 'tcp',
                    'ports' => 'all',
                    'destinations' => ['addresses' => ['0.0.0.0/0', '::/0']],
                ],
                [
                    'protocol' => 'udp',
                    'ports' => 'all',
                    'destinations' => ['addresses' => ['0.0.0.0/0', '::/0']],
                ],
            ];

            $api->createFirewall($firewallName, $inboundRules, $outboundRules, [$dropletId]);
        }

        logModuleCall('digitalocean', 'CreateAccount', $params, $result, 'Droplet created successfully');

        return [
            'success' => true,
            'droplet_id' => $dropletId,
            'ip_address' => $ipAddress,
        ];

    } catch (\Exception $e) {
        logModuleCall('digitalocean', 'CreateAccount', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => 'Droplet creation failed: ' . $e->getMessage()];
    }
}

/**
 * Suspend droplet (power off)
 */
function digitalocean_SuspendAccount(array $params)
{
    try {
        $api = digitalocean_getApiClient($params);
        $dropletId = $params['domain'];

        if (empty($dropletId) || !is_numeric($dropletId)) {
            return ['error' => 'Droplet ID not found'];
        }

        $result = $api->powerOff($dropletId);

        if (isset($result['error'])) {
            logModuleCall('digitalocean', 'SuspendAccount', $params, $result, $result['error']);
            return ['error' => $result['error']];
        }

        logModuleCall('digitalocean', 'SuspendAccount', $params, $result, 'Droplet powered off');
        return ['success' => true];

    } catch (\Exception $e) {
        logModuleCall('digitalocean', 'SuspendAccount', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => 'Suspension failed: ' . $e->getMessage()];
    }
}

/**
 * Unsuspend droplet (power on)
 */
function digitalocean_UnsuspendAccount(array $params)
{
    try {
        $api = digitalocean_getApiClient($params);
        $dropletId = $params['domain'];

        if (empty($dropletId) || !is_numeric($dropletId)) {
            return ['error' => 'Droplet ID not found'];
        }

        $result = $api->powerOn($dropletId);

        if (isset($result['error'])) {
            logModuleCall('digitalocean', 'UnsuspendAccount', $params, $result, $result['error']);
            return ['error' => $result['error']];
        }

        logModuleCall('digitalocean', 'UnsuspendAccount', $params, $result, 'Droplet powered on');
        return ['success' => true];

    } catch (\Exception $e) {
        logModuleCall('digitalocean', 'UnsuspendAccount', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => 'Unsuspension failed: ' . $e->getMessage()];
    }
}

/**
 * Terminate droplet (delete)
 */
function digitalocean_TerminateAccount(array $params)
{
    try {
        $api = digitalocean_getApiClient($params);
        $dropletId = $params['domain'];

        if (empty($dropletId) || !is_numeric($dropletId)) {
            return ['error' => 'Droplet ID not found'];
        }

        $result = $api->deleteDroplet($dropletId);

        if (isset($result['error'])) {
            logModuleCall('digitalocean', 'TerminateAccount', $params, $result, $result['error']);
            return ['error' => $result['error']];
        }

        logModuleCall('digitalocean', 'TerminateAccount', $params, $result, 'Droplet deleted');
        return ['success' => true];

    } catch (\Exception $e) {
        logModuleCall('digitalocean', 'TerminateAccount', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => 'Termination failed: ' . $e->getMessage()];
    }
}

/**
 * Change package (resize droplet)
 */
function digitalocean_ChangePackage(array $params)
{
    try {
        $api = digitalocean_getApiClient($params);
        $dropletId = $params['domain'];
        $newSize = $params['configoption1'] ?? '';

        if (empty($dropletId) || !is_numeric($dropletId)) {
            return ['error' => 'Droplet ID not found'];
        }

        if (empty($newSize)) {
            return ['error' => 'New size not specified'];
        }

        // Power off droplet first
        $api->powerOff($dropletId);
        sleep(10);

        $result = $api->resize($dropletId, $newSize, false);

        if (isset($result['error'])) {
            logModuleCall('digitalocean', 'ChangePackage', $params, $result, $result['error']);
            return ['error' => $result['error']];
        }

        // Wait for resize to complete
        sleep(30);

        // Power on droplet
        $api->powerOn($dropletId);

        logModuleCall('digitalocean', 'ChangePackage', $params, $result, 'Droplet resized');
        return ['success' => true];

    } catch (\Exception $e) {
        logModuleCall('digitalocean', 'ChangePackage', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => 'Package change failed: ' . $e->getMessage()];
    }
}

// ============================================================================
// ADMIN FUNCTIONS
// ============================================================================

/**
 * Display additional fields in admin services tab
 */
function digitalocean_AdminServicesTabFields(array $params)
{
    try {
        $api = digitalocean_getApiClient($params);
        $dropletId = $params['domain'];

        if (empty($dropletId) || !is_numeric($dropletId)) {
            return ['Droplet ID' => 'Not found'];
        }

        $result = $api->getDroplet($dropletId);

        if (isset($result['error'])) {
            return ['Error' => $result['error']];
        }

        $droplet = $result['droplet'];

        $ipv4 = $droplet['networks']['v4'][0]['ip_address'] ?? 'N/A';
        $ipv6 = $droplet['networks']['v6'][0]['ip_address'] ?? 'N/A';

        return [
            'Droplet ID' => $droplet['id'],
            'Droplet Name' => $droplet['name'],
            'Status' => ucfirst($droplet['status']),
            'Size' => $droplet['size']['slug'],
            'Region' => $droplet['region']['name'],
            'IPv4 Address' => $ipv4,
            'IPv6 Address' => $ipv6,
            'vCPUs' => $droplet['vcpus'],
            'Memory' => $droplet['memory'] . ' MB',
            'Disk' => $droplet['disk'] . ' GB',
            'Backups Enabled' => $droplet['features'] && in_array('backups', $droplet['features']) ? 'Yes' : 'No',
            'Created' => date('Y-m-d H:i:s', strtotime($droplet['created_at'])),
        ];

    } catch (\Exception $e) {
        return ['Error' => $e->getMessage()];
    }
}

/**
 * Custom admin button functions
 */
function digitalocean_AdminCustomButtonArray()
{
    return [
        'Reboot Droplet' => 'reboot',
        'Power Cycle' => 'powercycle',
        'Enable Backups' => 'enablebackups',
        'Create Snapshot' => 'snapshot',
    ];
}

/**
 * Reboot droplet
 */
function digitalocean_reboot(array $params)
{
    try {
        $api = digitalocean_getApiClient($params);
        $dropletId = $params['domain'];

        $result = $api->reboot($dropletId);

        if (isset($result['error'])) {
            return $result['error'];
        }

        return 'success';

    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Power cycle droplet
 */
function digitalocean_powercycle(array $params)
{
    try {
        $api = digitalocean_getApiClient($params);
        $dropletId = $params['domain'];

        $result = $api->powerCycle($dropletId);

        if (isset($result['error'])) {
            return $result['error'];
        }

        return 'success';

    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Enable backups
 */
function digitalocean_enablebackups(array $params)
{
    try {
        $api = digitalocean_getApiClient($params);
        $dropletId = $params['domain'];

        $result = $api->enableBackups($dropletId);

        if (isset($result['error'])) {
            return $result['error'];
        }

        return 'success';

    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Create snapshot
 */
function digitalocean_snapshot(array $params)
{
    try {
        $api = digitalocean_getApiClient($params);
        $dropletId = $params['domain'];

        $snapshotName = 'snapshot-' . date('Y-m-d-H-i-s');
        $result = $api->createSnapshot($dropletId, $snapshotName);

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
function digitalocean_ClientArea(array $params)
{
    try {
        $api = digitalocean_getApiClient($params);
        $dropletId = $params['domain'];

        if (empty($dropletId) || !is_numeric($dropletId)) {
            return ['error' => 'Droplet information not available'];
        }

        $result = $api->getDroplet($dropletId);

        if (isset($result['error'])) {
            return ['error' => $result['error']];
        }

        $droplet = $result['droplet'];

        // Get snapshots
        $snapshotsResult = $api->getSnapshots($dropletId);
        $snapshots = $snapshotsResult['snapshots'] ?? [];

        // Get backups
        $backupsResult = $api->getBackups($dropletId);
        $backups = $backupsResult['backups'] ?? [];

        return [
            'templatefile' => 'templates/clientarea',
            'vars' => [
                'droplet' => $droplet,
                'snapshots' => $snapshots,
                'backups' => $backups,
                'status' => $droplet['status'],
                'ip_address' => $droplet['networks']['v4'][0]['ip_address'] ?? 'N/A',
                'ipv6_address' => $droplet['networks']['v6'][0]['ip_address'] ?? 'N/A',
            ],
        ];

    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Custom client area buttons
 */
function digitalocean_ClientAreaCustomButtonArray()
{
    return [
        'Power On' => 'poweron',
        'Power Off' => 'poweroff',
        'Reboot' => 'clientreboot',
    ];
}

/**
 * Client power on
 */
function digitalocean_poweron(array $params)
{
    try {
        $api = digitalocean_getApiClient($params);
        $dropletId = $params['domain'];

        $result = $api->powerOn($dropletId);

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
function digitalocean_poweroff(array $params)
{
    try {
        $api = digitalocean_getApiClient($params);
        $dropletId = $params['domain'];

        $result = $api->shutdown($dropletId);

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
function digitalocean_clientreboot(array $params)
{
    try {
        $api = digitalocean_getApiClient($params);
        $dropletId = $params['domain'];

        $result = $api->reboot($dropletId);

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
function digitalocean_TestConnection(array $params)
{
    try {
        $api = digitalocean_getApiClient($params);

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
            'version' => 'DigitalOcean API v2',
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
function digitalocean_AdminLink(array $params)
{
    $dropletId = $params['domain'];

    if (empty($dropletId)) {
        return '';
    }

    return '<a href="https://cloud.digitalocean.com/droplets/' . $dropletId . '" target="_blank" class="btn btn-sm btn-info">
        <i class="fas fa-external-link-alt"></i> Manage in DigitalOcean
    </a>';
}
