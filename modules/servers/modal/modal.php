<?php
/**
 * Modal.com Serverless GPU Provisioning Module
 *
 * Comprehensive integration with Modal.com API v1
 * Deploy serverless GPU functions for AI/ML workloads
 *
 * @version 1.0
 */

if (!defined('WHMCS')) {
    define('WHMCS', true);
}

// ============================================================================
// MODAL API CLIENT CLASS
// ============================================================================

class ModalAPI
{
    private $apiToken;
    private $apiUrl = 'https://api.modal.com/v1';

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
        } elseif ($method === 'PATCH') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
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
            return ['error' => $result['message'] ?? $result['error'] ?? 'Unknown API error'];
        }

        return $result;
    }

    /**
     * Create deployment
     */
    public function createDeployment($name, $gpuType, $gpuCount, $memory, $envVars = [])
    {
        $data = [
            'name' => $name,
            'gpu' => [
                'type' => $gpuType,
                'count' => $gpuCount,
            ],
            'memory' => $memory,
            'environment' => $envVars,
            'image' => 'modal-default::latest',
        ];

        return $this->request('/deployments', 'POST', $data);
    }

    /**
     * Get deployment details
     */
    public function getDeployment($deploymentId)
    {
        return $this->request('/deployments/' . $deploymentId);
    }

    /**
     * List deployments
     */
    public function listDeployments()
    {
        return $this->request('/deployments');
    }

    /**
     * Delete deployment
     */
    public function deleteDeployment($deploymentId)
    {
        return $this->request('/deployments/' . $deploymentId, 'DELETE');
    }

    /**
     * Pause deployment
     */
    public function pauseDeployment($deploymentId)
    {
        return $this->request('/deployments/' . $deploymentId . '/pause', 'POST');
    }

    /**
     * Resume deployment
     */
    public function resumeDeployment($deploymentId)
    {
        return $this->request('/deployments/' . $deploymentId . '/resume', 'POST');
    }

    /**
     * Update deployment
     */
    public function updateDeployment($deploymentId, $updates)
    {
        return $this->request('/deployments/' . $deploymentId, 'PATCH', $updates);
    }

    /**
     * Get deployment logs
     */
    public function getLogs($deploymentId, $limit = 100)
    {
        return $this->request('/deployments/' . $deploymentId . '/logs?limit=' . $limit);
    }

    /**
     * Get deployment metrics
     */
    public function getMetrics($deploymentId)
    {
        return $this->request('/deployments/' . $deploymentId . '/metrics');
    }

    /**
     * Create volume
     */
    public function createVolume($name, $size)
    {
        $data = [
            'name' => $name,
            'size_gb' => $size,
        ];

        return $this->request('/volumes', 'POST', $data);
    }

    /**
     * List volumes
     */
    public function listVolumes()
    {
        return $this->request('/volumes');
    }

    /**
     * Delete volume
     */
    public function deleteVolume($volumeId)
    {
        return $this->request('/volumes/' . $volumeId, 'DELETE');
    }

    /**
     * Get billing info
     */
    public function getBilling()
    {
        return $this->request('/billing');
    }

    /**
     * Get quota usage
     */
    public function getQuota()
    {
        return $this->request('/quota');
    }
}

// ============================================================================
// MODULE METADATA
// ============================================================================

/**
 * Module metadata
 */
function modal_MetaData()
{
    return [
        'DisplayName' => 'Modal.com Serverless GPU',
        'APIVersion' => '1.1',
        'RequiresServer' => false,
        'DefaultNonSSLPort' => '',
        'DefaultSSLPort' => '',
        'ServiceSingleSignOnLabel' => 'Access Dashboard',
        'AdminSingleSignOnLabel' => 'Manage Deployment',
    ];
}

// ============================================================================
// CONFIGURATION OPTIONS
// ============================================================================

/**
 * Configuration options for products
 */
function modal_ConfigOptions()
{
    return [
        'gpu_type' => [
            'FriendlyName' => 'GPU Type',
            'Type' => 'dropdown',
            'Options' => [
                'A100' => 'NVIDIA A100 (80GB) - High-end training',
                'A100-40GB' => 'NVIDIA A100 (40GB) - Training & inference',
                'H100' => 'NVIDIA H100 (80GB) - Latest generation',
                'A10G' => 'NVIDIA A10G (24GB) - Inference optimized',
                'T4' => 'NVIDIA T4 (16GB) - Cost-effective inference',
                'V100' => 'NVIDIA V100 (16GB) - General purpose',
            ],
            'Default' => 'A10G',
            'Description' => 'Select GPU type for compute',
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
        'memory' => [
            'FriendlyName' => 'System Memory',
            'Type' => 'dropdown',
            'Options' => [
                '8' => '8 GB RAM',
                '16' => '16 GB RAM',
                '32' => '32 GB RAM',
                '64' => '64 GB RAM',
                '128' => '128 GB RAM',
                '256' => '256 GB RAM',
            ],
            'Default' => '16',
            'Description' => 'System RAM allocation',
        ],
        'storage' => [
            'FriendlyName' => 'Storage Volume',
            'Type' => 'dropdown',
            'Options' => [
                '0' => 'No persistent storage',
                '10' => '10 GB Volume',
                '50' => '50 GB Volume',
                '100' => '100 GB Volume',
                '250' => '250 GB Volume',
                '500' => '500 GB Volume',
                '1000' => '1 TB Volume',
            ],
            'Default' => '50',
            'Description' => 'Persistent storage volume size',
        ],
        'environment' => [
            'FriendlyName' => 'Environment Variables',
            'Type' => 'text',
            'Size' => '50',
            'Default' => '',
            'Description' => 'Environment variables (JSON format)',
        ],
        'timeout' => [
            'FriendlyName' => 'Function Timeout',
            'Type' => 'dropdown',
            'Options' => [
                '60' => '1 minute',
                '300' => '5 minutes',
                '600' => '10 minutes',
                '1800' => '30 minutes',
                '3600' => '1 hour',
                '7200' => '2 hours',
            ],
            'Default' => '600',
            'Description' => 'Maximum function execution time',
        ],
    ];
}

// ============================================================================
// MODULE PARAMETERS
// ============================================================================

/**
 * Get API client from parameters
 */
function modal_getApiClient($params)
{
    $apiToken = $params['serverpassword'] ?? '';
    return new ModalAPI($apiToken);
}

// ============================================================================
// PROVISIONING FUNCTIONS
// ============================================================================

/**
 * Create new deployment
 */
function modal_CreateAccount(array $params)
{
    try {
        $api = modal_getApiClient($params);

        $gpuType = $params['configoption1'] ?? 'A10G';
        $gpuCount = intval($params['configoption2'] ?? 1);
        $memory = intval($params['configoption3'] ?? 16);
        $storage = intval($params['configoption4'] ?? 0);
        $envVars = [];

        if (!empty($params['configoption5'])) {
            $envVars = json_decode($params['configoption5'], true) ?: [];
        }

        $deploymentName = 'deployment-' . $params['serviceid'] . '-' . time();
        $domain = $params['domain'] ?? '';
        if (!empty($domain)) {
            $deploymentName = preg_replace('/[^a-z0-9-]/', '-', strtolower($domain));
        }

        // Create deployment
        $result = $api->createDeployment(
            $deploymentName,
            $gpuType,
            $gpuCount,
            $memory * 1024, // Convert to MB
            $envVars
        );

        if (isset($result['error'])) {
            logModuleCall('modal', 'CreateAccount', $params, $result, $result['error']);
            return ['error' => $result['error']];
        }

        $deploymentId = $result['id'] ?? '';
        $endpoint = $result['endpoint'] ?? '';

        // Create volume if specified
        $volumeId = null;
        if ($storage > 0) {
            $volumeResult = $api->createVolume($deploymentName . '-volume', $storage);
            if (!isset($volumeResult['error'])) {
                $volumeId = $volumeResult['id'] ?? null;
            }
        }

        // Store deployment details
        update_query('tblhosting', [
            'dedicatedip' => $endpoint,
            'username' => $deploymentId,
            'domain' => $deploymentId,
        ], ['id' => $params['serviceid']]);

        // Store volume ID in custom fields
        if ($volumeId) {
            update_query('tblhosting', [
                'notes' => 'Volume ID: ' . $volumeId,
            ], ['id' => $params['serviceid']]);
        }

        logModuleCall('modal', 'CreateAccount', $params, $result, 'Deployment created successfully');

        return [
            'success' => true,
            'deployment_id' => $deploymentId,
            'endpoint' => $endpoint,
        ];

    } catch (\Exception $e) {
        logModuleCall('modal', 'CreateAccount', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => 'Deployment creation failed: ' . $e->getMessage()];
    }
}

/**
 * Suspend deployment (pause)
 */
function modal_SuspendAccount(array $params)
{
    try {
        $api = modal_getApiClient($params);
        $deploymentId = $params['domain'];

        if (empty($deploymentId)) {
            return ['error' => 'Deployment ID not found'];
        }

        $result = $api->pauseDeployment($deploymentId);

        if (isset($result['error'])) {
            logModuleCall('modal', 'SuspendAccount', $params, $result, $result['error']);
            return ['error' => $result['error']];
        }

        logModuleCall('modal', 'SuspendAccount', $params, $result, 'Deployment paused');
        return ['success' => true];

    } catch (\Exception $e) {
        logModuleCall('modal', 'SuspendAccount', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => 'Suspension failed: ' . $e->getMessage()];
    }
}

/**
 * Unsuspend deployment (resume)
 */
function modal_UnsuspendAccount(array $params)
{
    try {
        $api = modal_getApiClient($params);
        $deploymentId = $params['domain'];

        if (empty($deploymentId)) {
            return ['error' => 'Deployment ID not found'];
        }

        $result = $api->resumeDeployment($deploymentId);

        if (isset($result['error'])) {
            logModuleCall('modal', 'UnsuspendAccount', $params, $result, $result['error']);
            return ['error' => $result['error']];
        }

        logModuleCall('modal', 'UnsuspendAccount', $params, $result, 'Deployment resumed');
        return ['success' => true];

    } catch (\Exception $e) {
        logModuleCall('modal', 'UnsuspendAccount', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => 'Unsuspension failed: ' . $e->getMessage()];
    }
}

/**
 * Terminate deployment (delete)
 */
function modal_TerminateAccount(array $params)
{
    try {
        $api = modal_getApiClient($params);
        $deploymentId = $params['domain'];

        if (empty($deploymentId)) {
            return ['error' => 'Deployment ID not found'];
        }

        // Delete volume if exists
        $notes = get_query_val('tblhosting', 'notes', ['id' => $params['serviceid']]);
        if (preg_match('/Volume ID: ([a-zA-Z0-9-]+)/', $notes, $matches)) {
            $volumeId = $matches[1];
            $api->deleteVolume($volumeId);
        }

        // Delete deployment
        $result = $api->deleteDeployment($deploymentId);

        if (isset($result['error'])) {
            logModuleCall('modal', 'TerminateAccount', $params, $result, $result['error']);
            return ['error' => $result['error']];
        }

        logModuleCall('modal', 'TerminateAccount', $params, $result, 'Deployment deleted');
        return ['success' => true];

    } catch (\Exception $e) {
        logModuleCall('modal', 'TerminateAccount', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => 'Termination failed: ' . $e->getMessage()];
    }
}

/**
 * Change package (upgrade/downgrade)
 */
function modal_ChangePackage(array $params)
{
    try {
        $api = modal_getApiClient($params);
        $deploymentId = $params['domain'];

        if (empty($deploymentId)) {
            return ['error' => 'Deployment ID not found'];
        }

        $gpuType = $params['configoption1'] ?? 'A10G';
        $gpuCount = intval($params['configoption2'] ?? 1);
        $memory = intval($params['configoption3'] ?? 16);

        $updates = [
            'gpu' => [
                'type' => $gpuType,
                'count' => $gpuCount,
            ],
            'memory' => $memory * 1024,
        ];

        $result = $api->updateDeployment($deploymentId, $updates);

        if (isset($result['error'])) {
            logModuleCall('modal', 'ChangePackage', $params, $result, $result['error']);
            return ['error' => $result['error']];
        }

        logModuleCall('modal', 'ChangePackage', $params, $result, 'Package changed');
        return ['success' => true];

    } catch (\Exception $e) {
        logModuleCall('modal', 'ChangePackage', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => 'Package change failed: ' . $e->getMessage()];
    }
}

// ============================================================================
// ADMIN FUNCTIONS
// ============================================================================

/**
 * Display additional fields in admin services tab
 */
function modal_AdminServicesTabFields(array $params)
{
    try {
        $api = modal_getApiClient($params);
        $deploymentId = $params['domain'];

        if (empty($deploymentId)) {
            return ['Deployment ID' => 'Not found'];
        }

        $result = $api->getDeployment($deploymentId);

        if (isset($result['error'])) {
            return ['Error' => $result['error']];
        }

        $metrics = $api->getMetrics($deploymentId);
        $billing = $api->getBilling();

        return [
            'Deployment ID' => $deploymentId,
            'Status' => ucfirst($result['status'] ?? 'unknown'),
            'Endpoint' => $result['endpoint'] ?? 'N/A',
            'GPU Type' => $result['gpu']['type'] ?? 'N/A',
            'GPU Count' => $result['gpu']['count'] ?? 'N/A',
            'Memory' => ($result['memory'] / 1024) . ' GB',
            'Executions (24h)' => $metrics['executions_24h'] ?? '0',
            'Total Runtime (24h)' => round(($metrics['runtime_seconds_24h'] ?? 0) / 3600, 2) . ' hours',
            'Current Cost (Month)' => '$' . number_format($billing['current_month'] ?? 0, 2),
            'Created' => $result['created_at'] ?? 'N/A',
        ];

    } catch (\Exception $e) {
        return ['Error' => $e->getMessage()];
    }
}

/**
 * Custom admin button functions
 */
function modal_AdminCustomButtonArray()
{
    return [
        'View Logs' => 'viewlogs',
        'View Metrics' => 'viewmetrics',
        'Restart Deployment' => 'restart',
        'View Billing' => 'billing',
    ];
}

/**
 * View logs
 */
function modal_viewlogs(array $params)
{
    try {
        $api = modal_getApiClient($params);
        $deploymentId = $params['domain'];

        $result = $api->getLogs($deploymentId, 50);

        if (isset($result['error'])) {
            return $result['error'];
        }

        $logs = $result['logs'] ?? [];
        $output = "Recent logs:\n\n";
        foreach ($logs as $log) {
            $output .= "[{$log['timestamp']}] {$log['message']}\n";
        }

        return 'success - ' . $output;

    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * View metrics
 */
function modal_viewmetrics(array $params)
{
    try {
        $api = modal_getApiClient($params);
        $deploymentId = $params['domain'];

        $result = $api->getMetrics($deploymentId);

        if (isset($result['error'])) {
            return $result['error'];
        }

        $output = sprintf(
            "Executions: %d | Runtime: %.2f hrs | GPU Time: %.2f hrs | Cost: $%.2f",
            $result['executions_24h'] ?? 0,
            ($result['runtime_seconds_24h'] ?? 0) / 3600,
            ($result['gpu_seconds_24h'] ?? 0) / 3600,
            $result['cost_24h'] ?? 0
        );

        return 'success - ' . $output;

    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Restart deployment
 */
function modal_restart(array $params)
{
    try {
        $api = modal_getApiClient($params);
        $deploymentId = $params['domain'];

        // Pause then resume
        $api->pauseDeployment($deploymentId);
        sleep(3);
        $result = $api->resumeDeployment($deploymentId);

        if (isset($result['error'])) {
            return $result['error'];
        }

        return 'success';

    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * View billing
 */
function modal_billing(array $params)
{
    try {
        $api = modal_getApiClient($params);
        $result = $api->getBilling();

        if (isset($result['error'])) {
            return $result['error'];
        }

        $output = sprintf(
            "Current Month: $%.2f | Last Month: $%.2f | Quota Used: %.0f%%",
            $result['current_month'] ?? 0,
            $result['last_month'] ?? 0,
            ($result['quota_used'] ?? 0) * 100
        );

        return 'success - ' . $output;

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
function modal_ClientArea(array $params)
{
    try {
        $api = modal_getApiClient($params);
        $deploymentId = $params['domain'];

        if (empty($deploymentId)) {
            return ['error' => 'Deployment information not available'];
        }

        $deployment = $api->getDeployment($deploymentId);

        if (isset($deployment['error'])) {
            return ['error' => $deployment['error']];
        }

        $metrics = $api->getMetrics($deploymentId);
        $logs = $api->getLogs($deploymentId, 20);

        return [
            'templatefile' => 'templates/clientarea',
            'vars' => [
                'deployment' => $deployment,
                'metrics' => $metrics,
                'logs' => $logs['logs'] ?? [],
                'status' => $deployment['status'] ?? 'unknown',
                'endpoint' => $deployment['endpoint'] ?? '',
            ],
        ];

    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Custom client area buttons
 */
function modal_ClientAreaCustomButtonArray()
{
    return [
        'View Logs' => 'clientlogs',
        'View Metrics' => 'clientmetrics',
        'Restart' => 'clientrestart',
    ];
}

/**
 * Client view logs
 */
function modal_clientlogs(array $params)
{
    return modal_viewlogs($params);
}

/**
 * Client view metrics
 */
function modal_clientmetrics(array $params)
{
    return modal_viewmetrics($params);
}

/**
 * Client restart
 */
function modal_clientrestart(array $params)
{
    return modal_restart($params);
}

// ============================================================================
// TEST CONNECTION
// ============================================================================

/**
 * Test API connection
 */
function modal_TestConnection(array $params)
{
    try {
        $api = modal_getApiClient($params);
        $result = $api->listDeployments();

        if (isset($result['error'])) {
            return [
                'success' => false,
                'error' => 'API connection failed: ' . $result['error'],
            ];
        }

        return [
            'success' => true,
            'version' => 'Modal.com API v1',
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
function modal_AdminLink(array $params)
{
    $deploymentId = $params['domain'];

    if (empty($deploymentId)) {
        return '';
    }

    return '<a href="https://modal.com/apps" target="_blank" class="btn btn-sm btn-info">
        <i class="fas fa-external-link-alt"></i> Manage in Modal Dashboard
    </a>';
}
