<?php
/**
 * DomainNameAPI Registrar Module for NUMZ.AI
 * WHMCS Compatible
 */

function domainnameapi_MetaData()
{
    return [
        'DisplayName' => 'DomainNameAPI',
        'APIVersion' => '1.1',
    ];
}

function domainnameapi_getConfigArray()
{
    return [
        'Username' => [
            'Type' => 'text',
            'Size' => '25',
            'Description' => 'Enter your DomainNameAPI username',
        ],
        'Password' => [
            'Type' => 'password',
            'Size' => '25',
            'Description' => 'Enter your DomainNameAPI password',
        ],
        'TestMode' => [
            'Type' => 'yesno',
            'Description' => 'Use test environment',
        ],
    ];
}

function domainnameapi_RegisterDomain($params)
{
    $apiUrl = $params['TestMode'] ? 'https://api-ote.domainnameapi.com' : 'https://api.domainnameapi.com';
    
    $postData = [
        'DomainName' => $params['sld'] . '.' . $params['tld'],
        'Period' => $params['regperiod'],
        'Nameservers' => [
            $params['ns1'],
            $params['ns2'],
            $params['ns3'] ?? null,
            $params['ns4'] ?? null,
        ],
        'Contacts' => [
            'Registrant' => [
                'FirstName' => $params['firstname'],
                'LastName' => $params['lastname'],
                'Email' => $params['email'],
                'Address' => $params['address1'],
                'City' => $params['city'],
                'State' => $params['state'],
                'PostalCode' => $params['postcode'],
                'Country' => $params['country'],
                'Phone' => $params['phonenumber'],
            ],
        ],
    ];

    $response = domainnameapi_apiCall($apiUrl, $params['Username'], $params['Password'], 'RegisterDomain', $postData);

    if ($response['Success']) {
        return ['success' => true];
    }

    return ['error' => $response['ErrorMessage'] ?? 'Registration failed'];
}

function domainnameapi_TransferDomain($params)
{
    $apiUrl = $params['TestMode'] ? 'https://api-ote.domainnameapi.com' : 'https://api.domainnameapi.com';
    
    $postData = [
        'DomainName' => $params['sld'] . '.' . $params['tld'],
        'AuthCode' => $params['eppcode'],
        'Period' => $params['regperiod'],
    ];

    $response = domainnameapi_apiCall($apiUrl, $params['Username'], $params['Password'], 'TransferDomain', $postData);

    return $response['Success'] ? ['success' => true] : ['error' => $response['ErrorMessage'] ?? 'Transfer failed'];
}

function domainnameapi_RenewDomain($params)
{
    $apiUrl = $params['TestMode'] ? 'https://api-ote.domainnameapi.com' : 'https://api.domainnameapi.com';
    
    $postData = [
        'DomainName' => $params['sld'] . '.' . $params['tld'],
        'Period' => $params['regperiod'],
    ];

    $response = domainnameapi_apiCall($apiUrl, $params['Username'], $params['Password'], 'RenewDomain', $postData);

    return $response['Success'] ? ['success' => true] : ['error' => $response['ErrorMessage'] ?? 'Renewal failed'];
}

function domainnameapi_GetNameservers($params)
{
    $apiUrl = $params['TestMode'] ? 'https://api-ote.domainnameapi.com' : 'https://api.domainnameapi.com';
    
    $response = domainnameapi_apiCall($apiUrl, $params['Username'], $params['Password'], 'GetDomainInfo', [
        'DomainName' => $params['sld'] . '.' . $params['tld'],
    ]);

    if ($response['Success'] && !empty($response['Nameservers'])) {
        return $response['Nameservers'];
    }

    return ['error' => 'Failed to get nameservers'];
}

function domainnameapi_SaveNameservers($params)
{
    $apiUrl = $params['TestMode'] ? 'https://api-ote.domainnameapi.com' : 'https://api.domainnameapi.com';
    
    $nameservers = array_filter([$params['ns1'], $params['ns2'], $params['ns3'], $params['ns4']]);
    
    $response = domainnameapi_apiCall($apiUrl, $params['Username'], $params['Password'], 'ModifyNameservers', [
        'DomainName' => $params['sld'] . '.' . $params['tld'],
        'Nameservers' => $nameservers,
    ]);

    return $response['Success'] ? ['success' => true] : ['error' => $response['ErrorMessage'] ?? 'Failed to save nameservers'];
}

function domainnameapi_apiCall($apiUrl, $username, $password, $action, $data)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "{$apiUrl}/{$action}");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode("{$username}:{$password}"),
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true) ?? [];
}
