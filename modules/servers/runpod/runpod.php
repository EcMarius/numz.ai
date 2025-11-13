<?php
/**
 * RunPod GPU Cloud Provisioning Module
 *
 * Comprehensive integration with RunPod GraphQL API
 * GPU pod provisioning for AI/ML workloads
 *
 * @version 1.0
 */

if (!defined('WHMCS')) {
    define('WHMCS', true);
}

// ============================================================================
// RUNPOD API CLIENT CLASS
// ============================================================================

class RunPodAPI
{
    private $apiKey;
    private $apiUrl = 'https://api.runpod.io/graphql';

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * Make GraphQL request
     */
    private function graphql($query, $variables = [])
    {
        $data = [
            'query' => $query,
            'variables' => $variables,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['error' => $error];
        }

        $result = json_decode($response, true);

        if ($httpCode >= 400 || isset($result['errors'])) {
            $errorMsg = $result['errors'][0]['message'] ?? 'Unknown API error';
            return ['error' => $errorMsg];
        }

        return $result['data'] ?? [];
    }

    /**
     * Create GPU pod
     */
    public function createPod($name, $gpuType, $gpuCount, $containerImage, $volumeSize, $spotInstance = false, $ports = [], $env = [])
    {
        $query = '
            mutation CreatePod($input: PodInput!) {
                podFindAndDeployOnDemand(input: $input) {
                    id
                    name
                    runtime {
                        uptimeInSeconds
                        ports {
                            ip
                            privatePort
                            publicPort
                        }
                    }
                    machine {
                        gpuType
                        gpuCount
                    }
                }
            }
        ';

        $input = [
            'name' => $name,
            'gpuTypeId' => $gpuType,
            'gpuCount' => $gpuCount,
            'containerDiskInGb' => $volumeSize,
            'dockerArgs' => $containerImage,
            'ports' => $ports,
            'env' => $env,
        ];

        if ($spotInstance) {
            // Use spot instance mutation
            $query = str_replace('podFindAndDeployOnDemand', 'podRentInterruptable', $query);
            $input['bidPerGpu'] = 0.5; // Default bid
        }

        $variables = ['input' => $input];

        return $this->graphql($query, $variables);
    }

    /**
     * Get pod details
     */
    public function getPod($podId)
    {
        $query = '
            query GetPod($podId: String!) {
                pod(input: {podId: $podId}) {
                    id
                    name
                    runtime {
                        uptimeInSeconds
                        ports {
                            ip
                            privatePort
                            publicPort
                        }
                        gpus {
                            id
                            gpuUtilPercent
                            memoryUtilPercent
                        }
                    }
                    machine {
                        gpuType
                        gpuCount
                        cpuCount
                        memoryInGb
                    }
                    costPerHr
                }
            }
        ';

        return $this->graphql($query, ['podId' => $podId]);
    }

    /**
     * List all pods
     */
    public function listPods()
    {
        $query = '
            query GetPods {
                myself {
                    pods {
                        id
                        name
                        runtime {
                            uptimeInSeconds
                        }
                        machine {
                            gpuType
                        }
                        costPerHr
                    }
                }
            }
        ';

        $result = $this->graphql($query);
        return $result['myself']['pods'] ?? [];
    }

    /**
     * Stop pod
     */
    public function stopPod($podId)
    {
        $query = '
            mutation StopPod($podId: String!) {
                podStop(input: {podId: $podId}) {
                    id
                }
            }
        ';

        return $this->graphql($query, ['podId' => $podId]);
    }

    /**
     * Start pod
     */
    public function startPod($podId)
    {
        $query = '
            mutation StartPod($podId: String!) {
                podResume(input: {podId: $podId}) {
                    id
                }
            }
        ';

        return $this->graphql($query, ['podId' => $podId]);
    }

    /**
     * Terminate pod
     */
    public function terminatePod($podId)
    {
        $query = '
            mutation TerminatePod($podId: String!) {
                podTerminate(input: {podId: $podId})
            }
        ';

        return $this->graphql($query, ['podId' => $podId]);
    }

    /**
     * Get GPU types
     */
    public function getGpuTypes()
    {
        $query = '
            query GetGpuTypes {
                gpuTypes {
                    id
                    displayName
                    memoryInGb
                }
            }
        ';

        $result = $this->graphql($query);
        return $result['gpuTypes'] ?? [];
    }

    /**
     * Get SSH keys
     */
    public function getSshKeys()
    {
        $query = '
            query GetSshKeys {
                myself {
                    sshKeys {
                        id
                        name
                        publicKey
                    }
                }
            }
        ';

        $result = $this->graphql($query);
        return $result['myself']['sshKeys'] ?? [];
    }

    /**
     * Get pod logs
     */
    public function getPodLogs($podId)
    {
        $query = '
            query GetPodLogs($podId: String!) {
                pod(input: {podId: $podId}) {
                    runtime {
                        logs
                    }
                }
            }
        ';

        $result = $this->graphql($query, ['podId' => $podId]);
        return $result['pod']['runtime']['logs'] ?? '';
    }

    /**
     * Create storage volume
     */
    public function createVolume($name, $size)
    {
        $query = '
            mutation CreateVolume($input: NetworkVolumeInput!) {
                createNetworkVolume(input: $input) {
                    id
                    name
                    size
                }
            }
        ';

        $input = [
            'name' => $name,
            'size' => $size,
            'dataCenterId' => 'US-OR-1',
        ];

        return $this->graphql($query, ['input' => $input]);
    }
}

// ============================================================================
// MODULE METADATA
// ============================================================================

/**
 * Module metadata
 */
function runpod_MetaData()
{
    return [
        'DisplayName' => 'RunPod GPU Cloud',
        'APIVersion' => '1.1',
        'RequiresServer' => false,
        'DefaultNonSSLPort' => '',
        'DefaultSSLPort' => '',
        'ServiceSingleSignOnLabel' => 'Access Pod',
        'AdminSingleSignOnLabel' => 'Manage Pod',
    ];
}

// ============================================================================
// CONFIGURATION OPTIONS
// ============================================================================

/**
 * Configuration options for products
 */
function runpod_ConfigOptions()
{
    return [
        'gpu_type' => [
            'FriendlyName' => 'GPU Type',
            'Type' => 'dropdown',
            'Options' => [
                'NVIDIA RTX A6000' => 'RTX A6000 (48GB) - Professional',
                'NVIDIA A100 80GB' => 'A100 (80GB) - High-end training',
                'NVIDIA A100 40GB' => 'A100 (40GB) - Training',
                'NVIDIA H100 80GB' => 'H100 (80GB) - Latest gen',
                'NVIDIA RTX 4090' => 'RTX 4090 (24GB) - Consumer flagship',
                'NVIDIA RTX 3090' => 'RTX 3090 (24GB) - Cost-effective',
                'NVIDIA A40' => 'A40 (48GB) - Datacenter',
                'NVIDIA RTX A5000' => 'RTX A5000 (24GB) - Workstation',
            ],
            'Default' => 'NVIDIA RTX 3090',
            'Description' => 'Select GPU type',
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
        'container_image' => [
            'FriendlyName' => 'Container Image',
            'Type' => 'dropdown',
            'Options' => [
                'runpod/pytorch:latest' => 'PyTorch (Latest)',
                'runpod/tensorflow:latest' => 'TensorFlow (Latest)',
                'runpod/base:latest' => 'Base Ubuntu',
                'jupyter/datascience-notebook' => 'Jupyter Data Science',
                'nvidia/cuda:12.0.0-base-ubuntu22.04' => 'CUDA 12.0 Ubuntu',
            ],
            'Default' => 'runpod/pytorch:latest',
            'Description' => 'Docker container image',
        ],
        'volume_size' => [
            'FriendlyName' => 'Container Disk Size',
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
            'Description' => 'Container disk size',
        ],
        'instance_type' => [
            'FriendlyName' => 'Instance Type',
            'Type' => 'dropdown',
            'Options' => [
                'on-demand' => 'On-Demand (Guaranteed)',
                'spot' => 'Spot Instance (Up to 80% cheaper)',
            ],
            'Default' => 'on-demand',
            'Description' => 'Pricing model',
        ],
        'ssh_access' => [
            'FriendlyName' => 'Enable SSH',
            'Type' => 'yesno',
            'Default' => 'yes',
            'Description' => 'Enable SSH access to pod',
        ],
        'jupyter_port' => [
            'FriendlyName' => 'Expose Jupyter Port',
            'Type' => 'yesno',
            'Default' => 'yes',
            'Description' => 'Expose port 8888 for Jupyter',
        ],
    ];
}

// ============================================================================
// MODULE PARAMETERS
// ============================================================================

/**
 * Get API client from parameters
 */
function runpod_getApiClient($params)
{
    $apiKey = $params['serverpassword'] ?? '';
    return new RunPodAPI($apiKey);
}

// ============================================================================
// PROVISIONING FUNCTIONS
// ============================================================================

/**
 * Create new pod
 */
function runpod_CreateAccount(array $params)
{
    try {
        $api = runpod_getApiClient($params);

        $gpuType = $params['configoption1'] ?? 'NVIDIA RTX 3090';
        $gpuCount = intval($params['configoption2'] ?? 1);
        $containerImage = $params['configoption3'] ?? 'runpod/pytorch:latest';
        $volumeSize = intval($params['configoption4'] ?? 50);
        $instanceType = $params['configoption5'] ?? 'on-demand';
        $sshAccess = $params['configoption6'] ?? 'on';
        $jupyterPort = $params['configoption7'] ?? 'on';

        $podName = 'pod-' . $params['serviceid'] . '-' . time();
        $domain = $params['domain'] ?? '';
        if (!empty($domain)) {
            $podName = preg_replace('/[^a-z0-9-]/', '-', strtolower($domain));
        }

        // Setup ports
        $ports = [];
        if ($sshAccess === 'on') {
            $ports[] = ['privatePort' => 22, 'publicPort' => 0];
        }
        if ($jupyterPort === 'on') {
            $ports[] = ['privatePort' => 8888, 'publicPort' => 0];
        }

        // Environment variables
        $env = [
            ['key' => 'JUPYTER_ENABLE_LAB', 'value' => 'yes'],
            ['key' => 'JUPYTER_TOKEN', 'value' => substr(md5(time()), 0, 16)],
        ];

        $spotInstance = ($instanceType === 'spot');

        // Create pod
        $result = $api->createPod(
            $podName,
            $gpuType,
            $gpuCount,
            $containerImage,
            $volumeSize,
            $spotInstance,
            $ports,
            $env
        );

        if (isset($result['error'])) {
            logModuleCall('runpod', 'CreateAccount', $params, $result, $result['error']);
            return ['error' => $result['error']];
        }

        $pod = $result['podFindAndDeployOnDemand'] ?? $result['podRentInterruptable'] ?? [];
        $podId = $pod['id'] ?? '';

        // Get IP and ports
        $ipAddress = '';
        $sshPort = '';
        $jupyterUrl = '';

        if (isset($pod['runtime']['ports']) && is_array($pod['runtime']['ports'])) {
            foreach ($pod['runtime']['ports'] as $port) {
                $ipAddress = $port['ip'] ?? '';
                if ($port['privatePort'] == 22) {
                    $sshPort = $port['publicPort'];
                }
                if ($port['privatePort'] == 8888) {
                    $jupyterUrl = 'http://' . $ipAddress . ':' . $port['publicPort'];
                }
            }
        }

        // Store pod details
        update_query('tblhosting', [
            'dedicatedip' => $ipAddress,
            'username' => 'root',
            'domain' => $podId,
        ], ['id' => $params['serviceid']]);

        // Store additional info in notes
        $notes = "SSH Port: $sshPort\nJupyter URL: $jupyterUrl";
        update_query('tblhosting', [
            'notes' => $notes,
        ], ['id' => $params['serviceid']]);

        logModuleCall('runpod', 'CreateAccount', $params, $result, 'Pod created successfully');

        return [
            'success' => true,
            'pod_id' => $podId,
            'ip_address' => $ipAddress,
            'ssh_port' => $sshPort,
            'jupyter_url' => $jupyterUrl,
        ];

    } catch (\Exception $e) {
        logModuleCall('runpod', 'CreateAccount', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => 'Pod creation failed: ' . $e->getMessage()];
    }
}

/**
 * Suspend pod (stop)
 */
function runpod_SuspendAccount(array $params)
{
    try {
        $api = runpod_getApiClient($params);
        $podId = $params['domain'];

        if (empty($podId)) {
            return ['error' => 'Pod ID not found'];
        }

        $result = $api->stopPod($podId);

        if (isset($result['error'])) {
            logModuleCall('runpod', 'SuspendAccount', $params, $result, $result['error']);
            return ['error' => $result['error']];
        }

        logModuleCall('runpod', 'SuspendAccount', $params, $result, 'Pod stopped');
        return ['success' => true];

    } catch (\Exception $e) {
        logModuleCall('runpod', 'SuspendAccount', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => 'Suspension failed: ' . $e->getMessage()];
    }
}

/**
 * Unsuspend pod (start)
 */
function runpod_UnsuspendAccount(array $params)
{
    try {
        $api = runpod_getApiClient($params);
        $podId = $params['domain'];

        if (empty($podId)) {
            return ['error' => 'Pod ID not found'];
        }

        $result = $api->startPod($podId);

        if (isset($result['error'])) {
            logModuleCall('runpod', 'UnsuspendAccount', $params, $result, $result['error']);
            return ['error' => $result['error']];
        }

        logModuleCall('runpod', 'UnsuspendAccount', $params, $result, 'Pod started');
        return ['success' => true];

    } catch (\Exception $e) {
        logModuleCall('runpod', 'UnsuspendAccount', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => 'Unsuspension failed: ' . $e->getMessage()];
    }
}

/**
 * Terminate pod (delete)
 */
function runpod_TerminateAccount(array $params)
{
    try {
        $api = runpod_getApiClient($params);
        $podId = $params['domain'];

        if (empty($podId)) {
            return ['error' => 'Pod ID not found'];
        }

        $result = $api->terminatePod($podId);

        if (isset($result['error'])) {
            logModuleCall('runpod', 'TerminateAccount', $params, $result, $result['error']);
            return ['error' => $result['error']];
        }

        logModuleCall('runpod', 'TerminateAccount', $params, $result, 'Pod terminated');
        return ['success' => true];

    } catch (\Exception $e) {
        logModuleCall('runpod', 'TerminateAccount', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => 'Termination failed: ' . $e->getMessage()];
    }
}

/**
 * Change package (upgrade)
 */
function runpod_ChangePackage(array $params)
{
    try {
        $api = runpod_getApiClient($params);
        $podId = $params['domain'];

        if (empty($podId)) {
            return ['error' => 'Pod ID not found'];
        }

        // RunPod doesn't support direct upgrades, need to recreate
        // Stop current pod
        $api->stopPod($podId);

        logModuleCall('runpod', 'ChangePackage', $params, [], 'Pod stopped for upgrade. Create new pod with new specs.');

        return [
            'success' => true,
            'message' => 'Package change requires pod recreation. Please terminate and create a new pod.',
        ];

    } catch (\Exception $e) {
        logModuleCall('runpod', 'ChangePackage', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => 'Package change failed: ' . $e->getMessage()];
    }
}

// ============================================================================
// ADMIN FUNCTIONS
// ============================================================================

/**
 * Display additional fields in admin services tab
 */
function runpod_AdminServicesTabFields(array $params)
{
    try {
        $api = runpod_getApiClient($params);
        $podId = $params['domain'];

        if (empty($podId)) {
            return ['Pod ID' => 'Not found'];
        }

        $result = $api->getPod($podId);

        if (isset($result['error'])) {
            return ['Error' => $result['error']];
        }

        $pod = $result['pod'] ?? [];
        $runtime = $pod['runtime'] ?? [];
        $machine = $pod['machine'] ?? [];

        $uptime = isset($runtime['uptimeInSeconds']) ?
            round($runtime['uptimeInSeconds'] / 3600, 2) . ' hours' : 'N/A';

        $gpuUtil = 'N/A';
        $memUtil = 'N/A';
        if (isset($runtime['gpus'][0])) {
            $gpuUtil = $runtime['gpus'][0]['gpuUtilPercent'] . '%';
            $memUtil = $runtime['gpus'][0]['memoryUtilPercent'] . '%';
        }

        return [
            'Pod ID' => $podId,
            'Name' => $pod['name'] ?? 'N/A',
            'GPU Type' => $machine['gpuType'] ?? 'N/A',
            'GPU Count' => $machine['gpuCount'] ?? 'N/A',
            'CPU Count' => $machine['cpuCount'] ?? 'N/A',
            'Memory' => ($machine['memoryInGb'] ?? 0) . ' GB',
            'Cost Per Hour' => '$' . number_format($pod['costPerHr'] ?? 0, 3),
            'Uptime' => $uptime,
            'GPU Utilization' => $gpuUtil,
            'Memory Utilization' => $memUtil,
        ];

    } catch (\Exception $e) {
        return ['Error' => $e->getMessage()];
    }
}

/**
 * Custom admin button functions
 */
function runpod_AdminCustomButtonArray()
{
    return [
        'Restart Pod' => 'restart',
        'View Logs' => 'viewlogs',
        'SSH Info' => 'sshinfo',
        'Jupyter URL' => 'jupyterurl',
    ];
}

/**
 * Restart pod
 */
function runpod_restart(array $params)
{
    try {
        $api = runpod_getApiClient($params);
        $podId = $params['domain'];

        $api->stopPod($podId);
        sleep(5);
        $result = $api->startPod($podId);

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
function runpod_viewlogs(array $params)
{
    try {
        $api = runpod_getApiClient($params);
        $podId = $params['domain'];

        $logs = $api->getPodLogs($podId);

        if (is_array($logs) && isset($logs['error'])) {
            return $logs['error'];
        }

        return 'success - Logs: ' . substr($logs, 0, 500);

    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * SSH info
 */
function runpod_sshinfo(array $params)
{
    try {
        $api = runpod_getApiClient($params);
        $podId = $params['domain'];

        $result = $api->getPod($podId);
        $pod = $result['pod'] ?? [];
        $ports = $pod['runtime']['ports'] ?? [];

        $sshInfo = '';
        foreach ($ports as $port) {
            if ($port['privatePort'] == 22) {
                $sshInfo = "ssh root@{$port['ip']} -p {$port['publicPort']}";
                break;
            }
        }

        if (empty($sshInfo)) {
            return 'SSH not configured';
        }

        return 'success - ' . $sshInfo;

    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Jupyter URL
 */
function runpod_jupyterurl(array $params)
{
    try {
        $api = runpod_getApiClient($params);
        $podId = $params['domain'];

        $result = $api->getPod($podId);
        $pod = $result['pod'] ?? [];
        $ports = $pod['runtime']['ports'] ?? [];

        $jupyterUrl = '';
        foreach ($ports as $port) {
            if ($port['privatePort'] == 8888) {
                $jupyterUrl = "http://{$port['ip']}:{$port['publicPort']}";
                break;
            }
        }

        if (empty($jupyterUrl)) {
            return 'Jupyter not configured';
        }

        return 'success - ' . $jupyterUrl;

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
function runpod_ClientArea(array $params)
{
    try {
        $api = runpod_getApiClient($params);
        $podId = $params['domain'];

        if (empty($podId)) {
            return ['error' => 'Pod information not available'];
        }

        $result = $api->getPod($podId);

        if (isset($result['error'])) {
            return ['error' => $result['error']];
        }

        $pod = $result['pod'] ?? [];

        return [
            'templatefile' => 'templates/clientarea',
            'vars' => [
                'pod' => $pod,
                'pod_id' => $podId,
                'ip_address' => $pod['runtime']['ports'][0]['ip'] ?? 'N/A',
            ],
        ];

    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Custom client area buttons
 */
function runpod_ClientAreaCustomButtonArray()
{
    return [
        'Start Pod' => 'clientstart',
        'Stop Pod' => 'clientstop',
        'Restart Pod' => 'clientrestart',
        'SSH Access' => 'clientssh',
        'Jupyter Access' => 'clientjupyter',
    ];
}

/**
 * Client start pod
 */
function runpod_clientstart(array $params)
{
    try {
        $api = runpod_getApiClient($params);
        $podId = $params['domain'];

        $result = $api->startPod($podId);

        if (isset($result['error'])) {
            return $result['error'];
        }

        return 'success';

    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Client stop pod
 */
function runpod_clientstop(array $params)
{
    try {
        $api = runpod_getApiClient($params);
        $podId = $params['domain'];

        $result = $api->stopPod($podId);

        if (isset($result['error'])) {
            return $result['error'];
        }

        return 'success';

    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Client restart pod
 */
function runpod_clientrestart(array $params)
{
    return runpod_restart($params);
}

/**
 * Client SSH access
 */
function runpod_clientssh(array $params)
{
    return runpod_sshinfo($params);
}

/**
 * Client Jupyter access
 */
function runpod_clientjupyter(array $params)
{
    return runpod_jupyterurl($params);
}

// ============================================================================
// TEST CONNECTION
// ============================================================================

/**
 * Test API connection
 */
function runpod_TestConnection(array $params)
{
    try {
        $api = runpod_getApiClient($params);
        $result = $api->listPods();

        if (isset($result['error'])) {
            return [
                'success' => false,
                'error' => 'API connection failed: ' . $result['error'],
            ];
        }

        return [
            'success' => true,
            'version' => 'RunPod GraphQL API',
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
function runpod_AdminLink(array $params)
{
    $podId = $params['domain'];

    if (empty($podId)) {
        return '';
    }

    return '<a href="https://www.runpod.io/console/pods" target="_blank" class="btn btn-sm btn-info">
        <i class="fas fa-external-link-alt"></i> Manage in RunPod Console
    </a>';
}
