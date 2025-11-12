<?php
/**
 * Example Domain Registrar Module
 *
 * Demonstrates WHMCS registrar module compatibility
 * for domain registration, transfer, renewal, and management
 */

if (!defined('WHMCS')) {
    define('WHMCS', true);
}

/**
 * Registrar module metadata
 */
function example_registrar_MetaData()
{
    return [
        'DisplayName' => 'Example Domain Registrar',
        'APIVersion' => '1.1',
    ];
}

/**
 * Registrar configuration
 */
function example_registrar_getConfigArray()
{
    return [
        'FriendlyName' => [
            'Type' => 'System',
            'Value' => 'Example Domain Registrar',
        ],
        'Description' => [
            'Type' => 'System',
            'Value' => 'Register and manage domains through Example Registrar API',
        ],
        'api_key' => [
            'FriendlyName' => 'API Key',
            'Type' => 'text',
            'Size' => '50',
            'Default' => '',
            'Description' => 'Enter your API key from the registrar',
        ],
        'api_secret' => [
            'FriendlyName' => 'API Secret',
            'Type' => 'password',
            'Size' => '50',
            'Default' => '',
            'Description' => 'Enter your API secret',
        ],
        'test_mode' => [
            'FriendlyName' => 'Test Mode',
            'Type' => 'yesno',
            'Description' => 'Enable test mode for sandbox environment',
        ],
        'promotional_code' => [
            'FriendlyName' => 'Promotional Code',
            'Type' => 'text',
            'Size' => '20',
            'Default' => '',
            'Description' => 'Optional promotional code for discounts',
        ],
        'auto_renew_default' => [
            'FriendlyName' => 'Auto-Renew Default',
            'Type' => 'yesno',
            'Description' => 'Enable auto-renew by default for new domains',
        ],
    ];
}

/**
 * Register a domain
 */
function example_registrar_RegisterDomain($params)
{
    try {
        // Configuration parameters
        $apiKey = $params['api_key'];
        $apiSecret = $params['api_secret'];
        $testMode = $params['test_mode'];

        // Domain parameters
        $domainName = $params['domainname']; // e.g., example.com
        $registrationPeriod = $params['regperiod']; // in years

        // Registrant details
        $firstName = $params['firstname'];
        $lastName = $params['lastname'];
        $fullName = $params['fullname'];
        $companyName = $params['companyname'] ?? '';
        $email = $params['email'];
        $address1 = $params['address1'];
        $address2 = $params['address2'] ?? '';
        $city = $params['city'];
        $state = $params['state'];
        $postcode = $params['postcode'];
        $country = $params['country']; // ISO 3166-1 two-letter code
        $phoneNumber = $params['phonenumber'];

        // Nameservers
        $nameservers = [];
        for ($i = 1; $i <= 5; $i++) {
            if (!empty($params["ns{$i}"])) {
                $nameservers[] = $params["ns{$i}"];
            }
        }

        // Additional domain fields (for specific TLDs)
        $additionalFields = $params['additionalfields'] ?? [];

        // Build API endpoint
        $apiUrl = $testMode
            ? 'https://api-sandbox.example-registrar.com/v1/domains/register'
            : 'https://api.example-registrar.com/v1/domains/register';

        // Prepare request data
        $requestData = [
            'domain' => $domainName,
            'years' => $registrationPeriod,
            'registrant' => [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'organization' => $companyName,
                'email' => $email,
                'address1' => $address1,
                'address2' => $address2,
                'city' => $city,
                'state' => $state,
                'postal_code' => $postcode,
                'country' => $country,
                'phone' => $phoneNumber,
            ],
            'nameservers' => $nameservers,
            'privacy_protection' => $params['idprotection'] ?? false,
            'auto_renew' => $params['auto_renew_default'] ?? false,
        ];

        // Add promotional code if set
        if (!empty($params['promotional_code'])) {
            $requestData['promo_code'] = $params['promotional_code'];
        }

        // Make API call
        $response = makeApiCall($apiUrl, $requestData, $apiKey, $apiSecret);

        // Log the call
        logModuleCall(
            'example_registrar',
            'RegisterDomain',
            $requestData,
            $response,
            $response
        );

        $result = json_decode($response, true);

        if ($result && $result['status'] === 'success') {
            return [
                'success' => true,
            ];
        } else {
            return [
                'error' => $result['message'] ?? 'Domain registration failed',
            ];
        }

    } catch (\Exception $e) {
        logModuleCall(
            'example_registrar',
            'RegisterDomain',
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return [
            'error' => $e->getMessage(),
        ];
    }
}

/**
 * Transfer a domain
 */
function example_registrar_TransferDomain($params)
{
    try {
        $apiKey = $params['api_key'];
        $apiSecret = $params['api_secret'];
        $testMode = $params['test_mode'];

        $domainName = $params['domainname'];
        $eppCode = $params['eppcode'] ?? $params['transfersecret'] ?? '';

        // Build API endpoint
        $apiUrl = $testMode
            ? 'https://api-sandbox.example-registrar.com/v1/domains/transfer'
            : 'https://api.example-registrar.com/v1/domains/transfer';

        $requestData = [
            'domain' => $domainName,
            'epp_code' => $eppCode,
            'registrant' => [
                'first_name' => $params['firstname'],
                'last_name' => $params['lastname'],
                'email' => $params['email'],
                'address1' => $params['address1'],
                'city' => $params['city'],
                'state' => $params['state'],
                'postal_code' => $params['postcode'],
                'country' => $params['country'],
                'phone' => $params['phonenumber'],
            ],
        ];

        $response = makeApiCall($apiUrl, $requestData, $apiKey, $apiSecret);

        logModuleCall('example_registrar', 'TransferDomain', $requestData, $response, $response);

        $result = json_decode($response, true);

        if ($result && $result['status'] === 'success') {
            return ['success' => true];
        } else {
            return ['error' => $result['message'] ?? 'Domain transfer failed'];
        }

    } catch (\Exception $e) {
        logModuleCall('example_registrar', 'TransferDomain', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Renew a domain
 */
function example_registrar_RenewDomain($params)
{
    try {
        $apiKey = $params['api_key'];
        $apiSecret = $params['api_secret'];
        $testMode = $params['test_mode'];

        $domainName = $params['domainname'];
        $renewalPeriod = $params['regperiod']; // in years

        $apiUrl = $testMode
            ? 'https://api-sandbox.example-registrar.com/v1/domains/renew'
            : 'https://api.example-registrar.com/v1/domains/renew';

        $requestData = [
            'domain' => $domainName,
            'years' => $renewalPeriod,
        ];

        $response = makeApiCall($apiUrl, $requestData, $apiKey, $apiSecret);

        logModuleCall('example_registrar', 'RenewDomain', $requestData, $response, $response);

        $result = json_decode($response, true);

        if ($result && $result['status'] === 'success') {
            return [
                'success' => true,
                'expirydate' => $result['expiry_date'] ?? null,
            ];
        } else {
            return ['error' => $result['message'] ?? 'Domain renewal failed'];
        }

    } catch (\Exception $e) {
        logModuleCall('example_registrar', 'RenewDomain', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get nameservers for a domain
 */
function example_registrar_GetNameservers($params)
{
    try {
        $apiKey = $params['api_key'];
        $apiSecret = $params['api_secret'];
        $testMode = $params['test_mode'];
        $domainName = $params['domainname'];

        $apiUrl = $testMode
            ? "https://api-sandbox.example-registrar.com/v1/domains/{$domainName}/nameservers"
            : "https://api.example-registrar.com/v1/domains/{$domainName}/nameservers";

        $response = makeApiCall($apiUrl, [], $apiKey, $apiSecret, 'GET');

        logModuleCall('example_registrar', 'GetNameservers', $params, $response, $response);

        $result = json_decode($response, true);

        if ($result && $result['status'] === 'success') {
            $nameservers = [];
            foreach ($result['nameservers'] as $index => $ns) {
                $nameservers['ns' . ($index + 1)] = $ns;
            }
            return $nameservers;
        } else {
            return ['error' => $result['message'] ?? 'Failed to retrieve nameservers'];
        }

    } catch (\Exception $e) {
        logModuleCall('example_registrar', 'GetNameservers', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Save nameservers for a domain
 */
function example_registrar_SaveNameservers($params)
{
    try {
        $apiKey = $params['api_key'];
        $apiSecret = $params['api_secret'];
        $testMode = $params['test_mode'];
        $domainName = $params['domainname'];

        // Collect nameservers
        $nameservers = [];
        for ($i = 1; $i <= 5; $i++) {
            if (!empty($params["ns{$i}"])) {
                $nameservers[] = $params["ns{$i}"];
            }
        }

        $apiUrl = $testMode
            ? "https://api-sandbox.example-registrar.com/v1/domains/{$domainName}/nameservers"
            : "https://api.example-registrar.com/v1/domains/{$domainName}/nameservers";

        $requestData = ['nameservers' => $nameservers];

        $response = makeApiCall($apiUrl, $requestData, $apiKey, $apiSecret, 'PUT');

        logModuleCall('example_registrar', 'SaveNameservers', $requestData, $response, $response);

        $result = json_decode($response, true);

        if ($result && $result['status'] === 'success') {
            return ['success' => true];
        } else {
            return ['error' => $result['message'] ?? 'Failed to update nameservers'];
        }

    } catch (\Exception $e) {
        logModuleCall('example_registrar', 'SaveNameservers', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get registrar lock status
 */
function example_registrar_GetRegistrarLock($params)
{
    try {
        $apiKey = $params['api_key'];
        $apiSecret = $params['api_secret'];
        $testMode = $params['test_mode'];
        $domainName = $params['domainname'];

        $apiUrl = $testMode
            ? "https://api-sandbox.example-registrar.com/v1/domains/{$domainName}"
            : "https://api.example-registrar.com/v1/domains/{$domainName}";

        $response = makeApiCall($apiUrl, [], $apiKey, $apiSecret, 'GET');

        logModuleCall('example_registrar', 'GetRegistrarLock', $params, $response, $response);

        $result = json_decode($response, true);

        if ($result && $result['status'] === 'success') {
            return $result['locked'] ? 'locked' : 'unlocked';
        } else {
            return ['error' => $result['message'] ?? 'Failed to get lock status'];
        }

    } catch (\Exception $e) {
        logModuleCall('example_registrar', 'GetRegistrarLock', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Save registrar lock
 */
function example_registrar_SaveRegistrarLock($params)
{
    try {
        $apiKey = $params['api_key'];
        $apiSecret = $params['api_secret'];
        $testMode = $params['test_mode'];
        $domainName = $params['domainname'];
        $lockStatus = $params['lockenabled']; // 'locked' or 'unlocked'

        $apiUrl = $testMode
            ? "https://api-sandbox.example-registrar.com/v1/domains/{$domainName}/lock"
            : "https://api.example-registrar.com/v1/domains/{$domainName}/lock";

        $requestData = ['locked' => ($lockStatus === 'locked')];

        $response = makeApiCall($apiUrl, $requestData, $apiKey, $apiSecret, 'PUT');

        logModuleCall('example_registrar', 'SaveRegistrarLock', $requestData, $response, $response);

        $result = json_decode($response, true);

        if ($result && $result['status'] === 'success') {
            return ['success' => true];
        } else {
            return ['error' => $result['message'] ?? 'Failed to update lock status'];
        }

    } catch (\Exception $e) {
        logModuleCall('example_registrar', 'SaveRegistrarLock', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get EPP code (auth code)
 */
function example_registrar_GetEPPCode($params)
{
    try {
        $apiKey = $params['api_key'];
        $apiSecret = $params['api_secret'];
        $testMode = $params['test_mode'];
        $domainName = $params['domainname'];

        $apiUrl = $testMode
            ? "https://api-sandbox.example-registrar.com/v1/domains/{$domainName}/epp"
            : "https://api.example-registrar.com/v1/domains/{$domainName}/epp";

        $response = makeApiCall($apiUrl, [], $apiKey, $apiSecret, 'GET');

        logModuleCall('example_registrar', 'GetEPPCode', $params, $response, $response);

        $result = json_decode($response, true);

        if ($result && $result['status'] === 'success') {
            return [
                'eppcode' => $result['epp_code'],
            ];
        } else {
            return ['error' => $result['message'] ?? 'Failed to retrieve EPP code'];
        }

    } catch (\Exception $e) {
        logModuleCall('example_registrar', 'GetEPPCode', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Register nameserver
 */
function example_registrar_RegisterNameserver($params)
{
    try {
        $apiKey = $params['api_key'];
        $apiSecret = $params['api_secret'];
        $testMode = $params['test_mode'];

        $nameserver = $params['nameserver'];
        $ipAddress = $params['ipaddress'];

        $apiUrl = $testMode
            ? 'https://api-sandbox.example-registrar.com/v1/nameservers/register'
            : 'https://api.example-registrar.com/v1/nameservers/register';

        $requestData = [
            'nameserver' => $nameserver,
            'ip_address' => $ipAddress,
        ];

        $response = makeApiCall($apiUrl, $requestData, $apiKey, $apiSecret);

        logModuleCall('example_registrar', 'RegisterNameserver', $requestData, $response, $response);

        $result = json_decode($response, true);

        if ($result && $result['status'] === 'success') {
            return ['success' => true];
        } else {
            return ['error' => $result['message'] ?? 'Failed to register nameserver'];
        }

    } catch (\Exception $e) {
        logModuleCall('example_registrar', 'RegisterNameserver', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Modify nameserver
 */
function example_registrar_ModifyNameserver($params)
{
    try {
        $apiKey = $params['api_key'];
        $apiSecret = $params['api_secret'];
        $testMode = $params['test_mode'];

        $nameserver = $params['nameserver'];
        $currentIp = $params['currentipaddress'];
        $newIp = $params['newipaddress'];

        $apiUrl = $testMode
            ? 'https://api-sandbox.example-registrar.com/v1/nameservers/modify'
            : 'https://api.example-registrar.com/v1/nameservers/modify';

        $requestData = [
            'nameserver' => $nameserver,
            'old_ip' => $currentIp,
            'new_ip' => $newIp,
        ];

        $response = makeApiCall($apiUrl, $requestData, $apiKey, $apiSecret);

        logModuleCall('example_registrar', 'ModifyNameserver', $requestData, $response, $response);

        $result = json_decode($response, true);

        if ($result && $result['status'] === 'success') {
            return ['success' => true];
        } else {
            return ['error' => $result['message'] ?? 'Failed to modify nameserver'];
        }

    } catch (\Exception $e) {
        logModuleCall('example_registrar', 'ModifyNameserver', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Delete nameserver
 */
function example_registrar_DeleteNameserver($params)
{
    try {
        $apiKey = $params['api_key'];
        $apiSecret = $params['api_secret'];
        $testMode = $params['test_mode'];
        $nameserver = $params['nameserver'];

        $apiUrl = $testMode
            ? 'https://api-sandbox.example-registrar.com/v1/nameservers/delete'
            : 'https://api.example-registrar.com/v1/nameservers/delete';

        $requestData = ['nameserver' => $nameserver];

        $response = makeApiCall($apiUrl, $requestData, $apiKey, $apiSecret, 'DELETE');

        logModuleCall('example_registrar', 'DeleteNameserver', $requestData, $response, $response);

        $result = json_decode($response, true);

        if ($result && $result['status'] === 'success') {
            return ['success' => true];
        } else {
            return ['error' => $result['message'] ?? 'Failed to delete nameserver'];
        }

    } catch (\Exception $e) {
        logModuleCall('example_registrar', 'DeleteNameserver', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Sync domain status and expiry date
 */
function example_registrar_Sync($params)
{
    try {
        $apiKey = $params['api_key'];
        $apiSecret = $params['api_secret'];
        $testMode = $params['test_mode'];
        $domainName = $params['domainname'];

        $apiUrl = $testMode
            ? "https://api-sandbox.example-registrar.com/v1/domains/{$domainName}"
            : "https://api.example-registrar.com/v1/domains/{$domainName}";

        $response = makeApiCall($apiUrl, [], $apiKey, $apiSecret, 'GET');

        logModuleCall('example_registrar', 'Sync', $params, $response, $response);

        $result = json_decode($response, true);

        if ($result && $result['status'] === 'success') {
            $domain = $result['domain'];

            return [
                'expirydate' => date('Y-m-d', strtotime($domain['expiry_date'])),
                'active' => $domain['status'] === 'active',
                'expired' => $domain['status'] === 'expired',
                'transferredAway' => $domain['status'] === 'transferred',
            ];
        } else {
            return ['error' => $result['message'] ?? 'Failed to sync domain'];
        }

    } catch (\Exception $e) {
        logModuleCall('example_registrar', 'Sync', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Request domain delete
 */
function example_registrar_RequestDelete($params)
{
    try {
        $apiKey = $params['api_key'];
        $apiSecret = $params['api_secret'];
        $testMode = $params['test_mode'];
        $domainName = $params['domainname'];

        $apiUrl = $testMode
            ? "https://api-sandbox.example-registrar.com/v1/domains/{$domainName}/delete"
            : "https://api.example-registrar.com/v1/domains/{$domainName}/delete";

        $response = makeApiCall($apiUrl, [], $apiKey, $apiSecret, 'POST');

        logModuleCall('example_registrar', 'RequestDelete', $params, $response, $response);

        $result = json_decode($response, true);

        if ($result && $result['status'] === 'success') {
            return ['success' => true];
        } else {
            return ['error' => $result['message'] ?? 'Failed to request domain deletion'];
        }

    } catch (\Exception $e) {
        logModuleCall('example_registrar', 'RequestDelete', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Helper function to make API calls
 */
function makeApiCall($url, $data = [], $apiKey = '', $apiSecret = '', $method = 'POST')
{
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    // Set authentication
    curl_setopt($ch, CURLOPT_USERPWD, "{$apiKey}:{$apiSecret}");

    // Set method and data
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    // Set headers
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new \Exception("API call failed: {$error}");
    }

    curl_close($ch);

    return $response;
}
