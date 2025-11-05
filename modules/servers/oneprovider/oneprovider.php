<?php
/**
 * OneProvider Server Module for NUMZ.AI
 * WHMCS Compatible - VPS/Cloud Server Provider
 */

function oneprovider_MetaData()
{
    return [
        'DisplayName' => 'OneProvider',
        'APIVersion' => '1.1',
        'RequiresServer' => true,
    ];
}

function oneprovider_ConfigOptions()
{
    return [
        'Package ID' => [
            'Type' => 'text',
            'Size' => '25',
            'Description' => 'OneProvider Package ID',
        ],
        'Operating System' => [
            'Type' => 'dropdown',
            'Options' => [
                'centos7' => 'CentOS 7',
                'centos8' => 'CentOS 8',
                'ubuntu20' => 'Ubuntu 20.04',
                'ubuntu22' => 'Ubuntu 22.04',
                'debian10' => 'Debian 10',
                'debian11' => 'Debian 11',
            ],
        ],
        'RAM (GB)' => [
            'Type' => 'dropdown',
            'Options' => [
                '1' => '1 GB',
                '2' => '2 GB',
                '4' => '4 GB',
                '8' => '8 GB',
                '16' => '16 GB',
            ],
        ],
        'Storage (GB)' => [
            'Type' => 'text',
            'Size' => '10',
            'Default' => '50',
        ],
    ];
}

function oneprovider_CreateAccount($params)
{
    $apiUrl = 'https://api.oneprovider.com/v1/servers';
    
    $postData = [
        'package_id' => $params['configoption1'],
        'hostname' => $params['domain'],
        'os' => $params['configoption2'],
        'ram' => $params['configoption3'],
        'storage' => $params['configoption4'],
        'location' => $params['serverlocation'] ?? 'us-east',
    ];

    $response = oneprovider_apiCall($params['serveraccesshash'], 'POST', $apiUrl, $postData);

    if ($response['status'] === 'success') {
        return [
            'success' => true,
            'dedicatedip' => $response['server']['ip_address'],
        ];
    }

    return ['error' => $response['message'] ?? 'Failed to create server'];
}

function oneprovider_SuspendAccount($params)
{
    $apiUrl = "https://api.oneprovider.com/v1/servers/{$params['serviceid']}/suspend";
    
    $response = oneprovider_apiCall($params['serveraccesshash'], 'POST', $apiUrl);

    return $response['status'] === 'success' ? 'success' : $response['message'];
}

function oneprovider_UnsuspendAccount($params)
{
    $apiUrl = "https://api.oneprovider.com/v1/servers/{$params['serviceid']}/unsuspend";
    
    $response = oneprovider_apiCall($params['serveraccesshash'], 'POST', $apiUrl);

    return $response['status'] === 'success' ? 'success' : $response['message'];
}

function oneprovider_TerminateAccount($params)
{
    $apiUrl = "https://api.oneprovider.com/v1/servers/{$params['serviceid']}";
    
    $response = oneprovider_apiCall($params['serveraccesshash'], 'DELETE', $apiUrl);

    return $response['status'] === 'success' ? 'success' : $response['message'];
}

function oneprovider_ClientArea($params)
{
    $apiUrl = "https://api.oneprovider.com/v1/servers/{$params['serviceid']}";
    
    $response = oneprovider_apiCall($params['serveraccesshash'], 'GET', $apiUrl);

    if ($response['status'] === 'success') {
        return [
            'templatefile' => 'clientarea',
            'vars' => [
                'server_status' => $response['server']['status'],
                'ip_address' => $response['server']['ip_address'],
                'hostname' => $response['server']['hostname'],
                'os' => $response['server']['os'],
                'ram' => $response['server']['ram'],
                'storage' => $response['server']['storage'],
            ],
        ];
    }

    return [];
}

function oneprovider_AdminServicesTabFields($params)
{
    $apiUrl = "https://api.oneprovider.com/v1/servers/{$params['serviceid']}";
    
    $response = oneprovider_apiCall($params['serveraccesshash'], 'GET', $apiUrl);

    if ($response['status'] === 'success') {
        return [
            'Server ID' => $response['server']['id'],
            'IP Address' => $response['server']['ip_address'],
            'Status' => $response['server']['status'],
            'RAM' => $response['server']['ram'] . ' GB',
            'Storage' => $response['server']['storage'] . ' GB',
        ];
    }

    return [];
}

function oneprovider_apiCall($apiKey, $method, $url, $data = [])
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        "Authorization: Bearer {$apiKey}",
    ]);

    if (!empty($data)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true) ?? [];
}
