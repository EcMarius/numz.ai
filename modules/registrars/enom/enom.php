<?php
/**
 * Enom Domain Registrar Module
 *
 * Full integration with Enom API for domain registration,
 * transfer, renewal, and management operations
 */

if (!defined('WHMCS')) {
    define('WHMCS', true);
}

/**
 * Registrar module metadata
 */
function enom_MetaData()
{
    return [
        'DisplayName' => 'Enom',
        'APIVersion' => '1.1',
    ];
}

/**
 * Registrar configuration
 */
function enom_getConfigArray()
{
    return [
        'FriendlyName' => [
            'Type' => 'System',
            'Value' => 'Enom',
        ],
        'Description' => [
            'Type' => 'System',
            'Value' => 'Register and manage domains through Enom API',
        ],
        'username' => [
            'FriendlyName' => 'Reseller ID',
            'Type' => 'text',
            'Size' => '50',
            'Default' => '',
            'Description' => 'Enter your Enom reseller ID',
        ],
        'password' => [
            'FriendlyName' => 'Password',
            'Type' => 'password',
            'Size' => '50',
            'Default' => '',
            'Description' => 'Enter your Enom account password',
        ],
        'test_mode' => [
            'FriendlyName' => 'Test Mode',
            'Type' => 'yesno',
            'Description' => 'Enable test mode for development',
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
function enom_RegisterDomain($params)
{
    try {
        $domainParts = explode('.', $params['domainname'], 2);
        $sld = $domainParts[0];
        $tld = $domainParts[1];

        // Build nameservers
        $nameservers = [];
        for ($i = 1; $i <= 12; $i++) {
            if (!empty($params["ns{$i}"])) {
                $nameservers["NS{$i}"] = $params["ns{$i}"];
            }
        }

        // Build API parameters
        $apiParams = array_merge([
            'command' => 'Purchase',
            'SLD' => $sld,
            'TLD' => $tld,
            'NumYears' => $params['regperiod'],

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
            'RegistrantPhone' => enom_formatPhone($params['phonenumber']),
            'RegistrantEmailAddress' => $params['email'],

            // Admin contact
            'AdminFirstName' => $params['firstname'],
            'AdminLastName' => $params['lastname'],
            'AdminOrganizationName' => $params['companyname'] ?? '',
            'AdminAddress1' => $params['address1'],
            'AdminAddress2' => $params['address2'] ?? '',
            'AdminCity' => $params['city'],
            'AdminStateProvince' => $params['state'],
            'AdminPostalCode' => $params['postcode'],
            'AdminCountry' => $params['country'],
            'AdminPhone' => enom_formatPhone($params['phonenumber']),
            'AdminEmailAddress' => $params['email'],

            // Tech contact
            'TechFirstName' => $params['firstname'],
            'TechLastName' => $params['lastname'],
            'TechOrganizationName' => $params['companyname'] ?? '',
            'TechAddress1' => $params['address1'],
            'TechAddress2' => $params['address2'] ?? '',
            'TechCity' => $params['city'],
            'TechStateProvince' => $params['state'],
            'TechPostalCode' => $params['postcode'],
            'TechCountry' => $params['country'],
            'TechPhone' => enom_formatPhone($params['phonenumber']),
            'TechEmailAddress' => $params['email'],

            // Billing contact
            'AuxBillingFirstName' => $params['firstname'],
            'AuxBillingLastName' => $params['lastname'],
            'AuxBillingOrganizationName' => $params['companyname'] ?? '',
            'AuxBillingAddress1' => $params['address1'],
            'AuxBillingAddress2' => $params['address2'] ?? '',
            'AuxBillingCity' => $params['city'],
            'AuxBillingStateProvince' => $params['state'],
            'AuxBillingPostalCode' => $params['postcode'],
            'AuxBillingCountry' => $params['country'],
            'AuxBillingPhone' => enom_formatPhone($params['phonenumber']),
            'AuxBillingEmailAddress' => $params['email'],

            // WHOIS privacy
            'UseDNS' => 'default',
            'IgnoreNSFail' => 'yes',
        ], $nameservers);

        // Add ID protection if enabled
        if ($params['idprotection'] ?? false) {
            $apiParams['AddWPPS'] = 'yes';
        }

        // Add promotional code if set
        if (!empty($params['promotional_code'])) {
            $apiParams['PromoCode'] = $params['promotional_code'];
        }

        $response = enom_apiCall($params, $apiParams);

        if ($response['ErrCount'] == 0) {
            return ['success' => true];
        } else {
            return ['error' => $response['errors'][0] ?? 'Domain registration failed'];
        }

    } catch (\Exception $e) {
        logModuleCall('enom', 'RegisterDomain', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Transfer a domain
 */
function enom_TransferDomain($params)
{
    try {
        $domainParts = explode('.', $params['domainname'], 2);
        $sld = $domainParts[0];
        $tld = $domainParts[1];

        $apiParams = [
            'command' => 'TP_CreateOrder',
            'SLD' => $sld,
            'TLD' => $tld,
            'AuthInfo1' => $params['eppcode'] ?? $params['transfersecret'] ?? '',
            'OrderType' => 'Autoverification',
            'UseContacts' => '0',
            'NumYears' => $params['regperiod'] ?? 1,
        ];

        // Add promotional code if set
        if (!empty($params['promotional_code'])) {
            $apiParams['PromoCode'] = $params['promotional_code'];
        }

        $response = enom_apiCall($params, $apiParams);

        if ($response['ErrCount'] == 0) {
            return [
                'success' => true,
                'transferid' => $response['TransferOrderID'] ?? null,
            ];
        } else {
            return ['error' => $response['errors'][0] ?? 'Domain transfer failed'];
        }

    } catch (\Exception $e) {
        logModuleCall('enom', 'TransferDomain', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Renew a domain
 */
function enom_RenewDomain($params)
{
    try {
        $domainParts = explode('.', $params['domainname'], 2);
        $sld = $domainParts[0];
        $tld = $domainParts[1];

        $apiParams = [
            'command' => 'Extend',
            'SLD' => $sld,
            'TLD' => $tld,
            'NumYears' => $params['regperiod'],
        ];

        // Add promotional code if set
        if (!empty($params['promotional_code'])) {
            $apiParams['PromoCode'] = $params['promotional_code'];
        }

        $response = enom_apiCall($params, $apiParams);

        if ($response['ErrCount'] == 0) {
            return [
                'success' => true,
                'expirydate' => $response['ExpirationDate'] ?? null,
            ];
        } else {
            return ['error' => $response['errors'][0] ?? 'Domain renewal failed'];
        }

    } catch (\Exception $e) {
        logModuleCall('enom', 'RenewDomain', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get nameservers for a domain
 */
function enom_GetNameservers($params)
{
    try {
        $domainParts = explode('.', $params['domainname'], 2);
        $sld = $domainParts[0];
        $tld = $domainParts[1];

        $apiParams = [
            'command' => 'GetDNS',
            'SLD' => $sld,
            'TLD' => $tld,
        ];

        $response = enom_apiCall($params, $apiParams);

        if ($response['ErrCount'] == 0) {
            $nameservers = [];
            for ($i = 1; $i <= 12; $i++) {
                if (!empty($response["NS{$i}"])) {
                    $nameservers["ns{$i}"] = $response["NS{$i}"];
                }
            }
            return $nameservers;
        } else {
            return ['error' => $response['errors'][0] ?? 'Failed to retrieve nameservers'];
        }

    } catch (\Exception $e) {
        logModuleCall('enom', 'GetNameservers', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Save nameservers for a domain
 */
function enom_SaveNameservers($params)
{
    try {
        $domainParts = explode('.', $params['domainname'], 2);
        $sld = $domainParts[0];
        $tld = $domainParts[1];

        $apiParams = [
            'command' => 'ModifyNS',
            'SLD' => $sld,
            'TLD' => $tld,
        ];

        // Collect nameservers
        for ($i = 1; $i <= 12; $i++) {
            if (!empty($params["ns{$i}"])) {
                $apiParams["NS{$i}"] = $params["ns{$i}"];
            }
        }

        $response = enom_apiCall($params, $apiParams);

        if ($response['ErrCount'] == 0) {
            return ['success' => true];
        } else {
            return ['error' => $response['errors'][0] ?? 'Failed to update nameservers'];
        }

    } catch (\Exception $e) {
        logModuleCall('enom', 'SaveNameservers', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get registrar lock status
 */
function enom_GetRegistrarLock($params)
{
    try {
        $domainParts = explode('.', $params['domainname'], 2);
        $sld = $domainParts[0];
        $tld = $domainParts[1];

        $apiParams = [
            'command' => 'GetRegLock',
            'SLD' => $sld,
            'TLD' => $tld,
        ];

        $response = enom_apiCall($params, $apiParams);

        if ($response['ErrCount'] == 0) {
            $isLocked = ($response['reg-lock'] ?? '0') === '1';
            return $isLocked ? 'locked' : 'unlocked';
        } else {
            return ['error' => $response['errors'][0] ?? 'Failed to get lock status'];
        }

    } catch (\Exception $e) {
        logModuleCall('enom', 'GetRegistrarLock', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Save registrar lock
 */
function enom_SaveRegistrarLock($params)
{
    try {
        $domainParts = explode('.', $params['domainname'], 2);
        $sld = $domainParts[0];
        $tld = $domainParts[1];

        $lockStatus = $params['lockenabled'] === 'locked' ? '1' : '0';

        $apiParams = [
            'command' => 'SetRegLock',
            'SLD' => $sld,
            'TLD' => $tld,
            'UnlockRegistrar' => $lockStatus === '0' ? '1' : '0',
        ];

        $response = enom_apiCall($params, $apiParams);

        if ($response['ErrCount'] == 0) {
            return ['success' => true];
        } else {
            return ['error' => $response['errors'][0] ?? 'Failed to update lock status'];
        }

    } catch (\Exception $e) {
        logModuleCall('enom', 'SaveRegistrarLock', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get EPP code (auth code)
 */
function enom_GetEPPCode($params)
{
    try {
        $domainParts = explode('.', $params['domainname'], 2);
        $sld = $domainParts[0];
        $tld = $domainParts[1];

        $apiParams = [
            'command' => 'SynchAuthInfo',
            'SLD' => $sld,
            'TLD' => $tld,
            'EmailEPP' => 'False',
            'RunSynchAutoInfo' => 'True',
        ];

        $response = enom_apiCall($params, $apiParams);

        if ($response['ErrCount'] == 0 && !empty($response['AuthInfo'])) {
            return [
                'eppcode' => $response['AuthInfo'],
            ];
        } else {
            return ['error' => $response['errors'][0] ?? 'Failed to retrieve EPP code'];
        }

    } catch (\Exception $e) {
        logModuleCall('enom', 'GetEPPCode', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get DNS records
 */
function enom_GetDNS($params)
{
    try {
        $domainParts = explode('.', $params['domainname'], 2);
        $sld = $domainParts[0];
        $tld = $domainParts[1];

        $apiParams = [
            'command' => 'GetDNS',
            'SLD' => $sld,
            'TLD' => $tld,
        ];

        $response = enom_apiCall($params, $apiParams);

        if ($response['ErrCount'] == 0) {
            $dnsRecords = [];

            // Parse A records
            if (!empty($response['services']['host'])) {
                $hosts = is_array($response['services']['host']) ? $response['services']['host'] : [$response['services']['host']];
                foreach ($hosts as $host) {
                    if (is_array($host)) {
                        $dnsRecords[] = [
                            'hostname' => $host['name'] ?? '',
                            'type' => 'A',
                            'address' => $host['address'] ?? '',
                            'priority' => '',
                        ];
                    }
                }
            }

            // Parse MX records
            if (!empty($response['services']['mx'])) {
                $mxRecords = is_array($response['services']['mx']) ? $response['services']['mx'] : [$response['services']['mx']];
                foreach ($mxRecords as $mx) {
                    if (is_array($mx)) {
                        $dnsRecords[] = [
                            'hostname' => '@',
                            'type' => 'MX',
                            'address' => $mx['address'] ?? '',
                            'priority' => $mx['priority'] ?? '10',
                        ];
                    }
                }
            }

            return ['records' => $dnsRecords];
        } else {
            return ['error' => $response['errors'][0] ?? 'Failed to retrieve DNS records'];
        }

    } catch (\Exception $e) {
        logModuleCall('enom', 'GetDNS', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Save DNS records
 */
function enom_SaveDNS($params)
{
    try {
        $domainParts = explode('.', $params['domainname'], 2);
        $sld = $domainParts[0];
        $tld = $domainParts[1];

        // First, we need to set DNS to use custom nameservers
        $apiParams = [
            'command' => 'SetDNSHost',
            'SLD' => $sld,
            'TLD' => $tld,
        ];

        // Build DNS records from params
        if (isset($params['dnsrecords']) && is_array($params['dnsrecords'])) {
            $hostRecords = [];
            $mxRecords = [];

            foreach ($params['dnsrecords'] as $record) {
                if ($record['type'] === 'A') {
                    $hostRecords[] = [
                        'HostName' => $record['hostname'],
                        'Address' => $record['address'],
                        'RecordType' => 'A',
                    ];
                } elseif ($record['type'] === 'MX') {
                    $mxRecords[] = [
                        'HostName' => $record['hostname'],
                        'Address' => $record['address'],
                        'Pref' => $record['priority'] ?? '10',
                    ];
                }
            }

            // Add host records
            foreach ($hostRecords as $index => $host) {
                $apiParams["HostName" . ($index + 1)] = $host['HostName'];
                $apiParams["RecordType" . ($index + 1)] = $host['RecordType'];
                $apiParams["Address" . ($index + 1)] = $host['Address'];
            }

            // Add MX records
            foreach ($mxRecords as $index => $mx) {
                $apiParams["MXHostName" . ($index + 1)] = $mx['HostName'];
                $apiParams["MXAddress" . ($index + 1)] = $mx['Address'];
                $apiParams["MXPref" . ($index + 1)] = $mx['Pref'];
            }
        }

        $response = enom_apiCall($params, $apiParams);

        if ($response['ErrCount'] == 0) {
            return ['success' => true];
        } else {
            return ['error' => $response['errors'][0] ?? 'Failed to update DNS records'];
        }

    } catch (\Exception $e) {
        logModuleCall('enom', 'SaveDNS', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Register nameserver (child nameserver)
 */
function enom_RegisterNameserver($params)
{
    try {
        $domainParts = explode('.', $params['domainname'], 2);
        $sld = $domainParts[0];
        $tld = $domainParts[1];

        // Extract nameserver hostname
        $nsParts = explode('.', $params['nameserver']);
        $nsHostname = $nsParts[0];

        $apiParams = [
            'command' => 'RegisterNameServer',
            'SLD' => $sld,
            'TLD' => $tld,
            'NSName' => $nsHostname,
            'IP' => $params['ipaddress'],
        ];

        $response = enom_apiCall($params, $apiParams);

        if ($response['ErrCount'] == 0) {
            return ['success' => true];
        } else {
            return ['error' => $response['errors'][0] ?? 'Failed to register nameserver'];
        }

    } catch (\Exception $e) {
        logModuleCall('enom', 'RegisterNameserver', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Modify nameserver
 */
function enom_ModifyNameserver($params)
{
    try {
        $domainParts = explode('.', $params['domainname'], 2);
        $sld = $domainParts[0];
        $tld = $domainParts[1];

        // Extract nameserver hostname
        $nsParts = explode('.', $params['nameserver']);
        $nsHostname = $nsParts[0];

        $apiParams = [
            'command' => 'UpdateNameServer',
            'SLD' => $sld,
            'TLD' => $tld,
            'NSName' => $nsHostname,
            'OldIP' => $params['currentipaddress'],
            'NewIP' => $params['newipaddress'],
        ];

        $response = enom_apiCall($params, $apiParams);

        if ($response['ErrCount'] == 0) {
            return ['success' => true];
        } else {
            return ['error' => $response['errors'][0] ?? 'Failed to modify nameserver'];
        }

    } catch (\Exception $e) {
        logModuleCall('enom', 'ModifyNameserver', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Delete nameserver
 */
function enom_DeleteNameserver($params)
{
    try {
        $domainParts = explode('.', $params['domainname'], 2);
        $sld = $domainParts[0];
        $tld = $domainParts[1];

        // Extract nameserver hostname
        $nsParts = explode('.', $params['nameserver']);
        $nsHostname = $nsParts[0];

        $apiParams = [
            'command' => 'DeleteNameServer',
            'SLD' => $sld,
            'TLD' => $tld,
            'NSName' => $nsHostname,
        ];

        $response = enom_apiCall($params, $apiParams);

        if ($response['ErrCount'] == 0) {
            return ['success' => true];
        } else {
            return ['error' => $response['errors'][0] ?? 'Failed to delete nameserver'];
        }

    } catch (\Exception $e) {
        logModuleCall('enom', 'DeleteNameserver', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get contact details (WHOIS info)
 */
function enom_GetContactDetails($params)
{
    try {
        $domainParts = explode('.', $params['domainname'], 2);
        $sld = $domainParts[0];
        $tld = $domainParts[1];

        $apiParams = [
            'command' => 'GetContacts',
            'SLD' => $sld,
            'TLD' => $tld,
        ];

        $response = enom_apiCall($params, $apiParams);

        if ($response['ErrCount'] == 0) {
            return [
                'Registrant' => enom_extractContact($response, 'Registrant'),
                'Admin' => enom_extractContact($response, 'Admin'),
                'Tech' => enom_extractContact($response, 'Tech'),
                'Billing' => enom_extractContact($response, 'AuxBilling'),
            ];
        } else {
            return ['error' => $response['errors'][0] ?? 'Failed to retrieve contact details'];
        }

    } catch (\Exception $e) {
        logModuleCall('enom', 'GetContactDetails', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Save contact details (WHOIS info)
 */
function enom_SaveContactDetails($params)
{
    try {
        $domainParts = explode('.', $params['domainname'], 2);
        $sld = $domainParts[0];
        $tld = $domainParts[1];

        $apiParams = [
            'command' => 'Contacts',
            'SLD' => $sld,
            'TLD' => $tld,
        ];

        // Build contact parameters
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
                $apiParams["{$prefix}Phone"] = enom_formatPhone($contact['Phone Number'] ?? '');
                $apiParams["{$prefix}EmailAddress"] = $contact['Email'] ?? '';
            }
        }

        $response = enom_apiCall($params, $apiParams);

        if ($response['ErrCount'] == 0) {
            return ['success' => true];
        } else {
            return ['error' => $response['errors'][0] ?? 'Failed to update contact details'];
        }

    } catch (\Exception $e) {
        logModuleCall('enom', 'SaveContactDetails', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Sync domain status and expiry date
 */
function enom_Sync($params)
{
    try {
        $domainParts = explode('.', $params['domainname'], 2);
        $sld = $domainParts[0];
        $tld = $domainParts[1];

        $apiParams = [
            'command' => 'GetDomainInfo',
            'SLD' => $sld,
            'TLD' => $tld,
        ];

        $response = enom_apiCall($params, $apiParams);

        if ($response['ErrCount'] == 0) {
            $expiryDate = $response['GetDomainInfo']['expiration'] ?? null;
            $status = strtolower($response['GetDomainInfo']['status'] ?? '');

            return [
                'expirydate' => $expiryDate ? date('Y-m-d', strtotime($expiryDate)) : null,
                'active' => $status === 'active' || $status === 'ok',
                'expired' => $status === 'expired',
                'transferredAway' => false,
            ];
        } else {
            return ['error' => $response['errors'][0] ?? 'Failed to sync domain'];
        }

    } catch (\Exception $e) {
        logModuleCall('enom', 'Sync', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Sync transfer status
 */
function enom_TransferSync($params)
{
    try {
        $apiParams = [
            'command' => 'TP_GetOrderDetail',
            'OrderID' => $params['transferid'] ?? 0,
        ];

        $response = enom_apiCall($params, $apiParams);

        if ($response['ErrCount'] == 0 && isset($response['TransferOrderDetailInfo'])) {
            $status = strtolower($response['TransferOrderDetailInfo']['StatusType'] ?? '');

            if ($status === 'completed' || $status === 'success') {
                return [
                    'completed' => true,
                    'expirydate' => $response['TransferOrderDetailInfo']['ExpirationDate'] ?? null,
                ];
            } elseif ($status === 'failed' || $status === 'cancelled') {
                return [
                    'failed' => true,
                    'reason' => $response['TransferOrderDetailInfo']['StatusDesc'] ?? 'Transfer failed',
                ];
            } else {
                return ['completed' => false];
            }
        } else {
            return ['error' => $response['errors'][0] ?? 'Failed to check transfer status'];
        }

    } catch (\Exception $e) {
        logModuleCall('enom', 'TransferSync', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Request domain delete
 */
function enom_RequestDelete($params)
{
    try {
        $domainParts = explode('.', $params['domainname'], 2);
        $sld = $domainParts[0];
        $tld = $domainParts[1];

        $apiParams = [
            'command' => 'DeleteRegistration',
            'SLD' => $sld,
            'TLD' => $tld,
        ];

        $response = enom_apiCall($params, $apiParams);

        if ($response['ErrCount'] == 0) {
            return ['success' => true];
        } else {
            return ['error' => $response['errors'][0] ?? 'Failed to request domain deletion'];
        }

    } catch (\Exception $e) {
        logModuleCall('enom', 'RequestDelete', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Helper function to make Enom API calls
 */
function enom_apiCall($params, $apiParams)
{
    $username = $params['username'];
    $password = $params['password'];
    $testMode = $params['test_mode'];

    // Determine API endpoint
    $apiUrl = $testMode
        ? 'https://resellertest.enom.com/interface.asp'
        : 'https://reseller.enom.com/interface.asp';

    // Add authentication
    $apiParams['uid'] = $username;
    $apiParams['pw'] = $password;
    $apiParams['responsetype'] = 'xml';

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

    // Convert to array
    $json = json_encode($xml);
    $data = json_decode($json, true);

    logModuleCall('enom', $apiParams['command'] ?? 'unknown', $apiParams, $response, $data);

    // Extract errors
    $errors = [];
    if (isset($data['Err1'])) {
        $errors[] = $data['Err1'];
    }

    $data['errors'] = $errors;

    return $data;
}

/**
 * Format phone number for Enom
 */
function enom_formatPhone($phone)
{
    // Remove all non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);

    // Format as +CountryCode.Phone
    if (strlen($phone) >= 10) {
        return '+1.' . $phone;
    }

    return '+' . $phone;
}

/**
 * Extract contact details from API response
 */
function enom_extractContact($response, $type)
{
    return [
        'First Name' => $response["{$type}FirstName"] ?? '',
        'Last Name' => $response["{$type}LastName"] ?? '',
        'Company Name' => $response["{$type}OrganizationName"] ?? '',
        'Email' => $response["{$type}EmailAddress"] ?? '',
        'Address' => $response["{$type}Address1"] ?? '',
        'Address 2' => $response["{$type}Address2"] ?? '',
        'City' => $response["{$type}City"] ?? '',
        'State' => $response["{$type}StateProvince"] ?? '',
        'Postcode' => $response["{$type}PostalCode"] ?? '',
        'Country' => $response["{$type}Country"] ?? '',
        'Phone Number' => $response["{$type}Phone"] ?? '',
    ];
}
