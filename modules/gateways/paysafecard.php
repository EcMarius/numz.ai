<?php
/**
 * Paysafecard Payment Gateway for NUMZ.AI
 * WHMCS Compatible
 */

function paysafecard_MetaData()
{
    return [
        'DisplayName' => 'Paysafecard',
        'APIVersion' => '1.1',
        'DisableLocalCreditCardInput' => true,
    ];
}

function paysafecard_config()
{
    return [
        'FriendlyName' => [
            'Type' => 'System',
            'Value' => 'Paysafecard',
        ],
        'apiKey' => [
            'FriendlyName' => 'API Key',
            'Type' => 'password',
            'Size' => '50',
            'Description' => 'Your Paysafecard API Key',
        ],
        'environment' => [
            'FriendlyName' => 'Environment',
            'Type' => 'dropdown',
            'Options' => [
                'test' => 'Test',
                'production' => 'Production',
            ],
        ],
    ];
}

function paysafecard_link($params)
{
    $apiUrl = $params['environment'] === 'production' 
        ? 'https://api.paysafecard.com/v1/payments'
        : 'https://apitest.paysafecard.com/v1/payments';

    $postData = [
        'type' => 'PAYSAFECARD',
        'amount' => $params['amount'],
        'currency' => $params['currency'],
        'redirect' => [
            'success_url' => $params['returnurl'],
            'failure_url' => $params['returnurl'],
        ],
        'notification_url' => $params['systemurl'] . '/modules/gateways/callback/paysafecard.php',
        'customer' => [
            'id' => $params['clientdetails']['id'],
            'email' => $params['clientdetails']['email'],
        ],
    ];

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode($params['apiKey'] . ':'),
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 201) {
        $data = json_decode($response, true);
        return '<a href="' . $data['redirect']['auth_url'] . '" class="btn btn-primary">Pay with Paysafecard</a>';
    }

    return 'Error initiating Paysafecard payment';
}
