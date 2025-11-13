<?php
/**
 * cPanel/WHM Complete Provisioning Module
 *
 * Production-ready WHMCS-compatible provisioning module with full WHM API v1 support
 * Includes all cPanel features, comprehensive error handling, and SSO integration
 *
 * @version 2.0.0
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
function cpanel_complete_MetaData()
{
    return [
        'DisplayName' => 'cPanel/WHM Complete',
        'APIVersion' => '1.1',
        'RequiresServer' => true,
        'DefaultNonSSLPort' => '2086',
        'DefaultSSLPort' => '2087',
        'ServiceSingleSignOnLabel' => 'Login to cPanel',
        'AdminSingleSignOnLabel' => 'Login to WHM',
        'ListAccountsUniqueIdentifierField' => 'domain',
    ];
}

/**
 * Configuration options for the module
 *
 * @return array Configuration fields
 */
function cpanel_complete_ConfigOptions()
{
    return [
        'package' => [
            'FriendlyName' => 'cPanel Package Name',
            'Type' => 'text',
            'Size' => '25',
            'Default' => 'default',
            'Description' => 'Enter the package name configured in WHM',
            'SimpleMode' => true,
        ],
        'diskspace' => [
            'FriendlyName' => 'Disk Space (MB)',
            'Type' => 'text',
            'Size' => '10',
            'Default' => '1000',
            'Description' => 'Disk space quota in MB (0 or unlimited for unlimited)',
            'Loader' => 'cpanel_complete_DiskSpaceLoader',
        ],
        'bandwidth' => [
            'FriendlyName' => 'Bandwidth (MB)',
            'Type' => 'text',
            'Size' => '10',
            'Default' => '10000',
            'Description' => 'Monthly bandwidth in MB (0 or unlimited for unlimited)',
            'Loader' => 'cpanel_complete_BandwidthLoader',
        ],
        'maxaddon' => [
            'FriendlyName' => 'Max Addon Domains',
            'Type' => 'text',
            'Size' => '5',
            'Default' => '0',
            'Description' => 'Maximum number of addon domains (0 for unlimited)',
        ],
        'maxsub' => [
            'FriendlyName' => 'Max Subdomains',
            'Type' => 'text',
            'Size' => '5',
            'Default' => '0',
            'Description' => 'Maximum number of subdomains (0 for unlimited)',
        ],
        'maxpark' => [
            'FriendlyName' => 'Max Parked Domains',
            'Type' => 'text',
            'Size' => '5',
            'Default' => '0',
            'Description' => 'Maximum number of parked domains (0 for unlimited)',
        ],
        'maxftp' => [
            'FriendlyName' => 'Max FTP Accounts',
            'Type' => 'text',
            'Size' => '5',
            'Default' => '0',
            'Description' => 'Maximum number of FTP accounts (0 for unlimited)',
        ],
        'maxsql' => [
            'FriendlyName' => 'Max SQL Databases',
            'Type' => 'text',
            'Size' => '5',
            'Default' => '0',
            'Description' => 'Maximum number of SQL databases (0 for unlimited)',
        ],
        'maxpop' => [
            'FriendlyName' => 'Max Email Accounts',
            'Type' => 'text',
            'Size' => '5',
            'Default' => '0',
            'Description' => 'Maximum number of email accounts (0 for unlimited)',
        ],
        'maxemailperhour' => [
            'FriendlyName' => 'Max Emails Per Hour',
            'Type' => 'text',
            'Size' => '5',
            'Default' => '0',
            'Description' => 'Maximum emails per hour (0 for unlimited)',
        ],
        'maxlst' => [
            'FriendlyName' => 'Max Mailing Lists',
            'Type' => 'text',
            'Size' => '5',
            'Default' => '0',
            'Description' => 'Maximum mailing lists (0 for unlimited)',
        ],
        'hasshell' => [
            'FriendlyName' => 'Shell Access',
            'Type' => 'yesno',
            'Description' => 'Enable SSH/Shell access',
            'Default' => 'no',
        ],
        'cgi' => [
            'FriendlyName' => 'CGI Access',
            'Type' => 'yesno',
            'Description' => 'Enable CGI script execution',
            'Default' => 'yes',
        ],
        'language' => [
            'FriendlyName' => 'Language',
            'Type' => 'dropdown',
            'Options' => [
                'en' => 'English',
                'es' => 'Spanish',
                'fr' => 'French',
                'de' => 'German',
                'it' => 'Italian',
                'pt' => 'Portuguese',
                'ru' => 'Russian',
                'zh' => 'Chinese',
            ],
            'Default' => 'en',
            'Description' => 'cPanel interface language',
        ],
    ];
}

/**
 * Test connection to WHM server
 *
 * @param array $params Server parameters
 * @return array Success or error message
 */
function cpanel_complete_TestConnection(array $params)
{
    try {
        $result = cpanel_complete_ApiRequest($params, 'version', []);

        if (isset($result['version'])) {
            return [
                'success' => true,
                'version' => $result['version'],
            ];
        }

        return [
            'error' => 'Failed to retrieve WHM version. Response: ' . json_encode($result),
        ];

    } catch (\Exception $e) {
        cpanel_complete_LogError('TestConnection', $params, $e->getMessage());
        return [
            'error' => 'Connection failed: ' . $e->getMessage(),
        ];
    }
}

/**
 * Create a new cPanel account
 *
 * @param array $params Service parameters
 * @return array Success or error message
 */
function cpanel_complete_CreateAccount(array $params)
{
    try {
        // Extract parameters
        $domain = $params['domain'];
        $username = $params['username'];
        $password = $params['password'];
        $clientEmail = $params['clientsdetails']['email'] ?? '';

        // Get configuration options
        $packageName = $params['configoption1'] ?? 'default';
        $diskSpace = cpanel_complete_ParseLimit($params['configoption2'] ?? '1000');
        $bandwidth = cpanel_complete_ParseLimit($params['configoption3'] ?? '10000');
        $maxAddon = cpanel_complete_ParseLimit($params['configoption4'] ?? '0');
        $maxSub = cpanel_complete_ParseLimit($params['configoption5'] ?? '0');
        $maxPark = cpanel_complete_ParseLimit($params['configoption6'] ?? '0');
        $maxFtp = cpanel_complete_ParseLimit($params['configoption7'] ?? '0');
        $maxSql = cpanel_complete_ParseLimit($params['configoption8'] ?? '0');
        $maxPop = cpanel_complete_ParseLimit($params['configoption9'] ?? '0');
        $maxEmailPerHour = cpanel_complete_ParseLimit($params['configoption10'] ?? '0');
        $maxLst = cpanel_complete_ParseLimit($params['configoption11'] ?? '0');
        $hasShell = ($params['configoption12'] ?? 'no') === 'on' ? 1 : 0;
        $cgi = ($params['configoption13'] ?? 'yes') === 'on' ? 1 : 0;
        $language = $params['configoption14'] ?? 'en';

        // Get custom nameservers if configured
        $nameservers = cpanel_complete_GetNameservers($params);

        // Build API parameters
        $apiParams = [
            'username' => $username,
            'domain' => $domain,
            'password' => $password,
            'contactemail' => $clientEmail,
            'plan' => $packageName,
        ];

        // Add individual limits if not using package defaults
        if ($diskSpace !== null) {
            $apiParams['quota'] = $diskSpace;
        }
        if ($bandwidth !== null) {
            $apiParams['bwlimit'] = $bandwidth;
        }
        if ($maxAddon !== null) {
            $apiParams['maxaddon'] = $maxAddon;
        }
        if ($maxSub !== null) {
            $apiParams['maxsub'] = $maxSub;
        }
        if ($maxPark !== null) {
            $apiParams['maxpark'] = $maxPark;
        }
        if ($maxFtp !== null) {
            $apiParams['maxftp'] = $maxFtp;
        }
        if ($maxSql !== null) {
            $apiParams['maxsql'] = $maxSql;
        }
        if ($maxPop !== null) {
            $apiParams['maxpop'] = $maxPop;
        }
        if ($maxEmailPerHour !== null) {
            $apiParams['max_email_per_hour'] = $maxEmailPerHour;
        }
        if ($maxLst !== null) {
            $apiParams['maxlst'] = $maxLst;
        }

        // Shell and CGI access
        $apiParams['hasshell'] = $hasShell;
        $apiParams['cgi'] = $cgi;

        // Language
        if ($language) {
            $apiParams['language'] = $language;
        }

        // Custom nameservers
        if (!empty($nameservers)) {
            $apiParams['useregns'] = 1;
            foreach ($nameservers as $i => $ns) {
                $apiParams['ns' . ($i + 1)] = $ns;
            }
        }

        // Make API request
        $result = cpanel_complete_ApiRequest($params, 'createacct', $apiParams);

        // Check result
        if (isset($result['metadata']['result']) && $result['metadata']['result'] == 1) {
            cpanel_complete_LogActivity('CreateAccount', $params, 'Account created successfully: ' . $username);
            return ['success' => true];
        } else {
            $errorMsg = $result['metadata']['reason'] ?? 'Unknown error occurred';
            cpanel_complete_LogError('CreateAccount', $params, $errorMsg, $result);
            return ['error' => $errorMsg];
        }

    } catch (\Exception $e) {
        cpanel_complete_LogError('CreateAccount', $params, $e->getMessage());
        return ['error' => 'Exception: ' . $e->getMessage()];
    }
}

/**
 * Suspend a cPanel account
 *
 * @param array $params Service parameters
 * @return array Success or error message
 */
function cpanel_complete_SuspendAccount(array $params)
{
    try {
        $username = $params['username'];
        $reason = $params['suspendreason'] ?? 'Account suspended by billing system';

        $apiParams = [
            'user' => $username,
            'reason' => $reason,
        ];

        $result = cpanel_complete_ApiRequest($params, 'suspendacct', $apiParams);

        if (isset($result['metadata']['result']) && $result['metadata']['result'] == 1) {
            cpanel_complete_LogActivity('SuspendAccount', $params, 'Account suspended: ' . $username);
            return ['success' => true];
        } else {
            $errorMsg = $result['metadata']['reason'] ?? 'Suspension failed';
            cpanel_complete_LogError('SuspendAccount', $params, $errorMsg, $result);
            return ['error' => $errorMsg];
        }

    } catch (\Exception $e) {
        cpanel_complete_LogError('SuspendAccount', $params, $e->getMessage());
        return ['error' => 'Exception: ' . $e->getMessage()];
    }
}

/**
 * Unsuspend a cPanel account
 *
 * @param array $params Service parameters
 * @return array Success or error message
 */
function cpanel_complete_UnsuspendAccount(array $params)
{
    try {
        $username = $params['username'];

        $apiParams = [
            'user' => $username,
        ];

        $result = cpanel_complete_ApiRequest($params, 'unsuspendacct', $apiParams);

        if (isset($result['metadata']['result']) && $result['metadata']['result'] == 1) {
            cpanel_complete_LogActivity('UnsuspendAccount', $params, 'Account unsuspended: ' . $username);
            return ['success' => true];
        } else {
            $errorMsg = $result['metadata']['reason'] ?? 'Unsuspension failed';
            cpanel_complete_LogError('UnsuspendAccount', $params, $errorMsg, $result);
            return ['error' => $errorMsg];
        }

    } catch (\Exception $e) {
        cpanel_complete_LogError('UnsuspendAccount', $params, $e->getMessage());
        return ['error' => 'Exception: ' . $e->getMessage()];
    }
}

/**
 * Terminate a cPanel account
 *
 * @param array $params Service parameters
 * @return array Success or error message
 */
function cpanel_complete_TerminateAccount(array $params)
{
    try {
        $username = $params['username'];

        // Option to keep DNS records
        $keepDns = isset($params['keepdns']) && $params['keepdns'] ? 1 : 0;

        $apiParams = [
            'user' => $username,
            'keepdns' => $keepDns,
        ];

        $result = cpanel_complete_ApiRequest($params, 'removeacct', $apiParams);

        if (isset($result['metadata']['result']) && $result['metadata']['result'] == 1) {
            cpanel_complete_LogActivity('TerminateAccount', $params, 'Account terminated: ' . $username);
            return ['success' => true];
        } else {
            $errorMsg = $result['metadata']['reason'] ?? 'Termination failed';
            cpanel_complete_LogError('TerminateAccount', $params, $errorMsg, $result);
            return ['error' => $errorMsg];
        }

    } catch (\Exception $e) {
        cpanel_complete_LogError('TerminateAccount', $params, $e->getMessage());
        return ['error' => 'Exception: ' . $e->getMessage()];
    }
}

/**
 * Change package of an existing cPanel account
 *
 * @param array $params Service parameters
 * @return array Success or error message
 */
function cpanel_complete_ChangePackage(array $params)
{
    try {
        $username = $params['username'];
        $packageName = $params['configoption1'] ?? 'default';

        $apiParams = [
            'user' => $username,
            'pkg' => $packageName,
        ];

        $result = cpanel_complete_ApiRequest($params, 'changepackage', $apiParams);

        if (isset($result['metadata']['result']) && $result['metadata']['result'] == 1) {
            cpanel_complete_LogActivity('ChangePackage', $params, "Package changed to {$packageName} for {$username}");
            return ['success' => true];
        } else {
            $errorMsg = $result['metadata']['reason'] ?? 'Package change failed';
            cpanel_complete_LogError('ChangePackage', $params, $errorMsg, $result);
            return ['error' => $errorMsg];
        }

    } catch (\Exception $e) {
        cpanel_complete_LogError('ChangePackage', $params, $e->getMessage());
        return ['error' => 'Exception: ' . $e->getMessage()];
    }
}

/**
 * Change password for a cPanel account
 *
 * @param array $params Service parameters
 * @return array Success or error message
 */
function cpanel_complete_ChangePassword(array $params)
{
    try {
        $username = $params['username'];
        $newPassword = $params['password'];

        $apiParams = [
            'user' => $username,
            'password' => $newPassword,
        ];

        $result = cpanel_complete_ApiRequest($params, 'passwd', $apiParams);

        if (isset($result['metadata']['result']) && $result['metadata']['result'] == 1) {
            cpanel_complete_LogActivity('ChangePassword', $params, 'Password changed for: ' . $username);
            return ['success' => true];
        } else {
            $errorMsg = $result['metadata']['reason'] ?? 'Password change failed';
            cpanel_complete_LogError('ChangePassword', $params, $errorMsg, $result);
            return ['error' => $errorMsg];
        }

    } catch (\Exception $e) {
        cpanel_complete_LogError('ChangePassword', $params, $e->getMessage());
        return ['error' => 'Exception: ' . $e->getMessage()];
    }
}

/**
 * Modify account settings
 *
 * @param array $params Service parameters
 * @return array Success or error message
 */
function cpanel_complete_ModifyAccount(array $params)
{
    try {
        $username = $params['username'];

        // Build modification parameters
        $apiParams = ['user' => $username];

        // Get current configuration options
        $diskSpace = cpanel_complete_ParseLimit($params['configoption2'] ?? null);
        $bandwidth = cpanel_complete_ParseLimit($params['configoption3'] ?? null);
        $maxAddon = cpanel_complete_ParseLimit($params['configoption4'] ?? null);
        $maxSub = cpanel_complete_ParseLimit($params['configoption5'] ?? null);
        $maxPark = cpanel_complete_ParseLimit($params['configoption6'] ?? null);
        $maxFtp = cpanel_complete_ParseLimit($params['configoption7'] ?? null);
        $maxSql = cpanel_complete_ParseLimit($params['configoption8'] ?? null);
        $maxPop = cpanel_complete_ParseLimit($params['configoption9'] ?? null);
        $maxEmailPerHour = cpanel_complete_ParseLimit($params['configoption10'] ?? null);
        $maxLst = cpanel_complete_ParseLimit($params['configoption11'] ?? null);
        $hasShell = ($params['configoption12'] ?? 'no') === 'on' ? 1 : 0;

        // Add modifications
        if ($diskSpace !== null) {
            $apiParams['QUOTA'] = $diskSpace;
        }
        if ($bandwidth !== null) {
            $apiParams['BWLIMIT'] = $bandwidth;
        }
        if ($maxAddon !== null) {
            $apiParams['MAXADDON'] = $maxAddon;
        }
        if ($maxSub !== null) {
            $apiParams['MAXSUB'] = $maxSub;
        }
        if ($maxPark !== null) {
            $apiParams['MAXPARK'] = $maxPark;
        }
        if ($maxFtp !== null) {
            $apiParams['MAXFTP'] = $maxFtp;
        }
        if ($maxSql !== null) {
            $apiParams['MAXSQL'] = $maxSql;
        }
        if ($maxPop !== null) {
            $apiParams['MAXPOP'] = $maxPop;
        }
        if ($maxEmailPerHour !== null) {
            $apiParams['MAX_EMAIL_PER_HOUR'] = $maxEmailPerHour;
        }
        if ($maxLst !== null) {
            $apiParams['MAXLST'] = $maxLst;
        }

        $apiParams['HASSHELL'] = $hasShell;

        $result = cpanel_complete_ApiRequest($params, 'modifyacct', $apiParams);

        if (isset($result['metadata']['result']) && $result['metadata']['result'] == 1) {
            cpanel_complete_LogActivity('ModifyAccount', $params, 'Account modified: ' . $username);
            return ['success' => true];
        } else {
            $errorMsg = $result['metadata']['reason'] ?? 'Account modification failed';
            cpanel_complete_LogError('ModifyAccount', $params, $errorMsg, $result);
            return ['error' => $errorMsg];
        }

    } catch (\Exception $e) {
        cpanel_complete_LogError('ModifyAccount', $params, $e->getMessage());
        return ['error' => 'Exception: ' . $e->getMessage()];
    }
}

/**
 * Get client area output with SSO to cPanel
 *
 * @param array $params Service parameters
 * @return array Template variables
 */
function cpanel_complete_ClientArea(array $params)
{
    try {
        $username = $params['username'];
        $domain = $params['domain'];

        // Generate cPanel auto-login link
        $loginUrl = cpanel_complete_GenerateSsoUrl($params, 'cpanel');

        return [
            'templatefile' => 'clientarea',
            'vars' => [
                'username' => $username,
                'domain' => $domain,
                'cpanel_url' => $loginUrl,
                'server_ip' => $params['serverip'],
            ],
        ];

    } catch (\Exception $e) {
        cpanel_complete_LogError('ClientArea', $params, $e->getMessage());
        return [
            'templatefile' => 'clientarea',
            'vars' => [
                'error' => $e->getMessage(),
            ],
        ];
    }
}

/**
 * Get single sign-on URL for client cPanel access
 *
 * @param array $params Service parameters
 * @return array SSO URL
 */
function cpanel_complete_ServiceSingleSignOn(array $params)
{
    try {
        $ssoUrl = cpanel_complete_GenerateSsoUrl($params, 'cpanel');

        return [
            'success' => true,
            'redirectTo' => $ssoUrl,
        ];

    } catch (\Exception $e) {
        cpanel_complete_LogError('ServiceSingleSignOn', $params, $e->getMessage());
        return [
            'success' => false,
            'errorMsg' => $e->getMessage(),
        ];
    }
}

/**
 * Get single sign-on URL for admin WHM access
 *
 * @param array $params Service parameters
 * @return array SSO URL
 */
function cpanel_complete_AdminSingleSignOn(array $params)
{
    try {
        $ssoUrl = cpanel_complete_GenerateSsoUrl($params, 'whm');

        return [
            'success' => true,
            'redirectTo' => $ssoUrl,
        ];

    } catch (\Exception $e) {
        cpanel_complete_LogError('AdminSingleSignOn', $params, $e->getMessage());
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
function cpanel_complete_AdminServicesTabFields(array $params)
{
    try {
        $username = $params['username'];

        // Get account details from WHM
        $accountInfo = cpanel_complete_FetchAccountInfo($params, $username);

        $fields = [
            'Username' => $username,
            'Domain' => $params['domain'],
            'Server IP' => $params['serverip'],
            'Package' => $params['configoption1'] ?? 'default',
        ];

        if (!empty($accountInfo)) {
            $fields['Disk Used'] = cpanel_complete_FormatBytes($accountInfo['diskused'] ?? 0);
            $fields['Disk Limit'] = cpanel_complete_FormatBytes($accountInfo['disklimit'] ?? 0);
            $fields['Bandwidth Used'] = cpanel_complete_FormatBytes($accountInfo['bandwidthused'] ?? 0);
            $fields['Bandwidth Limit'] = cpanel_complete_FormatBytes($accountInfo['bandwidthlimit'] ?? 0);
            $fields['Status'] = isset($accountInfo['suspended']) && $accountInfo['suspended'] == 1 ? 'Suspended' : 'Active';
        }

        return $fields;

    } catch (\Exception $e) {
        cpanel_complete_LogError('AdminServicesTabFields', $params, $e->getMessage());
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
function cpanel_complete_AdminCustomButtonArray()
{
    return [
        'Get Account Info' => 'GetAccountInfo',
        'Rebuild Web Config' => 'RebuildWebConfig',
        'Fix Permissions' => 'FixPermissions',
    ];
}

/**
 * Get detailed account information
 *
 * @param array $params Service parameters
 * @return string Result message
 */
function cpanel_complete_GetAccountInfo(array $params)
{
    try {
        $username = $params['username'];

        $result = cpanel_complete_ApiRequest($params, 'accountsummary', [
            'user' => $username,
        ]);

        if (isset($result['data']['acct'][0])) {
            $account = $result['data']['acct'][0];

            $info = "Account Information for: {$username}\n\n";
            $info .= "Domain: " . ($account['domain'] ?? 'N/A') . "\n";
            $info .= "Email: " . ($account['email'] ?? 'N/A') . "\n";
            $info .= "Disk Used: " . cpanel_complete_FormatBytes($account['diskused'] ?? 0) . "\n";
            $info .= "Disk Limit: " . cpanel_complete_FormatBytes($account['disklimit'] ?? 0) . "\n";
            $info .= "Package: " . ($account['plan'] ?? 'N/A') . "\n";
            $info .= "Suspended: " . (isset($account['suspended']) && $account['suspended'] == 1 ? 'Yes' : 'No') . "\n";

            return $info;
        }

        return 'Account information not found';

    } catch (\Exception $e) {
        cpanel_complete_LogError('GetAccountInfo', $params, $e->getMessage());
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Rebuild web server configuration
 *
 * @param array $params Service parameters
 * @return string Result message
 */
function cpanel_complete_RebuildWebConfig(array $params)
{
    try {
        $username = $params['username'];

        $result = cpanel_complete_ApiRequest($params, 'rebuild_webserver_config', [
            'user' => $username,
        ]);

        if (isset($result['metadata']['result']) && $result['metadata']['result'] == 1) {
            return 'Web server configuration rebuilt successfully';
        }

        return 'Failed to rebuild web configuration: ' . ($result['metadata']['reason'] ?? 'Unknown error');

    } catch (\Exception $e) {
        cpanel_complete_LogError('RebuildWebConfig', $params, $e->getMessage());
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Fix file permissions
 *
 * @param array $params Service parameters
 * @return string Result message
 */
function cpanel_complete_FixPermissions(array $params)
{
    try {
        $username = $params['username'];

        $result = cpanel_complete_ApiRequest($params, 'fixperms', [
            'user' => $username,
        ]);

        if (isset($result['metadata']['result']) && $result['metadata']['result'] == 1) {
            return 'Permissions fixed successfully';
        }

        return 'Failed to fix permissions: ' . ($result['metadata']['reason'] ?? 'Unknown error');

    } catch (\Exception $e) {
        cpanel_complete_LogError('FixPermissions', $params, $e->getMessage());
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Client area custom buttons
 *
 * @return array Custom client buttons
 */
function cpanel_complete_ClientAreaCustomButtonArray()
{
    return [
        'Login to cPanel' => 'LoginToCpanel',
        'Login to Webmail' => 'LoginToWebmail',
    ];
}

/**
 * Login to cPanel button action
 *
 * @param array $params Service parameters
 * @return array Redirect URL
 */
function cpanel_complete_LoginToCpanel(array $params)
{
    $ssoUrl = cpanel_complete_GenerateSsoUrl($params, 'cpanel');

    return [
        'success' => true,
        'redirectTo' => $ssoUrl,
    ];
}

/**
 * Login to Webmail button action
 *
 * @param array $params Service parameters
 * @return array Redirect URL
 */
function cpanel_complete_LoginToWebmail(array $params)
{
    $ssoUrl = cpanel_complete_GenerateSsoUrl($params, 'webmail');

    return [
        'success' => true,
        'redirectTo' => $ssoUrl,
    ];
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Make WHM API request
 *
 * @param array $params Server parameters
 * @param string $function API function name
 * @param array $apiParams API parameters
 * @return array API response
 */
function cpanel_complete_ApiRequest(array $params, string $function, array $apiParams = [])
{
    $serverIp = $params['serverhostname'] ?? $params['serverip'];
    $serverPort = $params['serverport'] ?? 2087;
    $serverUsername = $params['serverusername'];
    $serverAccessHash = $params['serveraccesshash'] ?? '';
    $serverPassword = $params['serverpassword'] ?? '';
    $serverSecure = $params['serversecure'] ?? true;

    // Build URL
    $protocol = $serverSecure ? 'https' : 'http';
    $url = "{$protocol}://{$serverIp}:{$serverPort}/json-api/{$function}";

    // Add parameters to URL
    if (!empty($apiParams)) {
        $url .= '?' . http_build_query($apiParams);
    }

    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);

    // Set authentication
    if (!empty($serverAccessHash)) {
        // Use access hash (preferred method)
        $accessHash = preg_replace('/\s+/', '', $serverAccessHash);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: whm {$serverUsername}:{$accessHash}",
        ]);
    } else {
        // Use username:password
        curl_setopt($ch, CURLOPT_USERPWD, "{$serverUsername}:{$serverPassword}");
    }

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

    // Parse JSON response
    $result = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new \Exception("JSON Parse Error: " . json_last_error_msg());
    }

    // Log the API call
    logModuleCall(
        'cpanel_complete',
        $function,
        $apiParams,
        $response,
        $result,
        [$serverPassword, $serverAccessHash] // Hide sensitive data
    );

    return $result;
}

/**
 * Generate SSO URL for cPanel/WHM access
 *
 * @param array $params Service parameters
 * @param string $service Service type (cpanel, whm, webmail)
 * @return string SSO URL
 */
function cpanel_complete_GenerateSsoUrl(array $params, string $service = 'cpanel')
{
    $serverIp = $params['serverhostname'] ?? $params['serverip'];
    $serverSecure = $params['serversecure'] ?? true;
    $username = $params['username'];

    try {
        // Generate one-time login token
        $result = cpanel_complete_ApiRequest($params, 'create_user_session', [
            'user' => $username,
            'service' => $service,
        ]);

        if (isset($result['data']['url'])) {
            return $result['data']['url'];
        }

        // Fallback to basic URL if token generation fails
        $protocol = $serverSecure ? 'https' : 'http';

        switch ($service) {
            case 'whm':
                $port = $serverSecure ? 2087 : 2086;
                return "{$protocol}://{$serverIp}:{$port}/";
            case 'webmail':
                $port = $serverSecure ? 2096 : 2095;
                return "{$protocol}://{$serverIp}:{$port}/";
            case 'cpanel':
            default:
                $port = $serverSecure ? 2083 : 2082;
                return "{$protocol}://{$serverIp}:{$port}/";
        }

    } catch (\Exception $e) {
        // Return basic URL on error
        $protocol = $serverSecure ? 'https' : 'http';
        $port = $service === 'whm' ? 2087 : 2083;
        return "{$protocol}://{$serverIp}:{$port}/";
    }
}

/**
 * Fetch account information from WHM (helper function)
 *
 * @param array $params Service parameters
 * @param string $username Account username
 * @return array Account information
 */
function cpanel_complete_FetchAccountInfo(array $params, string $username)
{
    try {
        $result = cpanel_complete_ApiRequest($params, 'accountsummary', [
            'user' => $username,
        ]);

        if (isset($result['data']['acct'][0])) {
            return $result['data']['acct'][0];
        }

        return [];

    } catch (\Exception $e) {
        return [];
    }
}

/**
 * Get nameservers from configuration
 *
 * @param array $params Service parameters
 * @return array Nameservers
 */
function cpanel_complete_GetNameservers(array $params)
{
    $nameservers = [];

    // Check for custom nameservers in product configuration
    for ($i = 1; $i <= 5; $i++) {
        $ns = $params["ns{$i}"] ?? '';
        if (!empty($ns)) {
            $nameservers[] = $ns;
        }
    }

    return $nameservers;
}

/**
 * Parse limit value (handles 'unlimited' keyword)
 *
 * @param mixed $value Limit value
 * @return int|null Parsed limit
 */
function cpanel_complete_ParseLimit($value)
{
    if ($value === null || $value === '') {
        return null;
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
function cpanel_complete_FormatBytes($bytes)
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
function cpanel_complete_LogActivity(string $action, array $params, string $message)
{
    try {
        if (function_exists('logActivity')) {
            logActivity("cPanel Complete - {$action}: {$message}");
        }

        if (class_exists('Log')) {
            Log::info("cPanel Complete - {$action}", [
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
function cpanel_complete_LogError(string $action, array $params, string $error, array $response = [])
{
    try {
        if (function_exists('logModuleCall')) {
            logModuleCall(
                'cpanel_complete',
                $action,
                $params,
                $error,
                $response
            );
        }

        if (class_exists('Log')) {
            Log::error("cPanel Complete - {$action} Error", [
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

/**
 * Disk space loader (for dynamic options)
 *
 * @param array $params Parameters
 * @return array Options
 */
function cpanel_complete_DiskSpaceLoader(array $params)
{
    return [
        '500' => '500 MB',
        '1000' => '1 GB',
        '2000' => '2 GB',
        '5000' => '5 GB',
        '10000' => '10 GB',
        '20000' => '20 GB',
        '50000' => '50 GB',
        'unlimited' => 'Unlimited',
    ];
}

/**
 * Bandwidth loader (for dynamic options)
 *
 * @param array $params Parameters
 * @return array Options
 */
function cpanel_complete_BandwidthLoader(array $params)
{
    return [
        '10000' => '10 GB',
        '25000' => '25 GB',
        '50000' => '50 GB',
        '100000' => '100 GB',
        '250000' => '250 GB',
        '500000' => '500 GB',
        '1000000' => '1 TB',
        'unlimited' => 'Unlimited',
    ];
}
