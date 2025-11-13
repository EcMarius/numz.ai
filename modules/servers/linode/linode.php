<?php
/**
 * Linode Cloud Provisioning Module
 *
 * Complete integration with Linode API v4:
 * - Linode instance provisioning
 * - Shared, Dedicated, and High Memory plans
 * - 11 global regions
 * - Block Storage volumes
 * - Backups
 * - Resize operations
 * - Private networking
 * - Firewall rules
 * - Rescue mode
 *
 * @version 1.0
 */

if (!defined('WHMCS')) {
    define('WHMCS', true);
}

// ============================================================================
// LINODE API CLIENT CLASS
// ============================================================================

class LinodeAPI
{
    private $apiToken;
    private $apiUrl = 'https://api.linode.com/v4';

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
            $errorMsg = 'Unknown API error';
            if (isset($result['errors'][0]['reason'])) {
                $errorMsg = $result['errors'][0]['reason'];
            } elseif (isset($result['error'])) {
                $errorMsg = $result['error'];
            }
            return ['error' => $errorMsg];
        }

        return $result;
    }

    /**
     * Create Linode instance
     */
    public function createLinode($label, $region, $type, $image, $rootPass, $authorizedKeys = [], $backupsEnabled = false, $privateIp = true, $tags = [])
    {
        $data = [
            'label' => $label,
            'region' => $region,
            'type' => $type,
            'image' => $image,
            'root_pass' => $rootPass,
            'backups_enabled' => $backupsEnabled,
            'private_ip' => $privateIp,
            'tags' => $tags,
            'booted' => true,
        ];

        if (!empty($authorizedKeys)) {
            $data['authorized_keys'] = $authorizedKeys;
        }

        return $this->request('/linode/instances', 'POST', $data);
    }

    /**
     * Get Linode instance details
     */
    public function getLinode($linodeId)
    {
        return $this->request('/linode/instances/' . $linodeId);
    }

    /**
     * List all Linode instances
     */
    public function listLinodes()
    {
        return $this->request('/linode/instances');
    }

    /**
     * Delete Linode instance
     */
    public function deleteLinode($linodeId)
    {
        return $this->request('/linode/instances/' . $linodeId, 'DELETE');
    }

    /**
     * Boot Linode instance
     */
    public function bootLinode($linodeId)
    {
        return $this->request('/linode/instances/' . $linodeId . '/boot', 'POST');
    }

    /**
     * Shutdown Linode instance
     */
    public function shutdownLinode($linodeId)
    {
        return $this->request('/linode/instances/' . $linodeId . '/shutdown', 'POST');
    }

    /**
     * Reboot Linode instance
     */
    public function rebootLinode($linodeId)
    {
        return $this->request('/linode/instances/' . $linodeId . '/reboot', 'POST');
    }

    /**
     * Resize Linode instance
     */
    public function resizeLinode($linodeId, $newType)
    {
        $data = ['type' => $newType];
        return $this->request('/linode/instances/' . $linodeId . '/resize', 'POST', $data);
    }

    /**
     * Rebuild Linode instance
     */
    public function rebuildLinode($linodeId, $image, $rootPass, $authorizedKeys = [])
    {
        $data = [
            'image' => $image,
            'root_pass' => $rootPass,
        ];

        if (!empty($authorizedKeys)) {
            $data['authorized_keys'] = $authorizedKeys;
        }

        return $this->request('/linode/instances/' . $linodeId . '/rebuild', 'POST', $data);
    }

    /**
     * Update Linode instance label
     */
    public function updateLinode($linodeId, $label)
    {
        $data = ['label' => $label];
        return $this->request('/linode/instances/' . $linodeId, 'PUT', $data);
    }

    /**
     * Enable backups
     */
    public function enableBackups($linodeId)
    {
        return $this->request('/linode/instances/' . $linodeId . '/backups/enable', 'POST');
    }

    /**
     * Disable backups
     */
    public function disableBackups($linodeId)
    {
        return $this->request('/linode/instances/' . $linodeId . '/backups/cancel', 'POST');
    }

    /**
     * Create snapshot
     */
    public function createSnapshot($linodeId, $label = '')
    {
        $data = ['label' => $label ?: 'snapshot-' . date('Y-m-d-H-i-s')];
        return $this->request('/linode/instances/' . $linodeId . '/backups', 'POST', $data);
    }

    /**
     * Get backups
     */
    public function getBackups($linodeId)
    {
        return $this->request('/linode/instances/' . $linodeId . '/backups');
    }

    /**
     * Restore from backup
     */
    public function restoreBackup($linodeId, $backupId, $targetLinodeId = null, $overwrite = true)
    {
        $data = [
            'linode_id' => $targetLinodeId ?: $linodeId,
            'overwrite' => $overwrite,
        ];

        return $this->request('/linode/instances/' . $linodeId . '/backups/' . $backupId . '/restore', 'POST', $data);
    }

    /**
     * Get Linode IPs
     */
    public function getIPs($linodeId)
    {
        return $this->request('/linode/instances/' . $linodeId . '/ips');
    }

    /**
     * Allocate private IP
     */
    public function allocatePrivateIP($linodeId)
    {
        $data = ['type' => 'ipv4', 'public' => false];
        return $this->request('/linode/instances/' . $linodeId . '/ips', 'POST', $data);
    }

    /**
     * Get Linode stats
     */
    public function getStats($linodeId)
    {
        return $this->request('/linode/instances/' . $linodeId . '/stats');
    }

    /**
     * Boot into rescue mode
     */
    public function rescueMode($linodeId, $devices = [])
    {
        $data = ['devices' => $devices];
        return $this->request('/linode/instances/' . $linodeId . '/rescue', 'POST', $data);
    }

    /**
     * List regions
     */
    public function listRegions()
    {
        return $this->request('/regions');
    }

    /**
     * List types (plans)
     */
    public function listTypes()
    {
        return $this->request('/linode/types');
    }

    /**
     * List images
     */
    public function listImages()
    {
        return $this->request('/images');
    }

    /**
     * Create volume
     */
    public function createVolume($label, $sizeGb, $region, $linodeId = null, $tags = [])
    {
        $data = [
            'label' => $label,
            'size' => $sizeGb,
            'region' => $region,
            'tags' => $tags,
        ];

        if ($linodeId) {
            $data['linode_id'] = $linodeId;
        }

        return $this->request('/volumes', 'POST', $data);
    }

    /**
     * Attach volume to Linode
     */
    public function attachVolume($volumeId, $linodeId)
    {
        $data = ['linode_id' => $linodeId];
        return $this->request('/volumes/' . $volumeId . '/attach', 'POST', $data);
    }

    /**
     * Detach volume from Linode
     */
    public function detachVolume($volumeId)
    {
        return $this->request('/volumes/' . $volumeId . '/detach', 'POST');
    }

    /**
     * Delete volume
     */
    public function deleteVolume($volumeId)
    {
        return $this->request('/volumes/' . $volumeId, 'DELETE');
    }

    /**
     * Clone Linode
     */
    public function cloneLinode($linodeId, $region, $type = null, $label = null, $backupsEnabled = false)
    {
        $data = [
            'region' => $region,
            'backups_enabled' => $backupsEnabled,
        ];

        if ($type) {
            $data['type'] = $type;
        }

        if ($label) {
            $data['label'] = $label;
        }

        return $this->request('/linode/instances/' . $linodeId . '/clone', 'POST', $data);
    }

    /**
     * Create firewall
     */
    public function createFirewall($label, $rules = [], $tags = [])
    {
        $data = [
            'label' => $label,
            'rules' => $rules,
            'tags' => $tags,
        ];

        return $this->request('/networking/firewalls', 'POST', $data);
    }

    /**
     * Attach firewall to Linode
     */
    public function attachFirewall($firewallId, $linodeIds = [])
    {
        $data = ['linodes' => $linodeIds];
        return $this->request('/networking/firewalls/' . $firewallId . '/devices', 'POST', $data);
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
function linode_MetaData()
{
    return [
        'DisplayName' => 'Linode Cloud',
        'APIVersion' => '1.1',
        'RequiresServer' => false,
        'DefaultNonSSLPort' => '',
        'DefaultSSLPort' => '',
        'ServiceSingleSignOnLabel' => 'Access Console',
        'AdminSingleSignOnLabel' => 'Manage Linode',
    ];
}

// ============================================================================
// CONFIGURATION OPTIONS
// ============================================================================

/**
 * Configuration options for products
 */
function linode_ConfigOptions()
{
    return [
        'type' => [
            'FriendlyName' => 'Linode Plan',
            'Type' => 'dropdown',
            'Options' => [
                // Shared CPU
                'g6-nanode-1' => 'Nanode 1GB - 1 CPU, 1GB RAM, 25GB Storage',
                'g6-standard-1' => 'Linode 2GB - 1 CPU, 2GB RAM, 50GB Storage',
                'g6-standard-2' => 'Linode 4GB - 2 CPU, 4GB RAM, 80GB Storage',
                'g6-standard-4' => 'Linode 8GB - 4 CPU, 8GB RAM, 160GB Storage',
                'g6-standard-6' => 'Linode 16GB - 6 CPU, 16GB RAM, 320GB Storage',
                'g6-standard-8' => 'Linode 32GB - 8 CPU, 32GB RAM, 640GB Storage',
                // Dedicated CPU
                'g6-dedicated-2' => 'Dedicated 4GB - 2 CPU, 4GB RAM, 80GB Storage',
                'g6-dedicated-4' => 'Dedicated 8GB - 4 CPU, 8GB RAM, 160GB Storage',
                'g6-dedicated-8' => 'Dedicated 16GB - 8 CPU, 16GB RAM, 320GB Storage',
                'g6-dedicated-16' => 'Dedicated 32GB - 16 CPU, 32GB RAM, 640GB Storage',
                // High Memory
                'g6-highmem-1' => 'High Memory 24GB - 1 CPU, 24GB RAM, 20GB Storage',
                'g6-highmem-2' => 'High Memory 48GB - 2 CPU, 48GB RAM, 40GB Storage',
                'g6-highmem-4' => 'High Memory 90GB - 4 CPU, 90GB RAM, 90GB Storage',
                'g6-highmem-8' => 'High Memory 150GB - 8 CPU, 150GB RAM, 200GB Storage',
            ],
            'Default' => 'g6-nanode-1',
            'Description' => 'Select Linode plan type',
        ],
        'region' => [
            'FriendlyName' => 'Region',
            'Type' => 'dropdown',
            'Options' => [
                'us-east' => 'Newark, NJ, USA',
                'us-central' => 'Dallas, TX, USA',
                'us-west' => 'Fremont, CA, USA',
                'us-southeast' => 'Atlanta, GA, USA',
                'ca-central' => 'Toronto, Canada',
                'eu-west' => 'London, UK',
                'eu-central' => 'Frankfurt, Germany',
                'ap-south' => 'Singapore',
                'ap-northeast' => 'Tokyo, Japan',
                'ap-west' => 'Mumbai, India',
                'ap-southeast' => 'Sydney, Australia',
            ],
            'Default' => 'us-east',
            'Description' => 'Select datacenter region',
        ],
        'image' => [
            'FriendlyName' => 'Operating System',
            'Type' => 'dropdown',
            'Options' => [
                'linode/ubuntu22.04' => 'Ubuntu 22.04 LTS',
                'linode/ubuntu20.04' => 'Ubuntu 20.04 LTS',
                'linode/debian11' => 'Debian 11',
                'linode/debian12' => 'Debian 12',
                'linode/centos-stream9' => 'CentOS Stream 9',
                'linode/rocky9' => 'Rocky Linux 9',
                'linode/fedora38' => 'Fedora 38',
                'linode/almalinux9' => 'AlmaLinux 9',
            ],
            'Default' => 'linode/ubuntu22.04',
            'Description' => 'Select operating system image',
        ],
        'enable_backups' => [
            'FriendlyName' => 'Enable Backups',
            'Type' => 'yesno',
            'Default' => 'no',
            'Description' => 'Enable automated backups (additional cost)',
        ],
        'enable_private_ip' => [
            'FriendlyName' => 'Enable Private IP',
            'Type' => 'yesno',
            'Default' => 'yes',
            'Description' => 'Enable private networking',
        ],
        'authorized_keys' => [
            'FriendlyName' => 'SSH Keys',
            'Type' => 'text',
            'Size' => '50',
            'Default' => '',
            'Description' => 'Comma-separated SSH public keys',
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
            'Description' => 'Additional block storage volume size in GB (10-10000, 0 to disable)',
        ],
    ];
}

// ============================================================================
// MODULE PARAMETERS
// ============================================================================

/**
 * Get API client from parameters
 */
function linode_getApiClient($params)
{
    $apiToken = $params['serverapitoken'] ?? $params['configoption9'] ?? '';
    return new LinodeAPI($apiToken);
}

/**
 * Generate secure random password
 */
function linode_generatePassword($length = 32)
{
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

// ============================================================================
// PROVISIONING FUNCTIONS
// ============================================================================

/**
 * Create new Linode instance
 */
function linode_CreateAccount(array $params)
{
    try {
        $api = linode_getApiClient($params);

        $type = $params['configoption1'] ?? 'g6-nanode-1';
        $region = $params['configoption2'] ?? 'us-east';
        $image = $params['configoption3'] ?? 'linode/ubuntu22.04';
        $enableBackups = ($params['configoption4'] ?? 'no') === 'yes';
        $enablePrivateIp = ($params['configoption5'] ?? 'yes') === 'yes';
        $authorizedKeys = $params['configoption6'] ?? '';
        $enableFirewall = ($params['configoption7'] ?? 'no') === 'yes';
        $blockStorageSize = intval($params['configoption8'] ?? 0);

        $label = 'linode-' . $params['serviceid'] . '-' . time();
        $domain = $params['domain'] ?? '';
        if (!empty($domain)) {
            $label = preg_replace('/[^a-z0-9-]/', '-', strtolower($domain));
        }

        $rootPass = linode_generatePassword();

        $sshKeys = [];
        if (!empty($authorizedKeys)) {
            $keys = explode(',', $authorizedKeys);
            foreach ($keys as $key) {
                $key = trim($key);
                if (!empty($key)) {
                    $sshKeys[] = $key;
                }
            }
        }

        $tags = ['whmcs', 'service-' . $params['serviceid']];

        $result = $api->createLinode(
            $label,
            $region,
            $type,
            $image,
            $rootPass,
            $sshKeys,
            $enableBackups,
            $enablePrivateIp,
            $tags
        );

        if (isset($result['error'])) {
            logModuleCall('linode', 'CreateAccount', $params, $result, $result['error']);
            return ['error' => $result['error']];
        }

        $linode = $result;
        $linodeId = $linode['id'];

        // Wait for Linode to get IP address
        sleep(5);
        $linodeInfo = $api->getLinode($linodeId);
        $linodeData = $linodeInfo ?? $linode;

        $ipAddress = '';
        if (isset($linodeData['ipv4'][0])) {
            $ipAddress = $linodeData['ipv4'][0];
        }

        // Store Linode ID and details
        update_query('tblhosting', [
            'dedicatedip' => $ipAddress,
            'username' => 'root',
            'password' => encrypt($rootPass),
            'domain' => $linodeId, // Store Linode ID
        ], ['id' => $params['serviceid']]);

        // Create block storage if requested
        if ($blockStorageSize >= 10) {
            $volumeLabel = 'volume-' . $params['serviceid'];
            $volumeResult = $api->createVolume(
                $volumeLabel,
                $blockStorageSize,
                $region,
                $linodeId,
                ['whmcs']
            );

            if (isset($volumeResult['error'])) {
                logModuleCall('linode', 'CreateVolume', $params, $volumeResult, 'Volume creation failed but Linode created');
            }
        }

        // Create firewall if enabled
        if ($enableFirewall) {
            $firewallLabel = 'firewall-' . $params['serviceid'];
            $rules = [
                'inbound' => [
                    [
                        'protocol' => 'TCP',
                        'ports' => '22',
                        'addresses' => ['ipv4' => ['0.0.0.0/0'], 'ipv6' => ['::/0']],
                        'action' => 'ACCEPT',
                    ],
                    [
                        'protocol' => 'TCP',
                        'ports' => '80',
                        'addresses' => ['ipv4' => ['0.0.0.0/0'], 'ipv6' => ['::/0']],
                        'action' => 'ACCEPT',
                    ],
                    [
                        'protocol' => 'TCP',
                        'ports' => '443',
                        'addresses' => ['ipv4' => ['0.0.0.0/0'], 'ipv6' => ['::/0']],
                        'action' => 'ACCEPT',
                    ],
                ],
                'inbound_policy' => 'DROP',
                'outbound_policy' => 'ACCEPT',
            ];

            $firewallResult = $api->createFirewall($firewallLabel, $rules, ['whmcs']);

            if (!isset($firewallResult['error']) && isset($firewallResult['id'])) {
                $api->attachFirewall($firewallResult['id'], [$linodeId]);
            }
        }

        logModuleCall('linode', 'CreateAccount', $params, $result, 'Linode created successfully');

        return [
            'success' => true,
            'linode_id' => $linodeId,
            'ip_address' => $ipAddress,
            'root_password' => $rootPass,
        ];

    } catch (\Exception $e) {
        logModuleCall('linode', 'CreateAccount', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => 'Linode creation failed: ' . $e->getMessage()];
    }
}

/**
 * Suspend Linode instance (shutdown)
 */
function linode_SuspendAccount(array $params)
{
    try {
        $api = linode_getApiClient($params);
        $linodeId = $params['domain'];

        if (empty($linodeId) || !is_numeric($linodeId)) {
            return ['error' => 'Linode ID not found'];
        }

        $result = $api->shutdownLinode($linodeId);

        if (isset($result['error'])) {
            logModuleCall('linode', 'SuspendAccount', $params, $result, $result['error']);
            return ['error' => $result['error']];
        }

        logModuleCall('linode', 'SuspendAccount', $params, $result, 'Linode shutdown');
        return ['success' => true];

    } catch (\Exception $e) {
        logModuleCall('linode', 'SuspendAccount', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => 'Suspension failed: ' . $e->getMessage()];
    }
}

/**
 * Unsuspend Linode instance (boot)
 */
function linode_UnsuspendAccount(array $params)
{
    try {
        $api = linode_getApiClient($params);
        $linodeId = $params['domain'];

        if (empty($linodeId) || !is_numeric($linodeId)) {
            return ['error' => 'Linode ID not found'];
        }

        $result = $api->bootLinode($linodeId);

        if (isset($result['error'])) {
            logModuleCall('linode', 'UnsuspendAccount', $params, $result, $result['error']);
            return ['error' => $result['error']];
        }

        logModuleCall('linode', 'UnsuspendAccount', $params, $result, 'Linode booted');
        return ['success' => true];

    } catch (\Exception $e) {
        logModuleCall('linode', 'UnsuspendAccount', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => 'Unsuspension failed: ' . $e->getMessage()];
    }
}

/**
 * Terminate Linode instance (delete)
 */
function linode_TerminateAccount(array $params)
{
    try {
        $api = linode_getApiClient($params);
        $linodeId = $params['domain'];

        if (empty($linodeId) || !is_numeric($linodeId)) {
            return ['error' => 'Linode ID not found'];
        }

        $result = $api->deleteLinode($linodeId);

        if (isset($result['error'])) {
            logModuleCall('linode', 'TerminateAccount', $params, $result, $result['error']);
            return ['error' => $result['error']];
        }

        logModuleCall('linode', 'TerminateAccount', $params, $result, 'Linode deleted');
        return ['success' => true];

    } catch (\Exception $e) {
        logModuleCall('linode', 'TerminateAccount', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => 'Termination failed: ' . $e->getMessage()];
    }
}

/**
 * Change package (resize Linode)
 */
function linode_ChangePackage(array $params)
{
    try {
        $api = linode_getApiClient($params);
        $linodeId = $params['domain'];
        $newType = $params['configoption1'] ?? '';

        if (empty($linodeId) || !is_numeric($linodeId)) {
            return ['error' => 'Linode ID not found'];
        }

        if (empty($newType)) {
            return ['error' => 'New type not specified'];
        }

        // Shutdown Linode first
        $api->shutdownLinode($linodeId);
        sleep(10);

        $result = $api->resizeLinode($linodeId, $newType);

        if (isset($result['error'])) {
            logModuleCall('linode', 'ChangePackage', $params, $result, $result['error']);
            return ['error' => $result['error']];
        }

        // Wait for resize to complete
        sleep(30);

        // Boot Linode
        $api->bootLinode($linodeId);

        logModuleCall('linode', 'ChangePackage', $params, $result, 'Linode resized');
        return ['success' => true];

    } catch (\Exception $e) {
        logModuleCall('linode', 'ChangePackage', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => 'Package change failed: ' . $e->getMessage()];
    }
}

// ============================================================================
// ADMIN FUNCTIONS
// ============================================================================

/**
 * Display additional fields in admin services tab
 */
function linode_AdminServicesTabFields(array $params)
{
    try {
        $api = linode_getApiClient($params);
        $linodeId = $params['domain'];

        if (empty($linodeId) || !is_numeric($linodeId)) {
            return ['Linode ID' => 'Not found'];
        }

        $result = $api->getLinode($linodeId);

        if (isset($result['error'])) {
            return ['Error' => $result['error']];
        }

        $linode = $result;

        $ipv4 = $linode['ipv4'][0] ?? 'N/A';
        $ipv6 = $linode['ipv6'] ?? 'N/A';

        return [
            'Linode ID' => $linode['id'],
            'Label' => $linode['label'],
            'Status' => ucfirst($linode['status']),
            'Type' => $linode['type'],
            'Region' => $linode['region'],
            'IPv4 Address' => $ipv4,
            'IPv6 Address' => $ipv6,
            'vCPUs' => $linode['specs']['vcpus'],
            'Memory' => $linode['specs']['memory'] . ' MB',
            'Disk' => $linode['specs']['disk'] . ' MB',
            'Transfer' => $linode['specs']['transfer'] . ' MB',
            'Backups Enabled' => $linode['backups']['enabled'] ? 'Yes' : 'No',
            'Created' => date('Y-m-d H:i:s', strtotime($linode['created'])),
        ];

    } catch (\Exception $e) {
        return ['Error' => $e->getMessage()];
    }
}

/**
 * Custom admin button functions
 */
function linode_AdminCustomButtonArray()
{
    return [
        'Reboot Linode' => 'reboot',
        'Enable Backups' => 'enablebackups',
        'Create Snapshot' => 'snapshot',
        'Rescue Mode' => 'rescue',
    ];
}

/**
 * Reboot Linode
 */
function linode_reboot(array $params)
{
    try {
        $api = linode_getApiClient($params);
        $linodeId = $params['domain'];

        $result = $api->rebootLinode($linodeId);

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
function linode_enablebackups(array $params)
{
    try {
        $api = linode_getApiClient($params);
        $linodeId = $params['domain'];

        $result = $api->enableBackups($linodeId);

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
function linode_snapshot(array $params)
{
    try {
        $api = linode_getApiClient($params);
        $linodeId = $params['domain'];

        $label = 'snapshot-' . date('Y-m-d-H-i-s');
        $result = $api->createSnapshot($linodeId, $label);

        if (isset($result['error'])) {
            return $result['error'];
        }

        return 'success - Snapshot creation initiated';

    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Boot into rescue mode
 */
function linode_rescue(array $params)
{
    try {
        $api = linode_getApiClient($params);
        $linodeId = $params['domain'];

        $result = $api->rescueMode($linodeId);

        if (isset($result['error'])) {
            return $result['error'];
        }

        return 'success - Linode booted into rescue mode';

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
function linode_ClientArea(array $params)
{
    try {
        $api = linode_getApiClient($params);
        $linodeId = $params['domain'];

        if (empty($linodeId) || !is_numeric($linodeId)) {
            return ['error' => 'Linode information not available'];
        }

        $result = $api->getLinode($linodeId);

        if (isset($result['error'])) {
            return ['error' => $result['error']];
        }

        $linode = $result;

        // Get backups
        $backupsResult = $api->getBackups($linodeId);
        $backups = $backupsResult['automatic'] ?? [];

        return [
            'templatefile' => 'templates/clientarea',
            'vars' => [
                'linode' => $linode,
                'backups' => $backups,
                'status' => $linode['status'],
                'ip_address' => $linode['ipv4'][0] ?? 'N/A',
                'ipv6_address' => $linode['ipv6'] ?? 'N/A',
            ],
        ];

    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Custom client area buttons
 */
function linode_ClientAreaCustomButtonArray()
{
    return [
        'Boot' => 'boot',
        'Shutdown' => 'shutdown',
        'Reboot' => 'clientreboot',
    ];
}

/**
 * Client boot
 */
function linode_boot(array $params)
{
    try {
        $api = linode_getApiClient($params);
        $linodeId = $params['domain'];

        $result = $api->bootLinode($linodeId);

        if (isset($result['error'])) {
            return $result['error'];
        }

        return 'success';

    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Client shutdown
 */
function linode_shutdown(array $params)
{
    try {
        $api = linode_getApiClient($params);
        $linodeId = $params['domain'];

        $result = $api->shutdownLinode($linodeId);

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
function linode_clientreboot(array $params)
{
    try {
        $api = linode_getApiClient($params);
        $linodeId = $params['domain'];

        $result = $api->rebootLinode($linodeId);

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
function linode_TestConnection(array $params)
{
    try {
        $api = linode_getApiClient($params);

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
            'version' => 'Linode API v4',
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
function linode_AdminLink(array $params)
{
    $linodeId = $params['domain'];

    if (empty($linodeId)) {
        return '';
    }

    return '<a href="https://cloud.linode.com/linodes/' . $linodeId . '" target="_blank" class="btn btn-sm btn-info">
        <i class="fas fa-external-link-alt"></i> Manage in Linode
    </a>';
}
