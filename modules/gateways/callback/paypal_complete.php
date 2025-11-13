<?php
/**
 * PayPal Complete Callback Handler
 *
 * Processes PayPal IPN (Instant Payment Notification)
 * Handles both Express Checkout and Standard payments
 */

// Require WHMCS initialization
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';

use WHMCS\Database\Capsule;

// Get gateway module name
$gatewayModuleName = basename(__FILE__, '.php');

// Retrieve gateway configuration
$gatewayParams = getGatewayVariables($gatewayModuleName);

// Die if module not active
if (!$gatewayParams['type']) {
    http_response_code(400);
    die('Module Not Activated');
}

// Handle Express Checkout capture
if (isset($_GET['paypal_action']) && $_GET['paypal_action'] === 'capture') {
    handleExpressCheckoutCapture($gatewayParams);
    exit;
}

// Handle IPN (for Standard payments and webhooks)
handleIPN($gatewayParams);

/**
 * Handle Express Checkout capture
 */
function handleExpressCheckoutCapture($gatewayParams)
{
    $token = $_GET['token'] ?? '';
    $orderId = $_GET['token'] ?? ''; // Token is the order ID in v2 API

    if (empty($orderId)) {
        logTransaction($gatewayParams['name'], ['error' => 'No order ID provided'], 'Capture Error');
        header('Location: ' . $_GET['returnurl'] ?? '/');
        exit;
    }

    $mode = $gatewayParams['mode'];
    $clientId = $gatewayParams['client_id'];
    $secret = $gatewayParams['secret'];

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
        curl_close($ch);

        $tokenData = json_decode($response, true);
        $accessToken = $tokenData['access_token'];

        // Capture the order
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl . '/v2/checkout/orders/' . $orderId . '/capture');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, '{}');
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

        $order = json_decode($response, true);

        if ($httpCode === 201 && $order['status'] === 'COMPLETED') {
            // Get capture details
            $capture = $order['purchase_units'][0]['payments']['captures'][0];
            $captureId = $capture['id'];
            $invoiceId = $order['purchase_units'][0]['custom_id'];
            $amount = $capture['amount']['value'];

            // Validate invoice
            $invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);

            // Check if already processed
            $existingPayment = Capsule::table('tblaccounts')
                ->where('transid', $captureId)
                ->first();

            if (!$existingPayment) {
                // Calculate fee
                $fee = 0;
                if (isset($capture['seller_receivable_breakdown']['paypal_fee'])) {
                    $fee = $capture['seller_receivable_breakdown']['paypal_fee']['value'];
                }

                // Add payment
                addInvoicePayment(
                    $invoiceId,
                    $captureId,
                    $amount,
                    $fee,
                    $gatewayParams['name']
                );

                logTransaction($gatewayParams['name'], [
                    'order_id' => $orderId,
                    'capture_id' => $captureId,
                    'invoice_id' => $invoiceId,
                    'amount' => $amount,
                    'fee' => $fee,
                ], 'Payment Captured');

                logActivity('Payment Received via PayPal - Invoice ID: ' . $invoiceId . ' - Transaction ID: ' . $captureId);
            }
        } else {
            logTransaction($gatewayParams['name'], [
                'order_id' => $orderId,
                'status' => $order['status'] ?? 'unknown',
                'response' => $response,
            ], 'Capture Failed');
        }

    } catch (Exception $e) {
        logTransaction($gatewayParams['name'], [
            'error' => $e->getMessage(),
            'order_id' => $orderId,
        ], 'Capture Exception');
    }

    // Redirect back
    $returnUrl = $_GET['returnurl'] ?? '/';
    header('Location: ' . $returnUrl);
    exit;
}

/**
 * Handle IPN (Instant Payment Notification)
 */
function handleIPN($gatewayParams)
{
    // Read POST data
    $raw_post_data = file_get_contents('php://input');
    $raw_post_array = explode('&', $raw_post_data);
    $myPost = [];
    foreach ($raw_post_array as $keyval) {
        $keyval = explode('=', $keyval);
        if (count($keyval) == 2) {
            $myPost[$keyval[0]] = urldecode($keyval[1]);
        }
    }

    // Read from $_POST if raw data is empty
    if (empty($myPost)) {
        $myPost = $_POST;
    }

    // Build verification request
    $req = 'cmd=_notify-validate';
    foreach ($myPost as $key => $value) {
        $value = urlencode($value);
        $req .= "&$key=$value";
    }

    // Verify with PayPal
    $mode = $gatewayParams['mode'];
    $paypalUrl = $mode === 'live'
        ? 'https://ipnpb.paypal.com/cgi-bin/webscr'
        : 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr';

    $ch = curl_init($paypalUrl);
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Connection: Close',
        'User-Agent: WHMCS-IPN-Verification',
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Log IPN
    logTransaction($gatewayParams['name'], $myPost, 'IPN Received');

    // Verify response
    if ($httpCode !== 200 || strcmp($res, 'VERIFIED') !== 0) {
        logTransaction($gatewayParams['name'], [
            'verification_response' => $res,
            'http_code' => $httpCode,
        ], 'IPN Verification Failed');
        http_response_code(400);
        die('IPN Verification Failed');
    }

    // Process IPN
    $paymentStatus = $myPost['payment_status'] ?? '';
    $transactionId = $myPost['txn_id'] ?? '';
    $invoiceId = $myPost['item_number'] ?? $myPost['custom'] ?? '';
    $amount = $myPost['mc_gross'] ?? 0;
    $fee = $myPost['mc_fee'] ?? 0;
    $currency = $myPost['mc_currency'] ?? '';
    $receiverEmail = $myPost['receiver_email'] ?? '';

    // Validate receiver email
    if (!empty($gatewayParams['paypal_email']) &&
        strtolower($receiverEmail) !== strtolower($gatewayParams['paypal_email'])) {
        logTransaction($gatewayParams['name'], [
            'error' => 'Receiver email mismatch',
            'expected' => $gatewayParams['paypal_email'],
            'received' => $receiverEmail,
        ], 'IPN Validation Failed');
        http_response_code(400);
        die('Receiver Email Mismatch');
    }

    if (empty($invoiceId) || empty($transactionId)) {
        logTransaction($gatewayParams['name'], [
            'error' => 'Missing invoice ID or transaction ID',
        ], 'IPN Processing Error');
        http_response_code(400);
        die('Missing Required Data');
    }

    // Validate invoice
    $invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);

    // Check transaction status
    switch (strtolower($paymentStatus)) {
        case 'completed':
        case 'success':
            // Check if already processed
            $existingPayment = Capsule::table('tblaccounts')
                ->where('transid', $transactionId)
                ->first();

            if (!$existingPayment) {
                // Add payment
                addInvoicePayment(
                    $invoiceId,
                    $transactionId,
                    $amount,
                    $fee,
                    $gatewayParams['name']
                );

                logTransaction($gatewayParams['name'], [
                    'invoice_id' => $invoiceId,
                    'transaction_id' => $transactionId,
                    'amount' => $amount,
                    'fee' => $fee,
                ], 'IPN Payment Successful');

                logActivity('Payment Received via PayPal IPN - Invoice ID: ' . $invoiceId . ' - Transaction ID: ' . $transactionId);
            }
            break;

        case 'pending':
            logTransaction($gatewayParams['name'], [
                'invoice_id' => $invoiceId,
                'transaction_id' => $transactionId,
                'pending_reason' => $myPost['pending_reason'] ?? 'unknown',
            ], 'IPN Payment Pending');
            break;

        case 'refunded':
        case 'reversed':
            logTransaction($gatewayParams['name'], [
                'invoice_id' => $invoiceId,
                'transaction_id' => $transactionId,
                'amount' => $amount,
            ], 'IPN Payment Refunded');

            logActivity('Payment Refunded via PayPal IPN - Invoice ID: ' . $invoiceId . ' - Transaction ID: ' . $transactionId);
            break;

        case 'failed':
        case 'denied':
        case 'expired':
            logTransaction($gatewayParams['name'], [
                'invoice_id' => $invoiceId,
                'transaction_id' => $transactionId,
                'status' => $paymentStatus,
            ], 'IPN Payment Failed');
            break;

        default:
            logTransaction($gatewayParams['name'], [
                'invoice_id' => $invoiceId,
                'transaction_id' => $transactionId,
                'status' => $paymentStatus,
            ], 'IPN Unknown Status');
            break;
    }

    http_response_code(200);
    echo 'IPN Processed';
}
