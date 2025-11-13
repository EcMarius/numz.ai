<?php
/**
 * Lambda GPU Cloud Provisioning Module
 *
 * Comprehensive integration with Lambda Labs GPU Cloud REST API
 * High-performance GPU instances for AI/ML workloads
 *
 * @version 1.0
 */

if (!defined('WHMCS')) {
    define('WHMCS', true);
}

// ============================================================================
// LAMBDA API CLIENT CLASS
// ============================================================================

class LambdaAPI
{
    private $apiKey;
    private $apiUrl = 'https://cloud.lambdalabs.com/api/v1';

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
            return ['error' => $result['error']['message'] ?? $result['error'] ?? 'Unknown API error'];
        }

        return $result['data'] ?? $result;
    }

    /**
     * Launch instance
     */
    public function launchInstance($instanceType, $region, $sshKeyNames, $fileSystemNames = [], $quantity = 1)
    {
        $data = [
            'instance_type_name' => $instanceType,
            'region_name' => $region,
            'ssh_key_names' => $sshKeyNames,
            'file_system_names' => $fileSystemNames,
            'quantity' => $quantity,
        ];

        return $this->request('/instance-operations/launch', 'POST', $data);
    }

    /**
     * List instances
     */
    public function listInstances()
    {
        return $this->request('/instances');
    }

    /**
     * Get instance details
     */
    public function getInstance($instanceId)
    {
        return $this->request('/instances/' . $instanceId);
    }

    /**
     * Terminate instance
     */
    public function terminateInstance($instanceIds)
    {
        $data = ['instance_ids' => is_array($instanceIds) ? $instanceIds : [$instanceIds]];
        return $this->request('/instance-operations/terminate', 'POST', $data);
    }

    /**
     * Restart instance
     */
    public function restartInstance($instanceIds)
    {
        $data = ['instance_ids' => is_array($instanceIds) ? $instanceIds : [$instanceIds]];
        return $this->request('/instance-operations/restart', 'POST', $data);
    }

    /**
     * List instance types
     */
    public function listInstanceTypes()
    {
        return $this->request('/instance-types');
    }

    /**
     * List SSH keys
     */
    public function listSshKeys()
    {
        return $this->request('/ssh-keys');
    }

    /**
     * Add SSH key
     */
    public function addSshKey($name, $publicKey)
    {
        $data = [
            'name' => $name,
            'public_key' => $publicKey,
        ];

        return $this->request('/ssh-keys', 'POST', $data);
    }

    /**
     * Delete SSH key
     */
    public function deleteSshKey($keyId)
    {
        return $this->request('/ssh-keys/' . $keyId, 'DELETE');
    }

    /**
     * List file systems
     */
    public function listFileSystems()
    {
        return $this->request('/file-systems');
    }

    /**
     * Create file system
     */
    public function createFileSystem($name, $region)
    {
        $data = [
            'name' => $name,
            'region_name' => $region,
        ];

        return $this->request('/file-systems', 'POST', $data);
    }

    /**
     * List available regions
     */
    public function listRegions()
    {
        // Lambda regions are part of instance-types response
        return $this->request('/instance-types');
    }
}

// ============================================================================
// MODULE METADATA
// ============================================================================

/**
 * Module metadata
 */
function lambda_MetaData()
{
    return [
        'DisplayName' => 'Lambda GPU Cloud',
        'APIVersion' => '1.1',
        'RequiresServer' => false,
        'DefaultNonSSLPort' => '',
        'DefaultSSLPort' => '',
        'ServiceSingleSignOnLabel' => 'SSH Access',
        'AdminSingleSignOnLabel' => 'Manage Instance',
    ];
}

// ============================================================================
// CONFIGURATION OPTIONS
// ============================================================================

/**
 * Configuration options for products
 */
function lambda_ConfigOptions()
{
    return [
        'instance_type' => [
            'FriendlyName' => 'Instance Type',
            'Type' => 'dropdown',
            'Options' => [
                'gpu_1x_a100' => '1x A100 (40GB) - $1.10/hr',
                'gpu_1x_a100_sxm4' => '1x A100 SXM4 (40GB) - $1.29/hr',
                'gpu_2x_a100' => '2x A100 (40GB) - $2.20/hr',
                'gpu_4x_a100' => '4x A100 (40GB) - $4.40/hr',
                'gpu_8x_a100' => '8x A100 (40GB) - $8.80/hr',
                'gpu_1x_a10' => '1x A10 (24GB) - $0.60/hr',
                'gpu_1x_a6000' => '1x RTX A6000 (48GB) - $0.80/hr',
                'gpu_2x_a6000' => '2x RTX A6000 (48GB) - $1.60/hr',
                'gpu_4x_a6000' => '4x RTX A6000 (48GB) - $3.20/hr',
                'gpu_1x_v100' => '1x V100 (16GB) - $0.50/hr',
                'gpu_2x_v100' => '2x V100 (16GB) - $1.00/hr',
                'gpu_4x_v100' => '4x V100 (16GB) - $2.00/hr',
                'gpu_8x_v100' => '8x V100 (16GB) - $4.00/hr',
            ],
            'Default' => 'gpu_1x_a10',
            'Description' => 'Select GPU instance type',
        ],
        'region' => [
            'FriendlyName' => 'Region',
            'Type' => 'dropdown',
            'Options' => [
                'us-west-1' => 'US West (California)',
                'us-west-2' => 'US West (Oregon)',
                'us-east-1' => 'US East (Virginia)',
                'us-south-1' => 'US South (Texas)',
                'us-midwest-1' => 'US Midwest (Illinois)',
                'europe-central-1' => 'Europe (Germany)',
                'asia-northeast-1' => 'Asia Northeast (Japan)',
                'asia-south-1' => 'Asia South (India)',
            ],
            'Default' => 'us-west-2',
            'Description' => 'Select datacenter region',
        ],
        'filesystem_size' => [
            'FriendlyName' => 'Persistent Storage',
            'Type' => 'dropdown',
            'Options' => [
                '0' => 'No persistent storage',
                '100' => '100 GB Filesystem',
                '250' => '250 GB Filesystem',
                '500' => '500 GB Filesystem',
                '1000' => '1 TB Filesystem',
                '2000' => '2 TB Filesystem',
            ],
            'Default' => '0',
            'Description' => 'Persistent filesystem (additional cost)',
        ],
        'ssh_key_name' => [
            'FriendlyName' => 'SSH Key Name',
            'Type' => 'text',
            'Size' => '30',
            'Default' => '',
            'Description' => 'SSH key name from Lambda account (required)',
        ],
    ];
}

// ============================================================================
// MODULE PARAMETERS
// ============================================================================

/**
 * Get API client from parameters
 */
function lambda_getApiClient($params)
{
    $apiKey = $params['serverpassword'] ?? '';
    return new LambdaAPI($apiKey);
}

// ============================================================================
// PROVISIONING FUNCTIONS
// ============================================================================

/**
 * Create new instance
 */
function lambda_CreateAccount(array $params)
{
    try {
        $api = lambda_getApiClient($params);

        $instanceType = $params['configoption1'] ?? 'gpu_1x_a10';
        $region = $params['configoption2'] ?? 'us-west-2';
        $filesystemSize = intval($params['configoption3'] ?? 0);
        $sshKeyName = $params['configoption4'] ?? '';

        if (empty($sshKeyName)) {
            return ['error' => 'SSH key name is required. Please add SSH key to Lambda account first.'];
        }

        $fileSystemNames = [];

        // Create filesystem if requested
        if ($filesystemSize > 0) {
            $fsName = 'fs-' . $params['serviceid'] . '-' . time();
            $fsResult = $api->createFileSystem($fsName, $region);

            if (!isset($fsResult['error'])) {
                $fileSystemNames[] = $fsName;

                // Store filesystem name
                update_query('tblhosting', [
                    'notes' => 'Filesystem: ' . $fsName,
                ], ['id' => $params['serviceid']]);
            }
        }

        // Launch instance
        $result = $api->launchInstance(
            $instanceType,
            $region,
            [$sshKeyName],
            $fileSystemNames,
            1
        );

        if (isset($result['error'])) {
            logModuleCall('lambda', 'CreateAccount', $params, $result, $result['error']);
            return ['error' => $result['error']];
        }

        $instances = $result['instance_ids'] ?? [];
        if (empty($instances)) {
            return ['error' => 'No instance ID returned'];
        }

        $instanceId = $instances[0];

        // Wait a moment and get instance details
        sleep(5);
        $instanceInfo = $api->getInstance($instanceId);

        $ipAddress = '';
        if (!isset($instanceInfo['error'])) {
            $ipAddress = $instanceInfo['ip'] ?? '';
        }

        // Store instance details
        update_query('tblhosting', [
            'dedicatedip' => $ipAddress,
            'username' => 'ubuntu',
            'domain' => $instanceId,
        ], ['id' => $params['serviceid']]);

        logModuleCall('lambda', 'CreateAccount', $params, $result, 'Instance created successfully');

        return [
            'success' => true,
            'instance_id' => $instanceId,
            'ip_address' => $ipAddress,
        ];

    } catch (\Exception $e) {
        logModuleCall('lambda', 'CreateAccount', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => 'Instance creation failed: ' . $e->getMessage()];
    }
}

/**
 * Suspend instance (not supported - will terminate)
 */
function lambda_SuspendAccount(array $params)
{
    // Lambda doesn't support pause/suspend, only terminate
    // We'll just log this action
    logModuleCall('lambda', 'SuspendAccount', $params, [], 'Lambda does not support suspension. Instance remains active.');

    return [
        'success' => true,
        'message' => 'Note: Lambda instances cannot be suspended. Instance remains active and billing continues.',
    ];
}

/**
 * Unsuspend instance (no action needed)
 */
function lambda_UnsuspendAccount(array $params)
{
    logModuleCall('lambda', 'UnsuspendAccount', $params, [], 'No action needed');

    return ['success' => true];
}

/**
 * Terminate instance (delete)
 */
function lambda_TerminateAccount(array $params)
{
    try {
        $api = lambda_getApiClient($params);
        $instanceId = $params['domain'];

        if (empty($instanceId)) {
            return ['error' => 'Instance ID not found'];
        }

        $result = $api->terminateInstance($instanceId);

        if (isset($result['error'])) {
            logModuleCall('lambda', 'TerminateAccount', $params, $result, $result['error']);
            return ['error' => $result['error']];
        }

        // Note: Filesystems are persistent and need manual deletion
        // They can be reused for future instances

        logModuleCall('lambda', 'TerminateAccount', $params, $result, 'Instance terminated');
        return ['success' => true];

    } catch (\Exception $e) {
        logModuleCall('lambda', 'TerminateAccount', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => 'Termination failed: ' . $e->getMessage()];
    }
}

/**
 * Change package (requires new instance)
 */
function lambda_ChangePackage(array $params)
{
    // Lambda doesn't support resizing
    // Instance must be terminated and recreated

    logModuleCall('lambda', 'ChangePackage', $params, [], 'Package change requires instance recreation');

    return [
        'success' => true,
        'message' => 'Note: Lambda instances cannot be resized. Please terminate and create a new instance with the desired specifications.',
    ];
}

// ============================================================================
// ADMIN FUNCTIONS
// ============================================================================

/**
 * Display additional fields in admin services tab
 */
function lambda_AdminServicesTabFields(array $params)
{
    try {
        $api = lambda_getApiClient($params);
        $instanceId = $params['domain'];

        if (empty($instanceId)) {
            return ['Instance ID' => 'Not found'];
        }

        $result = $api->getInstance($instanceId);

        if (isset($result['error'])) {
            return ['Error' => $result['error']];
        }

        $instance = $result;

        return [
            'Instance ID' => $instance['id'] ?? 'N/A',
            'Name' => $instance['name'] ?? 'N/A',
            'Status' => ucfirst($instance['status'] ?? 'unknown'),
            'Instance Type' => $instance['instance_type']['name'] ?? 'N/A',
            'GPU Type' => $instance['instance_type']['description'] ?? 'N/A',
            'Region' => $instance['region']['name'] ?? 'N/A',
            'IP Address' => $instance['ip'] ?? 'N/A',
            'Hostname' => $instance['hostname'] ?? 'N/A',
            'SSH Keys' => implode(', ', $instance['ssh_key_names'] ?? []),
            'Filesystems' => implode(', ', $instance['file_system_names'] ?? []),
        ];

    } catch (\Exception $e) {
        return ['Error' => $e->getMessage()];
    }
}

/**
 * Custom admin button functions
 */
function lambda_AdminCustomButtonArray()
{
    return [
        'Restart Instance' => 'restart',
        'Get SSH Command' => 'sshcmd',
        'List SSH Keys' => 'listssh',
        'List Filesystems' => 'listfs',
    ];
}

/**
 * Restart instance
 */
function lambda_restart(array $params)
{
    try {
        $api = lambda_getApiClient($params);
        $instanceId = $params['domain'];

        $result = $api->restartInstance($instanceId);

        if (isset($result['error'])) {
            return $result['error'];
        }

        return 'success';

    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Get SSH command
 */
function lambda_sshcmd(array $params)
{
    try {
        $api = lambda_getApiClient($params);
        $instanceId = $params['domain'];

        $result = $api->getInstance($instanceId);

        if (isset($result['error'])) {
            return $result['error'];
        }

        $ip = $result['ip'] ?? '';
        $hostname = $result['hostname'] ?? '';

        if (empty($ip)) {
            return 'IP address not available';
        }

        $cmd = "ssh ubuntu@{$ip}";
        if (!empty($hostname)) {
            $cmd .= " (or ssh ubuntu@{$hostname})";
        }

        return 'success - ' . $cmd;

    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * List SSH keys
 */
function lambda_listssh(array $params)
{
    try {
        $api = lambda_getApiClient($params);
        $result = $api->listSshKeys();

        if (isset($result['error'])) {
            return $result['error'];
        }

        $keys = [];
        foreach ($result as $key) {
            $keys[] = $key['name'] ?? 'unnamed';
        }

        return 'success - SSH Keys: ' . implode(', ', $keys);

    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * List filesystems
 */
function lambda_listfs(array $params)
{
    try {
        $api = lambda_getApiClient($params);
        $result = $api->listFileSystems();

        if (isset($result['error'])) {
            return $result['error'];
        }

        $filesystems = [];
        foreach ($result as $fs) {
            $filesystems[] = ($fs['name'] ?? 'unnamed') . ' (' . ($fs['region']['name'] ?? 'unknown') . ')';
        }

        return 'success - Filesystems: ' . implode(', ', $filesystems);

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
function lambda_ClientArea(array $params)
{
    try {
        $api = lambda_getApiClient($params);
        $instanceId = $params['domain'];

        if (empty($instanceId)) {
            return ['error' => 'Instance information not available'];
        }

        $result = $api->getInstance($instanceId);

        if (isset($result['error'])) {
            return ['error' => $result['error']];
        }

        $instance = $result;

        return [
            'templatefile' => 'templates/clientarea',
            'vars' => [
                'instance' => $instance,
                'instance_id' => $instanceId,
                'ip_address' => $instance['ip'] ?? 'N/A',
                'hostname' => $instance['hostname'] ?? 'N/A',
                'status' => $instance['status'] ?? 'unknown',
            ],
        ];

    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Custom client area buttons
 */
function lambda_ClientAreaCustomButtonArray()
{
    return [
        'Restart Instance' => 'clientrestart',
        'SSH Command' => 'clientssh',
    ];
}

/**
 * Client restart
 */
function lambda_clientrestart(array $params)
{
    return lambda_restart($params);
}

/**
 * Client SSH command
 */
function lambda_clientssh(array $params)
{
    return lambda_sshcmd($params);
}

// ============================================================================
// TEST CONNECTION
// ============================================================================

/**
 * Test API connection
 */
function lambda_TestConnection(array $params)
{
    try {
        $api = lambda_getApiClient($params);
        $result = $api->listInstances();

        if (isset($result['error'])) {
            return [
                'success' => false,
                'error' => 'API connection failed: ' . $result['error'],
            ];
        }

        return [
            'success' => true,
            'version' => 'Lambda GPU Cloud API v1',
        ];

    } catch (\Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage(),
        ];
    }
}

/**
 * Admin link
 */
function lambda_AdminLink(array $params)
{
    $instanceId = $params['domain'];

    if (empty($instanceId)) {
        return '';
    }

    return '<a href="https://cloud.lambdalabs.com/instances" target="_blank" class="btn btn-sm btn-info">
        <i class="fas fa-external-link-alt"></i> Manage in Lambda Cloud
    </a>';
}
