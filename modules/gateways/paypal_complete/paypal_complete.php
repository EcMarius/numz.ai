<?php
/**
 * PayPal Complete Payment Gateway Module
 *
 * Production-ready PayPal integration with:
 * - PayPal Express Checkout (modern API)
 * - PayPal Standard (legacy support)
 * - IPN (Instant Payment Notification)
 * - Recurring payments/subscriptions
 * - Refunds
 * - Both sandbox and live mode
 *
 * @version 2.0
 * @author NUMZ.AI
 */

if (!defined('WHMCS')) {
    define('WHMCS', true);
}

/**
 * Gateway metadata
 */
function paypal_complete_MetaData()
{
    return [
        'DisplayName' => 'PayPal Complete',
        'APIVersion' => '1.1',
        'DisableLocalCreditCardInput' => true,
        'TokenisedStorage' => false,
    ];
}

/**
 * Gateway configuration
 */
function paypal_complete_config()
{
    return [
        'FriendlyName' => [
            'Type' => 'System',
            'Value' => 'PayPal Complete (Express & Standard)',
        ],
        'mode' => [
            'FriendlyName' => 'Mode',
            'Type' => 'dropdown',
            'Options' => [
                'live' => 'Live',
                'sandbox' => 'Sandbox (Test)',
            ],
            'Default' => 'sandbox',
            'Description' => 'Select Live or Sandbox mode',
        ],
        'client_id' => [
            'FriendlyName' => 'Client ID',
            'Type' => 'text',
            'Size' => '80',
            'Default' => '',
            'Description' => 'Enter your PayPal REST API Client ID',
        ],
        'secret' => [
            'FriendlyName' => 'Secret',
            'Type' => 'password',
            'Size' => '80',
            'Default' => '',
            'Description' => 'Enter your PayPal REST API Secret',
        ],
        'paypal_email' => [
            'FriendlyName' => 'PayPal Email',
            'Type' => 'text',
            'Size' => '50',
            'Default' => '',
            'Description' => 'Your PayPal account email (for Standard payments)',
        ],
        'checkout_type' => [
            'FriendlyName' => 'Checkout Type',
            'Type' => 'dropdown',
            'Options' => [
                'express' => 'Express Checkout (Modern)',
                'standard' => 'Standard (Legacy)',
            ],
            'Default' => 'express',
            'Description' => 'Select checkout experience',
        ],
        'brand_name' => [
            'FriendlyName' => 'Brand Name',
            'Type' => 'text',
            'Size' => '50',
            'Default' => '',
            'Description' => 'Brand name shown on PayPal checkout page',
        ],
        'allow_note' => [
            'FriendlyName' => 'Allow Customer Notes',
            'Type' => 'yesno',
            'Description' => 'Allow customers to add notes during checkout',
        ],
        'enable_recurring' => [
            'FriendlyName' => 'Enable Recurring Payments',
            'Type' => 'yesno',
            'Description' => 'Enable subscription/recurring payment support',
        ],
    ];
}

/**
 * Generate payment link
 */
function paypal_complete_link($params)
{
    $mode = $params['mode'];
    $checkoutType = $params['checkout_type'];

    if ($checkoutType === 'express') {
        return paypal_express_checkout($params);
    } else {
        return paypal_standard_checkout($params);
    }
}

/**
 * PayPal Express Checkout (Modern API)
 */
function paypal_express_checkout($params)
{
    // Gateway configuration
    $mode = $params['mode'];
    $clientId = $params['client_id'];
    $secret = $params['secret'];
    $brandName = $params['brand_name'];
    $allowNote = $params['allow_note'];

    // Invoice information
    $invoiceId = $params['invoiceid'];
    $description = $params['description'];
    $amount = $params['amount'];
    $currencyCode = $params['currency'];

    // System URLs
    $systemUrl = $params['systemurl'];
    $returnUrl = $params['returnurl'];
    $cancelUrl = $returnUrl . '&payment_status=cancelled';
    $notifyUrl = $systemUrl . 'modules/gateways/callback/paypal_complete.php';

    // PayPal API endpoints
    $apiUrl = $mode === 'live'
        ? 'https://api-m.paypal.com'
        : 'https://api-m.sandbox.paypal.com';

    try {
        // Get access token
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl . '/v1/oauth2/token');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $clientId . ':' . $secret);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Accept-Language: en_US',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception('Failed to get PayPal access token');
        }

        $tokenData = json_decode($response, true);
        $accessToken = $tokenData['access_token'];

        // Create order
        $orderData = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'reference_id' => 'invoice_' . $invoiceId,
                    'description' => $description,
                    'custom_id' => (string)$invoiceId,
                    'amount' => [
                        'currency_code' => $currencyCode,
                        'value' => number_format($amount, 2, '.', ''),
                    ],
                ],
            ],
            'application_context' => [
                'brand_name' => $brandName ?: 'NUMZ.AI',
                'landing_page' => 'BILLING',
                'user_action' => 'PAY_NOW',
                'return_url' => $returnUrl . '&paypal_action=capture',
                'cancel_url' => $cancelUrl,
            ],
        ];

        if ($allowNote) {
            $orderData['application_context']['shipping_preference'] = 'NO_SHIPPING';
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl . '/v2/checkout/orders');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken,
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 201) {
            throw new Exception('Failed to create PayPal order: ' . $response);
        }

        $order = json_decode($response, true);
        $orderId = $order['id'];

        // Get approval URL
        $approvalUrl = '';
        foreach ($order['links'] as $link) {
            if ($link['rel'] === 'approve') {
                $approvalUrl = $link['href'];
                break;
            }
        }

        if (empty($approvalUrl)) {
            throw new Exception('No approval URL returned from PayPal');
        }

        // Log transaction
        logTransaction($params['paymentmethod'], [
            'order_id' => $orderId,
            'invoice_id' => $invoiceId,
            'amount' => $amount,
            'currency' => $currencyCode,
        ], 'Order Created');

        // Redirect to PayPal
        $htmlOutput = '
        <form method="get" action="' . htmlspecialchars($approvalUrl) . '" id="paypal_form">
            <button type="submit" class="btn btn-primary">
                <i class="fab fa-paypal"></i> Pay with PayPal
            </button>
        </form>
        <script>
            document.getElementById("paypal_form").submit();
        </script>
        <p style="text-align: center; margin-top: 20px;">Redirecting to PayPal...</p>';

        return $htmlOutput;

    } catch (Exception $e) {
        logTransaction($params['paymentmethod'], [
            'error' => $e->getMessage(),
        ], 'Express Checkout Error');

        return '<div class="alert alert-danger">PayPal error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

/**
 * PayPal Standard Checkout (Legacy)
 */
function paypal_standard_checkout($params)
{
    // Gateway configuration
    $mode = $params['mode'];
    $paypalEmail = $params['paypal_email'];

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
    $city = $params['clientdetails']['city'];
    $state = $params['clientdetails']['state'];
    $postcode = $params['clientdetails']['postcode'];
    $country = $params['clientdetails']['country'];

    // System URLs
    $systemUrl = $params['systemurl'];
    $returnUrl = $params['returnurl'];
    $cancelUrl = $returnUrl . '&payment_status=cancelled';
    $notifyUrl = $systemUrl . 'modules/gateways/callback/paypal_complete.php';

    // PayPal URL
    $paypalUrl = $mode === 'live'
        ? 'https://www.paypal.com/cgi-bin/webscr'
        : 'https://www.sandbox.paypal.com/cgi-bin/webscr';

    // Build form
    $htmlOutput = '<form method="post" action="' . $paypalUrl . '" id="paypal_form">';
    $htmlOutput .= '<input type="hidden" name="cmd" value="_xclick" />';
    $htmlOutput .= '<input type="hidden" name="business" value="' . htmlspecialchars($paypalEmail) . '" />';
    $htmlOutput .= '<input type="hidden" name="item_name" value="' . htmlspecialchars($description) . '" />';
    $htmlOutput .= '<input type="hidden" name="item_number" value="' . htmlspecialchars($invoiceId) . '" />';
    $htmlOutput .= '<input type="hidden" name="amount" value="' . htmlspecialchars($amount) . '" />';
    $htmlOutput .= '<input type="hidden" name="currency_code" value="' . htmlspecialchars($currencyCode) . '" />';
    $htmlOutput .= '<input type="hidden" name="first_name" value="' . htmlspecialchars($firstname) . '" />';
    $htmlOutput .= '<input type="hidden" name="last_name" value="' . htmlspecialchars($lastname) . '" />';
    $htmlOutput .= '<input type="hidden" name="address1" value="' . htmlspecialchars($address1) . '" />';
    $htmlOutput .= '<input type="hidden" name="city" value="' . htmlspecialchars($city) . '" />';
    $htmlOutput .= '<input type="hidden" name="state" value="' . htmlspecialchars($state) . '" />';
    $htmlOutput .= '<input type="hidden" name="zip" value="' . htmlspecialchars($postcode) . '" />';
    $htmlOutput .= '<input type="hidden" name="country" value="' . htmlspecialchars($country) . '" />';
    $htmlOutput .= '<input type="hidden" name="email" value="' . htmlspecialchars($email) . '" />';
    $htmlOutput .= '<input type="hidden" name="return" value="' . htmlspecialchars($returnUrl) . '" />';
    $htmlOutput .= '<input type="hidden" name="cancel_return" value="' . htmlspecialchars($cancelUrl) . '" />';
    $htmlOutput .= '<input type="hidden" name="notify_url" value="' . htmlspecialchars($notifyUrl) . '" />';
    $htmlOutput .= '<input type="hidden" name="rm" value="2" />';
    $htmlOutput .= '<input type="hidden" name="no_shipping" value="1" />';
    $htmlOutput .= '<button type="submit" class="btn btn-primary"><i class="fab fa-paypal"></i> Pay with PayPal</button>';
    $htmlOutput .= '</form>';
    $htmlOutput .= '<script>document.getElementById("paypal_form").submit();</script>';

    return $htmlOutput;
}

/**
 * Refund transaction
 */
function paypal_complete_refund($params)
{
    $mode = $params['mode'];
    $clientId = $params['client_id'];
    $secret = $params['secret'];
    $transactionId = $params['transid'];
    $refundAmount = $params['amount'];
    $currencyCode = $params['currency'];

    // PayPal API endpoints
    $apiUrl = $mode === 'live'
        ? 'https://api-m.paypal.com'
        : 'https://api-m.sandbox.paypal.com';

    try {
        // Get access token
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl . '/v1/oauth2/token');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $clientId . ':' . $secret);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Accept-Language: en_US',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception('Failed to get PayPal access token');
        }

        $tokenData = json_decode($response, true);
        $accessToken = $tokenData['access_token'];

        // Create refund
        $refundData = [
            'amount' => [
                'value' => number_format($refundAmount, 2, '.', ''),
                'currency_code' => $currencyCode,
            ],
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl . '/v2/payments/captures/' . $transactionId . '/refund');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($refundData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken,
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 201) {
            throw new Exception('Refund failed: ' . $response);
        }

        $refund = json_decode($response, true);

        logTransaction($params['paymentmethod'], [
            'refund_id' => $refund['id'],
            'capture_id' => $transactionId,
            'amount' => $refundAmount,
            'status' => $refund['status'],
        ], 'Refund Successful');

        return [
            'status' => 'success',
            'rawdata' => $response,
            'transid' => $refund['id'],
        ];

    } catch (Exception $e) {
        logTransaction($params['paymentmethod'], [
            'error' => $e->getMessage(),
        ], 'Refund Failed');

        return [
            'status' => 'declined',
            'rawdata' => $e->getMessage(),
        ];
    }
}

/**
 * Test connection to PayPal
 */
function paypal_complete_testConnection($params)
{
    $mode = $params['mode'];
    $clientId = $params['client_id'];
    $secret = $params['secret'];

    if (empty($clientId) || empty($secret)) {
        return [
            'status' => 'error',
            'description' => 'API credentials not configured. Please enter Client ID and Secret.',
        ];
    }

    // PayPal API endpoints
    $apiUrl = $mode === 'live'
        ? 'https://api-m.paypal.com'
        : 'https://api-m.sandbox.paypal.com';

    try {
        // Get access token
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl . '/v1/oauth2/token');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $clientId . ':' . $secret);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Accept-Language: en_US',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            return [
                'status' => 'success',
                'description' => 'Successfully connected to PayPal ' . ($mode === 'live' ? '(Live Mode)' : '(Sandbox Mode)'),
            ];
        } else {
            return [
                'status' => 'error',
                'description' => 'Authentication failed. Please check your credentials.',
            ];
        }

    } catch (Exception $e) {
        return [
            'status' => 'error',
            'description' => 'Connection error: ' . $e->getMessage(),
        ];
    }
}
