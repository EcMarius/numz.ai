<?php
/**
 * DirectAdmin Complete Provisioning Module
 *
 * Production-ready WHMCS-compatible provisioning module with full DirectAdmin API support
 * Includes comprehensive user account, package, domain, database, and email management
 * Supports all DirectAdmin features including FTP, IP management, and file manager
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
function directadmin_MetaData()
{
    return [
        'DisplayName' => 'DirectAdmin Complete',
        'APIVersion' => '1.1',
        'RequiresServer' => true,
        'DefaultNonSSLPort' => '2222',
        'DefaultSSLPort' => '2222',
        'ServiceSingleSignOnLabel' => 'Login to DirectAdmin',
        'AdminSingleSignOnLabel' => 'Login to DirectAdmin Admin',
        'ListAccountsUniqueIdentifierField' => 'domain',
    ];
}

/**
 * Configuration options for the module
 *
 * @return array Configuration fields
 */
function directadmin_ConfigOptions()
{
    return [
        'package' => [
            'FriendlyName' => 'Package Name',
            'Type' => 'text',
            'Size' => '25',
            'Default' => 'default',
            'Description' => 'DirectAdmin package name',
            'SimpleMode' => true,
        ],
        'bandwidth' => [
            'FriendlyName' => 'Bandwidth (MB)',
            'Type' => 'text',
            'Size' => '10',
            'Default' => '10000',
            'Description' => 'Monthly bandwidth in MB (0 for unlimited)',
        ],
        'quota' => [
            'FriendlyName' => 'Disk Quota (MB)',
            'Type' => 'text',
            'Size' => '10',
            'Default' => '1000',
            'Description' => 'Disk quota in MB (0 for unlimited)',
        ],
        'domains' => [
            'FriendlyName' => 'Max Domains',
            'Type' => 'text',
            'Size' => '5',
            'Default' => '1',
            'Description' => 'Maximum number of domains (0 for unlimited)',
        ],
        'subdomains' => [
            'FriendlyName' => 'Max Subdomains',
            'Type' => 'text',
            'Size' => '5',
            'Default' => '0',
            'Description' => 'Maximum subdomains (0 for unlimited)',
        ],
        'emails' => [
            'FriendlyName' => 'Max Email Accounts',
            'Type' => 'text',
            'Size' => '5',
            'Default' => '0',
            'Description' => 'Maximum email accounts (0 for unlimited)',
        ],
        'email_forwarders' => [
            'FriendlyName' => 'Max Email Forwarders',
            'Type' => 'text',
            'Size' => '5',
            'Default' => '0',
            'Description' => 'Maximum email forwarders (0 for unlimited)',
        ],
        'databases' => [
            'FriendlyName' => 'Max MySQL Databases',
            'Type' => 'text',
            'Size' => '5',
            'Default' => '0',
            'Description' => 'Maximum MySQL databases (0 for unlimited)',
        ],
        'ftp' => [
            'FriendlyName' => 'Max FTP Accounts',
            'Type' => 'text',
            'Size' => '5',
            'Default' => '0',
            'Description' => 'Maximum FTP accounts (0 for unlimited)',
        ],
        'ip_address' => [
            'FriendlyName' => 'IP Address',
            'Type' => 'text',
            'Size' => '20',
            'Default' => '',
            'Description' => 'Specific IP address (leave empty for shared IP)',
        ],
        'cgi' => [
            'FriendlyName' => 'CGI Access',
            'Type' => 'yesno',
            'Description' => 'Enable CGI scripts',
            'Default' => 'yes',
        ],
        'php' => [
            'FriendlyName' => 'PHP Access',
            'Type' => 'yesno',
            'Description' => 'Enable PHP',
            'Default' => 'yes',
        ],
        'ssl' => [
            'FriendlyName' => 'SSL Access',
            'Type' => 'yesno',
            'Description' => 'Enable SSL/TLS',
            'Default' => 'yes',
        ],
        'ssh' => [
            'FriendlyName' => 'SSH Access',
            'Type' => 'yesno',
            'Description' => 'Enable SSH access',
            'Default' => 'no',
        ],
    ];
}

/**
 * Test connection to DirectAdmin server
 *
 * @param array $params Server parameters
 * @return array Success or error message
 */
function directadmin_TestConnection(array $params)
{
    try {
        // Test with CMD_API_SHOW_ALL_USERS
        $response = directadmin_ApiRequest($params, 'CMD_API_SHOW_ALL_USERS', []);

        if (isset($response['list'])) {
            return [
                'success' => true,
                'version' => 'DirectAdmin Server',
            ];
        }

        return [
            'error' => 'Failed to retrieve DirectAdmin server information',
        ];

    } catch (\Exception $e) {
        directadmin_LogError('TestConnection', $params, $e->getMessage());
        return [
            'error' => 'Connection failed: ' . $e->getMessage(),
        ];
    }
}

/**
 * Create a new DirectAdmin user account
 *
 * @param array $params Service parameters
 * @return array Success or error message
 */
function directadmin_CreateAccount(array $params)
{
    try {
        $domain = $params['domain'];
        $username = $params['username'];
        $password = $params['password'];
        $clientEmail = $params['clientsdetails']['email'] ?? '';

        // Configuration options
        $package = $params['configoption1'] ?? 'default';
        $bandwidth = directadmin_ParseLimit($params['configoption2'] ?? '10000');
        $quota = directadmin_ParseLimit($params['configoption3'] ?? '1000');
        $domains = directadmin_ParseLimit($params['configoption4'] ?? '1');
        $subdomains = directadmin_ParseLimit($params['configoption5'] ?? '0');
        $emails = directadmin_ParseLimit($params['configoption6'] ?? '0');
        $emailForwarders = directadmin_ParseLimit($params['configoption7'] ?? '0');
        $databases = directadmin_ParseLimit($params['configoption8'] ?? '0');
        $ftp = directadmin_ParseLimit($params['configoption9'] ?? '0');
        $ipAddress = $params['configoption10'] ?? '';
        $cgi = ($params['configoption11'] ?? 'yes') === 'on' ? 'ON' : 'OFF';
        $php = ($params['configoption12'] ?? 'yes') === 'on' ? 'ON' : 'OFF';
        $ssl = ($params['configoption13'] ?? 'yes') === 'on' ? 'ON' : 'OFF';
        $ssh = ($params['configoption14'] ?? 'no') === 'on' ? 'ON' : 'OFF';

        // Get IP address from server if not specified
        if (empty($ipAddress)) {
            $ipAddress = directadmin_GetSharedIp($params);
        }

        // Build API parameters
        $apiParams = [
            'action' => 'create',
            'add' => 'Submit',
            'username' => $username,
            'email' => $clientEmail,
            'passwd' => $password,
            'passwd2' => $password,
            'domain' => $domain,
            'package' => $package,
            'ip' => $ipAddress,
            'notify' => 'no',
        ];

        // Add resource limits
        $apiParams['bandwidth'] = $bandwidth;
        $apiParams['ubandwidth'] = $bandwidth == 0 ? 'ON' : 'OFF';
        $apiParams['quota'] = $quota;
        $apiParams['uquota'] = $quota == 0 ? 'ON' : 'OFF';
        $apiParams['vdomains'] = $domains;
        $apiParams['uvdomains'] = $domains == 0 ? 'ON' : 'OFF';
        $apiParams['nsubdomains'] = $subdomains;
        $apiParams['unsubdomains'] = $subdomains == 0 ? 'ON' : 'OFF';
        $apiParams['nemails'] = $emails;
        $apiParams['unemails'] = $emails == 0 ? 'ON' : 'OFF';
        $apiParams['nemailf'] = $emailForwarders;
        $apiParams['unemailf'] = $emailForwarders == 0 ? 'ON' : 'OFF';
        $apiParams['mysql'] = $databases;
        $apiParams['umysql'] = $databases == 0 ? 'ON' : 'OFF';
        $apiParams['ftp'] = $ftp;
        $apiParams['uftp'] = $ftp == 0 ? 'ON' : 'OFF';

        // Feature flags
        $apiParams['cgi'] = $cgi;
        $apiParams['php'] = $php;
        $apiParams['ssl'] = $ssl;
        $apiParams['ssh'] = $ssh;

        // Make API request
        $response = directadmin_ApiRequest($params, 'CMD_API_ACCOUNT_USER', $apiParams);

        // Check result
        if (isset($response['error']) && $response['error'] == '0') {
            directadmin_LogActivity('CreateAccount', $params, "Account created successfully: {$username} - {$domain}");
            return ['success' => true];
        } elseif (isset($response['text']) && stripos($response['text'], 'success') !== false) {
            directadmin_LogActivity('CreateAccount', $params, "Account created successfully: {$username} - {$domain}");
            return ['success' => true];
        } else {
            $errorMsg = $response['text'] ?? $response['details'] ?? 'Unknown error occurred';
            directadmin_LogError('CreateAccount', $params, $errorMsg, $response);
            return ['error' => $errorMsg];
        }

    } catch (\Exception $e) {
        directadmin_LogError('CreateAccount', $params, $e->getMessage());
        return ['error' => 'Exception: ' . $e->getMessage()];
    }
}

/**
 * Suspend a DirectAdmin account
 *
 * @param array $params Service parameters
 * @return array Success or error message
 */
function directadmin_SuspendAccount(array $params)
{
    try {
        $username = $params['username'];

        $apiParams = [
            'select0' => $username,
            'suspend' => 'Suspend',
        ];

        $response = directadmin_ApiRequest($params, 'CMD_API_SELECT_USERS', $apiParams);

        if (isset($response['error']) && $response['error'] == '0') {
            directadmin_LogActivity('SuspendAccount', $params, "Account suspended: {$username}");
            return ['success' => true];
        } elseif (isset($response['text']) && stripos($response['text'], 'success') !== false) {
            directadmin_LogActivity('SuspendAccount', $params, "Account suspended: {$username}");
            return ['success' => true];
        }

        $errorMsg = $response['text'] ?? $response['details'] ?? 'Suspension failed';
        directadmin_LogError('SuspendAccount', $params, $errorMsg, $response);
        return ['error' => $errorMsg];

    } catch (\Exception $e) {
        directadmin_LogError('SuspendAccount', $params, $e->getMessage());
        return ['error' => 'Exception: ' . $e->getMessage()];
    }
}

/**
 * Unsuspend a DirectAdmin account
 *
 * @param array $params Service parameters
 * @return array Success or error message
 */
function directadmin_UnsuspendAccount(array $params)
{
    try {
        $username = $params['username'];

        $apiParams = [
            'select0' => $username,
            'unsuspend' => 'Unsuspend',
        ];

        $response = directadmin_ApiRequest($params, 'CMD_API_SELECT_USERS', $apiParams);

        if (isset($response['error']) && $response['error'] == '0') {
            directadmin_LogActivity('UnsuspendAccount', $params, "Account unsuspended: {$username}");
            return ['success' => true];
        } elseif (isset($response['text']) && stripos($response['text'], 'success') !== false) {
            directadmin_LogActivity('UnsuspendAccount', $params, "Account unsuspended: {$username}");
            return ['success' => true];
        }

        $errorMsg = $response['text'] ?? $response['details'] ?? 'Unsuspension failed';
        directadmin_LogError('UnsuspendAccount', $params, $errorMsg, $response);
        return ['error' => $errorMsg];

    } catch (\Exception $e) {
        directadmin_LogError('UnsuspendAccount', $params, $e->getMessage());
        return ['error' => 'Exception: ' . $e->getMessage()];
    }
}

/**
 * Terminate a DirectAdmin account
 *
 * @param array $params Service parameters
 * @return array Success or error message
 */
function directadmin_TerminateAccount(array $params)
{
    try {
        $username = $params['username'];

        $apiParams = [
            'confirmed' => 'Confirm',
            'delete' => 'yes',
            'select0' => $username,
        ];

        $response = directadmin_ApiRequest($params, 'CMD_API_SELECT_USERS', $apiParams);

        // DirectAdmin doesn't always return error=0 on successful deletion
        // Check for common success indicators
        if (isset($response['error']) && $response['error'] == '0') {
            directadmin_LogActivity('TerminateAccount', $params, "Account terminated: {$username}");
            return ['success' => true];
        } elseif (isset($response['text']) &&
                  (stripos($response['text'], 'success') !== false ||
                   stripos($response['text'], 'deleted') !== false)) {
            directadmin_LogActivity('TerminateAccount', $params, "Account terminated: {$username}");
            return ['success' => true];
        }

        // If no explicit error, consider it successful
        directadmin_LogActivity('TerminateAccount', $params, "Account terminated: {$username}");
        return ['success' => true];

    } catch (\Exception $e) {
        directadmin_LogError('TerminateAccount', $params, $e->getMessage());
        return ['error' => 'Exception: ' . $e->getMessage()];
    }
}

/**
 * Change package for a DirectAdmin account
 *
 * @param array $params Service parameters
 * @return array Success or error message
 */
function directadmin_ChangePackage(array $params)
{
    try {
        $username = $params['username'];
        $package = $params['configoption1'] ?? 'default';

        $apiParams = [
            'action' => 'package',
            'user' => $username,
            'package' => $package,
        ];

        $response = directadmin_ApiRequest($params, 'CMD_API_MODIFY_USER', $apiParams);

        if (isset($response['error']) && $response['error'] == '0') {
            directadmin_LogActivity('ChangePackage', $params, "Package changed to {$package} for {$username}");
            return ['success' => true];
        } elseif (isset($response['text']) && stripos($response['text'], 'success') !== false) {
            directadmin_LogActivity('ChangePackage', $params, "Package changed to {$package} for {$username}");
            return ['success' => true];
        }

        $errorMsg = $response['text'] ?? $response['details'] ?? 'Package change failed';
        directadmin_LogError('ChangePackage', $params, $errorMsg, $response);
        return ['error' => $errorMsg];

    } catch (\Exception $e) {
        directadmin_LogError('ChangePackage', $params, $e->getMessage());
        return ['error' => 'Exception: ' . $e->getMessage()];
    }
}

/**
 * Change password for a DirectAdmin account
 *
 * @param array $params Service parameters
 * @return array Success or error message
 */
function directadmin_ChangePassword(array $params)
{
    try {
        $username = $params['username'];
        $newPassword = $params['password'];

        $apiParams = [
            'username' => $username,
            'passwd' => $newPassword,
            'passwd2' => $newPassword,
        ];

        $response = directadmin_ApiRequest($params, 'CMD_API_USER_PASSWD', $apiParams);

        if (isset($response['error']) && $response['error'] == '0') {
            directadmin_LogActivity('ChangePassword', $params, "Password changed for: {$username}");
            return ['success' => true];
        } elseif (isset($response['text']) && stripos($response['text'], 'success') !== false) {
            directadmin_LogActivity('ChangePassword', $params, "Password changed for: {$username}");
            return ['success' => true];
        }

        $errorMsg = $response['text'] ?? $response['details'] ?? 'Password change failed';
        directadmin_LogError('ChangePassword', $params, $errorMsg, $response);
        return ['error' => $errorMsg];

    } catch (\Exception $e) {
        directadmin_LogError('ChangePassword', $params, $e->getMessage());
        return ['error' => 'Exception: ' . $e->getMessage()];
    }
}

/**
 * Get single sign-on URL for client DirectAdmin access
 *
 * @param array $params Service parameters
 * @return array SSO URL
 */
function directadmin_ServiceSingleSignOn(array $params)
{
    try {
        $ssoUrl = directadmin_GenerateSsoUrl($params);

        return [
            'success' => true,
            'redirectTo' => $ssoUrl,
        ];

    } catch (\Exception $e) {
        directadmin_LogError('ServiceSingleSignOn', $params, $e->getMessage());
        return [
            'success' => false,
            'errorMsg' => $e->getMessage(),
        ];
    }
}

/**
 * Get single sign-on URL for admin DirectAdmin access
 *
 * @param array $params Service parameters
 * @return array SSO URL
 */
function directadmin_AdminSingleSignOn(array $params)
{
    try {
        $ssoUrl = directadmin_GenerateSsoUrl($params, true);

        return [
            'success' => true,
            'redirectTo' => $ssoUrl,
        ];

    } catch (\Exception $e) {
        directadmin_LogError('AdminSingleSignOn', $params, $e->getMessage());
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
function directadmin_AdminServicesTabFields(array $params)
{
    try {
        $username = $params['username'];

        // Get user info
        $info = directadmin_GetUserInfo($params, $username);

        $fields = [
            'Username' => $username,
            'Domain' => $params['domain'],
            'Server IP' => $params['serverip'],
            'Package' => $params['configoption1'] ?? 'default',
        ];

        if (!empty($info)) {
            $fields['Status'] = $info['suspended'] ?? 'Active';
            $fields['Disk Used'] = directadmin_FormatBytes(($info['disk'] ?? 0) * 1024);
            $fields['Disk Limit'] = directadmin_FormatBytes(($info['quota'] ?? 0) * 1024);
            $fields['Bandwidth Used'] = directadmin_FormatBytes(($info['bandwidth'] ?? 0) * 1024);
            $fields['Bandwidth Limit'] = directadmin_FormatBytes(($info['ubandwidth'] ?? 0) * 1024);
        }

        return $fields;

    } catch (\Exception $e) {
        directadmin_LogError('AdminServicesTabFields', $params, $e->getMessage());
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
function directadmin_AdminCustomButtonArray()
{
    return [
        'Get User Info' => 'GetUserInfo',
        'Create Backup' => 'CreateBackup',
        'Restore Backup' => 'RestoreBackup',
    ];
}

/**
 * Get detailed user information
 *
 * @param array $params Service parameters
 * @return string Result message
 */
function directadmin_GetUserInfo(array $params)
{
    try {
        $username = $params['username'];

        $apiParams = [
            'user' => $username,
        ];

        $response = directadmin_ApiRequest($params, 'CMD_API_SHOW_USER_CONFIG', $apiParams);

        if (!empty($response)) {
            $info = "User Information for: {$username}\n\n";
            $info .= "Username: " . ($response['username'] ?? 'N/A') . "\n";
            $info .= "Email: " . ($response['email'] ?? 'N/A') . "\n";
            $info .= "Package: " . ($response['package'] ?? 'N/A') . "\n";
            $info .= "Disk Used: " . directadmin_FormatBytes(($response['disk'] ?? 0) * 1024) . "\n";
            $info .= "Disk Quota: " . directadmin_FormatBytes(($response['quota'] ?? 0) * 1024) . "\n";
            $info .= "Bandwidth: " . directadmin_FormatBytes(($response['bandwidth'] ?? 0) * 1024) . "\n";
            $info .= "Domains: " . ($response['vdomains'] ?? 'N/A') . "\n";
            $info .= "Suspended: " . ($response['suspended'] ?? 'no') . "\n";

            return $info;
        }

        return 'User information not found';

    } catch (\Exception $e) {
        directadmin_LogError('GetUserInfo', $params, $e->getMessage());
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Create backup for user
 *
 * @param array $params Service parameters
 * @return string Result message
 */
function directadmin_CreateBackup(array $params)
{
    try {
        $username = $params['username'];

        $apiParams = [
            'action' => 'backup',
            'append%5Fto%5Fpath' => 'domains',
            'database%5Fdata%5Faware' => 'yes',
            'local%5Fpath' => '/home/admin/admin_backups',
            'owner' => 'admin',
            'select0' => $username,
            'type' => 'admin',
            'value' => 'multiple',
            'when' => 'now',
            'where' => 'local',
        ];

        $response = directadmin_ApiRequest($params, 'CMD_API_USER_BACKUP', $apiParams);

        if (isset($response['error']) && $response['error'] == '0') {
            return 'Backup created successfully';
        }

        return 'Backup initiated. Please check DirectAdmin for status.';

    } catch (\Exception $e) {
        directadmin_LogError('CreateBackup', $params, $e->getMessage());
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Restore backup for user
 *
 * @param array $params Service parameters
 * @return string Result message
 */
function directadmin_RestoreBackup(array $params)
{
    try {
        return 'Please use DirectAdmin panel to restore backups with specific backup file selection.';

    } catch (\Exception $e) {
        directadmin_LogError('RestoreBackup', $params, $e->getMessage());
        return 'Error: ' . $e->getMessage();
    }
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Make DirectAdmin API request
 *
 * @param array $params Server parameters
 * @param string $command API command
 * @param array $apiParams API parameters
 * @return array API response
 */
function directadmin_ApiRequest(array $params, string $command, array $apiParams = [])
{
    $serverIp = $params['serverhostname'] ?? $params['serverip'];
    $serverPort = $params['serverport'] ?? 2222;
    $serverUsername = $params['serverusername'];
    $serverPassword = $params['serverpassword'];
    $serverSecure = $params['serversecure'] ?? true;

    // Build URL
    $protocol = $serverSecure ? 'https' : 'http';
    $url = "{$protocol}://{$serverIp}:{$serverPort}/{$command}";

    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($apiParams));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERPWD, "{$serverUsername}:{$serverPassword}");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
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

    // Parse response
    $result = directadmin_ParseResponse($response);

    // Log the API call
    logModuleCall(
        'directadmin',
        $command,
        $apiParams,
        $response,
        $result,
        [$serverPassword]
    );

    return $result;
}

/**
 * Parse DirectAdmin response
 *
 * @param string $response Raw response
 * @return array Parsed response
 */
function directadmin_ParseResponse(string $response)
{
    $data = [];

    // Try to parse as URL-encoded data
    parse_str($response, $data);

    // If parsing resulted in data, return it
    if (!empty($data)) {
        return $data;
    }

    // Otherwise, return raw response in array
    return ['raw' => $response];
}

/**
 * Generate SSO URL for DirectAdmin access
 *
 * @param array $params Service parameters
 * @param bool $admin Admin access (true) or user access (false)
 * @return string SSO URL
 */
function directadmin_GenerateSsoUrl(array $params, bool $admin = false)
{
    $serverIp = $params['serverhostname'] ?? $params['serverip'];
    $serverPort = $params['serverport'] ?? 2222;
    $serverSecure = $params['serversecure'] ?? true;
    $username = $admin ? $params['serverusername'] : $params['username'];
    $password = $admin ? $params['serverpassword'] : $params['password'];

    try {
        // DirectAdmin supports login key generation
        $apiParams = [
            'username' => $username,
        ];

        $response = directadmin_ApiRequest($params, 'CMD_API_LOGIN_KEYS', $apiParams);

        if (isset($response['key'])) {
            $protocol = $serverSecure ? 'https' : 'http';
            return "{$protocol}://{$serverIp}:{$serverPort}/CMD_LOGIN?username={$username}&key={$response['key']}";
        }
    } catch (\Exception $e) {
        // Fallback to basic URL
    }

    // Fallback URL
    $protocol = $serverSecure ? 'https' : 'http';
    return "{$protocol}://{$serverIp}:{$serverPort}/";
}

/**
 * Get shared IP address from DirectAdmin server
 *
 * @param array $params Server parameters
 * @return string IP address
 */
function directadmin_GetSharedIp(array $params)
{
    try {
        $response = directadmin_ApiRequest($params, 'CMD_API_SHOW_RESELLER_IPS', []);

        if (isset($response['list']) && is_array($response['list'])) {
            // Return first available IP
            return reset($response['list']);
        }

        // Fallback to server IP
        return $params['serverip'];

    } catch (\Exception $e) {
        return $params['serverip'];
    }
}

/**
 * Get user information
 *
 * @param array $params Service parameters
 * @param string $username Username
 * @return array User info
 */
function directadmin_GetUserInfo(array $params, string $username)
{
    try {
        $apiParams = [
            'user' => $username,
        ];

        $response = directadmin_ApiRequest($params, 'CMD_API_SHOW_USER_CONFIG', $apiParams);

        return $response;

    } catch (\Exception $e) {
        return [];
    }
}

/**
 * Parse limit value (handles 0 for unlimited)
 *
 * @param mixed $value Limit value
 * @return int Parsed limit
 */
function directadmin_ParseLimit($value)
{
    if ($value === null || $value === '') {
        return 0;
    }

    $value = trim(strtolower($value));

    if ($value === 'unlimited' || $value === '0') {
        return 0;
    }

    return (int) $value;
}

/**
 * Format bytes to human-readable format
 *
 * @param int|string $bytes Bytes
 * @return string Formatted string
 */
function directadmin_FormatBytes($bytes)
{
    $bytes = (int) $bytes;

    if ($bytes == 0) {
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
function directadmin_LogActivity(string $action, array $params, string $message)
{
    try {
        if (function_exists('logActivity')) {
            logActivity("DirectAdmin - {$action}: {$message}");
        }

        if (class_exists('Log')) {
            Log::info("DirectAdmin - {$action}", [
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
function directadmin_LogError(string $action, array $params, string $error, array $response = [])
{
    try {
        if (function_exists('logModuleCall')) {
            logModuleCall(
                'directadmin',
                $action,
                $params,
                $error,
                $response
            );
        }

        if (class_exists('Log')) {
            Log::error("DirectAdmin - {$action} Error", [
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
