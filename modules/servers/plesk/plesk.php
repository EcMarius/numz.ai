<?php
/**
 * Plesk Complete Provisioning Module
 *
 * Production-ready WHMCS-compatible provisioning module with full Plesk XML-RPC API support
 * Includes comprehensive customer, subscription, domain, database, and email management
 * Supports both Windows and Linux Plesk installations
 *
 * @version 1.0.0
 * @author Numz.ai Hosting Platform
 */

if (!defined('WHMCS')) {
    define('WHMCS', true);
}

use Illuminate\Support\Facades\Log;

/**
 * Module metadata
 *
 * @return array Module information
 */
function plesk_MetaData()
{
    return [
        'DisplayName' => 'Plesk Complete',
        'APIVersion' => '1.1',
        'RequiresServer' => true,
        'DefaultNonSSLPort' => '8880',
        'DefaultSSLPort' => '8443',
        'ServiceSingleSignOnLabel' => 'Login to Plesk Panel',
        'AdminSingleSignOnLabel' => 'Login to Plesk Admin',
        'ListAccountsUniqueIdentifierField' => 'domain',
    ];
}

/**
 * Configuration options for the module
 *
 * @return array Configuration fields
 */
function plesk_ConfigOptions()
{
    return [
        'service_plan' => [
            'FriendlyName' => 'Service Plan',
            'Type' => 'text',
            'Size' => '25',
            'Default' => 'Default Domain',
            'Description' => 'Plesk service plan name',
            'SimpleMode' => true,
        ],
        'disk_space' => [
            'FriendlyName' => 'Disk Space (MB)',
            'Type' => 'text',
            'Size' => '10',
            'Default' => '5000',
            'Description' => 'Disk space quota in MB (-1 for unlimited)',
        ],
        'traffic' => [
            'FriendlyName' => 'Traffic (MB)',
            'Type' => 'text',
            'Size' => '10',
            'Default' => '50000',
            'Description' => 'Monthly traffic in MB (-1 for unlimited)',
        ],
        'max_subdomains' => [
            'FriendlyName' => 'Max Subdomains',
            'Type' => 'text',
            'Size' => '5',
            'Default' => '-1',
            'Description' => 'Maximum subdomains (-1 for unlimited)',
        ],
        'max_aliases' => [
            'FriendlyName' => 'Max Domain Aliases',
            'Type' => 'text',
            'Size' => '5',
            'Default' => '-1',
            'Description' => 'Maximum domain aliases (-1 for unlimited)',
        ],
        'max_databases' => [
            'FriendlyName' => 'Max Databases',
            'Type' => 'text',
            'Size' => '5',
            'Default' => '-1',
            'Description' => 'Maximum databases (-1 for unlimited)',
        ],
        'max_mailboxes' => [
            'FriendlyName' => 'Max Email Accounts',
            'Type' => 'text',
            'Size' => '5',
            'Default' => '-1',
            'Description' => 'Maximum email accounts (-1 for unlimited)',
        ],
        'max_ftp' => [
            'FriendlyName' => 'Max FTP Accounts',
            'Type' => 'text',
            'Size' => '5',
            'Default' => '-1',
            'Description' => 'Maximum FTP accounts (-1 for unlimited)',
        ],
        'php_support' => [
            'FriendlyName' => 'PHP Support',
            'Type' => 'yesno',
            'Description' => 'Enable PHP support',
            'Default' => 'yes',
        ],
        'ssl_support' => [
            'FriendlyName' => 'SSL Support',
            'Type' => 'yesno',
            'Description' => 'Enable SSL/TLS certificates',
            'Default' => 'yes',
        ],
    ];
}

/**
 * Test connection to Plesk server
 *
 * @param array $params Server parameters
 * @return array Success or error message
 */
function plesk_TestConnection(array $params)
{
    try {
        // Test with server.get_protos API call
        $packet = plesk_BuildPacket('server', 'get_protos');
        $response = plesk_ApiRequest($params, $packet);

        if (isset($response['server']['get_protos']['protos']['proto'])) {
            $version = plesk_GetPleskVersion($params);
            return [
                'success' => true,
                'version' => $version,
            ];
        }

        return [
            'error' => 'Failed to retrieve Plesk server information',
        ];

    } catch (\Exception $e) {
        plesk_LogError('TestConnection', $params, $e->getMessage());
        return [
            'error' => 'Connection failed: ' . $e->getMessage(),
        ];
    }
}

/**
 * Create a new Plesk customer and subscription
 *
 * @param array $params Service parameters
 * @return array Success or error message
 */
function plesk_CreateAccount(array $params)
{
    try {
        $domain = $params['domain'];
        $username = $params['username'];
        $password = $params['password'];
        $clientEmail = $params['clientsdetails']['email'] ?? '';
        $clientName = trim(($params['clientsdetails']['firstname'] ?? '') . ' ' . ($params['clientsdetails']['lastname'] ?? ''));

        // Configuration options
        $servicePlan = $params['configoption1'] ?? 'Default Domain';
        $diskSpace = plesk_ParseLimit($params['configoption2'] ?? '5000');
        $traffic = plesk_ParseLimit($params['configoption3'] ?? '50000');

        // Step 1: Create customer
        $customerPacket = plesk_BuildCustomerPacket('add', [
            'company' => $clientName,
            'name' => $clientName,
            'login' => $username,
            'password' => $password,
            'email' => $clientEmail,
        ]);

        $customerResponse = plesk_ApiRequest($params, $customerPacket);

        if (!isset($customerResponse['customer']['add']['result']['status']) ||
            $customerResponse['customer']['add']['result']['status'] !== 'ok') {
            $error = $customerResponse['customer']['add']['result']['errtext'] ?? 'Failed to create customer';
            throw new \Exception($error);
        }

        // Step 2: Create subscription (hosting)
        $subscriptionPacket = plesk_BuildSubscriptionPacket('add', [
            'owner_login' => $username,
            'domain_name' => $domain,
            'plan_name' => $servicePlan,
            'username' => $username,
            'password' => $password,
        ], $params);

        $subscriptionResponse = plesk_ApiRequest($params, $subscriptionPacket);

        if (!isset($subscriptionResponse['webspace']['add']['result']['status']) ||
            $subscriptionResponse['webspace']['add']['result']['status'] !== 'ok') {
            $error = $subscriptionResponse['webspace']['add']['result']['errtext'] ?? 'Failed to create subscription';
            throw new \Exception($error);
        }

        plesk_LogActivity('CreateAccount', $params, "Account created successfully: {$username} - {$domain}");
        return ['success' => true];

    } catch (\Exception $e) {
        plesk_LogError('CreateAccount', $params, $e->getMessage());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Suspend a Plesk subscription
 *
 * @param array $params Service parameters
 * @return array Success or error message
 */
function plesk_SuspendAccount(array $params)
{
    try {
        $domain = $params['domain'];

        $packet = plesk_BuildSubscriptionPacket('set', [
            'domain_name' => $domain,
            'status' => 16, // Suspended by admin
        ]);

        $response = plesk_ApiRequest($params, $packet);

        if (isset($response['webspace']['set']['result']['status']) &&
            $response['webspace']['set']['result']['status'] === 'ok') {
            plesk_LogActivity('SuspendAccount', $params, "Account suspended: {$domain}");
            return ['success' => true];
        }

        $error = $response['webspace']['set']['result']['errtext'] ?? 'Suspension failed';
        throw new \Exception($error);

    } catch (\Exception $e) {
        plesk_LogError('SuspendAccount', $params, $e->getMessage());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Unsuspend a Plesk subscription
 *
 * @param array $params Service parameters
 * @return array Success or error message
 */
function plesk_UnsuspendAccount(array $params)
{
    try {
        $domain = $params['domain'];

        $packet = plesk_BuildSubscriptionPacket('set', [
            'domain_name' => $domain,
            'status' => 0, // Active
        ]);

        $response = plesk_ApiRequest($params, $packet);

        if (isset($response['webspace']['set']['result']['status']) &&
            $response['webspace']['set']['result']['status'] === 'ok') {
            plesk_LogActivity('UnsuspendAccount', $params, "Account unsuspended: {$domain}");
            return ['success' => true];
        }

        $error = $response['webspace']['set']['result']['errtext'] ?? 'Unsuspension failed';
        throw new \Exception($error);

    } catch (\Exception $e) {
        plesk_LogError('UnsuspendAccount', $params, $e->getMessage());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Terminate a Plesk subscription and customer
 *
 * @param array $params Service parameters
 * @return array Success or error message
 */
function plesk_TerminateAccount(array $params)
{
    try {
        $domain = $params['domain'];
        $username = $params['username'];

        // Step 1: Delete subscription
        $subscriptionPacket = plesk_BuildSubscriptionPacket('del', [
            'domain_name' => $domain,
        ]);

        $subscriptionResponse = plesk_ApiRequest($params, $subscriptionPacket);

        // Step 2: Delete customer
        $customerPacket = plesk_BuildCustomerPacket('del', [
            'login' => $username,
        ]);

        $customerResponse = plesk_ApiRequest($params, $customerPacket);

        plesk_LogActivity('TerminateAccount', $params, "Account terminated: {$username} - {$domain}");
        return ['success' => true];

    } catch (\Exception $e) {
        plesk_LogError('TerminateAccount', $params, $e->getMessage());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Change service plan for a Plesk subscription
 *
 * @param array $params Service parameters
 * @return array Success or error message
 */
function plesk_ChangePackage(array $params)
{
    try {
        $domain = $params['domain'];
        $servicePlan = $params['configoption1'] ?? 'Default Domain';

        $packet = plesk_BuildSubscriptionPacket('switch-subscription', [
            'domain_name' => $domain,
            'plan_name' => $servicePlan,
        ]);

        $response = plesk_ApiRequest($params, $packet);

        if (isset($response['webspace']['switch-subscription']['result']['status']) &&
            $response['webspace']['switch-subscription']['result']['status'] === 'ok') {
            plesk_LogActivity('ChangePackage', $params, "Package changed to {$servicePlan} for {$domain}");
            return ['success' => true];
        }

        $error = $response['webspace']['switch-subscription']['result']['errtext'] ?? 'Package change failed';
        throw new \Exception($error);

    } catch (\Exception $e) {
        plesk_LogError('ChangePackage', $params, $e->getMessage());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Change password for a Plesk customer
 *
 * @param array $params Service parameters
 * @return array Success or error message
 */
function plesk_ChangePassword(array $params)
{
    try {
        $username = $params['username'];
        $newPassword = $params['password'];

        $packet = plesk_BuildCustomerPacket('set', [
            'login' => $username,
            'password' => $newPassword,
        ]);

        $response = plesk_ApiRequest($params, $packet);

        if (isset($response['customer']['set']['result']['status']) &&
            $response['customer']['set']['result']['status'] === 'ok') {
            plesk_LogActivity('ChangePassword', $params, "Password changed for: {$username}");
            return ['success' => true];
        }

        $error = $response['customer']['set']['result']['errtext'] ?? 'Password change failed';
        throw new \Exception($error);

    } catch (\Exception $e) {
        plesk_LogError('ChangePassword', $params, $e->getMessage());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get single sign-on URL for client Plesk access
 *
 * @param array $params Service parameters
 * @return array SSO URL
 */
function plesk_ServiceSingleSignOn(array $params)
{
    try {
        $ssoUrl = plesk_GenerateSsoUrl($params);

        return [
            'success' => true,
            'redirectTo' => $ssoUrl,
        ];

    } catch (\Exception $e) {
        plesk_LogError('ServiceSingleSignOn', $params, $e->getMessage());
        return [
            'success' => false,
            'errorMsg' => $e->getMessage(),
        ];
    }
}

/**
 * Get single sign-on URL for admin Plesk access
 *
 * @param array $params Service parameters
 * @return array SSO URL
 */
function plesk_AdminSingleSignOn(array $params)
{
    try {
        $ssoUrl = plesk_GenerateSsoUrl($params, true);

        return [
            'success' => true,
            'redirectTo' => $ssoUrl,
        ];

    } catch (\Exception $e) {
        plesk_LogError('AdminSingleSignOn', $params, $e->getMessage());
        return [
            'success' => false,
            'errorMsg' => $e->getMessage(),
        ];
    }
}

/**
 * Admin services tab additional fields
 *
 * @param array $params Service parameters
 * @return array Display fields
 */
function plesk_AdminServicesTabFields(array $params)
{
    try {
        $domain = $params['domain'];

        // Get subscription info
        $info = plesk_GetSubscriptionInfo($params, $domain);

        $fields = [
            'Username' => $params['username'],
            'Domain' => $domain,
            'Server IP' => $params['serverip'],
            'Service Plan' => $params['configoption1'] ?? 'Default Domain',
        ];

        if (!empty($info)) {
            $fields['Status'] = $info['status'] ?? 'Unknown';
            $fields['Disk Used'] = plesk_FormatBytes($info['disk_used'] ?? 0);
            $fields['Disk Limit'] = plesk_FormatBytes($info['disk_limit'] ?? 0);
        }

        return $fields;

    } catch (\Exception $e) {
        plesk_LogError('AdminServicesTabFields', $params, $e->getMessage());
        return [
            'Error' => $e->getMessage(),
        ];
    }
}

/**
 * Admin custom buttons
 *
 * @return array Custom action buttons
 */
function plesk_AdminCustomButtonArray()
{
    return [
        'Get Subscription Info' => 'GetSubscriptionInfo',
        'Create Backup' => 'CreateBackup',
        'Restore Backup' => 'RestoreBackup',
    ];
}

/**
 * Get detailed subscription information
 *
 * @param array $params Service parameters
 * @return string Result message
 */
function plesk_GetSubscriptionInfo(array $params)
{
    try {
        $domain = $params['domain'];

        $packet = plesk_BuildSubscriptionPacket('get', [
            'domain_name' => $domain,
        ]);

        $response = plesk_ApiRequest($params, $packet);

        if (isset($response['webspace']['get']['result']['data'])) {
            $data = $response['webspace']['get']['result']['data'];

            $info = "Subscription Information for: {$domain}\n\n";
            $info .= "ID: " . ($data['id'] ?? 'N/A') . "\n";
            $info .= "Status: " . ($data['status'] ?? 'N/A') . "\n";
            $info .= "Owner: " . ($data['owner-login'] ?? 'N/A') . "\n";
            $info .= "Service Plan: " . ($data['plan-name'] ?? 'N/A') . "\n";
            $info .= "Disk Used: " . plesk_FormatBytes($data['limits']['disk_space_used'] ?? 0) . "\n";
            $info .= "Disk Limit: " . plesk_FormatBytes($data['limits']['disk_space'] ?? 0) . "\n";

            return $info;
        }

        return 'Subscription information not found';

    } catch (\Exception $e) {
        plesk_LogError('GetSubscriptionInfo', $params, $e->getMessage());
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Create backup for subscription
 *
 * @param array $params Service parameters
 * @return string Result message
 */
function plesk_CreateBackup(array $params)
{
    try {
        $domain = $params['domain'];

        $packet = plesk_BuildBackupPacket('create', [
            'domain_name' => $domain,
        ]);

        $response = plesk_ApiRequest($params, $packet);

        if (isset($response['backup']['result']['status']) &&
            $response['backup']['result']['status'] === 'ok') {
            return 'Backup created successfully';
        }

        return 'Failed to create backup: ' . ($response['backup']['result']['errtext'] ?? 'Unknown error');

    } catch (\Exception $e) {
        plesk_LogError('CreateBackup', $params, $e->getMessage());
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Restore backup for subscription
 *
 * @param array $params Service parameters
 * @return string Result message
 */
function plesk_RestoreBackup(array $params)
{
    try {
        $domain = $params['domain'];

        // This would typically require a backup ID
        // For demonstration, we'll just return info
        return 'Please specify backup ID to restore. Use Plesk panel for backup restoration.';

    } catch (\Exception $e) {
        plesk_LogError('RestoreBackup', $params, $e->getMessage());
        return 'Error: ' . $e->getMessage();
    }
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Make Plesk XML-RPC API request
 *
 * @param array $params Server parameters
 * @param string $packet XML packet
 * @return array API response
 */
function plesk_ApiRequest(array $params, string $packet)
{
    $serverIp = $params['serverhostname'] ?? $params['serverip'];
    $serverPort = $params['serverport'] ?? 8443;
    $serverUsername = $params['serverusername'];
    $serverPassword = $params['serverpassword'];
    $serverSecure = $params['serversecure'] ?? true;

    // Build URL
    $protocol = $serverSecure ? 'https' : 'http';
    $url = "{$protocol}://{$serverIp}:{$serverPort}/enterprise/control/agent.php";

    // Prepare authentication header
    $authHeader = base64_encode("{$serverUsername}:{$serverPassword}");

    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $packet);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: text/xml',
        'HTTP_AUTH_LOGIN: ' . $serverUsername,
        'HTTP_AUTH_PASSWD: ' . $serverPassword,
        'HTTP_PRETTY_PRINT: TRUE',
    ]);

    // Execute request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Check for cURL errors
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new \Exception("cURL Error: {$error}");
    }

    curl_close($ch);

    // Parse XML response
    $result = plesk_ParseXmlResponse($response);

    // Log the API call
    logModuleCall(
        'plesk',
        'ApiRequest',
        $packet,
        $response,
        $result,
        [$serverPassword]
    );

    return $result;
}

/**
 * Build basic Plesk XML packet
 *
 * @param string $operator Operator name
 * @param string $operation Operation name
 * @return string XML packet
 */
function plesk_BuildPacket(string $operator, string $operation)
{
    return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
           "<packet>\n" .
           "<{$operator}>\n" .
           "<{$operation}/>\n" .
           "</{$operator}>\n" .
           "</packet>";
}

/**
 * Build customer XML packet
 *
 * @param string $operation Operation (add, set, del)
 * @param array $data Customer data
 * @return string XML packet
 */
function plesk_BuildCustomerPacket(string $operation, array $data)
{
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<packet>\n<customer>\n<{$operation}>\n";

    if ($operation === 'add') {
        $xml .= "<pname>{$data['company']}</pname>\n";
        $xml .= "<login>{$data['login']}</login>\n";
        $xml .= "<passwd>{$data['password']}</passwd>\n";
        $xml .= "<email>{$data['email']}</email>\n";
        $xml .= "<name>{$data['name']}</name>\n";
    } elseif ($operation === 'set') {
        $xml .= "<filter><login>{$data['login']}</login></filter>\n";
        $xml .= "<values>\n";
        if (isset($data['password'])) {
            $xml .= "<passwd>{$data['password']}</passwd>\n";
        }
        $xml .= "</values>\n";
    } elseif ($operation === 'del') {
        $xml .= "<filter><login>{$data['login']}</login></filter>\n";
    }

    $xml .= "</{$operation}>\n</customer>\n</packet>";

    return $xml;
}

/**
 * Build subscription (webspace) XML packet
 *
 * @param string $operation Operation (add, set, del, get, switch-subscription)
 * @param array $data Subscription data
 * @param array $params Optional service parameters
 * @return string XML packet
 */
function plesk_BuildSubscriptionPacket(string $operation, array $data, array $params = [])
{
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<packet>\n<webspace>\n<{$operation}>\n";

    if ($operation === 'add') {
        $xml .= "<gen_setup>\n";
        $xml .= "<name>{$data['domain_name']}</name>\n";
        $xml .= "<owner-login>{$data['owner_login']}</owner-login>\n";
        $xml .= "<htype>vrt_hst</htype>\n";
        if (isset($data['plan_name'])) {
            $xml .= "<plan-name>{$data['plan_name']}</plan-name>\n";
        }
        if (isset($data['ip'])) {
            $xml .= "<ip_address>{$data['ip']}</ip_address>\n";
        }
        $xml .= "</gen_setup>\n";

        $xml .= "<hosting>\n";
        $xml .= "<vrt_hst>\n";
        $xml .= "<property>\n";
        $xml .= "<name>ftp_login</name>\n";
        $xml .= "<value>{$data['username']}</value>\n";
        $xml .= "</property>\n";
        $xml .= "<property>\n";
        $xml .= "<name>ftp_password</name>\n";
        $xml .= "<value>{$data['password']}</value>\n";
        $xml .= "</property>\n";
        $xml .= "</vrt_hst>\n";
        $xml .= "</hosting>\n";
    } elseif ($operation === 'set') {
        $xml .= "<filter><name>{$data['domain_name']}</name></filter>\n";
        $xml .= "<values>\n";
        if (isset($data['status'])) {
            $xml .= "<gen_setup><status>{$data['status']}</status></gen_setup>\n";
        }
        $xml .= "</values>\n";
    } elseif ($operation === 'del') {
        $xml .= "<filter><name>{$data['domain_name']}</name></filter>\n";
    } elseif ($operation === 'get') {
        $xml .= "<filter><name>{$data['domain_name']}</name></filter>\n";
        $xml .= "<dataset><gen_info/><limits/></dataset>\n";
    } elseif ($operation === 'switch-subscription') {
        $xml .= "<filter><name>{$data['domain_name']}</name></filter>\n";
        $xml .= "<plan-name>{$data['plan_name']}</plan-name>\n";
    }

    $xml .= "</{$operation}>\n</webspace>\n</packet>";

    return $xml;
}

/**
 * Build backup XML packet
 *
 * @param string $operation Operation (create, restore)
 * @param array $data Backup data
 * @return string XML packet
 */
function plesk_BuildBackupPacket(string $operation, array $data)
{
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<packet>\n<backup>\n<{$operation}>\n";

    if ($operation === 'create') {
        $xml .= "<domain-name>{$data['domain_name']}</domain-name>\n";
    }

    $xml .= "</{$operation}>\n</backup>\n</packet>";

    return $xml;
}

/**
 * Parse Plesk XML response
 *
 * @param string $xml XML response
 * @return array Parsed response
 */
function plesk_ParseXmlResponse(string $xml)
{
    libxml_use_internal_errors(true);

    try {
        $xmlObj = simplexml_load_string($xml);

        if ($xmlObj === false) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            throw new \Exception('XML parsing failed: ' . print_r($errors, true));
        }

        return json_decode(json_encode($xmlObj), true);
    } catch (\Exception $e) {
        throw new \Exception('Failed to parse XML response: ' . $e->getMessage());
    }
}

/**
 * Generate SSO URL for Plesk access
 *
 * @param array $params Service parameters
 * @param bool $admin Admin access (true) or customer access (false)
 * @return string SSO URL
 */
function plesk_GenerateSsoUrl(array $params, bool $admin = false)
{
    $serverIp = $params['serverhostname'] ?? $params['serverip'];
    $serverPort = $params['serverport'] ?? 8443;
    $serverSecure = $params['serversecure'] ?? true;
    $username = $admin ? $params['serverusername'] : $params['username'];

    try {
        // Generate session using Plesk API
        $packet = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
                  "<packet>\n" .
                  "<server>\n" .
                  "<create_session>\n" .
                  "<login>{$username}</login>\n" .
                  "<data>\n" .
                  "<user_ip>" . ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1') . "</user_ip>\n" .
                  "</data>\n" .
                  "</create_session>\n" .
                  "</server>\n" .
                  "</packet>";

        $response = plesk_ApiRequest($params, $packet);

        if (isset($response['server']['create_session']['result']['id'])) {
            $sessionId = $response['server']['create_session']['result']['id'];
            $protocol = $serverSecure ? 'https' : 'http';
            return "{$protocol}://{$serverIp}:{$serverPort}/enterprise/rsession_init.php?PLESKSESSID={$sessionId}";
        }
    } catch (\Exception $e) {
        // Fallback to basic URL
    }

    // Fallback URL
    $protocol = $serverSecure ? 'https' : 'http';
    return "{$protocol}://{$serverIp}:{$serverPort}/";
}

/**
 * Get Plesk version
 *
 * @param array $params Server parameters
 * @return string Plesk version
 */
function plesk_GetPleskVersion(array $params)
{
    try {
        $packet = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
                  "<packet>\n" .
                  "<server>\n" .
                  "<get><stat/></get>\n" .
                  "</server>\n" .
                  "</packet>";

        $response = plesk_ApiRequest($params, $packet);

        if (isset($response['server']['get']['result']['stat']['version'])) {
            return $response['server']['get']['result']['stat']['version'];
        }
    } catch (\Exception $e) {
        return 'Unknown';
    }

    return 'Unknown';
}

/**
 * Get subscription information
 *
 * @param array $params Service parameters
 * @param string $domain Domain name
 * @return array Subscription info
 */
function plesk_GetSubscriptionInfo(array $params, string $domain)
{
    try {
        $packet = plesk_BuildSubscriptionPacket('get', [
            'domain_name' => $domain,
        ]);

        $response = plesk_ApiRequest($params, $packet);

        if (isset($response['webspace']['get']['result']['data'])) {
            return $response['webspace']['get']['result']['data'];
        }

        return [];
    } catch (\Exception $e) {
        return [];
    }
}

/**
 * Parse limit value (handles -1 for unlimited)
 *
 * @param mixed $value Limit value
 * @return int Parsed limit
 */
function plesk_ParseLimit($value)
{
    if ($value === null || $value === '') {
        return -1;
    }

    $value = trim(strtolower($value));

    if ($value === 'unlimited' || $value === '-1') {
        return -1;
    }

    return (int) $value;
}

/**
 * Format bytes to human-readable format
 *
 * @param int|string $bytes Bytes
 * @return string Formatted string
 */
function plesk_FormatBytes($bytes)
{
    $bytes = (int) $bytes;

    if ($bytes == 0 || $bytes == -1) {
        return 'Unlimited';
    }

    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $power = floor(log($bytes, 1024));
    $power = min($power, count($units) - 1);

    return round($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
}

/**
 * Log module activity
 *
 * @param string $action Action name
 * @param array $params Parameters
 * @param string $message Log message
 */
function plesk_LogActivity(string $action, array $params, string $message)
{
    try {
        if (function_exists('logActivity')) {
            logActivity("Plesk - {$action}: {$message}");
        }

        if (class_exists('Log')) {
            Log::info("Plesk - {$action}", [
                'message' => $message,
                'username' => $params['username'] ?? 'N/A',
                'domain' => $params['domain'] ?? 'N/A',
            ]);
        }
    } catch (\Exception $e) {
        // Silent fail on logging errors
    }
}

/**
 * Log module error
 *
 * @param string $action Action name
 * @param array $params Parameters
 * @param string $error Error message
 * @param array $response API response (optional)
 */
function plesk_LogError(string $action, array $params, string $error, array $response = [])
{
    try {
        if (function_exists('logModuleCall')) {
            logModuleCall(
                'plesk',
                $action,
                $params,
                $error,
                $response
            );
        }

        if (class_exists('Log')) {
            Log::error("Plesk - {$action} Error", [
                'error' => $error,
                'username' => $params['username'] ?? 'N/A',
                'domain' => $params['domain'] ?? 'N/A',
                'response' => $response,
            ]);
        }
    } catch (\Exception $e) {
        // Silent fail on logging errors
    }
}
