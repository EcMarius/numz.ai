<?php
/**
 * Paddle Callback Handler
 *
 * Processes webhooks from Paddle with signature verification
 * Handles: payment_succeeded, subscription_created, subscription_cancelled, refund_created
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

// Get webhook data
$webhookData = $_POST;

if (empty($webhookData)) {
    http_response_code(400);
    die('No webhook data received');
}

// Log webhook received
logTransaction($gatewayModuleName, $webhookData, 'Webhook Received');

// Verify webhook signature
$publicKey = $gatewayParams['public_key'];

if (!empty($publicKey)) {
    if (!verifyPaddleWebhook($webhookData, $publicKey)) {
        logTransaction($gatewayModuleName, [
            'error' => 'Signature verification failed',
        ], 'Webhook Verification Failed');
        http_response_code(403);
        die('Signature verification failed');
    }
}

// Get alert type
$alertName = $webhookData['alert_name'] ?? '';

// Process webhook based on type
switch ($alertName) {
    case 'payment_succeeded':
        handlePaymentSucceeded($webhookData, $gatewayModuleName, $gatewayParams);
        break;

    case 'subscription_created':
        handleSubscriptionCreated($webhookData, $gatewayModuleName, $gatewayParams);
        break;

    case 'subscription_updated':
        handleSubscriptionUpdated($webhookData, $gatewayModuleName, $gatewayParams);
        break;

    case 'subscription_cancelled':
        handleSubscriptionCancelled($webhookData, $gatewayModuleName, $gatewayParams);
        break;

    case 'subscription_payment_succeeded':
        handleSubscriptionPaymentSucceeded($webhookData, $gatewayModuleName, $gatewayParams);
        break;

    case 'subscription_payment_failed':
        handleSubscriptionPaymentFailed($webhookData, $gatewayModuleName, $gatewayParams);
        break;

    case 'payment_refunded':
        handlePaymentRefunded($webhookData, $gatewayModuleName, $gatewayParams);
        break;

    case 'payment_dispute_created':
        handleDisputeCreated($webhookData, $gatewayModuleName, $gatewayParams);
        break;

    default:
        logTransaction($gatewayModuleName, [
            'alert_name' => $alertName,
            'message' => 'Unhandled webhook type',
        ], 'Webhook Info');
        break;
}

http_response_code(200);
echo json_encode(['status' => 'success']);

/**
 * Verify Paddle webhook signature
 */
function verifyPaddleWebhook($data, $publicKey)
{
    // Get signature
    $signature = $data['p_signature'] ?? '';

    if (empty($signature)) {
        return false;
    }

    // Remove signature from data
    unset($data['p_signature']);

    // Sort data by key
    ksort($data);

    // Serialize data
    $serialized = '';
    foreach ($data as $key => $value) {
        $serialized .= $key;
        if (is_array($value)) {
            $serialized .= json_encode($value);
        } else {
            $serialized .= $value;
        }
    }

    // Verify signature
    $verification = openssl_verify(
        $serialized,
        base64_decode($signature),
        $publicKey,
        OPENSSL_ALGO_SHA1
    );

    return $verification === 1;
}

/**
 * Handle payment succeeded
 */
function handlePaymentSucceeded($data, $gatewayModuleName, $gatewayParams)
{
    $orderId = $data['order_id'] ?? '';
    $checkoutId = $data['checkout_id'] ?? '';
    $amount = $data['sale_gross'] ?? 0;
    $fee = $data['payment_tax'] ?? 0;
    $currency = $data['currency'] ?? '';
    $passthrough = json_decode($data['passthrough'] ?? '{}', true);
    $invoiceId = $passthrough['invoice_id'] ?? '';

    if (empty($invoiceId) || empty($orderId)) {
        logTransaction($gatewayModuleName, [
            'error' => 'Missing invoice ID or order ID',
        ], 'Payment Error');
        return;
    }

    // Validate invoice
    $invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);

    // Check if already processed
    $existingPayment = Capsule::table('tblaccounts')
        ->where('transid', $orderId)
        ->first();

    if ($existingPayment) {
        logTransaction($gatewayModuleName, [
            'order_id' => $orderId,
            'message' => 'Payment already processed',
        ], 'Duplicate Payment');
        return;
    }

    // Add payment
    addInvoicePayment(
        $invoiceId,
        $orderId,
        $amount,
        $fee,
        $gatewayModuleName
    );

    logTransaction($gatewayModuleName, [
        'invoice_id' => $invoiceId,
        'order_id' => $orderId,
        'checkout_id' => $checkoutId,
        'amount' => $amount,
        'fee' => $fee,
    ], 'Payment Successful');

    logActivity('Payment Received via Paddle - Invoice ID: ' . $invoiceId . ' - Order ID: ' . $orderId);
}

/**
 * Handle subscription created
 */
function handleSubscriptionCreated($data, $gatewayModuleName, $gatewayParams)
{
    $subscriptionId = $data['subscription_id'] ?? '';
    $subscriptionPlanId = $data['subscription_plan_id'] ?? '';
    $status = $data['status'] ?? '';
    $passthrough = json_decode($data['passthrough'] ?? '{}', true);

    logTransaction($gatewayModuleName, [
        'subscription_id' => $subscriptionId,
        'plan_id' => $subscriptionPlanId,
        'status' => $status,
        'passthrough' => $passthrough,
    ], 'Subscription Created');

    logActivity('Paddle Subscription Created - Subscription ID: ' . $subscriptionId);
}

/**
 * Handle subscription updated
 */
function handleSubscriptionUpdated($data, $gatewayModuleName, $gatewayParams)
{
    $subscriptionId = $data['subscription_id'] ?? '';
    $status = $data['status'] ?? '';
    $newPrice = $data['new_price'] ?? '';

    logTransaction($gatewayModuleName, [
        'subscription_id' => $subscriptionId,
        'status' => $status,
        'new_price' => $newPrice,
    ], 'Subscription Updated');

    logActivity('Paddle Subscription Updated - Subscription ID: ' . $subscriptionId);
}

/**
 * Handle subscription cancelled
 */
function handleSubscriptionCancelled($data, $gatewayModuleName, $gatewayParams)
{
    $subscriptionId = $data['subscription_id'] ?? '';
    $status = $data['status'] ?? '';
    $cancellationEffectiveDate = $data['cancellation_effective_date'] ?? '';

    logTransaction($gatewayModuleName, [
        'subscription_id' => $subscriptionId,
        'status' => $status,
        'cancellation_date' => $cancellationEffectiveDate,
    ], 'Subscription Cancelled');

    logActivity('Paddle Subscription Cancelled - Subscription ID: ' . $subscriptionId);
}

/**
 * Handle subscription payment succeeded
 */
function handleSubscriptionPaymentSucceeded($data, $gatewayModuleName, $gatewayParams)
{
    $subscriptionId = $data['subscription_id'] ?? '';
    $orderId = $data['order_id'] ?? '';
    $amount = $data['sale_gross'] ?? 0;
    $fee = $data['payment_tax'] ?? 0;
    $passthrough = json_decode($data['passthrough'] ?? '{}', true);
    $invoiceId = $passthrough['invoice_id'] ?? '';

    logTransaction($gatewayModuleName, [
        'subscription_id' => $subscriptionId,
        'order_id' => $orderId,
        'amount' => $amount,
        'fee' => $fee,
    ], 'Subscription Payment Succeeded');

    if (!empty($invoiceId)) {
        // Check if already processed
        $existingPayment = Capsule::table('tblaccounts')
            ->where('transid', $orderId)
            ->first();

        if (!$existingPayment) {
            $invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);

            addInvoicePayment(
                $invoiceId,
                $orderId,
                $amount,
                $fee,
                $gatewayModuleName
            );

            logActivity('Paddle Subscription Payment - Invoice ID: ' . $invoiceId . ' - Order ID: ' . $orderId);
        }
    }
}

/**
 * Handle subscription payment failed
 */
function handleSubscriptionPaymentFailed($data, $gatewayModuleName, $gatewayParams)
{
    $subscriptionId = $data['subscription_id'] ?? '';
    $amount = $data['amount'] ?? 0;
    $nextRetryDate = $data['next_retry_date'] ?? '';

    logTransaction($gatewayModuleName, [
        'subscription_id' => $subscriptionId,
        'amount' => $amount,
        'next_retry' => $nextRetryDate,
    ], 'Subscription Payment Failed');

    logActivity('Paddle Subscription Payment Failed - Subscription ID: ' . $subscriptionId);
}

/**
 * Handle payment refunded
 */
function handlePaymentRefunded($data, $gatewayModuleName, $gatewayParams)
{
    $orderId = $data['order_id'] ?? '';
    $amount = $data['amount'] ?? 0;
    $refundType = $data['refund_type'] ?? '';
    $refundReason = $data['refund_reason'] ?? '';

    logTransaction($gatewayModuleName, [
        'order_id' => $orderId,
        'amount' => $amount,
        'refund_type' => $refundType,
        'reason' => $refundReason,
    ], 'Payment Refunded');

    // Find the original payment
    $payment = Capsule::table('tblaccounts')
        ->where('transid', $orderId)
        ->first();

    if ($payment) {
        logActivity('Paddle Refund Processed - Invoice ID: ' . $payment->invoiceid . ' - Order ID: ' . $orderId . ' - Amount: ' . $amount);
    }
}

/**
 * Handle dispute created
 */
function handleDisputeCreated($data, $gatewayModuleName, $gatewayParams)
{
    $orderId = $data['order_id'] ?? '';
    $amount = $data['amount'] ?? 0;
    $disputeReason = $data['reason'] ?? '';

    logTransaction($gatewayModuleName, [
        'order_id' => $orderId,
        'amount' => $amount,
        'reason' => $disputeReason,
    ], 'Dispute Created');

    // Find the payment
    $payment = Capsule::table('tblaccounts')
        ->where('transid', $orderId)
        ->first();

    if ($payment) {
        logActivity('Paddle Payment Dispute - Invoice ID: ' . $payment->invoiceid . ' - Order ID: ' . $orderId . ' - Reason: ' . $disputeReason);
    }
}
