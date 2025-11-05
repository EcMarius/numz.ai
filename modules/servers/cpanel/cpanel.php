<?php
/**
 * cPanel/WHM Server Module for NUMZ.AI
 * Compatible with WHMCS cPanel module format
 */

function cpanel_MetaData()
{
    return [
        'DisplayName' => 'cPanel/WHM',
        'APIVersion' => '1.1',
        'RequiresServer' => true,
    ];
}

function cpanel_ConfigOptions()
{
    return [
        'Package Name' => ['Type' => 'text', 'Size' => '25'],
        'Disk Space' => ['Type' => 'text', 'Size' => '10', 'Description' => 'MB'],
        'Bandwidth' => ['Type' => 'text', 'Size' => '10', 'Description' => 'MB'],
    ];
}

function cpanel_CreateAccount($params)
{
    $serverUrl = ($params['serversecure'] ? 'https://' : 'http://') 
               . $params['serverip'] . ':' . $params['serverport'];

    $result = cpanel_ApiCall($serverUrl, $params['serveraccesshash'], 'createacct', [
        'user' => $params['username'],
        'domain' => $params['domain'],
        'pass' => $params['password'],
        'contactemail' => $params['clientsdetails']['email'] ?? '',
    ]);

    return $result['result'] ?? false ? 'success' : 'Failed to create account';
}

function cpanel_SuspendAccount($params)
{
    $serverUrl = ($params['serversecure'] ? 'https://' : 'http://') 
               . $params['serverip'] . ':' . $params['serverport'];

    $result = cpanel_ApiCall($serverUrl, $params['serveraccesshash'], 'suspendacct', [
        'user' => $params['username'],
    ]);

    return $result['result'] ?? false ? 'success' : 'Failed';
}

function cpanel_TerminateAccount($params)
{
    $serverUrl = ($params['serversecure'] ? 'https://' : 'http://') 
               . $params['serverip'] . ':' . $params['serverport'];

    $result = cpanel_ApiCall($serverUrl, $params['serveraccesshash'], 'removeacct', [
        'user' => $params['username'],
    ]);

    return $result['result'] ?? false ? 'success' : 'Failed';
}

function cpanel_ApiCall($serverUrl, $accessHash, $function, $params = [])
{
    $url = "{$serverUrl}/json-api/{$function}?" . http_build_query($params);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: WHM root:{$accessHash}"]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true) ?? [];
}
