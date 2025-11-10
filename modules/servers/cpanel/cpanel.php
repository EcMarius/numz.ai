<?php
/**
 * cPanel/WHM Provisioning Module
 *
 * This is an example WHMCS-compatible provisioning module
 * that demonstrates full backward compatibility
 */

if (!defined('WHMCS')) {
    define('WHMCS', true);
}

/**
 * Module metadata
 */
function cpanel_MetaData()
{
    return [
        'DisplayName' => 'cPanel/WHM',
        'APIVersion' => '1.1',
        'RequiresServer' => true,
        'DefaultNonSSLPort' => '2086',
        'DefaultSSLPort' => '2087',
        'ServiceSingleSignOnLabel' => 'Login to cPanel',
        'AdminSingleSignOnLabel' => 'Login to WHM',
    ];
}

/**
 * Configuration options
 */
function cpanel_ConfigOptions()
{
    return [
        'package' => [
            'FriendlyName' => 'cPanel Package Name',
            'Type' => 'text',
            'Size' => '25',
            'Default' => 'default',
            'Description' => 'Enter the package name configured in WHM',
        ],
        'diskspace' => [
            'FriendlyName' => 'Disk Space (MB)',
            'Type' => 'text',
            'Size' => '10',
            'Default' => '1000',
            'Description' => 'Disk space quota in MB (0 for unlimited)',
        ],
        'bandwidth' => [
            'FriendlyName' => 'Bandwidth (MB)',
            'Type' => 'text',
            'Size' => '10',
            'Default' => '10000',
            'Description' => 'Monthly bandwidth in MB (0 for unlimited)',
        ],
        'maxaddon' => [
            'FriendlyName' => 'Max Addon Domains',
            'Type' => 'text',
            'Size' => '5',
            'Default' => '0',
            'Description' => 'Maximum number of addon domains',
        ],
        'maxsub' => [
            'FriendlyName' => 'Max Subdomains',
            'Type' => 'text',
            'Size' => '5',
            'Default' => '0',
            'Description' => 'Maximum number of subdomains',
        ],
        'maxpark' => [
            'FriendlyName' => 'Max Parked Domains',
            'Type' => 'text',
            'Size' => '5',
            'Default' => '0',
            'Description' => 'Maximum number of parked domains',
        ],
        'maxftp' => [
            'FriendlyName' => 'Max FTP Accounts',
            'Type' => 'text',
            'Size' => '5',
            'Default' => '0',
            'Description' => 'Maximum number of FTP accounts',
        ],
        'maxsql' => [
            'FriendlyName' => 'Max SQL Databases',
            'Type' => 'text',
            'Size' => '5',
            'Default' => '0',
            'Description' => 'Maximum number of SQL databases',
        ],
        'maxpop' => [
            'FriendlyName' => 'Max Email Accounts',
            'Type' => 'text',
            'Size' => '5',
            'Default' => '0',
            'Description' => 'Maximum number of email accounts',
        ],
    ];
}

/**
 * Create a new cPanel account
 */
function cpanel_CreateAccount(array $params)
{
    try {
        $serverIp = $params['serverip'];
        $serverPort = $params['serverport'] ?? 2087;
        $serverUsername = $params['serverusername'];
        $serverPassword = $params['serverpassword'];
        $serverSecure = $params['serversecure'] ?? true;

        $domain = $params['domain'];
        $username = $params['username'];
        $password = $params['password'];

        // Get config options
        $packageName = $params['configoption1'] ?? 'default';
        $diskSpace = $params['configoption2'] ?? 1000;
        $bandwidth = $params['configoption3'] ?? 10000;

        // Build API request
        $apiUrl = ($serverSecure ? 'https' : 'http') . "://{$serverIp}:{$serverPort}/json-api/createacct";

        $postData = [
            'username' => $username,
            'domain' => $domain,
            'plan' => $packageName,
            'password' => $password,
            'quota' => $diskSpace,
            'bwlimit' => $bandwidth,
        ];

        // Make API call
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERPWD, "{$serverUsername}:{$serverPassword}");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);

            logModuleCall('cpanel', 'CreateAccount', $postData, $error, $error);
            return ['error' => "Connection failed: {$error}"];
        }

        curl_close($ch);

        $result = json_decode($response, true);

        // Log the API call
        logModuleCall('cpanel', 'CreateAccount', $postData, $response, $result);

        if ($result['metadata']['result'] == 1) {
            return ['success' => true];
        } else {
            $errorMsg = $result['metadata']['reason'] ?? 'Unknown error';
            return ['error' => $errorMsg];
        }

    } catch (\Exception $e) {
        logModuleCall('cpanel', 'CreateAccount', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Suspend a cPanel account
 */
function cpanel_SuspendAccount(array $params)
{
    try {
        $serverIp = $params['serverip'];
        $serverPort = $params['serverport'] ?? 2087;
        $serverUsername = $params['serverusername'];
        $serverPassword = $params['serverpassword'];
        $serverSecure = $params['serversecure'] ?? true;

        $username = $params['username'];
        $reason = $params['suspendreason'] ?? 'Administrative action';

        // Build API request
        $apiUrl = ($serverSecure ? 'https' : 'http') . "://{$serverIp}:{$serverPort}/json-api/suspendacct";

        $postData = [
            'user' => $username,
            'reason' => $reason,
        ];

        // Make API call
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERPWD, "{$serverUsername}:{$serverPassword}");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));

        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);

        logModuleCall('cpanel', 'SuspendAccount', $postData, $response, $result);

        if ($result['metadata']['result'] == 1) {
            return ['success' => true];
        } else {
            return ['error' => $result['metadata']['reason'] ?? 'Suspension failed'];
        }

    } catch (\Exception $e) {
        logModuleCall('cpanel', 'SuspendAccount', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Unsuspend a cPanel account
 */
function cpanel_UnsuspendAccount(array $params)
{
    try {
        $serverIp = $params['serverip'];
        $serverPort = $params['serverport'] ?? 2087;
        $serverUsername = $params['serverusername'];
        $serverPassword = $params['serverpassword'];
        $serverSecure = $params['serversecure'] ?? true;

        $username = $params['username'];

        // Build API request
        $apiUrl = ($serverSecure ? 'https' : 'http') . "://{$serverIp}:{$serverPort}/json-api/unsuspendacct";

        $postData = [
            'user' => $username,
        ];

        // Make API call
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERPWD, "{$serverUsername}:{$serverPassword}");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));

        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);

        logModuleCall('cpanel', 'UnsuspendAccount', $postData, $response, $result);

        if ($result['metadata']['result'] == 1) {
            return ['success' => true];
        } else {
            return ['error' => $result['metadata']['reason'] ?? 'Unsuspension failed'];
        }

    } catch (\Exception $e) {
        logModuleCall('cpanel', 'UnsuspendAccount', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Terminate a cPanel account
 */
function cpanel_TerminateAccount(array $params)
{
    try {
        $serverIp = $params['serverip'];
        $serverPort = $params['serverport'] ?? 2087;
        $serverUsername = $params['serverusername'];
        $serverPassword = $params['serverpassword'];
        $serverSecure = $params['serversecure'] ?? true;

        $username = $params['username'];

        // Build API request
        $apiUrl = ($serverSecure ? 'https' : 'http') . "://{$serverIp}:{$serverPort}/json-api/removeacct";

        $postData = [
            'user' => $username,
        ];

        // Make API call
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERPWD, "{$serverUsername}:{$serverPassword}");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));

        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);

        logModuleCall('cpanel', 'TerminateAccount', $postData, $response, $result);

        if ($result['metadata']['result'] == 1) {
            return ['success' => true];
        } else {
            return ['error' => $result['metadata']['reason'] ?? 'Termination failed'];
        }

    } catch (\Exception $e) {
        logModuleCall('cpanel', 'TerminateAccount', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Change package of existing account
 */
function cpanel_ChangePackage(array $params)
{
    try {
        $serverIp = $params['serverip'];
        $serverPort = $params['serverport'] ?? 2087;
        $serverUsername = $params['serverusername'];
        $serverPassword = $params['serverpassword'];
        $serverSecure = $params['serversecure'] ?? true;

        $username = $params['username'];
        $packageName = $params['configoption1'] ?? 'default';

        // Build API request
        $apiUrl = ($serverSecure ? 'https' : 'http') . "://{$serverIp}:{$serverPort}/json-api/changepackage";

        $postData = [
            'user' => $username,
            'pkg' => $packageName,
        ];

        // Make API call
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERPWD, "{$serverUsername}:{$serverPassword}");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));

        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);

        logModuleCall('cpanel', 'ChangePackage', $postData, $response, $result);

        if ($result['metadata']['result'] == 1) {
            return ['success' => true];
        } else {
            return ['error' => $result['metadata']['reason'] ?? 'Package change failed'];
        }

    } catch (\Exception $e) {
        logModuleCall('cpanel', 'ChangePackage', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get client area single sign-on URL
 */
function cpanel_ClientArea(array $params)
{
    try {
        $serverIp = $params['serverip'];
        $serverPort = 2083; // cPanel port
        $serverSecure = $params['serversecure'] ?? true;
        $username = $params['username'];

        $url = ($serverSecure ? 'https' : 'http') . "://{$serverIp}:{$serverPort}";

        return [
            'templatefile' => 'templates/clientarea',
            'vars' => [
                'cpanel_url' => $url,
                'username' => $username,
            ],
        ];

    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Admin services tab additional fields
 */
function cpanel_AdminServicesTabFields(array $params)
{
    try {
        $serverIp = $params['serverip'];
        $username = $params['username'];

        return [
            'Username' => $username,
            'Server IP' => $serverIp,
            'Package' => $params['configoption1'] ?? 'default',
            'Disk Space' => $params['configoption2'] ?? '1000' . ' MB',
            'Bandwidth' => $params['configoption3'] ?? '10000' . ' MB',
        ];

    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Test connection to server
 */
function cpanel_TestConnection(array $params)
{
    try {
        $serverIp = $params['serverip'];
        $serverPort = $params['serverport'] ?? 2087;
        $serverUsername = $params['serverusername'];
        $serverPassword = $params['serverpassword'];
        $serverSecure = $params['serversecure'] ?? true;

        // Build API request
        $apiUrl = ($serverSecure ? 'https' : 'http') . "://{$serverIp}:{$serverPort}/json-api/version";

        // Make API call
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERPWD, "{$serverUsername}:{$serverPassword}");
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            return ['error' => "Connection failed: {$error}"];
        }

        curl_close($ch);

        $result = json_decode($response, true);

        if ($httpCode == 200 && isset($result['version'])) {
            return [
                'success' => true,
                'version' => $result['version'],
            ];
        } else {
            return ['error' => 'Invalid response from server'];
        }

    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}
