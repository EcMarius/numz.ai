<?php
/**
 * Razorpay Payment Gateway Module
 *
 * Production-ready Razorpay integration for Indian payments:
 * - UPI (PhonePe, Google Pay, Paytm, etc.)
 * - Credit/Debit Cards (Visa, Mastercard, RuPay, Amex)
 * - Net Banking (all major Indian banks)
 * - Wallets (Paytm, Mobikwik, FreeCharge, etc.)
 * - EMI options
 * - International cards
 * - Webhook support
 * - Refunds
 * - QR Code payments
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
function razorpay_MetaData()
{
    return [
        'DisplayName' => 'Razorpay',
        'APIVersion' => '1.1',
        'DisableLocalCreditCardInput' => true,
        'TokenisedStorage' => false,
    ];
}

/**
 * Gateway configuration
 */
function razorpay_config()
{
    return [
        'FriendlyName' => [
            'Type' => 'System',
            'Value' => 'Razorpay (Indian Payments)',
        ],
        'key_id' => [
            'FriendlyName' => 'Key ID',
            'Type' => 'text',
            'Size' => '50',
            'Default' => '',
            'Description' => 'Enter your Razorpay Key ID (starts with rzp_test_ or rzp_live_)',
        ],
        'key_secret' => [
            'FriendlyName' => 'Key Secret',
            'Type' => 'password',
            'Size' => '50',
            'Default' => '',
            'Description' => 'Enter your Razorpay Key Secret',
        ],
        'webhook_secret' => [
            'FriendlyName' => 'Webhook Secret',
            'Type' => 'password',
            'Size' => '50',
            'Default' => '',
            'Description' => 'Enter your Razorpay Webhook Secret',
        ],
        'test_mode' => [
            'FriendlyName' => 'Test Mode',
            'Type' => 'yesno',
            'Description' => 'Enable test mode (use test keys)',
        ],
        'payment_methods' => [
            'FriendlyName' => 'Payment Methods',
            'Type' => 'text',
            'Size' => '100',
            'Default' => 'card,upi,netbanking,wallet',
            'Description' => 'Comma-separated: card,upi,netbanking,wallet,emi,paylater,cardless_emi',
        ],
        'brand_name' => [
            'FriendlyName' => 'Brand Name',
            'Type' => 'text',
            'Size' => '50',
            'Default' => '',
            'Description' => 'Your business name shown on checkout',
        ],
        'brand_logo' => [
            'FriendlyName' => 'Brand Logo URL',
            'Type' => 'text',
            'Size' => '100',
            'Default' => '',
            'Description' => 'URL to your logo (square, min 256x256px)',
        ],
        'theme_color' => [
            'FriendlyName' => 'Theme Color',
            'Type' => 'text',
            'Size' => '10',
            'Default' => '#3399cc',
            'Description' => 'Checkout theme color (hex code)',
        ],
        'auto_capture' => [
            'FriendlyName' => 'Auto Capture',
            'Type' => 'yesno',
            'Description' => 'Automatically capture payments (untick for manual authorization)',
            'Default' => 'on',
        ],
        'allow_international' => [
            'FriendlyName' => 'International Cards',
            'Type' => 'yesno',
            'Description' => 'Accept international credit/debit cards',
        ],
    ];
}

/**
 * Generate payment link
 */
function razorpay_link($params)
{
    // Gateway configuration
    $keyId = $params['key_id'];
    $keySecret = $params['key_secret'];
    $testMode = $params['test_mode'];
    $brandName = $params['brand_name'] ?: 'NUMZ.AI';
    $brandLogo = $params['brand_logo'];
    $themeColor = $params['theme_color'] ?: '#3399cc';
    $autoCapture = $params['auto_capture'];
    $allowInternational = $params['allow_international'];
    $paymentMethods = array_map('trim', explode(',', $params['payment_methods'] ?: 'card,upi,netbanking,wallet'));

    // Invoice information
    $invoiceId = $params['invoiceid'];
    $description = $params['description'];
    $amount = $params['amount'];
    $currencyCode = $params['currency'];

    // Client information
    $firstname = $params['clientdetails']['firstname'];
    $lastname = $params['clientdetails']['lastname'];
    $email = $params['clientdetails']['email'];
    $phone = $params['clientdetails']['phonenumber'];
    $address1 = $params['clientdetails']['address1'];
    $city = $params['clientdetails']['city'];
    $state = $params['clientdetails']['state'];
    $postcode = $params['clientdetails']['postcode'];
    $country = $params['clientdetails']['country'];

    // System URLs
    $systemUrl = $params['systemurl'];
    $returnUrl = $params['returnurl'];
    $callbackUrl = $systemUrl . 'modules/gateways/callback/razorpay.php';

    if (empty($keyId) || empty($keySecret)) {
        return '<div class="alert alert-danger">Razorpay credentials not configured</div>';
    }

    try {
        // Create order via Razorpay API
        $orderData = [
            'amount' => (int)($amount * 100), // Amount in paise
            'currency' => $currencyCode,
            'receipt' => 'invoice_' . $invoiceId,
            'notes' => [
                'invoice_id' => $invoiceId,
                'client_name' => trim($firstname . ' ' . $lastname),
                'client_email' => $email,
            ],
        ];

        if (!$autoCapture) {
            $orderData['payment_capture'] = 0; // Manual capture
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.razorpay.com/v1/orders');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $keyId . ':' . $keySecret);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception('Failed to create Razorpay order: ' . $response);
        }

        $order = json_decode($response, true);
        $orderId = $order['id'];

        // Log transaction
        logTransaction($params['paymentmethod'], [
            'order_id' => $orderId,
            'invoice_id' => $invoiceId,
            'amount' => $amount,
            'currency' => $currencyCode,
        ], 'Order Created');

        // Build checkout options
        $checkoutOptions = [
            'key' => $keyId,
            'amount' => $order['amount'],
            'currency' => $order['currency'],
            'name' => $brandName,
            'description' => $description,
            'order_id' => $orderId,
            'handler' => 'razorpayHandler',
            'prefill' => [
                'name' => trim($firstname . ' ' . $lastname),
                'email' => $email,
                'contact' => preg_replace('/[^0-9]/', '', $phone),
            ],
            'notes' => [
                'invoice_id' => $invoiceId,
            ],
            'theme' => [
                'color' => $themeColor,
            ],
            'modal' => [
                'ondismiss' => 'razorpayDismissHandler',
            ],
        ];

        if ($brandLogo) {
            $checkoutOptions['image'] = $brandLogo;
        }

        // Add payment methods
        if (!empty($paymentMethods)) {
            $checkoutOptions['method'] = $paymentMethods;
        }

        if ($allowInternational) {
            $checkoutOptions['config'] = [
                'display' => [
                    'preferences' => [
                        'show_default_blocks' => true,
                    ],
                ],
            ];
        }

        $checkoutJson = json_encode($checkoutOptions);

        // Build HTML output
        $htmlOutput = '
        <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
        <form name="razorpayform" id="razorpayform" action="' . htmlspecialchars($callbackUrl) . '" method="POST">
            <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
            <input type="hidden" name="razorpay_order_id" id="razorpay_order_id">
            <input type="hidden" name="razorpay_signature" id="razorpay_signature">
            <input type="hidden" name="invoice_id" value="' . htmlspecialchars($invoiceId) . '">
        </form>
        <button type="button" class="btn btn-primary" id="razorpay_btn">
            <i class="fas fa-credit-card"></i> Pay with Razorpay
        </button>
        <div style="margin-top: 10px; font-size: 12px; color: #666;">
            <i class="fas fa-shield-alt"></i> Supports UPI, Cards, Net Banking, Wallets & more
        </div>
        <script>
        var options = ' . $checkoutJson . ';

        function razorpayHandler(response) {
            document.getElementById("razorpay_payment_id").value = response.razorpay_payment_id;
            document.getElementById("razorpay_order_id").value = response.razorpay_order_id;
            document.getElementById("razorpay_signature").value = response.razorpay_signature;
            document.getElementById("razorpayform").submit();
        }

        function razorpayDismissHandler() {
            console.log("Razorpay checkout dismissed");
        }

        var rzp = new Razorpay(options);

        document.getElementById("razorpay_btn").onclick = function(e) {
            rzp.open();
            e.preventDefault();
        }
        </script>';

        return $htmlOutput;

    } catch (Exception $e) {
        logTransaction($params['paymentmethod'], [
            'error' => $e->getMessage(),
        ], 'Order Creation Error');

        return '<div class="alert alert-danger">Razorpay error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

/**
 * Refund transaction
 */
function razorpay_refund($params)
{
    $keyId = $params['key_id'];
    $keySecret = $params['key_secret'];
    $paymentId = $params['transid'];
    $refundAmount = $params['amount'];

    try {
        // Create refund
        $refundData = [
            'amount' => (int)($refundAmount * 100), // Amount in paise
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.razorpay.com/v1/payments/' . $paymentId . '/refund');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($refundData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $keyId . ':' . $keySecret);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception('Refund failed: ' . $response);
        }

        $refund = json_decode($response, true);

        logTransaction($params['paymentmethod'], [
            'refund_id' => $refund['id'],
            'payment_id' => $paymentId,
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
            'payment_id' => $paymentId,
        ], 'Refund Failed');

        return [
            'status' => 'declined',
            'rawdata' => $e->getMessage(),
        ];
    }
}

/**
 * Capture authorized payment
 */
function razorpay_capture($params)
{
    $keyId = $params['key_id'];
    $keySecret = $params['key_secret'];
    $paymentId = $params['transid'];
    $amount = $params['amount'];

    try {
        // Capture payment
        $captureData = [
            'amount' => (int)($amount * 100), // Amount in paise
            'currency' => $params['currency'],
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.razorpay.com/v1/payments/' . $paymentId . '/capture');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($captureData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $keyId . ':' . $keySecret);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception('Capture failed: ' . $response);
        }

        $payment = json_decode($response, true);

        logTransaction($params['paymentmethod'], [
            'payment_id' => $paymentId,
            'amount' => $amount,
            'status' => $payment['status'],
        ], 'Payment Captured');

        return [
            'status' => 'success',
            'rawdata' => $response,
            'transid' => $paymentId,
            'fee' => $payment['fee'] / 100 ?? 0,
        ];

    } catch (Exception $e) {
        logTransaction($params['paymentmethod'], [
            'error' => $e->getMessage(),
            'payment_id' => $paymentId,
        ], 'Capture Failed');

        return [
            'status' => 'error',
            'rawdata' => $e->getMessage(),
        ];
    }
}

/**
 * Test connection to Razorpay
 */
function razorpay_testConnection($params)
{
    $keyId = $params['key_id'];
    $keySecret = $params['key_secret'];
    $testMode = $params['test_mode'];

    if (empty($keyId) || empty($keySecret)) {
        return [
            'status' => 'error',
            'description' => 'Razorpay credentials not configured',
        ];
    }

    // Validate key format
    $expectedPrefix = $testMode ? 'rzp_test_' : 'rzp_live_';
    if (strpos($keyId, $expectedPrefix) !== 0) {
        return [
            'status' => 'error',
            'description' => 'Invalid key format. Expected key starting with ' . $expectedPrefix,
        ];
    }

    try {
        // Test API by fetching payments (limit 1)
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.razorpay.com/v1/payments?count=1');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $keyId . ':' . $keySecret);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            return [
                'status' => 'success',
                'description' => 'Successfully connected to Razorpay ' . ($testMode ? '(Test Mode)' : '(Live Mode)'),
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
