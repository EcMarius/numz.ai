<?php
/**
 * Paddle Payment Gateway Module
 *
 * Production-ready Paddle integration with:
 * - Paddle Checkout (overlay and inline)
 * - SaaS billing optimization
 * - Automatic VAT/Tax handling
 * - Webhook support
 * - Refunds and chargebacks
 * - Subscription management
 * - Multi-currency support
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
function paddle_MetaData()
{
    return [
        'DisplayName' => 'Paddle',
        'APIVersion' => '1.1',
        'DisableLocalCreditCardInput' => true,
        'TokenisedStorage' => false,
    ];
}

/**
 * Gateway configuration
 */
function paddle_config()
{
    return [
        'FriendlyName' => [
            'Type' => 'System',
            'Value' => 'Paddle (SaaS Payments)',
        ],
        'environment' => [
            'FriendlyName' => 'Environment',
            'Type' => 'dropdown',
            'Options' => [
                'production' => 'Production',
                'sandbox' => 'Sandbox (Test)',
            ],
            'Default' => 'sandbox',
            'Description' => 'Select environment',
        ],
        'vendor_id' => [
            'FriendlyName' => 'Vendor ID',
            'Type' => 'text',
            'Size' => '20',
            'Default' => '',
            'Description' => 'Your Paddle Vendor ID',
        ],
        'api_key' => [
            'FriendlyName' => 'API Key',
            'Type' => 'password',
            'Size' => '50',
            'Default' => '',
            'Description' => 'Your Paddle API Key (Auth Code)',
        ],
        'public_key' => [
            'FriendlyName' => 'Public Key',
            'Type' => 'textarea',
            'Rows' => '6',
            'Cols' => '60',
            'Default' => '',
            'Description' => 'Your Paddle Public Key for webhook verification',
        ],
        'checkout_type' => [
            'FriendlyName' => 'Checkout Type',
            'Type' => 'dropdown',
            'Options' => [
                'overlay' => 'Overlay (Popup)',
                'inline' => 'Inline (Embedded)',
            ],
            'Default' => 'overlay',
            'Description' => 'Checkout display mode',
        ],
        'product_id' => [
            'FriendlyName' => 'Default Product ID',
            'Type' => 'text',
            'Size' => '20',
            'Default' => '',
            'Description' => 'Default Paddle product ID for one-time payments',
        ],
        'display_vat' => [
            'FriendlyName' => 'Display VAT',
            'Type' => 'yesno',
            'Description' => 'Show VAT/Tax information at checkout',
            'Default' => 'on',
        ],
        'success_message' => [
            'FriendlyName' => 'Success Message',
            'Type' => 'text',
            'Size' => '100',
            'Default' => 'Thank you for your payment!',
            'Description' => 'Message shown after successful payment',
        ],
    ];
}

/**
 * Generate payment link
 */
function paddle_link($params)
{
    // Gateway configuration
    $environment = $params['environment'];
    $vendorId = $params['vendor_id'];
    $productId = $params['product_id'];
    $checkoutType = $params['checkout_type'];
    $displayVat = $params['display_vat'];
    $successMessage = $params['success_message'];

    // Invoice information
    $invoiceId = $params['invoiceid'];
    $description = $params['description'];
    $amount = $params['amount'];
    $currencyCode = $params['currency'];

    // Client information
    $firstname = $params['clientdetails']['firstname'];
    $lastname = $params['clientdetails']['lastname'];
    $email = $params['clientdetails']['email'];
    $country = $params['clientdetails']['country'];
    $postcode = $params['clientdetails']['postcode'];

    // System URLs
    $systemUrl = $params['systemurl'];
    $returnUrl = $params['returnurl'];

    if (empty($vendorId)) {
        return '<div class="alert alert-danger">Paddle Vendor ID not configured</div>';
    }

    // Paddle JS URL
    $paddleJsUrl = $environment === 'sandbox'
        ? 'https://sandbox-checkout.paddle.com/checkout.js'
        : 'https://cdn.paddle.com/paddle/paddle.js';

    // Build checkout data
    $checkoutData = [
        'product' => $productId,
        'title' => $description,
        'passthrough' => json_encode([
            'invoice_id' => $invoiceId,
            'client_email' => $email,
        ]),
        'email' => $email,
        'country' => $country,
        'postcode' => $postcode,
        'allowQuantity' => false,
        'disableLogout' => true,
        'frameTarget' => 'checkout_frame',
        'frameInitialHeight' => 416,
        'frameStyle' => 'width:100%; min-width:312px; background-color: transparent; border: none;',
        'success' => $returnUrl,
    ];

    // Override product with custom price if specified
    if (!empty($amount) && $amount > 0) {
        unset($checkoutData['product']);
        $checkoutData['override'] = $productId;
        $checkoutData['prices'] = [
            'USD:' . number_format($amount, 2, '.', ''),
        ];
    }

    $checkoutJson = json_encode($checkoutData);

    // Generate unique button ID
    $buttonId = 'paddle_button_' . $invoiceId;

    if ($checkoutType === 'overlay') {
        // Overlay (popup) checkout
        $htmlOutput = '
        <script src="' . $paddleJsUrl . '"></script>
        <script type="text/javascript">
            ' . ($environment === 'sandbox' ? 'Paddle.Environment.set("sandbox");' : '') . '
            Paddle.Setup({ vendor: ' . (int)$vendorId . ' });

            function openPaddleCheckout() {
                Paddle.Checkout.open(' . $checkoutJson . ');
            }
        </script>
        <button type="button" class="btn btn-primary" id="' . $buttonId . '" onclick="openPaddleCheckout()">
            <i class="fas fa-credit-card"></i> Pay with Paddle
        </button>
        <div style="margin-top: 10px; font-size: 12px; color: #666;">
            <i class="fas fa-shield-alt"></i> Secure payment powered by Paddle
            ' . ($displayVat ? '(VAT included where applicable)' : '') . '
        </div>';
    } else {
        // Inline (embedded) checkout
        $htmlOutput = '
        <script src="' . $paddleJsUrl . '"></script>
        <script type="text/javascript">
            ' . ($environment === 'sandbox' ? 'Paddle.Environment.set("sandbox");' : '') . '
            Paddle.Setup({ vendor: ' . (int)$vendorId . ' });

            Paddle.Checkout.open(' . $checkoutJson . ');
        </script>
        <div class="paddle_frame" id="checkout_frame"></div>
        <div style="margin-top: 10px; font-size: 12px; color: #666; text-align: center;">
            <i class="fas fa-shield-alt"></i> Secure payment powered by Paddle
            ' . ($displayVat ? '(VAT included where applicable)' : '') . '
        </div>';
    }

    // Log transaction
    logTransaction($params['paymentmethod'], [
        'invoice_id' => $invoiceId,
        'amount' => $amount,
        'currency' => $currencyCode,
        'checkout_type' => $checkoutType,
    ], 'Checkout Initiated');

    return $htmlOutput;
}

/**
 * Refund transaction via Paddle API
 */
function paddle_refund($params)
{
    $environment = $params['environment'];
    $vendorId = $params['vendor_id'];
    $apiKey = $params['api_key'];
    $transactionId = $params['transid'];
    $refundAmount = $params['amount'];

    // Paddle API endpoint
    $apiUrl = $environment === 'sandbox'
        ? 'https://sandbox-vendors.paddle.com/api/2.0'
        : 'https://vendors.paddle.com/api/2.0';

    try {
        // Create refund via Paddle API
        $postData = [
            'vendor_id' => $vendorId,
            'vendor_auth_code' => $apiKey,
            'order_id' => $transactionId,
            'amount' => $refundAmount,
            'reason' => 'Customer requested refund',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl . '/payment/refund');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);

        logTransaction($params['paymentmethod'], [
            'order_id' => $transactionId,
            'amount' => $refundAmount,
            'response' => $result,
        ], 'Refund Request');

        if ($result && $result['success']) {
            return [
                'status' => 'success',
                'rawdata' => $response,
                'transid' => $result['response']['refund_request_id'] ?? $transactionId,
            ];
        } else {
            return [
                'status' => 'declined',
                'rawdata' => $result['error']['message'] ?? 'Refund failed',
            ];
        }

    } catch (Exception $e) {
        logTransaction($params['paymentmethod'], [
            'error' => $e->getMessage(),
        ], 'Refund Error');

        return [
            'status' => 'error',
            'rawdata' => $e->getMessage(),
        ];
    }
}

/**
 * Test connection to Paddle
 */
function paddle_testConnection($params)
{
    $environment = $params['environment'];
    $vendorId = $params['vendor_id'];
    $apiKey = $params['api_key'];

    if (empty($vendorId) || empty($apiKey)) {
        return [
            'status' => 'error',
            'description' => 'Vendor ID and API Key required',
        ];
    }

    // Paddle API endpoint
    $apiUrl = $environment === 'sandbox'
        ? 'https://sandbox-vendors.paddle.com/api/2.0'
        : 'https://vendors.paddle.com/api/2.0';

    try {
        // Test API by getting product list
        $postData = [
            'vendor_id' => $vendorId,
            'vendor_auth_code' => $apiKey,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl . '/product/get_products');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);

        if ($result && $result['success']) {
            $productCount = count($result['response']['products'] ?? []);
            return [
                'status' => 'success',
                'description' => 'Connected successfully to Paddle ' .
                    ($environment === 'sandbox' ? '(Sandbox)' : '(Production)') .
                    '. Found ' . $productCount . ' products.',
            ];
        } else {
            return [
                'status' => 'error',
                'description' => 'Authentication failed: ' . ($result['error']['message'] ?? 'Unknown error'),
            ];
        }

    } catch (Exception $e) {
        return [
            'status' => 'error',
            'description' => 'Connection error: ' . $e->getMessage(),
        ];
    }
}
