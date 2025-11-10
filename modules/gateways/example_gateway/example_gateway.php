<?php
/**
 * Example Payment Gateway Module
 *
 * Demonstrates WHMCS payment gateway compatibility
 */

if (!defined('WHMCS')) {
    define('WHMCS', true);
}

/**
 * Gateway metadata
 */
function example_gateway_MetaData()
{
    return [
        'DisplayName' => 'Example Payment Gateway',
        'APIVersion' => '1.1',
        'DisableLocalCreditCardInput' => false,
        'TokenisedStorage' => false,
    ];
}

/**
 * Gateway configuration
 */
function example_gateway_config()
{
    return [
        'FriendlyName' => [
            'Type' => 'System',
            'Value' => 'Example Payment Gateway',
        ],
        'api_key' => [
            'FriendlyName' => 'API Key',
            'Type' => 'text',
            'Size' => '50',
            'Default' => '',
            'Description' => 'Enter your gateway API key',
        ],
        'api_secret' => [
            'FriendlyName' => 'API Secret',
            'Type' => 'password',
            'Size' => '50',
            'Default' => '',
            'Description' => 'Enter your gateway API secret',
        ],
        'test_mode' => [
            'FriendlyName' => 'Test Mode',
            'Type' => 'yesno',
            'Description' => 'Tick to enable test mode',
        ],
        'transaction_fee' => [
            'FriendlyName' => 'Transaction Fee',
            'Type' => 'text',
            'Size' => '5',
            'Default' => '0',
            'Description' => 'Transaction fee percentage (e.g., 2.9 for 2.9%)',
        ],
        'accepted_cards' => [
            'FriendlyName' => 'Accepted Cards',
            'Type' => 'dropdown',
            'Options' => [
                'all' => 'All Card Types',
                'visa_mc' => 'Visa & Mastercard Only',
                'amex' => 'American Express Only',
            ],
            'Default' => 'all',
            'Description' => 'Select which card types to accept',
        ],
    ];
}

/**
 * Generate payment link
 */
function example_gateway_link($params)
{
    // Gateway configuration
    $apiKey = $params['api_key'];
    $apiSecret = $params['api_secret'];
    $testMode = $params['test_mode'];

    // Invoice information
    $invoiceId = $params['invoiceid'];
    $description = $params['description'];
    $amount = $params['amount'];
    $currencyCode = $params['currency'];

    // Client information
    $firstname = $params['clientdetails']['firstname'];
    $lastname = $params['clientdetails']['lastname'];
    $email = $params['clientdetails']['email'];
    $address1 = $params['clientdetails']['address1'];
    $address2 = $params['clientdetails']['address2'];
    $city = $params['clientdetails']['city'];
    $state = $params['clientdetails']['state'];
    $postcode = $params['clientdetails']['postcode'];
    $country = $params['clientdetails']['country'];
    $phone = $params['clientdetails']['phonenumber'];

    // System URLs
    $systemUrl = $params['systemurl'];
    $returnUrl = $params['returnurl'];
    $langPayNow = $params['langpaynow'];
    $moduleDisplayName = $params['name'];
    $moduleName = $params['paymentmethod'];

    // Build the payment form
    $postfields = [
        'api_key' => $apiKey,
        'invoice_id' => $invoiceId,
        'amount' => $amount,
        'currency' => $currencyCode,
        'description' => $description,
        'first_name' => $firstname,
        'last_name' => $lastname,
        'email' => $email,
        'address1' => $address1,
        'city' => $city,
        'state' => $state,
        'postcode' => $postcode,
        'country' => $country,
        'phone' => $phone,
        'return_url' => $returnUrl,
        'callback_url' => $systemUrl . 'modules/gateways/callback/' . $moduleName . '.php',
        'test_mode' => $testMode ? '1' : '0',
    ];

    // Generate signature
    $signature = hash_hmac('sha256', json_encode($postfields), $apiSecret);
    $postfields['signature'] = $signature;

    // Build payment gateway URL
    $gatewayUrl = $testMode ? 'https://sandbox.example-gateway.com/pay' : 'https://secure.example-gateway.com/pay';

    // Build HTML form
    $htmlOutput = '<form method="post" action="' . $gatewayUrl . '">';
    foreach ($postfields as $key => $value) {
        $htmlOutput .= '<input type="hidden" name="' . $key . '" value="' . htmlspecialchars($value) . '" />';
    }
    $htmlOutput .= '<button type="submit" class="btn btn-primary">' . $langPayNow . '</button>';
    $htmlOutput .= '</form>';

    return $htmlOutput;
}

/**
 * Refund transaction
 */
function example_gateway_refund($params)
{
    // Gateway configuration
    $apiKey = $params['api_key'];
    $apiSecret = $params['api_secret'];
    $testMode = $params['test_mode'];

    // Transaction information
    $transactionIdToRefund = $params['transid'];
    $refundAmount = $params['amount'];
    $currencyCode = $params['currency'];

    // Build API endpoint
    $gatewayUrl = $testMode
        ? 'https://sandbox.example-gateway.com/api/refund'
        : 'https://secure.example-gateway.com/api/refund';

    // Prepare request data
    $postData = [
        'transaction_id' => $transactionIdToRefund,
        'amount' => $refundAmount,
        'currency' => $currencyCode,
    ];

    // Make API call
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $gatewayUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_USERPWD, $apiKey . ':' . $apiSecret);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    // Log transaction
    logTransaction($params['paymentmethod'], [
        'request' => $postData,
        'response' => $response,
        'error' => $error,
    ], 'Refund');

    if ($error) {
        return [
            'status' => 'error',
            'rawdata' => $error,
        ];
    }

    $result = json_decode($response, true);

    if ($result && $result['status'] === 'success') {
        return [
            'status' => 'success',
            'rawdata' => $response,
            'transid' => $result['refund_transaction_id'],
            'fees' => $result['fees'] ?? 0,
        ];
    } else {
        return [
            'status' => 'declined',
            'rawdata' => $response,
        ];
    }
}

/**
 * Capture authorized transaction
 */
function example_gateway_capture($params)
{
    // Gateway configuration
    $apiKey = $params['api_key'];
    $apiSecret = $params['api_secret'];
    $testMode = $params['test_mode'];

    // Transaction information
    $transactionIdToCapture = $params['transid'];
    $amount = $params['amount'];
    $currencyCode = $params['currency'];

    // Build API endpoint
    $gatewayUrl = $testMode
        ? 'https://sandbox.example-gateway.com/api/capture'
        : 'https://secure.example-gateway.com/api/capture';

    // Prepare request data
    $postData = [
        'transaction_id' => $transactionIdToCapture,
        'amount' => $amount,
        'currency' => $currencyCode,
    ];

    // Make API call
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $gatewayUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_USERPWD, $apiKey . ':' . $apiSecret);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    // Log transaction
    logTransaction($params['paymentmethod'], [
        'request' => $postData,
        'response' => $response,
        'error' => $error,
    ], 'Capture');

    if ($error) {
        return [
            'status' => 'error',
            'rawdata' => $error,
        ];
    }

    $result = json_decode($response, true);

    if ($result && $result['status'] === 'success') {
        return [
            'status' => 'success',
            'rawdata' => $response,
            'transid' => $result['transaction_id'],
            'fee' => $result['fees'] ?? 0,
        ];
    } else {
        return [
            'status' => 'error',
            'rawdata' => $response,
        ];
    }
}

/**
 * Test connection to gateway
 */
function example_gateway_testConnection($params)
{
    $apiKey = $params['api_key'];
    $apiSecret = $params['api_secret'];
    $testMode = $params['test_mode'];

    if (empty($apiKey) || empty($apiSecret)) {
        return [
            'status' => 'error',
            'description' => 'API credentials not configured',
        ];
    }

    // Build test API endpoint
    $gatewayUrl = $testMode
        ? 'https://sandbox.example-gateway.com/api/ping'
        : 'https://secure.example-gateway.com/api/ping';

    // Make API call
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $gatewayUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_USERPWD, $apiKey . ':' . $apiSecret);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($error) {
        return [
            'status' => 'error',
            'description' => 'Connection failed: ' . $error,
        ];
    }

    if ($httpCode === 200) {
        return [
            'status' => 'success',
            'description' => 'Connection successful' . ($testMode ? ' (Test Mode)' : ''),
        ];
    } else {
        return [
            'status' => 'error',
            'description' => 'Invalid credentials or gateway unreachable',
        ];
    }
}
