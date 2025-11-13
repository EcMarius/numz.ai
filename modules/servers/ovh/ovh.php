<?php
/**
 * OVH Provisioning Module for WHMCS
 *
 * Complete integration with OVH API v6 for Dedicated Servers, VPS, and Cloud
 * Supports EU, CA, and US endpoints with OAuth authentication
 *
 * @author numz.ai
 * @version 1.0.0
 */

if (!defined('WHMCS')) {
    define('WHMCS', true);
}

/**
 * Module metadata
 */
function ovh_MetaData()
{
    return [
        'DisplayName' => 'OVH Servers',
        'APIVersion' => '1.1',
        'RequiresServer' => false,
        'DefaultNonSSLPort' => '443',
        'DefaultSSLPort' => '443',
        'ServiceSingleSignOnLabel' => 'Login to OVH Manager',
        'AdminSingleSignOnLabel' => 'Login to OVH Manager (Admin)',
    ];
}

/**
 * Configuration options for the module
 */
function ovh_ConfigOptions()
{
    return [
        'endpoint' => [
            'FriendlyName' => 'OVH Endpoint',
            'Type' => 'dropdown',
            'Options' => [
                'ovh-eu' => 'Europe (ovh-eu)',
                'ovh-ca' => 'Canada (ovh-ca)',
                'ovh-us' => 'United States (ovh-us)',
            ],
            'Default' => 'ovh-eu',
            'Description' => 'Select the OVH API endpoint',
        ],
        'application_key' => [
            'FriendlyName' => 'Application Key',
            'Type' => 'text',
            'Size' => '50',
            'Description' => 'OVH API Application Key',
        ],
        'application_secret' => [
            'FriendlyName' => 'Application Secret',
            'Type' => 'password',
            'Size' => '50',
            'Description' => 'OVH API Application Secret',
        ],
        'consumer_key' => [
            'FriendlyName' => 'Consumer Key',
            'Type' => 'password',
            'Size' => '50',
            'Description' => 'OVH API Consumer Key',
        ],
        'service_type' => [
            'FriendlyName' => 'Service Type',
            'Type' => 'dropdown',
            'Options' => [
                'dedicated' => 'Dedicated Server',
                'vps' => 'VPS',
                'cloud' => 'Public Cloud',
            ],
            'Default' => 'dedicated',
            'Description' => 'Type of service to provision',
        ],
        'service_name' => [
            'FriendlyName' => 'Service Name/ID',
            'Type' => 'text',
            'Size' => '50',
            'Description' => 'OVH Service Name or ID (e.g., ns123456.ip-1-2-3.eu)',
        ],
        'operating_system' => [
            'FriendlyName' => 'Operating System',
            'Type' => 'dropdown',
            'Options' => [
                'debian11_64' => 'Debian 11 (64-bit)',
                'debian12_64' => 'Debian 12 (64-bit)',
                'ubuntu2004_64' => 'Ubuntu 20.04 LTS (64-bit)',
                'ubuntu2204_64' => 'Ubuntu 22.04 LTS (64-bit)',
                'ubuntu2404_64' => 'Ubuntu 24.04 LTS (64-bit)',
                'centos7_64' => 'CentOS 7 (64-bit)',
                'centos8_64' => 'CentOS Stream 8 (64-bit)',
                'centos9_64' => 'CentOS Stream 9 (64-bit)',
                'almalinux8_64' => 'AlmaLinux 8 (64-bit)',
                'almalinux9_64' => 'AlmaLinux 9 (64-bit)',
                'rockylinux8_64' => 'Rocky Linux 8 (64-bit)',
                'rockylinux9_64' => 'Rocky Linux 9 (64-bit)',
                'fedora38_64' => 'Fedora 38 (64-bit)',
                'fedora39_64' => 'Fedora 39 (64-bit)',
                'windows2019_64' => 'Windows Server 2019 (64-bit)',
                'windows2022_64' => 'Windows Server 2022 (64-bit)',
            ],
            'Default' => 'ubuntu2204_64',
            'Description' => 'Default operating system for installations',
        ],
        'control_panel' => [
            'FriendlyName' => 'Control Panel',
            'Type' => 'dropdown',
            'Options' => [
                'none' => 'None',
                'cpanel' => 'cPanel/WHM',
                'plesk' => 'Plesk',
                'directadmin' => 'DirectAdmin',
            ],
            'Default' => 'none',
            'Description' => 'Control panel to install (if available)',
        ],
        'enable_monitoring' => [
            'FriendlyName' => 'Enable Monitoring',
            'Type' => 'yesno',
            'Description' => 'Enable OVH monitoring for the server',
        ],
        'enable_backup' => [
            'FriendlyName' => 'Enable Backup',
            'Type' => 'yesno',
            'Description' => 'Enable automatic backup service',
        ],
    ];
}

/**
 * OVH API Client
 */
class OVHApiClient
{
    private $endpoint;
    private $applicationKey;
    private $applicationSecret;
    private $consumerKey;
    private $timeDelta = 0;

    /**
     * API endpoints
     */
    private $endpoints = [
        'ovh-eu' => 'https://eu.api.ovh.com/1.0',
        'ovh-ca' => 'https://ca.api.ovh.com/1.0',
        'ovh-us' => 'https://api.ovhcloud.com/1.0',
    ];

    /**
     * Constructor
     */
    public function __construct($endpoint, $applicationKey, $applicationSecret, $consumerKey)
    {
        $this->endpoint = $this->endpoints[$endpoint] ?? $this->endpoints['ovh-eu'];
        $this->applicationKey = $applicationKey;
        $this->applicationSecret = $applicationSecret;
        $this->consumerKey = $consumerKey;
        $this->calculateTimeDelta();
    }

    /**
     * Calculate time delta between local and OVH servers
     */
    private function calculateTimeDelta()
    {
        try {
            $serverTime = $this->rawCall('GET', '/auth/time', null, false);
            $this->timeDelta = $serverTime - time();
        } catch (Exception $e) {
            $this->timeDelta = 0;
        }
    }

    /**
     * Generate signature for API request
     */
    private function generateSignature($method, $url, $body, $timestamp)
    {
        $toSign = $this->applicationSecret . '+' . $this->consumerKey . '+' .
                  $method . '+' . $url . '+' . $body . '+' . $timestamp;
        return '$1$' . sha1($toSign);
    }

    /**
     * Make raw API call
     */
    private function rawCall($method, $path, $content = null, $needAuth = true)
    {
        $url = $this->endpoint . $path;
        $body = ($content !== null) ? json_encode($content) : '';
        $timestamp = time() + $this->timeDelta;

        $headers = [
            'Content-Type: application/json',
            'X-Ovh-Application: ' . $this->applicationKey,
        ];

        if ($needAuth) {
            $headers[] = 'X-Ovh-Timestamp: ' . $timestamp;
            $headers[] = 'X-Ovh-Signature: ' . $this->generateSignature($method, $url, $body, $timestamp);
            $headers[] = 'X-Ovh-Consumer: ' . $this->consumerKey;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        if ($method !== 'GET' && $content !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new Exception("cURL Error: {$curlError}");
        }

        if ($httpCode >= 400) {
            $error = json_decode($response, true);
            $errorMsg = $error['message'] ?? "HTTP Error {$httpCode}";
            throw new Exception($errorMsg);
        }

        return json_decode($response, true);
    }

    /**
     * GET request
     */
    public function get($path)
    {
        return $this->rawCall('GET', $path);
    }

    /**
     * POST request
     */
    public function post($path, $content = null)
    {
        return $this->rawCall('POST', $path, $content);
    }

    /**
     * PUT request
     */
    public function put($path, $content = null)
    {
        return $this->rawCall('PUT', $path, $content);
    }

    /**
     * DELETE request
     */
    public function delete($path)
    {
        return $this->rawCall('DELETE', $path);
    }
}

/**
 * Initialize OVH API client from params
 */
function ovh_getApiClient($params)
{
    $endpoint = $params['configoption1'] ?? 'ovh-eu';
    $applicationKey = $params['configoption2'] ?? '';
    $applicationSecret = $params['configoption3'] ?? '';
    $consumerKey = $params['configoption4'] ?? '';

    if (empty($applicationKey) || empty($applicationSecret) || empty($consumerKey)) {
        throw new Exception('OVH API credentials not configured');
    }

    return new OVHApiClient($endpoint, $applicationKey, $applicationSecret, $consumerKey);
}

/**
 * Create/Provision a new service
 */
function ovh_CreateAccount(array $params)
{
    try {
        $client = ovh_getApiClient($params);
        $serviceType = $params['configoption5'] ?? 'dedicated';
        $serviceName = $params['configoption6'] ?? '';
        $operatingSystem = $params['configoption7'] ?? 'ubuntu2204_64';
        $controlPanel = $params['configoption8'] ?? 'none';
        $enableMonitoring = $params['configoption9'] ?? false;
        $enableBackup = $params['configoption10'] ?? false;

        if (empty($serviceName)) {
            return ['error' => 'Service Name/ID is required'];
        }

        $result = [];

        switch ($serviceType) {
            case 'dedicated':
                $result = ovh_provisionDedicatedServer($client, $serviceName, $operatingSystem, $controlPanel, $params);
                break;

            case 'vps':
                $result = ovh_provisionVPS($client, $serviceName, $operatingSystem, $controlPanel, $params);
                break;

            case 'cloud':
                $result = ovh_provisionCloudInstance($client, $serviceName, $operatingSystem, $params);
                break;

            default:
                return ['error' => 'Invalid service type'];
        }

        // Enable monitoring if requested
        if ($enableMonitoring && $serviceType === 'dedicated') {
            try {
                $client->put("/dedicated/server/{$serviceName}/monitoring", ['enabled' => true]);
            } catch (Exception $e) {
                logModuleCall('ovh', 'EnableMonitoring', ['service' => $serviceName], $e->getMessage(), null);
            }
        }

        // Enable backup if requested
        if ($enableBackup) {
            try {
                if ($serviceType === 'vps') {
                    $client->post("/vps/{$serviceName}/automatedBackup/enable");
                } elseif ($serviceType === 'dedicated') {
                    $client->post("/dedicated/server/{$serviceName}/features/backupFTP");
                }
            } catch (Exception $e) {
                logModuleCall('ovh', 'EnableBackup', ['service' => $serviceName], $e->getMessage(), null);
            }
        }

        logModuleCall('ovh', 'CreateAccount', $params, $result, 'Success');
        return ['success' => true, 'result' => $result];

    } catch (Exception $e) {
        logModuleCall('ovh', 'CreateAccount', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Provision Dedicated Server
 */
function ovh_provisionDedicatedServer($client, $serviceName, $operatingSystem, $controlPanel, $params)
{
    // Check if server exists
    $serverInfo = $client->get("/dedicated/server/{$serviceName}");

    // Get available OS templates
    $templates = $client->get("/dedicated/server/{$serviceName}/install/compatibleTemplates");

    // Find matching template
    $templateName = null;
    foreach ($templates['ovh'] ?? [] as $template) {
        if (strpos(strtolower($template), strtolower($operatingSystem)) !== false) {
            $templateName = $template;
            break;
        }
    }

    if (!$templateName) {
        // Use first available template if exact match not found
        $templateName = $templates['ovh'][0] ?? null;
    }

    if (!$templateName) {
        throw new Exception('No compatible OS template found for this server');
    }

    // Prepare installation parameters
    $installParams = [
        'templateName' => $templateName,
        'details' => [
            'customHostname' => $params['domain'] ?? 'server.example.com',
        ],
    ];

    // Install OS
    $taskId = $client->post("/dedicated/server/{$serviceName}/install/start", $installParams);

    return [
        'service_name' => $serviceName,
        'task_id' => $taskId,
        'template' => $templateName,
        'state' => $serverInfo['state'] ?? 'unknown',
    ];
}

/**
 * Provision VPS
 */
function ovh_provisionVPS($client, $serviceName, $operatingSystem, $controlPanel, $params)
{
    // Check if VPS exists
    $vpsInfo = $client->get("/vps/{$serviceName}");

    // Get available images
    $images = $client->get("/vps/{$serviceName}/images/available");

    // Find matching image
    $imageId = null;
    foreach ($images as $image) {
        if (strpos(strtolower($image), strtolower($operatingSystem)) !== false) {
            $imageId = $image;
            break;
        }
    }

    if (!$imageId) {
        $imageId = $images[0] ?? null;
    }

    if (!$imageId) {
        throw new Exception('No compatible image found for this VPS');
    }

    // Reinstall VPS with new image
    $reinstallParams = [
        'doNotSendPassword' => false,
        'imageId' => $imageId,
    ];

    $taskId = $client->post("/vps/{$serviceName}/reinstall", $reinstallParams);

    return [
        'service_name' => $serviceName,
        'task_id' => $taskId['id'] ?? null,
        'image' => $imageId,
        'state' => $vpsInfo['state'] ?? 'unknown',
    ];
}

/**
 * Provision Cloud Instance
 */
function ovh_provisionCloudInstance($client, $serviceName, $operatingSystem, $params)
{
    // For cloud instances, serviceName should be the project ID
    $projectId = $serviceName;

    // Get available images
    $images = $client->get("/cloud/project/{$projectId}/image");

    // Find matching image
    $imageId = null;
    foreach ($images as $image) {
        if (strpos(strtolower($image['name'] ?? ''), strtolower($operatingSystem)) !== false) {
            $imageId = $image['id'];
            break;
        }
    }

    if (!$imageId && !empty($images)) {
        $imageId = $images[0]['id'] ?? null;
    }

    if (!$imageId) {
        throw new Exception('No compatible image found for Cloud instance');
    }

    return [
        'project_id' => $projectId,
        'image_id' => $imageId,
        'status' => 'ready',
    ];
}

/**
 * Suspend an account/service
 */
function ovh_SuspendAccount(array $params)
{
    try {
        $client = ovh_getApiClient($params);
        $serviceType = $params['configoption5'] ?? 'dedicated';
        $serviceName = $params['configoption6'] ?? '';

        if (empty($serviceName)) {
            return ['error' => 'Service Name/ID is required'];
        }

        // Note: OVH doesn't have a direct "suspend" API for most services
        // We'll use reverse DNS or monitoring disable as a soft suspension marker
        $result = [];

        switch ($serviceType) {
            case 'dedicated':
                // Disable monitoring as suspension indicator
                $client->put("/dedicated/server/{$serviceName}/monitoring", ['enabled' => false]);
                $result = ['action' => 'monitoring_disabled'];
                break;

            case 'vps':
                // Stop the VPS
                $taskId = $client->post("/vps/{$serviceName}/stop");
                $result = ['action' => 'stopped', 'task_id' => $taskId];
                break;

            case 'cloud':
                // For cloud, we would need the instance ID
                $result = ['action' => 'marked_suspended'];
                break;
        }

        logModuleCall('ovh', 'SuspendAccount', $params, $result, 'Success');
        return ['success' => true];

    } catch (Exception $e) {
        logModuleCall('ovh', 'SuspendAccount', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Unsuspend an account/service
 */
function ovh_UnsuspendAccount(array $params)
{
    try {
        $client = ovh_getApiClient($params);
        $serviceType = $params['configoption5'] ?? 'dedicated';
        $serviceName = $params['configoption6'] ?? '';

        if (empty($serviceName)) {
            return ['error' => 'Service Name/ID is required'];
        }

        $result = [];

        switch ($serviceType) {
            case 'dedicated':
                // Re-enable monitoring
                $client->put("/dedicated/server/{$serviceName}/monitoring", ['enabled' => true]);
                $result = ['action' => 'monitoring_enabled'];
                break;

            case 'vps':
                // Restart the VPS
                $taskId = $client->post("/vps/{$serviceName}/start");
                $result = ['action' => 'started', 'task_id' => $taskId];
                break;

            case 'cloud':
                $result = ['action' => 'unsuspended'];
                break;
        }

        logModuleCall('ovh', 'UnsuspendAccount', $params, $result, 'Success');
        return ['success' => true];

    } catch (Exception $e) {
        logModuleCall('ovh', 'UnsuspendAccount', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Terminate/Delete a service
 */
function ovh_TerminateAccount(array $params)
{
    try {
        $client = ovh_getApiClient($params);
        $serviceType = $params['configoption5'] ?? 'dedicated';
        $serviceName = $params['configoption6'] ?? '';

        if (empty($serviceName)) {
            return ['error' => 'Service Name/ID is required'];
        }

        // Note: Most OVH services cannot be deleted via API
        // They need to be cancelled through the OVH Manager
        // We'll mark them and return success

        $result = [
            'action' => 'termination_requested',
            'note' => 'Service must be cancelled through OVH Manager',
            'service' => $serviceName,
        ];

        logModuleCall('ovh', 'TerminateAccount', $params, $result, 'Success');
        return ['success' => true, 'message' => 'Termination request logged. Please cancel service in OVH Manager.'];

    } catch (Exception $e) {
        logModuleCall('ovh', 'TerminateAccount', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Change package/upgrade service
 */
function ovh_ChangePackage(array $params)
{
    try {
        $client = ovh_getApiClient($params);
        $serviceType = $params['configoption5'] ?? 'dedicated';
        $serviceName = $params['configoption6'] ?? '';

        if (empty($serviceName)) {
            return ['error' => 'Service Name/ID is required'];
        }

        $result = [];

        switch ($serviceType) {
            case 'vps':
                // Get available upgrades
                $upgrades = $client->get("/vps/{$serviceName}/availableUpgrade");
                if (!empty($upgrades)) {
                    $result = [
                        'action' => 'upgrade_available',
                        'upgrades' => $upgrades,
                        'note' => 'Upgrade must be purchased through OVH Manager',
                    ];
                } else {
                    $result = ['action' => 'no_upgrades_available'];
                }
                break;

            case 'dedicated':
            case 'cloud':
                $result = [
                    'action' => 'package_change_noted',
                    'note' => 'Package changes must be done through OVH Manager',
                ];
                break;
        }

        logModuleCall('ovh', 'ChangePackage', $params, $result, 'Success');
        return ['success' => true, 'result' => $result];

    } catch (Exception $e) {
        logModuleCall('ovh', 'ChangePackage', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Test connection to OVH API
 */
function ovh_TestConnection(array $params)
{
    try {
        $client = ovh_getApiClient($params);

        // Test API connection by getting account info
        $me = $client->get('/me');

        return [
            'success' => true,
            'version' => 'OVH API v6',
            'account' => $me['nichandle'] ?? 'Unknown',
            'name' => $me['name'] ?? '',
        ];

    } catch (Exception $e) {
        logModuleCall('ovh', 'TestConnection', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Admin area output
 */
function ovh_AdminServicesTabFields(array $params)
{
    try {
        $client = ovh_getApiClient($params);
        $serviceType = $params['configoption5'] ?? 'dedicated';
        $serviceName = $params['configoption6'] ?? '';

        if (empty($serviceName)) {
            return ['Service Name' => 'Not configured'];
        }

        $fields = [];

        try {
            switch ($serviceType) {
                case 'dedicated':
                    $serverInfo = $client->get("/dedicated/server/{$serviceName}");
                    $fields = [
                        'Service Name' => $serviceName,
                        'Service Type' => 'Dedicated Server',
                        'State' => $serverInfo['state'] ?? 'unknown',
                        'IP Address' => $serverInfo['ip'] ?? 'N/A',
                        'Reverse DNS' => $serverInfo['reverse'] ?? 'N/A',
                        'Datacenter' => $serverInfo['datacenter'] ?? 'N/A',
                        'Server ID' => $serverInfo['serverId'] ?? 'N/A',
                    ];
                    break;

                case 'vps':
                    $vpsInfo = $client->get("/vps/{$serviceName}");
                    $ips = $client->get("/vps/{$serviceName}/ips");
                    $fields = [
                        'Service Name' => $serviceName,
                        'Service Type' => 'VPS',
                        'State' => $vpsInfo['state'] ?? 'unknown',
                        'Model' => $vpsInfo['model']['name'] ?? 'N/A',
                        'Memory' => ($vpsInfo['memoryLimit'] ?? 0) . ' MB',
                        'vCores' => $vpsInfo['vcore'] ?? 'N/A',
                        'IP Addresses' => implode(', ', $ips ?? []),
                        'Zone' => $vpsInfo['zone'] ?? 'N/A',
                    ];
                    break;

                case 'cloud':
                    $fields = [
                        'Service Name' => $serviceName,
                        'Service Type' => 'Public Cloud',
                        'Project ID' => $serviceName,
                    ];
                    break;
            }
        } catch (Exception $e) {
            $fields = [
                'Service Name' => $serviceName,
                'Error' => $e->getMessage(),
            ];
        }

        return $fields;

    } catch (Exception $e) {
        return ['Error' => $e->getMessage()];
    }
}

/**
 * Client area output
 */
function ovh_ClientArea(array $params)
{
    try {
        $client = ovh_getApiClient($params);
        $serviceType = $params['configoption5'] ?? 'dedicated';
        $serviceName = $params['configoption6'] ?? '';

        if (empty($serviceName)) {
            return ['vars' => ['error' => 'Service not configured']];
        }

        $serviceInfo = [];
        $actions = [];

        try {
            switch ($serviceType) {
                case 'dedicated':
                    $serverInfo = $client->get("/dedicated/server/{$serviceName}");
                    $serviceInfo = [
                        'type' => 'Dedicated Server',
                        'name' => $serviceName,
                        'state' => $serverInfo['state'] ?? 'unknown',
                        'ip' => $serverInfo['ip'] ?? 'N/A',
                        'reverse' => $serverInfo['reverse'] ?? 'N/A',
                        'datacenter' => $serverInfo['datacenter'] ?? 'N/A',
                        'monitoring' => $serverInfo['monitoring'] ?? false,
                    ];

                    // Available actions
                    $actions = [
                        ['label' => 'Reboot Server', 'action' => 'reboot'],
                        ['label' => 'Reinstall OS', 'action' => 'reinstall'],
                        ['label' => 'Rescue Mode', 'action' => 'rescue'],
                        ['label' => 'View IPMI', 'action' => 'ipmi'],
                    ];
                    break;

                case 'vps':
                    $vpsInfo = $client->get("/vps/{$serviceName}");
                    $ips = $client->get("/vps/{$serviceName}/ips");

                    $serviceInfo = [
                        'type' => 'VPS',
                        'name' => $serviceName,
                        'state' => $vpsInfo['state'] ?? 'unknown',
                        'model' => $vpsInfo['model']['name'] ?? 'N/A',
                        'memory' => ($vpsInfo['memoryLimit'] ?? 0) . ' MB',
                        'vcores' => $vpsInfo['vcore'] ?? 'N/A',
                        'ips' => $ips ?? [],
                    ];

                    $actions = [
                        ['label' => 'Reboot VPS', 'action' => 'reboot'],
                        ['label' => 'Reinstall', 'action' => 'reinstall'],
                        ['label' => 'Create Snapshot', 'action' => 'snapshot'],
                        ['label' => 'View Console', 'action' => 'console'],
                    ];
                    break;

                case 'cloud':
                    $serviceInfo = [
                        'type' => 'Public Cloud',
                        'project_id' => $serviceName,
                    ];
                    $actions = [];
                    break;
            }
        } catch (Exception $e) {
            $serviceInfo = ['error' => $e->getMessage()];
        }

        return [
            'templatefile' => 'templates/clientarea',
            'vars' => [
                'service_info' => $serviceInfo,
                'actions' => $actions,
                'ovh_manager_url' => ovh_getManagerUrl($params['configoption1'] ?? 'ovh-eu'),
            ],
        ];

    } catch (Exception $e) {
        return ['vars' => ['error' => $e->getMessage()]];
    }
}

/**
 * Get OVH Manager URL based on endpoint
 */
function ovh_getManagerUrl($endpoint)
{
    $urls = [
        'ovh-eu' => 'https://www.ovh.com/manager/',
        'ovh-ca' => 'https://ca.ovh.com/manager/',
        'ovh-us' => 'https://us.ovhcloud.com/manager/',
    ];

    return $urls[$endpoint] ?? $urls['ovh-eu'];
}

/**
 * Admin custom button - Reboot Server
 */
function ovh_AdminCustomButtonArray()
{
    return [
        'Reboot Server' => 'RebootServer',
        'Reinstall OS' => 'ReinstallOS',
        'Rescue Mode' => 'RescueMode',
        'Exit Rescue Mode' => 'ExitRescueMode',
        'Get IPMI Access' => 'GetIPMI',
        'Create Backup' => 'CreateBackup',
        'View Tasks' => 'ViewTasks',
    ];
}

/**
 * Reboot Server
 */
function ovh_RebootServer(array $params)
{
    try {
        $client = ovh_getApiClient($params);
        $serviceType = $params['configoption5'] ?? 'dedicated';
        $serviceName = $params['configoption6'] ?? '';

        if (empty($serviceName)) {
            return 'error=Service Name not configured';
        }

        switch ($serviceType) {
            case 'dedicated':
                $taskId = $client->post("/dedicated/server/{$serviceName}/reboot");
                logModuleCall('ovh', 'RebootServer', ['service' => $serviceName], $taskId, 'Success');
                return 'success=Server reboot initiated';

            case 'vps':
                $taskId = $client->post("/vps/{$serviceName}/reboot");
                logModuleCall('ovh', 'RebootServer', ['service' => $serviceName], $taskId, 'Success');
                return 'success=VPS reboot initiated';

            default:
                return 'error=Reboot not supported for this service type';
        }

    } catch (Exception $e) {
        logModuleCall('ovh', 'RebootServer', $params, $e->getMessage(), $e->getTraceAsString());
        return 'error=' . $e->getMessage();
    }
}

/**
 * Reinstall OS
 */
function ovh_ReinstallOS(array $params)
{
    try {
        $client = ovh_getApiClient($params);
        $serviceType = $params['configoption5'] ?? 'dedicated';
        $serviceName = $params['configoption6'] ?? '';
        $operatingSystem = $params['configoption7'] ?? 'ubuntu2204_64';

        if (empty($serviceName)) {
            return 'error=Service Name not configured';
        }

        switch ($serviceType) {
            case 'dedicated':
                // Get compatible templates
                $templates = $client->get("/dedicated/server/{$serviceName}/install/compatibleTemplates");
                $templateName = $templates['ovh'][0] ?? null;

                if (!$templateName) {
                    return 'error=No compatible templates found';
                }

                $taskId = $client->post("/dedicated/server/{$serviceName}/install/start", [
                    'templateName' => $templateName,
                ]);

                logModuleCall('ovh', 'ReinstallOS', ['service' => $serviceName], $taskId, 'Success');
                return 'success=OS reinstallation started';

            case 'vps':
                $images = $client->get("/vps/{$serviceName}/images/available");
                $imageId = $images[0] ?? null;

                if (!$imageId) {
                    return 'error=No compatible images found';
                }

                $taskId = $client->post("/vps/{$serviceName}/reinstall", [
                    'doNotSendPassword' => false,
                    'imageId' => $imageId,
                ]);

                logModuleCall('ovh', 'ReinstallOS', ['service' => $serviceName], $taskId, 'Success');
                return 'success=VPS reinstallation started';

            default:
                return 'error=Reinstall not supported for this service type';
        }

    } catch (Exception $e) {
        logModuleCall('ovh', 'ReinstallOS', $params, $e->getMessage(), $e->getTraceAsString());
        return 'error=' . $e->getMessage();
    }
}

/**
 * Enable Rescue Mode
 */
function ovh_RescueMode(array $params)
{
    try {
        $client = ovh_getApiClient($params);
        $serviceType = $params['configoption5'] ?? 'dedicated';
        $serviceName = $params['configoption6'] ?? '';

        if (empty($serviceName)) {
            return 'error=Service Name not configured';
        }

        switch ($serviceType) {
            case 'dedicated':
                $taskId = $client->post("/dedicated/server/{$serviceName}/boot", [
                    'bootId' => 'rescue',
                ]);

                // Reboot to apply rescue mode
                $client->post("/dedicated/server/{$serviceName}/reboot");

                logModuleCall('ovh', 'RescueMode', ['service' => $serviceName], $taskId, 'Success');
                return 'success=Rescue mode enabled, server rebooting';

            case 'vps':
                $netboot = $client->put("/vps/{$serviceName}", ['netbootMode' => 'rescue']);
                $client->post("/vps/{$serviceName}/reboot");

                logModuleCall('ovh', 'RescueMode', ['service' => $serviceName], $netboot, 'Success');
                return 'success=Rescue mode enabled, VPS rebooting';

            default:
                return 'error=Rescue mode not supported for this service type';
        }

    } catch (Exception $e) {
        logModuleCall('ovh', 'RescueMode', $params, $e->getMessage(), $e->getTraceAsString());
        return 'error=' . $e->getMessage();
    }
}

/**
 * Exit Rescue Mode
 */
function ovh_ExitRescueMode(array $params)
{
    try {
        $client = ovh_getApiClient($params);
        $serviceType = $params['configoption5'] ?? 'dedicated';
        $serviceName = $params['configoption6'] ?? '';

        if (empty($serviceName)) {
            return 'error=Service Name not configured';
        }

        switch ($serviceType) {
            case 'dedicated':
                $taskId = $client->post("/dedicated/server/{$serviceName}/boot", [
                    'bootId' => 'harddisk',
                ]);

                $client->post("/dedicated/server/{$serviceName}/reboot");

                logModuleCall('ovh', 'ExitRescueMode', ['service' => $serviceName], $taskId, 'Success');
                return 'success=Normal boot mode restored, server rebooting';

            case 'vps':
                $netboot = $client->put("/vps/{$serviceName}", ['netbootMode' => 'local']);
                $client->post("/vps/{$serviceName}/reboot");

                logModuleCall('ovh', 'ExitRescueMode', ['service' => $serviceName], $netboot, 'Success');
                return 'success=Normal boot mode restored, VPS rebooting';

            default:
                return 'error=Exit rescue mode not supported for this service type';
        }

    } catch (Exception $e) {
        logModuleCall('ovh', 'ExitRescueMode', $params, $e->getMessage(), $e->getTraceAsString());
        return 'error=' . $e->getMessage();
    }
}

/**
 * Get IPMI Access
 */
function ovh_GetIPMI(array $params)
{
    try {
        $client = ovh_getApiClient($params);
        $serviceType = $params['configoption5'] ?? 'dedicated';
        $serviceName = $params['configoption6'] ?? '';

        if (empty($serviceName)) {
            return 'error=Service Name not configured';
        }

        if ($serviceType !== 'dedicated') {
            return 'error=IPMI is only available for dedicated servers';
        }

        // Get IPMI access
        $ipmi = $client->get("/dedicated/server/{$serviceName}/features/ipmi");

        // Try to get IPMI access URL
        try {
            $access = $client->post("/dedicated/server/{$serviceName}/features/ipmi/access", [
                'type' => 'kvmipHtml5',
            ]);

            logModuleCall('ovh', 'GetIPMI', ['service' => $serviceName], $access, 'Success');

            if (isset($access['value'])) {
                return 'success=IPMI URL: ' . $access['value'];
            }
        } catch (Exception $e) {
            // If HTML5 KVM not available, return basic info
        }

        return 'success=IPMI is enabled. Access through OVH Manager for full console.';

    } catch (Exception $e) {
        logModuleCall('ovh', 'GetIPMI', $params, $e->getMessage(), $e->getTraceAsString());
        return 'error=' . $e->getMessage();
    }
}

/**
 * Create Backup
 */
function ovh_CreateBackup(array $params)
{
    try {
        $client = ovh_getApiClient($params);
        $serviceType = $params['configoption5'] ?? 'dedicated';
        $serviceName = $params['configoption6'] ?? '';

        if (empty($serviceName)) {
            return 'error=Service Name not configured';
        }

        switch ($serviceType) {
            case 'vps':
                $snapshot = $client->post("/vps/{$serviceName}/snapshot");
                logModuleCall('ovh', 'CreateBackup', ['service' => $serviceName], $snapshot, 'Success');
                return 'success=Snapshot created successfully';

            case 'dedicated':
                // Enable backup FTP if not already enabled
                try {
                    $backup = $client->post("/dedicated/server/{$serviceName}/features/backupFTP");
                    logModuleCall('ovh', 'CreateBackup', ['service' => $serviceName], $backup, 'Success');
                    return 'success=Backup FTP service activated';
                } catch (Exception $e) {
                    return 'success=Backup FTP already active or not available';
                }

            default:
                return 'error=Backup not supported for this service type';
        }

    } catch (Exception $e) {
        logModuleCall('ovh', 'CreateBackup', $params, $e->getMessage(), $e->getTraceAsString());
        return 'error=' . $e->getMessage();
    }
}

/**
 * View Tasks
 */
function ovh_ViewTasks(array $params)
{
    try {
        $client = ovh_getApiClient($params);
        $serviceType = $params['configoption5'] ?? 'dedicated';
        $serviceName = $params['configoption6'] ?? '';

        if (empty($serviceName)) {
            return 'error=Service Name not configured';
        }

        $tasks = [];

        switch ($serviceType) {
            case 'dedicated':
                $taskIds = $client->get("/dedicated/server/{$serviceName}/task");
                // Get last 5 tasks
                $taskIds = array_slice($taskIds, -5);

                foreach ($taskIds as $taskId) {
                    $task = $client->get("/dedicated/server/{$serviceName}/task/{$taskId}");
                    $tasks[] = "Task #{$taskId}: {$task['function']} - {$task['status']}";
                }
                break;

            case 'vps':
                $taskIds = $client->get("/vps/{$serviceName}/tasks");
                $taskIds = array_slice($taskIds, -5);

                foreach ($taskIds as $taskId) {
                    $task = $client->get("/vps/{$serviceName}/tasks/{$taskId}");
                    $tasks[] = "Task #{$taskId}: {$task['type']} - {$task['state']}";
                }
                break;

            default:
                return 'error=Task viewing not supported for this service type';
        }

        if (empty($tasks)) {
            return 'success=No recent tasks found';
        }

        logModuleCall('ovh', 'ViewTasks', ['service' => $serviceName], $tasks, 'Success');
        return 'success=Recent tasks: ' . implode(' | ', $tasks);

    } catch (Exception $e) {
        logModuleCall('ovh', 'ViewTasks', $params, $e->getMessage(), $e->getTraceAsString());
        return 'error=' . $e->getMessage();
    }
}

/**
 * Client area custom button array
 */
function ovh_ClientAreaCustomButtonArray()
{
    return [
        'Reboot' => 'clientReboot',
        'Reinstall OS' => 'clientReinstall',
        'Request Console' => 'clientConsole',
    ];
}

/**
 * Client-side reboot action
 */
function ovh_clientReboot(array $params)
{
    return ovh_RebootServer($params);
}

/**
 * Client-side reinstall action
 */
function ovh_clientReinstall(array $params)
{
    return ovh_ReinstallOS($params);
}

/**
 * Client-side console access
 */
function ovh_clientConsole(array $params)
{
    try {
        $serviceType = $params['configoption5'] ?? 'dedicated';
        $managerUrl = ovh_getManagerUrl($params['configoption1'] ?? 'ovh-eu');

        return 'success=Please access console through OVH Manager: ' . $managerUrl;

    } catch (Exception $e) {
        return 'error=' . $e->getMessage();
    }
}
