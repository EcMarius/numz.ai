<?php
/**
 * Vast.ai GPU Marketplace Provisioning Module
 *
 * Comprehensive integration with Vast.ai API
 * Rent GPU instances from marketplace with spot pricing
 *
 * @version 1.0
 */

if (!defined('WHMCS')) {
    define('WHMCS', true);
}

// ============================================================================
// VAST.AI API CLIENT CLASS
// ============================================================================

class VastAiAPI
{
    private $apiKey;
    private $apiUrl = 'https://console.vast.ai/api/v0';

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

        // Add API key to query string for Vast.ai
        $separator = (strpos($url, '?') === false) ? '?' : '&';
        $url .= $separator . 'api_key=' . $this->apiKey;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
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
            return ['error' => $result['msg'] ?? $result['error'] ?? 'Unknown API error'];
        }

        return $result;
    }

    /**
     * Search for offers
     */
    public function searchOffers($filters = [])
    {
        $query = http_build_query($filters);
        return $this->request('/bundles?' . $query);
    }

    /**
     * Create instance from offer
     */
    public function createInstance($offerId, $image, $diskSpace, $label = '', $env = [])
    {
        $data = [
            'client_id' => 'whmcs',
            'image' => $image,
            'disk' => $diskSpace,
            'label' => $label,
            'env' => $env,
        ];

        return $this->request('/asks/' . $offerId, 'PUT', $data);
    }

    /**
     * Get instance details
     */
    public function getInstance($instanceId)
    {
        $result = $this->request('/instances?id=' . $instanceId);

        if (isset($result['instances']) && is_array($result['instances'])) {
            return $result['instances'][0] ?? ['error' => 'Instance not found'];
        }

        return $result;
    }

    /**
     * List all instances
     */
    public function listInstances()
    {
        $result = $this->request('/instances');
        return $result['instances'] ?? [];
    }

    /**
     * Stop instance
     */
    public function stopInstance($instanceId)
    {
        return $this->request('/instances/' . $instanceId . '/stop', 'PUT');
    }

    /**
     * Start instance
     */
    public function startInstance($instanceId)
    {
        return $this->request('/instances/' . $instanceId . '/start', 'PUT');
    }

    /**
     * Destroy instance
     */
    public function destroyInstance($instanceId)
    {
        return $this->request('/instances/' . $instanceId, 'DELETE');
    }

    /**
     * Restart instance
     */
    public function restartInstance($instanceId)
    {
        return $this->request('/instances/' . $instanceId . '/reboot', 'PUT');
    }

    /**
     * Get instance logs
     */
    public function getInstanceLogs($instanceId)
    {
        return $this->request('/instances/' . $instanceId . '/logs');
    }

    /**
     * Change bid price
     */
    public function changeBid($instanceId, $newBid)
    {
        $data = ['price' => $newBid];
        return $this->request('/instances/' . $instanceId . '/change_bid', 'PUT', $data);
    }

    /**
     * Get user info
     */
    public function getUserInfo()
    {
        return $this->request('/users/current');
    }

    /**
     * Get GPU stats
     */
    public function getGpuStats($instanceId)
    {
        return $this->request('/instances/' . $instanceId . '/gpu_stats');
    }
}

// ============================================================================
// MODULE METADATA
// ============================================================================

/**
 * Module metadata
 */
function vastai_MetaData()
{
    return [
        'DisplayName' => 'Vast.ai GPU Marketplace',
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
function vastai_ConfigOptions()
{
    return [
        'gpu_type' => [
            'FriendlyName' => 'GPU Type',
            'Type' => 'dropdown',
            'Options' => [
                'RTX_4090' => 'RTX 4090 (24GB)',
                'RTX_4080' => 'RTX 4080 (16GB)',
                'RTX_3090' => 'RTX 3090 (24GB)',
                'RTX_3080' => 'RTX 3080 (10GB)',
                'A100_PCIE' => 'A100 PCIe (40GB/80GB)',
                'A100_SXM4' => 'A100 SXM4 (40GB/80GB)',
                'A6000' => 'RTX A6000 (48GB)',
                'A5000' => 'RTX A5000 (24GB)',
                'V100' => 'Tesla V100 (16GB/32GB)',
                'P100' => 'Tesla P100 (16GB)',
            ],
            'Default' => 'RTX_3090',
            'Description' => 'Select GPU type to search for',
        ],
        'gpu_count' => [
            'FriendlyName' => 'GPU Count',
            'Type' => 'dropdown',
            'Options' => [
                '1' => '1 GPU',
                '2' => '2 GPUs',
                '4' => '4 GPUs',
                '8' => '8 GPUs',
            ],
            'Default' => '1',
            'Description' => 'Number of GPUs',
        ],
        'disk_space' => [
            'FriendlyName' => 'Disk Space',
            'Type' => 'dropdown',
            'Options' => [
                '10' => '10 GB',
                '20' => '20 GB',
                '50' => '50 GB',
                '100' => '100 GB',
                '250' => '250 GB',
                '500' => '500 GB',
                '1000' => '1 TB',
            ],
            'Default' => '50',
            'Description' => 'Storage allocation',
        ],
        'docker_image' => [
            'FriendlyName' => 'Docker Image',
            'Type' => 'dropdown',
            'Options' => [
                'pytorch/pytorch:latest' => 'PyTorch (Latest)',
                'tensorflow/tensorflow:latest-gpu' => 'TensorFlow GPU',
                'nvidia/cuda:12.0.0-base-ubuntu22.04' => 'CUDA 12.0 Base',
                'jupyter/datascience-notebook:latest' => 'Jupyter Data Science',
                'python:3.10-slim' => 'Python 3.10',
            ],
            'Default' => 'pytorch/pytorch:latest',
            'Description' => 'Docker container image',
        ],
        'max_bid_price' => [
            'FriendlyName' => 'Max Bid Price ($/hour)',
            'Type' => 'text',
            'Size' => '10',
            'Default' => '0.50',
            'Description' => 'Maximum bid price per hour',
        ],
        'min_download' => [
            'FriendlyName' => 'Min Download Speed',
            'Type' => 'dropdown',
            'Options' => [
                '100' => '100 Mbps',
                '500' => '500 Mbps',
                '1000' => '1 Gbps',
                '5000' => '5 Gbps',
                '10000' => '10 Gbps',
            ],
            'Default' => '500',
            'Description' => 'Minimum download bandwidth',
        ],
        'verified_hosts' => [
            'FriendlyName' => 'Verified Hosts Only',
            'Type' => 'yesno',
            'Default' => 'yes',
            'Description' => 'Only use verified host providers',
        ],
    ];
}

// ============================================================================
// MODULE PARAMETERS
// ============================================================================

/**
 * Get API client from parameters
 */
function vastai_getApiClient($params)
{
    $apiKey = $params['serverpassword'] ?? '';
    return new VastAiAPI($apiKey);
}

// ============================================================================
// PROVISIONING FUNCTIONS
// ============================================================================

/**
 * Create new instance
 */
function vastai_CreateAccount(array $params)
{
    try {
        $api = vastai_getApiClient($params);

        $gpuType = $params['configoption1'] ?? 'RTX_3090';
        $gpuCount = intval($params['configoption2'] ?? 1);
        $diskSpace = intval($params['configoption3'] ?? 50);
        $dockerImage = $params['configoption4'] ?? 'pytorch/pytorch:latest';
        $maxBidPrice = floatval($params['configoption5'] ?? 0.50);
        $minDownload = intval($params['configoption6'] ?? 500);
        $verifiedOnly = ($params['configoption7'] ?? 'on') === 'on';

        // Search for available offers
        $searchFilters = [
            'q' => json_encode([
                'gpu_name' => ['eq' => str_replace('_', ' ', $gpuType)],
                'num_gpus' => ['eq' => $gpuCount],
                'dph_total' => ['lte' => $maxBidPrice],
                'inet_down' => ['gte' => $minDownload],
                'verified' => ['eq' => $verifiedOnly],
                'rentable' => ['eq' => true],
            ]),
            'order' => 'dph_total',
            'type' => 'on-demand',
        ];

        $offers = $api->searchOffers($searchFilters);

        if (isset($offers['error'])) {
            logModuleCall('vastai', 'CreateAccount', $params, $offers, $offers['error']);
            return ['error' => $offers['error']];
        }

        if (empty($offers['offers'])) {
            return ['error' => 'No available offers found matching your criteria. Try adjusting GPU type or max bid price.'];
        }

        // Get the best (cheapest) offer
        $bestOffer = $offers['offers'][0];
        $offerId = $bestOffer['id'];

        // Create label
        $label = 'whmcs-' . $params['serviceid'] . '-' . time();
        $domain = $params['domain'] ?? '';
        if (!empty($domain)) {
            $label = preg_replace('/[^a-z0-9-]/', '-', strtolower($domain));
        }

        // Environment variables
        $env = [
            'JUPYTER_ENABLE_LAB' => 'yes',
        ];

        // Create instance
        $result = $api->createInstance(
            $offerId,
            $dockerImage,
            $diskSpace,
            $label,
            $env
        );

        if (isset($result['error'])) {
            logModuleCall('vastai', 'CreateAccount', $params, $result, $result['error']);
            return ['error' => $result['error']];
        }

        $instanceId = $result['new_contract'] ?? '';

        if (empty($instanceId)) {
            return ['error' => 'Instance creation failed - no contract ID returned'];
        }

        // Wait for instance to be ready and get details
        sleep(10);
        $instance = $api->getInstance($instanceId);

        $ipAddress = $instance['public_ipaddr'] ?? '';
        $sshPort = $instance['ssh_port'] ?? 22;
        $directSshPort = $instance['direct_port_start'] ?? '';

        // Store instance details
        update_query('tblhosting', [
            'dedicatedip' => $ipAddress,
            'username' => 'root',
            'domain' => $instanceId,
        ], ['id' => $params['serviceid']]);

        // Store additional info
        $notes = "SSH Port: $sshPort\nDirect SSH Port: $directSshPort\nGPU: " . ($instance['gpu_name'] ?? 'N/A');
        update_query('tblhosting', [
            'notes' => $notes,
        ], ['id' => $params['serviceid']]);

        logModuleCall('vastai', 'CreateAccount', $params, $result, 'Instance created successfully');

        return [
            'success' => true,
            'instance_id' => $instanceId,
            'ip_address' => $ipAddress,
            'ssh_port' => $sshPort,
        ];

    } catch (\Exception $e) {
        logModuleCall('vastai', 'CreateAccount', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => 'Instance creation failed: ' . $e->getMessage()];
    }
}

/**
 * Suspend instance (stop)
 */
function vastai_SuspendAccount(array $params)
{
    try {
        $api = vastai_getApiClient($params);
        $instanceId = $params['domain'];

        if (empty($instanceId)) {
            return ['error' => 'Instance ID not found'];
        }

        $result = $api->stopInstance($instanceId);

        if (isset($result['error'])) {
            logModuleCall('vastai', 'SuspendAccount', $params, $result, $result['error']);
            return ['error' => $result['error']];
        }

        logModuleCall('vastai', 'SuspendAccount', $params, $result, 'Instance stopped');
        return ['success' => true];

    } catch (\Exception $e) {
        logModuleCall('vastai', 'SuspendAccount', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => 'Suspension failed: ' . $e->getMessage()];
    }
}

/**
 * Unsuspend instance (start)
 */
function vastai_UnsuspendAccount(array $params)
{
    try {
        $api = vastai_getApiClient($params);
        $instanceId = $params['domain'];

        if (empty($instanceId)) {
            return ['error' => 'Instance ID not found'];
        }

        $result = $api->startInstance($instanceId);

        if (isset($result['error'])) {
            logModuleCall('vastai', 'UnsuspendAccount', $params, $result, $result['error']);
            return ['error' => $result['error']];
        }

        logModuleCall('vastai', 'UnsuspendAccount', $params, $result, 'Instance started');
        return ['success' => true];

    } catch (\Exception $e) {
        logModuleCall('vastai', 'UnsuspendAccount', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => 'Unsuspension failed: ' . $e->getMessage()];
    }
}

/**
 * Terminate instance (destroy)
 */
function vastai_TerminateAccount(array $params)
{
    try {
        $api = vastai_getApiClient($params);
        $instanceId = $params['domain'];

        if (empty($instanceId)) {
            return ['error' => 'Instance ID not found'];
        }

        $result = $api->destroyInstance($instanceId);

        if (isset($result['error'])) {
            logModuleCall('vastai', 'TerminateAccount', $params, $result, $result['error']);
            return ['error' => $result['error']];
        }

        logModuleCall('vastai', 'TerminateAccount', $params, $result, 'Instance destroyed');
        return ['success' => true];

    } catch (\Exception $e) {
        logModuleCall('vastai', 'TerminateAccount', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => 'Termination failed: ' . $e->getMessage()];
    }
}

/**
 * Change package (adjust bid price)
 */
function vastai_ChangePackage(array $params)
{
    try {
        $api = vastai_getApiClient($params);
        $instanceId = $params['domain'];

        if (empty($instanceId)) {
            return ['error' => 'Instance ID not found'];
        }

        $newMaxBid = floatval($params['configoption5'] ?? 0.50);

        // Change bid price
        $result = $api->changeBid($instanceId, $newMaxBid);

        if (isset($result['error'])) {
            logModuleCall('vastai', 'ChangePackage', $params, $result, $result['error']);
            return ['error' => $result['error']];
        }

        logModuleCall('vastai', 'ChangePackage', $params, $result, 'Bid price updated');

        return [
            'success' => true,
            'message' => 'Bid price updated. Note: For different GPU types, terminate and create a new instance.',
        ];

    } catch (\Exception $e) {
        logModuleCall('vastai', 'ChangePackage', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => 'Package change failed: ' . $e->getMessage()];
    }
}

// ============================================================================
// ADMIN FUNCTIONS
// ============================================================================

/**
 * Display additional fields in admin services tab
 */
function vastai_AdminServicesTabFields(array $params)
{
    try {
        $api = vastai_getApiClient($params);
        $instanceId = $params['domain'];

        if (empty($instanceId)) {
            return ['Instance ID' => 'Not found'];
        }

        $result = $api->getInstance($instanceId);

        if (isset($result['error'])) {
            return ['Error' => $result['error']];
        }

        $instance = $result;

        // Get GPU stats
        $gpuStats = $api->getGpuStats($instanceId);
        $gpuUtil = 'N/A';
        $gpuTemp = 'N/A';

        if (!isset($gpuStats['error']) && !empty($gpuStats)) {
            $gpuUtil = ($gpuStats[0]['utilization'] ?? 'N/A') . '%';
            $gpuTemp = ($gpuStats[0]['temperature'] ?? 'N/A') . '°C';
        }

        return [
            'Instance ID' => $instance['id'] ?? 'N/A',
            'Label' => $instance['label'] ?? 'N/A',
            'Status' => ucfirst($instance['actual_status'] ?? 'unknown'),
            'GPU Name' => $instance['gpu_name'] ?? 'N/A',
            'GPU Count' => $instance['num_gpus'] ?? 'N/A',
            'GPU RAM' => ($instance['gpu_ram'] ?? 0) . ' GB',
            'CPU Cores' => $instance['cpu_cores'] ?? 'N/A',
            'RAM' => ($instance['cpu_ram'] ?? 0) . ' GB',
            'Disk Space' => ($instance['disk_space'] ?? 0) . ' GB',
            'IP Address' => $instance['public_ipaddr'] ?? 'N/A',
            'SSH Port' => $instance['ssh_port'] ?? 'N/A',
            'Current Price' => '$' . number_format($instance['dph_total'] ?? 0, 4) . '/hr',
            'GPU Utilization' => $gpuUtil,
            'GPU Temperature' => $gpuTemp,
            'Host Location' => $instance['geolocation'] ?? 'N/A',
            'Verified Host' => ($instance['verified'] ?? false) ? 'Yes' : 'No',
        ];

    } catch (\Exception $e) {
        return ['Error' => $e->getMessage()];
    }
}

/**
 * Custom admin button functions
 */
function vastai_AdminCustomButtonArray()
{
    return [
        'Restart Instance' => 'restart',
        'View Logs' => 'viewlogs',
        'SSH Command' => 'sshcmd',
        'GPU Stats' => 'gpustats',
    ];
}

/**
 * Restart instance
 */
function vastai_restart(array $params)
{
    try {
        $api = vastai_getApiClient($params);
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
 * View logs
 */
function vastai_viewlogs(array $params)
{
    try {
        $api = vastai_getApiClient($params);
        $instanceId = $params['domain'];

        $result = $api->getInstanceLogs($instanceId);

        if (isset($result['error'])) {
            return $result['error'];
        }

        $logs = $result['logs'] ?? 'No logs available';
        return 'success - ' . substr($logs, 0, 500);

    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * SSH command
 */
function vastai_sshcmd(array $params)
{
    try {
        $api = vastai_getApiClient($params);
        $instanceId = $params['domain'];

        $result = $api->getInstance($instanceId);

        if (isset($result['error'])) {
            return $result['error'];
        }

        $ip = $result['public_ipaddr'] ?? '';
        $port = $result['ssh_port'] ?? 22;

        if (empty($ip)) {
            return 'IP address not available';
        }

        $cmd = "ssh -p {$port} root@{$ip}";

        return 'success - ' . $cmd;

    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * GPU stats
 */
function vastai_gpustats(array $params)
{
    try {
        $api = vastai_getApiClient($params);
        $instanceId = $params['domain'];

        $result = $api->getGpuStats($instanceId);

        if (isset($result['error'])) {
            return $result['error'];
        }

        if (empty($result)) {
            return 'No GPU stats available';
        }

        $stats = [];
        foreach ($result as $idx => $gpu) {
            $stats[] = sprintf(
                "GPU %d: %s%% util, %s°C, %sMB/%sMB VRAM",
                $idx,
                $gpu['utilization'] ?? 'N/A',
                $gpu['temperature'] ?? 'N/A',
                $gpu['memory_used'] ?? 'N/A',
                $gpu['memory_total'] ?? 'N/A'
            );
        }

        return 'success - ' . implode(' | ', $stats);

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
function vastai_ClientArea(array $params)
{
    try {
        $api = vastai_getApiClient($params);
        $instanceId = $params['domain'];

        if (empty($instanceId)) {
            return ['error' => 'Instance information not available'];
        }

        $result = $api->getInstance($instanceId);

        if (isset($result['error'])) {
            return ['error' => $result['error']];
        }

        $instance = $result;
        $gpuStats = $api->getGpuStats($instanceId);

        return [
            'templatefile' => 'templates/clientarea',
            'vars' => [
                'instance' => $instance,
                'gpu_stats' => $gpuStats,
                'instance_id' => $instanceId,
                'ip_address' => $instance['public_ipaddr'] ?? 'N/A',
                'ssh_port' => $instance['ssh_port'] ?? 'N/A',
                'status' => $instance['actual_status'] ?? 'unknown',
            ],
        ];

    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Custom client area buttons
 */
function vastai_ClientAreaCustomButtonArray()
{
    return [
        'Start' => 'clientstart',
        'Stop' => 'clientstop',
        'Restart' => 'clientrestart',
        'SSH Info' => 'clientssh',
        'GPU Stats' => 'clientgpu',
    ];
}

/**
 * Client start
 */
function vastai_clientstart(array $params)
{
    try {
        $api = vastai_getApiClient($params);
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
 * Client stop
 */
function vastai_clientstop(array $params)
{
    try {
        $api = vastai_getApiClient($params);
        $instanceId = $params['domain'];

        $result = $api->stopInstance($instanceId);

        if (isset($result['error'])) {
            return $result['error'];
        }

        return 'success';

    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Client restart
 */
function vastai_clientrestart(array $params)
{
    return vastai_restart($params);
}

/**
 * Client SSH info
 */
function vastai_clientssh(array $params)
{
    return vastai_sshcmd($params);
}

/**
 * Client GPU stats
 */
function vastai_clientgpu(array $params)
{
    return vastai_gpustats($params);
}

// ============================================================================
// TEST CONNECTION
// ============================================================================

/**
 * Test API connection
 */
function vastai_TestConnection(array $params)
{
    try {
        $api = vastai_getApiClient($params);
        $result = $api->getUserInfo();

        if (isset($result['error'])) {
            return [
                'success' => false,
                'error' => 'API connection failed: ' . $result['error'],
            ];
        }

        return [
            'success' => true,
            'version' => 'Vast.ai API v0',
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
function vastai_AdminLink(array $params)
{
    $instanceId = $params['domain'];

    if (empty($instanceId)) {
        return '';
    }

    return '<a href="https://console.vast.ai/instances/" target="_blank" class="btn btn-sm btn-info">
        <i class="fas fa-external-link-alt"></i> Manage in Vast.ai Console
    </a>';
}
