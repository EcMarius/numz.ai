<?php
/**
 * ResellerClub/LogicBoxes Domain Registrar Module
 *
 * Full integration with ResellerClub API for domain registration,
 * transfer, renewal, and management operations
 */

if (!defined('WHMCS')) {
    define('WHMCS', true);
}

/**
 * Registrar module metadata
 */
function resellerclub_MetaData()
{
    return [
        'DisplayName' => 'ResellerClub',
        'APIVersion' => '1.1',
    ];
}

/**
 * Registrar configuration
 */
function resellerclub_getConfigArray()
{
    return [
        'FriendlyName' => [
            'Type' => 'System',
            'Value' => 'ResellerClub / LogicBoxes',
        ],
        'Description' => [
            'Type' => 'System',
            'Value' => 'Register and manage domains through ResellerClub API',
        ],
        'reseller_id' => [
            'FriendlyName' => 'Reseller ID',
            'Type' => 'text',
            'Size' => '50',
            'Default' => '',
            'Description' => 'Enter your ResellerClub reseller ID',
        ],
        'api_key' => [
            'FriendlyName' => 'API Key',
            'Type' => 'password',
            'Size' => '50',
            'Default' => '',
            'Description' => 'Enter your ResellerClub API key',
        ],
        'test_mode' => [
            'FriendlyName' => 'Test Mode',
            'Type' => 'yesno',
            'Description' => 'Enable demo/test environment',
        ],
        'promotional_code' => [
            'FriendlyName' => 'Promotional Code',
            'Type' => 'text',
            'Size' => '20',
            'Default' => '',
            'Description' => 'Optional promotional code for discounts',
        ],
    ];
}

/**
 * Register a domain
 */
function resellerclub_RegisterDomain($params)
{
    try {
        // First, get or create customer
        $customerId = resellerclub_getCustomerId($params);

        if (!$customerId) {
            return ['error' => 'Failed to get or create customer'];
        }

        // Build nameservers
        $nameservers = [];
        for ($i = 1; $i <= 5; $i++) {
            if (!empty($params["ns{$i}"])) {
                $nameservers[] = $params["ns{$i}"];
            }
        }

        // Get contact IDs
        $contactIds = resellerclub_getContactIds($params, $customerId);

        if (!$contactIds) {
            return ['error' => 'Failed to create contacts'];
        }

        // Build API parameters
        $apiParams = [
            'domain-name' => $params['domainname'],
            'years' => $params['regperiod'],
            'ns' => $nameservers,
            'customer-id' => $customerId,
            'reg-contact-id' => $contactIds['registrant'],
            'admin-contact-id' => $contactIds['admin'],
            'tech-contact-id' => $contactIds['tech'],
            'billing-contact-id' => $contactIds['billing'],
            'invoice-option' => 'NoInvoice',
            'protect-privacy' => ($params['idprotection'] ?? false) ? 'true' : 'false',
        ];

        // Add promotional code if set
        if (!empty($params['promotional_code'])) {
            $apiParams['promo-code'] = $params['promotional_code'];
        }

        $response = resellerclub_apiCall($params, 'domains/register.json', $apiParams);

        if (isset($response['status']) && $response['status'] === 'Success') {
            return ['success' => true];
        } elseif (isset($response['entityid'])) {
            return ['success' => true];
        } else {
            $error = $response['message'] ?? $response['error'] ?? 'Domain registration failed';
            return ['error' => $error];
        }

    } catch (\Exception $e) {
        logModuleCall('resellerclub', 'RegisterDomain', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Transfer a domain
 */
function resellerclub_TransferDomain($params)
{
    try {
        // Get or create customer
        $customerId = resellerclub_getCustomerId($params);

        if (!$customerId) {
            return ['error' => 'Failed to get or create customer'];
        }

        // Get contact IDs
        $contactIds = resellerclub_getContactIds($params, $customerId);

        if (!$contactIds) {
            return ['error' => 'Failed to create contacts'];
        }

        $apiParams = [
            'domain-name' => $params['domainname'],
            'auth-code' => $params['eppcode'] ?? $params['transfersecret'] ?? '',
            'customer-id' => $customerId,
            'reg-contact-id' => $contactIds['registrant'],
            'admin-contact-id' => $contactIds['admin'],
            'tech-contact-id' => $contactIds['tech'],
            'billing-contact-id' => $contactIds['billing'],
            'invoice-option' => 'NoInvoice',
        ];

        // Add promotional code if set
        if (!empty($params['promotional_code'])) {
            $apiParams['promo-code'] = $params['promotional_code'];
        }

        $response = resellerclub_apiCall($params, 'domains/transfer.json', $apiParams);

        if (isset($response['status']) && $response['status'] === 'Success') {
            return ['success' => true];
        } elseif (isset($response['entityid'])) {
            return ['success' => true];
        } else {
            $error = $response['message'] ?? $response['error'] ?? 'Domain transfer failed';
            return ['error' => $error];
        }

    } catch (\Exception $e) {
        logModuleCall('resellerclub', 'TransferDomain', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Renew a domain
 */
function resellerclub_RenewDomain($params)
{
    try {
        // Get domain order ID
        $orderId = resellerclub_getOrderId($params);

        if (!$orderId) {
            return ['error' => 'Failed to find domain order'];
        }

        $apiParams = [
            'order-id' => $orderId,
            'years' => $params['regperiod'],
            'exp-date' => time(),
            'invoice-option' => 'NoInvoice',
        ];

        // Add promotional code if set
        if (!empty($params['promotional_code'])) {
            $apiParams['promo-code'] = $params['promotional_code'];
        }

        $response = resellerclub_apiCall($params, 'domains/renew.json', $apiParams);

        if (isset($response['status']) && $response['status'] === 'Success') {
            return [
                'success' => true,
                'expirydate' => $response['endtime'] ?? null,
            ];
        } elseif (isset($response['entityid'])) {
            return ['success' => true];
        } else {
            $error = $response['message'] ?? $response['error'] ?? 'Domain renewal failed';
            return ['error' => $error];
        }

    } catch (\Exception $e) {
        logModuleCall('resellerclub', 'RenewDomain', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get nameservers for a domain
 */
function resellerclub_GetNameservers($params)
{
    try {
        $orderId = resellerclub_getOrderId($params);

        if (!$orderId) {
            return ['error' => 'Failed to find domain order'];
        }

        $apiParams = [
            'order-id' => $orderId,
            'options' => 'OrderDetails',
        ];

        $response = resellerclub_apiCall($params, 'domains/details.json', $apiParams, 'GET');

        if (isset($response['nameserver'])) {
            $nameservers = [];
            foreach ($response['nameserver'] as $index => $ns) {
                $nameservers['ns' . ($index + 1)] = $ns;
            }
            return $nameservers;
        } else {
            return ['error' => 'Failed to retrieve nameservers'];
        }

    } catch (\Exception $e) {
        logModuleCall('resellerclub', 'GetNameservers', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Save nameservers for a domain
 */
function resellerclub_SaveNameservers($params)
{
    try {
        $orderId = resellerclub_getOrderId($params);

        if (!$orderId) {
            return ['error' => 'Failed to find domain order'];
        }

        // Collect nameservers
        $nameservers = [];
        for ($i = 1; $i <= 5; $i++) {
            if (!empty($params["ns{$i}"])) {
                $nameservers[] = $params["ns{$i}"];
            }
        }

        $apiParams = [
            'order-id' => $orderId,
            'ns' => $nameservers,
        ];

        $response = resellerclub_apiCall($params, 'domains/modify-ns.json', $apiParams);

        if (isset($response['status']) && $response['status'] === 'Success') {
            return ['success' => true];
        } elseif (isset($response['entityid'])) {
            return ['success' => true];
        } else {
            $error = $response['message'] ?? $response['error'] ?? 'Failed to update nameservers';
            return ['error' => $error];
        }

    } catch (\Exception $e) {
        logModuleCall('resellerclub', 'SaveNameservers', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get registrar lock status
 */
function resellerclub_GetRegistrarLock($params)
{
    try {
        $orderId = resellerclub_getOrderId($params);

        if (!$orderId) {
            return ['error' => 'Failed to find domain order'];
        }

        $apiParams = [
            'order-id' => $orderId,
            'options' => 'OrderDetails',
        ];

        $response = resellerclub_apiCall($params, 'domains/details.json', $apiParams, 'GET');

        if (isset($response['orderstatus'])) {
            $locks = $response['orderstatus'] ?? [];
            $isLocked = in_array('transferlock', array_map('strtolower', $locks));

            return $isLocked ? 'locked' : 'unlocked';
        } else {
            return ['error' => 'Failed to get lock status'];
        }

    } catch (\Exception $e) {
        logModuleCall('resellerclub', 'GetRegistrarLock', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Save registrar lock
 */
function resellerclub_SaveRegistrarLock($params)
{
    try {
        $orderId = resellerclub_getOrderId($params);

        if (!$orderId) {
            return ['error' => 'Failed to find domain order'];
        }

        $lockStatus = $params['lockenabled'] === 'locked';

        $endpoint = $lockStatus
            ? 'domains/enable-theft-protection.json'
            : 'domains/disable-theft-protection.json';

        $apiParams = [
            'order-id' => $orderId,
        ];

        $response = resellerclub_apiCall($params, $endpoint, $apiParams);

        if (isset($response['status']) && $response['status'] === 'Success') {
            return ['success' => true];
        } elseif (isset($response['eaqid']) || is_array($response)) {
            return ['success' => true];
        } else {
            $error = $response['message'] ?? $response['error'] ?? 'Failed to update lock status';
            return ['error' => $error];
        }

    } catch (\Exception $e) {
        logModuleCall('resellerclub', 'SaveRegistrarLock', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get EPP code (auth code)
 */
function resellerclub_GetEPPCode($params)
{
    try {
        $orderId = resellerclub_getOrderId($params);

        if (!$orderId) {
            return ['error' => 'Failed to find domain order'];
        }

        $apiParams = [
            'order-id' => $orderId,
            'options' => 'OrderDetails',
        ];

        $response = resellerclub_apiCall($params, 'domains/details.json', $apiParams, 'GET');

        if (isset($response['domsecret'])) {
            return [
                'eppcode' => $response['domsecret'],
            ];
        } else {
            return ['error' => 'Failed to retrieve EPP code'];
        }

    } catch (\Exception $e) {
        logModuleCall('resellerclub', 'GetEPPCode', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get DNS records
 */
function resellerclub_GetDNS($params)
{
    try {
        $domainName = $params['domainname'];

        $apiParams = [
            'domain-name' => $domainName,
        ];

        $response = resellerclub_apiCall($params, 'dns/manage/search-records.json', $apiParams, 'GET');

        if (is_array($response)) {
            $dnsRecords = [];

            foreach ($response as $record) {
                if (is_array($record)) {
                    $dnsRecords[] = [
                        'hostname' => $record['host'] ?? '',
                        'type' => $record['type'] ?? '',
                        'address' => $record['value'] ?? '',
                        'priority' => $record['priority'] ?? '',
                    ];
                }
            }

            return ['records' => $dnsRecords];
        } else {
            return ['error' => 'Failed to retrieve DNS records'];
        }

    } catch (\Exception $e) {
        logModuleCall('resellerclub', 'GetDNS', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Save DNS records
 */
function resellerclub_SaveDNS($params)
{
    try {
        $domainName = $params['domainname'];

        // First, delete all existing records
        $existingRecords = resellerclub_GetDNS($params);

        if (isset($existingRecords['records'])) {
            foreach ($existingRecords['records'] as $record) {
                $deleteParams = [
                    'domain-name' => $domainName,
                    'host' => $record['hostname'],
                    'value' => $record['address'],
                ];
                resellerclub_apiCall($params, 'dns/manage/delete-record.json', $deleteParams);
            }
        }

        // Add new records
        if (isset($params['dnsrecords']) && is_array($params['dnsrecords'])) {
            foreach ($params['dnsrecords'] as $record) {
                $addParams = [
                    'domain-name' => $domainName,
                    'host' => $record['hostname'],
                    'type' => $record['type'],
                    'value' => $record['address'],
                    'ttl' => $record['ttl'] ?? '3600',
                ];

                if ($record['type'] === 'MX') {
                    $addParams['priority'] = $record['priority'] ?? '10';
                }

                resellerclub_apiCall($params, 'dns/manage/add-record.json', $addParams);
            }
        }

        return ['success' => true];

    } catch (\Exception $e) {
        logModuleCall('resellerclub', 'SaveDNS', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Register nameserver (child nameserver)
 */
function resellerclub_RegisterNameserver($params)
{
    try {
        $orderId = resellerclub_getOrderId($params);

        if (!$orderId) {
            return ['error' => 'Failed to find domain order'];
        }

        $apiParams = [
            'order-id' => $orderId,
            'cns' => $params['nameserver'],
            'ip' => [$params['ipaddress']],
        ];

        $response = resellerclub_apiCall($params, 'domains/add-cns.json', $apiParams);

        if (isset($response['status']) && $response['status'] === 'Success') {
            return ['success' => true];
        } elseif (is_array($response) && !isset($response['status'])) {
            return ['success' => true];
        } else {
            $error = $response['message'] ?? $response['error'] ?? 'Failed to register nameserver';
            return ['error' => $error];
        }

    } catch (\Exception $e) {
        logModuleCall('resellerclub', 'RegisterNameserver', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Modify nameserver
 */
function resellerclub_ModifyNameserver($params)
{
    try {
        $orderId = resellerclub_getOrderId($params);

        if (!$orderId) {
            return ['error' => 'Failed to find domain order'];
        }

        $apiParams = [
            'order-id' => $orderId,
            'cns' => $params['nameserver'],
            'old-ip' => $params['currentipaddress'],
            'new-ip' => $params['newipaddress'],
        ];

        $response = resellerclub_apiCall($params, 'domains/modify-cns-ip.json', $apiParams);

        if (isset($response['status']) && $response['status'] === 'Success') {
            return ['success' => true];
        } elseif (is_array($response) && !isset($response['status'])) {
            return ['success' => true];
        } else {
            $error = $response['message'] ?? $response['error'] ?? 'Failed to modify nameserver';
            return ['error' => $error];
        }

    } catch (\Exception $e) {
        logModuleCall('resellerclub', 'ModifyNameserver', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Delete nameserver
 */
function resellerclub_DeleteNameserver($params)
{
    try {
        $orderId = resellerclub_getOrderId($params);

        if (!$orderId) {
            return ['error' => 'Failed to find domain order'];
        }

        $apiParams = [
            'order-id' => $orderId,
            'cns' => $params['nameserver'],
        ];

        $response = resellerclub_apiCall($params, 'domains/delete-cns-ip.json', $apiParams);

        if (isset($response['status']) && $response['status'] === 'Success') {
            return ['success' => true];
        } elseif (is_array($response) && !isset($response['status'])) {
            return ['success' => true];
        } else {
            $error = $response['message'] ?? $response['error'] ?? 'Failed to delete nameserver';
            return ['error' => $error];
        }

    } catch (\Exception $e) {
        logModuleCall('resellerclub', 'DeleteNameserver', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get contact details (WHOIS info)
 */
function resellerclub_GetContactDetails($params)
{
    try {
        $orderId = resellerclub_getOrderId($params);

        if (!$orderId) {
            return ['error' => 'Failed to find domain order'];
        }

        $apiParams = [
            'order-id' => $orderId,
            'options' => 'All',
        ];

        $response = resellerclub_apiCall($params, 'domains/details.json', $apiParams, 'GET');

        if (isset($response['registrantcontact'])) {
            return [
                'Registrant' => resellerclub_extractContact($response['registrantcontact']),
                'Admin' => resellerclub_extractContact($response['admincontact'] ?? []),
                'Tech' => resellerclub_extractContact($response['techcontact'] ?? []),
                'Billing' => resellerclub_extractContact($response['billingcontact'] ?? []),
            ];
        } else {
            return ['error' => 'Failed to retrieve contact details'];
        }

    } catch (\Exception $e) {
        logModuleCall('resellerclub', 'GetContactDetails', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Save contact details (WHOIS info)
 */
function resellerclub_SaveContactDetails($params)
{
    try {
        $orderId = resellerclub_getOrderId($params);

        if (!$orderId) {
            return ['error' => 'Failed to find domain order'];
        }

        $customerId = resellerclub_getCustomerId($params);

        // Update contacts through the API
        $contactIds = resellerclub_getContactIds($params, $customerId);

        if (!$contactIds) {
            return ['error' => 'Failed to update contacts'];
        }

        $apiParams = [
            'order-id' => $orderId,
            'reg-contact-id' => $contactIds['registrant'],
            'admin-contact-id' => $contactIds['admin'],
            'tech-contact-id' => $contactIds['tech'],
            'billing-contact-id' => $contactIds['billing'],
        ];

        $response = resellerclub_apiCall($params, 'domains/modify-contact.json', $apiParams);

        if (isset($response['status']) && $response['status'] === 'Success') {
            return ['success' => true];
        } elseif (is_array($response) && !isset($response['status'])) {
            return ['success' => true];
        } else {
            $error = $response['message'] ?? $response['error'] ?? 'Failed to update contact details';
            return ['error' => $error];
        }

    } catch (\Exception $e) {
        logModuleCall('resellerclub', 'SaveContactDetails', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Sync domain status and expiry date
 */
function resellerclub_Sync($params)
{
    try {
        $orderId = resellerclub_getOrderId($params);

        if (!$orderId) {
            return ['error' => 'Failed to find domain order'];
        }

        $apiParams = [
            'order-id' => $orderId,
            'options' => 'OrderDetails',
        ];

        $response = resellerclub_apiCall($params, 'domains/details.json', $apiParams, 'GET');

        if (isset($response['endtime'])) {
            $expiryDate = date('Y-m-d', $response['endtime']);
            $status = strtolower($response['currentstatus'] ?? '');

            return [
                'expirydate' => $expiryDate,
                'active' => $status === 'active',
                'expired' => $status === 'expired',
                'transferredAway' => $status === 'transferredaway',
            ];
        } else {
            return ['error' => 'Failed to sync domain'];
        }

    } catch (\Exception $e) {
        logModuleCall('resellerclub', 'Sync', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Sync transfer status
 */
function resellerclub_TransferSync($params)
{
    try {
        $orderId = $params['transferid'] ?? resellerclub_getOrderId($params);

        if (!$orderId) {
            return ['completed' => false];
        }

        $apiParams = [
            'order-id' => $orderId,
            'options' => 'OrderDetails',
        ];

        $response = resellerclub_apiCall($params, 'domains/details.json', $apiParams, 'GET');

        if (isset($response['currentstatus'])) {
            $status = strtolower($response['currentstatus']);

            if ($status === 'active') {
                return [
                    'completed' => true,
                    'expirydate' => date('Y-m-d', $response['endtime'] ?? time()),
                ];
            } elseif ($status === 'failed' || $status === 'cancelled') {
                return [
                    'failed' => true,
                    'reason' => $response['actionstatusdesc'] ?? 'Transfer failed',
                ];
            } else {
                return ['completed' => false];
            }
        } else {
            return ['completed' => false];
        }

    } catch (\Exception $e) {
        logModuleCall('resellerclub', 'TransferSync', $params, $e->getMessage(), $e->getTraceAsString());
        return ['completed' => false];
    }
}

/**
 * Request domain delete
 */
function resellerclub_RequestDelete($params)
{
    try {
        $orderId = resellerclub_getOrderId($params);

        if (!$orderId) {
            return ['error' => 'Failed to find domain order'];
        }

        $apiParams = [
            'order-id' => $orderId,
        ];

        $response = resellerclub_apiCall($params, 'domains/delete.json', $apiParams);

        if (isset($response['status']) && $response['status'] === 'Success') {
            return ['success' => true];
        } elseif (is_array($response) && !isset($response['status'])) {
            return ['success' => true];
        } else {
            $error = $response['message'] ?? $response['error'] ?? 'Failed to request domain deletion';
            return ['error' => $error];
        }

    } catch (\Exception $e) {
        logModuleCall('resellerclub', 'RequestDelete', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Helper function to make ResellerClub API calls
 */
function resellerclub_apiCall($params, $endpoint, $apiParams = [], $method = 'POST')
{
    $resellerId = $params['reseller_id'];
    $apiKey = $params['api_key'];
    $testMode = $params['test_mode'];

    // Determine API URL
    $baseUrl = $testMode
        ? 'https://test.httpapi.com/api/'
        : 'https://httpapi.com/api/';

    $apiUrl = $baseUrl . $endpoint;

    // Add authentication
    $apiParams['auth-userid'] = $resellerId;
    $apiParams['api-key'] = $apiKey;

    // Make API call
    $ch = curl_init();

    if ($method === 'GET') {
        $queryString = http_build_query($apiParams);
        curl_setopt($ch, CURLOPT_URL, $apiUrl . '?' . $queryString);
    } else {
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($apiParams));
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new \Exception("API call failed: {$error}");
    }

    curl_close($ch);

    // Parse JSON response
    $data = json_decode($response, true);

    logModuleCall('resellerclub', $endpoint, $apiParams, $response, $data);

    return $data ?? [];
}

/**
 * Get customer ID (create if doesn't exist)
 */
function resellerclub_getCustomerId($params)
{
    static $customerId = null;

    if ($customerId !== null) {
        return $customerId;
    }

    // Try to search for existing customer
    $searchParams = [
        'username' => $params['email'],
    ];

    $response = resellerclub_apiCall($params, 'customers/search.json', $searchParams, 'GET');

    if (isset($response['1'])) {
        $customerId = $response['1']['customerid'];
        return $customerId;
    }

    // Create new customer
    $createParams = [
        'username' => $params['email'],
        'passwd' => substr(md5(time()), 0, 12),
        'name' => $params['fullname'] ?? ($params['firstname'] . ' ' . $params['lastname']),
        'company' => $params['companyname'] ?? 'N/A',
        'address-line-1' => $params['address1'],
        'address-line-2' => $params['address2'] ?? '',
        'city' => $params['city'],
        'state' => $params['state'],
        'country' => $params['country'],
        'zipcode' => $params['postcode'],
        'phone-cc' => resellerclub_getCountryCallingCode($params['country']),
        'phone' => resellerclub_formatPhone($params['phonenumber']),
        'lang-pref' => 'en',
    ];

    $response = resellerclub_apiCall($params, 'customers/signup.json', $createParams);

    if (isset($response['customerid'])) {
        $customerId = $response['customerid'];
        return $customerId;
    }

    return null;
}

/**
 * Get contact IDs for all contact types
 */
function resellerclub_getContactIds($params, $customerId)
{
    $contactTypes = ['registrant', 'admin', 'tech', 'billing'];
    $contactIds = [];

    foreach ($contactTypes as $type) {
        $contactParams = [
            'customer-id' => $customerId,
            'type' => 'Contact',
            'email' => $params['email'],
            'name' => $params['fullname'] ?? ($params['firstname'] . ' ' . $params['lastname']),
            'company' => $params['companyname'] ?? 'N/A',
            'address-line-1' => $params['address1'],
            'address-line-2' => $params['address2'] ?? '',
            'city' => $params['city'],
            'state' => $params['state'],
            'country' => $params['country'],
            'zipcode' => $params['postcode'],
            'phone-cc' => resellerclub_getCountryCallingCode($params['country']),
            'phone' => resellerclub_formatPhone($params['phonenumber']),
        ];

        $response = resellerclub_apiCall($params, 'contacts/add.json', $contactParams);

        if (isset($response['entityid'])) {
            $contactIds[$type] = $response['entityid'];
        } else {
            return null;
        }
    }

    return $contactIds;
}

/**
 * Get order ID for a domain
 */
function resellerclub_getOrderId($params)
{
    static $orderId = null;

    if ($orderId !== null) {
        return $orderId;
    }

    $searchParams = [
        'domain-name' => $params['domainname'],
    ];

    $response = resellerclub_apiCall($params, 'domains/orderid.json', $searchParams, 'GET');

    if (is_numeric($response)) {
        $orderId = $response;
        return $orderId;
    }

    return null;
}

/**
 * Format phone number for ResellerClub
 */
function resellerclub_formatPhone($phone)
{
    // Remove all non-numeric characters
    return preg_replace('/[^0-9]/', '', $phone);
}

/**
 * Get country calling code
 */
function resellerclub_getCountryCallingCode($countryCode)
{
    $codes = [
        'US' => '1', 'CA' => '1', 'GB' => '44', 'AU' => '61', 'DE' => '49',
        'FR' => '33', 'IT' => '39', 'ES' => '34', 'NL' => '31', 'BE' => '32',
        'CH' => '41', 'AT' => '43', 'SE' => '46', 'NO' => '47', 'DK' => '45',
        'FI' => '358', 'PL' => '48', 'RU' => '7', 'CN' => '86', 'JP' => '81',
        'IN' => '91', 'BR' => '55', 'MX' => '52', 'AR' => '54', 'ZA' => '27',
    ];

    return $codes[$countryCode] ?? '1';
}

/**
 * Extract contact details from API response
 */
function resellerclub_extractContact($contact)
{
    return [
        'First Name' => $contact['name'] ?? '',
        'Last Name' => '',
        'Company Name' => $contact['company'] ?? '',
        'Email' => $contact['emailaddr'] ?? '',
        'Address' => $contact['address1'] ?? '',
        'Address 2' => $contact['address2'] ?? '',
        'City' => $contact['city'] ?? '',
        'State' => $contact['state'] ?? '',
        'Postcode' => $contact['zip'] ?? '',
        'Country' => $contact['country'] ?? '',
        'Phone Number' => ($contact['telnocc'] ?? '') . $contact['telno'] ?? '',
    ];
}
