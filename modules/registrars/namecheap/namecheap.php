<?php
/**
 * Namecheap Domain Registrar Module
 *
 * Full integration with Namecheap API for domain registration,
 * transfer, renewal, and management operations
 */

if (!defined('WHMCS')) {
    define('WHMCS', true);
}

/**
 * Registrar module metadata
 */
function namecheap_MetaData()
{
    return [
        'DisplayName' => 'Namecheap',
        'APIVersion' => '1.1',
    ];
}

/**
 * Registrar configuration
 */
function namecheap_getConfigArray()
{
    return [
        'FriendlyName' => [
            'Type' => 'System',
            'Value' => 'Namecheap',
        ],
        'Description' => [
            'Type' => 'System',
            'Value' => 'Register and manage domains through Namecheap API',
        ],
        'api_user' => [
            'FriendlyName' => 'API Username',
            'Type' => 'text',
            'Size' => '50',
            'Default' => '',
            'Description' => 'Enter your Namecheap API username',
        ],
        'api_key' => [
            'FriendlyName' => 'API Key',
            'Type' => 'password',
            'Size' => '50',
            'Default' => '',
            'Description' => 'Enter your Namecheap API key',
        ],
        'username' => [
            'FriendlyName' => 'Username',
            'Type' => 'text',
            'Size' => '50',
            'Default' => '',
            'Description' => 'Your Namecheap account username',
        ],
        'test_mode' => [
            'FriendlyName' => 'Test Mode (Sandbox)',
            'Type' => 'yesno',
            'Description' => 'Enable sandbox environment for testing',
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
function namecheap_RegisterDomain($params)
{
    try {
        $domainParts = explode('.', $params['domainname'], 2);
        $sld = $domainParts[0];
        $tld = $domainParts[1];

        // Build nameservers
        $nameservers = [];
        for ($i = 1; $i <= 5; $i++) {
            if (!empty($params["ns{$i}"])) {
                $nameservers["Nameserver{$i}"] = $params["ns{$i}"];
            }
        }

        // Build API parameters
        $apiParams = array_merge([
            'Command' => 'namecheap.domains.create',
            'DomainName' => $params['domainname'],
            'Years' => $params['regperiod'],

            // Registrant contact
            'RegistrantFirstName' => $params['firstname'],
            'RegistrantLastName' => $params['lastname'],
            'RegistrantOrganizationName' => $params['companyname'] ?? '',
            'RegistrantAddress1' => $params['address1'],
            'RegistrantAddress2' => $params['address2'] ?? '',
            'RegistrantCity' => $params['city'],
            'RegistrantStateProvince' => $params['state'],
            'RegistrantPostalCode' => $params['postcode'],
            'RegistrantCountry' => $params['country'],
            'RegistrantPhone' => formatPhoneNumber($params['phonenumber']),
            'RegistrantEmailAddress' => $params['email'],

            // Admin contact (same as registrant)
            'AdminFirstName' => $params['firstname'],
            'AdminLastName' => $params['lastname'],
            'AdminOrganizationName' => $params['companyname'] ?? '',
            'AdminAddress1' => $params['address1'],
            'AdminAddress2' => $params['address2'] ?? '',
            'AdminCity' => $params['city'],
            'AdminStateProvince' => $params['state'],
            'AdminPostalCode' => $params['postcode'],
            'AdminCountry' => $params['country'],
            'AdminPhone' => formatPhoneNumber($params['phonenumber']),
            'AdminEmailAddress' => $params['email'],

            // Tech contact (same as registrant)
            'TechFirstName' => $params['firstname'],
            'TechLastName' => $params['lastname'],
            'TechOrganizationName' => $params['companyname'] ?? '',
            'TechAddress1' => $params['address1'],
            'TechAddress2' => $params['address2'] ?? '',
            'TechCity' => $params['city'],
            'TechStateProvince' => $params['state'],
            'TechPostalCode' => $params['postcode'],
            'TechCountry' => $params['country'],
            'TechPhone' => formatPhoneNumber($params['phonenumber']),
            'TechEmailAddress' => $params['email'],

            // Billing contact (same as registrant)
            'AuxBillingFirstName' => $params['firstname'],
            'AuxBillingLastName' => $params['lastname'],
            'AuxBillingOrganizationName' => $params['companyname'] ?? '',
            'AuxBillingAddress1' => $params['address1'],
            'AuxBillingAddress2' => $params['address2'] ?? '',
            'AuxBillingCity' => $params['city'],
            'AuxBillingStateProvince' => $params['state'],
            'AuxBillingPostalCode' => $params['postcode'],
            'AuxBillingCountry' => $params['country'],
            'AuxBillingPhone' => formatPhoneNumber($params['phonenumber']),
            'AuxBillingEmailAddress' => $params['email'],

            // WHOIS privacy
            'AddFreeWhoisguard' => ($params['idprotection'] ?? false) ? 'yes' : 'no',
            'WGEnabled' => ($params['idprotection'] ?? false) ? 'yes' : 'no',
        ], $nameservers);

        // Add promotional code if set
        if (!empty($params['promotional_code'])) {
            $apiParams['PromotionCode'] = $params['promotional_code'];
        }

        $response = namecheap_apiCall($params, $apiParams);

        if ($response['status'] === 'OK') {
            return ['success' => true];
        } else {
            return ['error' => $response['error'] ?? 'Domain registration failed'];
        }

    } catch (\Exception $e) {
        logModuleCall('namecheap', 'RegisterDomain', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Transfer a domain
 */
function namecheap_TransferDomain($params)
{
    try {
        $domainParts = explode('.', $params['domainname'], 2);

        $apiParams = [
            'Command' => 'namecheap.domains.transfer.create',
            'DomainName' => $params['domainname'],
            'Years' => $params['regperiod'] ?? 1,
            'EPPCode' => $params['eppcode'] ?? $params['transfersecret'] ?? '',
        ];

        // Add promotional code if set
        if (!empty($params['promotional_code'])) {
            $apiParams['PromotionCode'] = $params['promotional_code'];
        }

        $response = namecheap_apiCall($params, $apiParams);

        if ($response['status'] === 'OK') {
            return ['success' => true];
        } else {
            return ['error' => $response['error'] ?? 'Domain transfer failed'];
        }

    } catch (\Exception $e) {
        logModuleCall('namecheap', 'TransferDomain', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Renew a domain
 */
function namecheap_RenewDomain($params)
{
    try {
        $apiParams = [
            'Command' => 'namecheap.domains.renew',
            'DomainName' => $params['domainname'],
            'Years' => $params['regperiod'],
        ];

        // Add promotional code if set
        if (!empty($params['promotional_code'])) {
            $apiParams['PromotionCode'] = $params['promotional_code'];
        }

        $response = namecheap_apiCall($params, $apiParams);

        if ($response['status'] === 'OK' && isset($response['data']['DomainRenewResult'])) {
            return [
                'success' => true,
                'expirydate' => $response['data']['DomainRenewResult']['@attributes']['DomainExpires'] ?? null,
            ];
        } else {
            return ['error' => $response['error'] ?? 'Domain renewal failed'];
        }

    } catch (\Exception $e) {
        logModuleCall('namecheap', 'RenewDomain', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get nameservers for a domain
 */
function namecheap_GetNameservers($params)
{
    try {
        $domainParts = explode('.', $params['domainname'], 2);
        $sld = $domainParts[0];
        $tld = $domainParts[1];

        $apiParams = [
            'Command' => 'namecheap.domains.dns.getList',
            'SLD' => $sld,
            'TLD' => $tld,
        ];

        $response = namecheap_apiCall($params, $apiParams);

        if ($response['status'] === 'OK' && isset($response['data']['DomainDNSGetListResult'])) {
            $nameservers = [];
            $nsList = $response['data']['DomainDNSGetListResult']['Nameserver'] ?? [];

            if (!empty($nsList)) {
                // Handle single nameserver
                if (isset($nsList['#text'])) {
                    $nameservers['ns1'] = $nsList['#text'];
                } else {
                    // Handle multiple nameservers
                    foreach ($nsList as $index => $ns) {
                        $nameservers['ns' . ($index + 1)] = is_array($ns) ? $ns['#text'] : $ns;
                    }
                }
            }

            return $nameservers;
        } else {
            return ['error' => $response['error'] ?? 'Failed to retrieve nameservers'];
        }

    } catch (\Exception $e) {
        logModuleCall('namecheap', 'GetNameservers', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Save nameservers for a domain
 */
function namecheap_SaveNameservers($params)
{
    try {
        $domainParts = explode('.', $params['domainname'], 2);
        $sld = $domainParts[0];
        $tld = $domainParts[1];

        $apiParams = [
            'Command' => 'namecheap.domains.dns.setCustom',
            'SLD' => $sld,
            'TLD' => $tld,
        ];

        // Collect nameservers
        $nameservers = [];
        for ($i = 1; $i <= 5; $i++) {
            if (!empty($params["ns{$i}"])) {
                $nameservers[] = $params["ns{$i}"];
            }
        }

        $apiParams['Nameservers'] = implode(',', $nameservers);

        $response = namecheap_apiCall($params, $apiParams);

        if ($response['status'] === 'OK') {
            return ['success' => true];
        } else {
            return ['error' => $response['error'] ?? 'Failed to update nameservers'];
        }

    } catch (\Exception $e) {
        logModuleCall('namecheap', 'SaveNameservers', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get registrar lock status
 */
function namecheap_GetRegistrarLock($params)
{
    try {
        $domainParts = explode('.', $params['domainname'], 2);
        $sld = $domainParts[0];
        $tld = $domainParts[1];

        $apiParams = [
            'Command' => 'namecheap.domains.getInfo',
            'DomainName' => $params['domainname'],
        ];

        $response = namecheap_apiCall($params, $apiParams);

        if ($response['status'] === 'OK' && isset($response['data']['DomainGetInfoResult'])) {
            $domainInfo = $response['data']['DomainGetInfoResult'];
            $isLocked = ($domainInfo['@attributes']['IsLocked'] ?? 'false') === 'true';

            return $isLocked ? 'locked' : 'unlocked';
        } else {
            return ['error' => $response['error'] ?? 'Failed to get lock status'];
        }

    } catch (\Exception $e) {
        logModuleCall('namecheap', 'GetRegistrarLock', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Save registrar lock
 */
function namecheap_SaveRegistrarLock($params)
{
    try {
        $domainParts = explode('.', $params['domainname'], 2);
        $sld = $domainParts[0];
        $tld = $domainParts[1];

        $lockStatus = $params['lockenabled'] === 'locked';

        $apiParams = [
            'Command' => 'namecheap.domains.setRegistrarLock',
            'DomainName' => $params['domainname'],
            'LockAction' => $lockStatus ? 'LOCK' : 'UNLOCK',
        ];

        $response = namecheap_apiCall($params, $apiParams);

        if ($response['status'] === 'OK') {
            return ['success' => true];
        } else {
            return ['error' => $response['error'] ?? 'Failed to update lock status'];
        }

    } catch (\Exception $e) {
        logModuleCall('namecheap', 'SaveRegistrarLock', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get EPP code (auth code)
 */
function namecheap_GetEPPCode($params)
{
    try {
        $apiParams = [
            'Command' => 'namecheap.domains.getInfo',
            'DomainName' => $params['domainname'],
        ];

        $response = namecheap_apiCall($params, $apiParams);

        if ($response['status'] === 'OK' && isset($response['data']['DomainGetInfoResult'])) {
            $domainInfo = $response['data']['DomainGetInfoResult'];

            return [
                'eppcode' => $domainInfo['Modificationrights']['@attributes']['EPPKey'] ?? 'Contact Namecheap support for EPP code',
            ];
        } else {
            return ['error' => $response['error'] ?? 'Failed to retrieve EPP code'];
        }

    } catch (\Exception $e) {
        logModuleCall('namecheap', 'GetEPPCode', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get DNS records
 */
function namecheap_GetDNS($params)
{
    try {
        $domainParts = explode('.', $params['domainname'], 2);
        $sld = $domainParts[0];
        $tld = $domainParts[1];

        $apiParams = [
            'Command' => 'namecheap.domains.dns.getHosts',
            'SLD' => $sld,
            'TLD' => $tld,
        ];

        $response = namecheap_apiCall($params, $apiParams);

        if ($response['status'] === 'OK' && isset($response['data']['DomainDNSGetHostsResult'])) {
            $hosts = $response['data']['DomainDNSGetHostsResult']['host'] ?? [];
            $dnsRecords = [];

            if (!empty($hosts)) {
                // Handle single record
                if (isset($hosts['@attributes'])) {
                    $hosts = [$hosts];
                }

                foreach ($hosts as $host) {
                    $attrs = $host['@attributes'] ?? $host;
                    $dnsRecords[] = [
                        'hostname' => $attrs['Name'],
                        'type' => $attrs['Type'],
                        'address' => $attrs['Address'],
                        'priority' => $attrs['MXPref'] ?? '',
                    ];
                }
            }

            return ['records' => $dnsRecords];
        } else {
            return ['error' => $response['error'] ?? 'Failed to retrieve DNS records'];
        }

    } catch (\Exception $e) {
        logModuleCall('namecheap', 'GetDNS', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Save DNS records
 */
function namecheap_SaveDNS($params)
{
    try {
        $domainParts = explode('.', $params['domainname'], 2);
        $sld = $domainParts[0];
        $tld = $domainParts[1];

        $apiParams = [
            'Command' => 'namecheap.domains.dns.setHosts',
            'SLD' => $sld,
            'TLD' => $tld,
        ];

        // Build DNS records from params
        if (isset($params['dnsrecords']) && is_array($params['dnsrecords'])) {
            foreach ($params['dnsrecords'] as $index => $record) {
                $i = $index + 1;
                $apiParams["HostName{$i}"] = $record['hostname'];
                $apiParams["RecordType{$i}"] = $record['type'];
                $apiParams["Address{$i}"] = $record['address'];
                $apiParams["MXPref{$i}"] = $record['priority'] ?? '10';
                $apiParams["TTL{$i}"] = $record['ttl'] ?? '1800';
            }
        }

        $response = namecheap_apiCall($params, $apiParams);

        if ($response['status'] === 'OK') {
            return ['success' => true];
        } else {
            return ['error' => $response['error'] ?? 'Failed to update DNS records'];
        }

    } catch (\Exception $e) {
        logModuleCall('namecheap', 'SaveDNS', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Register nameserver (child nameserver)
 */
function namecheap_RegisterNameserver($params)
{
    try {
        $domainParts = explode('.', $params['domainname'], 2);
        $sld = $domainParts[0];
        $tld = $domainParts[1];

        $apiParams = [
            'Command' => 'namecheap.domains.ns.create',
            'SLD' => $sld,
            'TLD' => $tld,
            'Nameserver' => $params['nameserver'],
            'IP' => $params['ipaddress'],
        ];

        $response = namecheap_apiCall($params, $apiParams);

        if ($response['status'] === 'OK') {
            return ['success' => true];
        } else {
            return ['error' => $response['error'] ?? 'Failed to register nameserver'];
        }

    } catch (\Exception $e) {
        logModuleCall('namecheap', 'RegisterNameserver', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Modify nameserver
 */
function namecheap_ModifyNameserver($params)
{
    try {
        $domainParts = explode('.', $params['domainname'], 2);
        $sld = $domainParts[0];
        $tld = $domainParts[1];

        $apiParams = [
            'Command' => 'namecheap.domains.ns.update',
            'SLD' => $sld,
            'TLD' => $tld,
            'Nameserver' => $params['nameserver'],
            'OldIP' => $params['currentipaddress'],
            'IP' => $params['newipaddress'],
        ];

        $response = namecheap_apiCall($params, $apiParams);

        if ($response['status'] === 'OK') {
            return ['success' => true];
        } else {
            return ['error' => $response['error'] ?? 'Failed to modify nameserver'];
        }

    } catch (\Exception $e) {
        logModuleCall('namecheap', 'ModifyNameserver', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Delete nameserver
 */
function namecheap_DeleteNameserver($params)
{
    try {
        $domainParts = explode('.', $params['domainname'], 2);
        $sld = $domainParts[0];
        $tld = $domainParts[1];

        $apiParams = [
            'Command' => 'namecheap.domains.ns.delete',
            'SLD' => $sld,
            'TLD' => $tld,
            'Nameserver' => $params['nameserver'],
        ];

        $response = namecheap_apiCall($params, $apiParams);

        if ($response['status'] === 'OK') {
            return ['success' => true];
        } else {
            return ['error' => $response['error'] ?? 'Failed to delete nameserver'];
        }

    } catch (\Exception $e) {
        logModuleCall('namecheap', 'DeleteNameserver', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get contact details (WHOIS info)
 */
function namecheap_GetContactDetails($params)
{
    try {
        $apiParams = [
            'Command' => 'namecheap.domains.getContacts',
            'DomainName' => $params['domainname'],
        ];

        $response = namecheap_apiCall($params, $apiParams);

        if ($response['status'] === 'OK' && isset($response['data']['DomainContactsResult'])) {
            $contacts = $response['data']['DomainContactsResult'];

            return [
                'Registrant' => extractContactDetails($contacts['Registrant'] ?? []),
                'Admin' => extractContactDetails($contacts['Admin'] ?? []),
                'Tech' => extractContactDetails($contacts['Tech'] ?? []),
                'Billing' => extractContactDetails($contacts['AuxBilling'] ?? []),
            ];
        } else {
            return ['error' => $response['error'] ?? 'Failed to retrieve contact details'];
        }

    } catch (\Exception $e) {
        logModuleCall('namecheap', 'GetContactDetails', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Save contact details (WHOIS info)
 */
function namecheap_SaveContactDetails($params)
{
    try {
        $apiParams = [
            'Command' => 'namecheap.domains.setContacts',
            'DomainName' => $params['domainname'],
        ];

        // Build contact parameters for each contact type
        foreach (['Registrant', 'Admin', 'Tech', 'AuxBilling'] as $contactType) {
            if (isset($params['contactdetails'][$contactType])) {
                $contact = $params['contactdetails'][$contactType];
                $prefix = $contactType;

                $apiParams["{$prefix}FirstName"] = $contact['First Name'] ?? '';
                $apiParams["{$prefix}LastName"] = $contact['Last Name'] ?? '';
                $apiParams["{$prefix}OrganizationName"] = $contact['Company Name'] ?? '';
                $apiParams["{$prefix}Address1"] = $contact['Address'] ?? '';
                $apiParams["{$prefix}Address2"] = $contact['Address 2'] ?? '';
                $apiParams["{$prefix}City"] = $contact['City'] ?? '';
                $apiParams["{$prefix}StateProvince"] = $contact['State'] ?? '';
                $apiParams["{$prefix}PostalCode"] = $contact['Postcode'] ?? '';
                $apiParams["{$prefix}Country"] = $contact['Country'] ?? '';
                $apiParams["{$prefix}Phone"] = formatPhoneNumber($contact['Phone Number'] ?? '');
                $apiParams["{$prefix}EmailAddress"] = $contact['Email'] ?? '';
            }
        }

        $response = namecheap_apiCall($params, $apiParams);

        if ($response['status'] === 'OK') {
            return ['success' => true];
        } else {
            return ['error' => $response['error'] ?? 'Failed to update contact details'];
        }

    } catch (\Exception $e) {
        logModuleCall('namecheap', 'SaveContactDetails', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Sync domain status and expiry date
 */
function namecheap_Sync($params)
{
    try {
        $apiParams = [
            'Command' => 'namecheap.domains.getInfo',
            'DomainName' => $params['domainname'],
        ];

        $response = namecheap_apiCall($params, $apiParams);

        if ($response['status'] === 'OK' && isset($response['data']['DomainGetInfoResult'])) {
            $domainInfo = $response['data']['DomainGetInfoResult']['@attributes'];

            $expiryDate = $domainInfo['Expires'] ?? null;
            $status = strtolower($domainInfo['Status'] ?? '');

            return [
                'expirydate' => $expiryDate ? date('Y-m-d', strtotime($expiryDate)) : null,
                'active' => $status === 'ok' || $status === 'active',
                'expired' => $status === 'expired',
                'transferredAway' => false,
            ];
        } else {
            return ['error' => $response['error'] ?? 'Failed to sync domain'];
        }

    } catch (\Exception $e) {
        logModuleCall('namecheap', 'Sync', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Sync transfer status
 */
function namecheap_TransferSync($params)
{
    try {
        $apiParams = [
            'Command' => 'namecheap.domains.transfer.getStatus',
            'TransferID' => $params['transferid'] ?? 0,
        ];

        $response = namecheap_apiCall($params, $apiParams);

        if ($response['status'] === 'OK' && isset($response['data']['DomainTransferGetStatusResult'])) {
            $transferInfo = $response['data']['DomainTransferGetStatusResult']['@attributes'];
            $status = strtolower($transferInfo['Status'] ?? '');

            if ($status === 'completed') {
                return [
                    'completed' => true,
                    'expirydate' => $transferInfo['ExpireDate'] ?? null,
                ];
            } elseif ($status === 'failed' || $status === 'cancelled') {
                return [
                    'failed' => true,
                    'reason' => $transferInfo['StatusDetail'] ?? 'Transfer failed',
                ];
            } else {
                return ['completed' => false];
            }
        } else {
            return ['error' => $response['error'] ?? 'Failed to check transfer status'];
        }

    } catch (\Exception $e) {
        logModuleCall('namecheap', 'TransferSync', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Request domain delete
 */
function namecheap_RequestDelete($params)
{
    try {
        // Namecheap doesn't support direct API deletion
        // Domains must be deleted through the control panel
        return [
            'error' => 'Domain deletion must be performed through the Namecheap control panel. API deletion is not supported.',
        ];

    } catch (\Exception $e) {
        logModuleCall('namecheap', 'RequestDelete', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Helper function to make Namecheap API calls
 */
function namecheap_apiCall($params, $apiParams)
{
    $apiUser = $params['api_user'];
    $apiKey = $params['api_key'];
    $userName = $params['username'];
    $testMode = $params['test_mode'];

    // Determine API endpoint
    $apiUrl = $testMode
        ? 'https://api.sandbox.namecheap.com/xml.response'
        : 'https://api.namecheap.com/xml.response';

    // Add common parameters
    $apiParams['ApiUser'] = $apiUser;
    $apiParams['ApiKey'] = $apiKey;
    $apiParams['UserName'] = $userName;
    $apiParams['ClientIp'] = $_SERVER['SERVER_ADDR'] ?? '127.0.0.1';

    // Build query string
    $queryString = http_build_query($apiParams);
    $fullUrl = $apiUrl . '?' . $queryString;

    // Make API call
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $fullUrl);
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

    // Parse XML response
    $xml = simplexml_load_string($response);

    if (!$xml) {
        throw new \Exception("Failed to parse API response");
    }

    // Convert to array for easier handling
    $json = json_encode($xml);
    $data = json_decode($json, true);

    logModuleCall('namecheap', $apiParams['Command'], $apiParams, $response, $data);

    // Check for errors
    if (isset($data['@attributes']['Status']) && $data['@attributes']['Status'] === 'ERROR') {
        $errors = $data['Errors']['Error'] ?? [];
        $errorMessage = is_array($errors)
            ? (isset($errors['#text']) ? $errors['#text'] : implode(', ', array_column($errors, '#text')))
            : 'Unknown error';

        return [
            'status' => 'ERROR',
            'error' => $errorMessage,
        ];
    }

    return [
        'status' => 'OK',
        'data' => $data['CommandResponse'] ?? [],
    ];
}

/**
 * Format phone number for Namecheap
 */
function formatPhoneNumber($phone)
{
    // Remove all non-numeric characters except + and .
    $phone = preg_replace('/[^0-9+.]/', '', $phone);

    // Ensure phone starts with +
    if (substr($phone, 0, 1) !== '+') {
        $phone = '+' . $phone;
    }

    return $phone;
}

/**
 * Extract contact details from API response
 */
function extractContactDetails($contact)
{
    $attrs = $contact['@attributes'] ?? $contact;

    return [
        'First Name' => $attrs['FirstName'] ?? '',
        'Last Name' => $attrs['LastName'] ?? '',
        'Company Name' => $attrs['OrganizationName'] ?? '',
        'Email' => $attrs['EmailAddress'] ?? '',
        'Address' => $attrs['Address1'] ?? '',
        'Address 2' => $attrs['Address2'] ?? '',
        'City' => $attrs['City'] ?? '',
        'State' => $attrs['StateProvince'] ?? '',
        'Postcode' => $attrs['PostalCode'] ?? '',
        'Country' => $attrs['Country'] ?? '',
        'Phone Number' => $attrs['Phone'] ?? '',
    ];
}
