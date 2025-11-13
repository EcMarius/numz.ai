<?php
/**
 * Razorpay Callback Handler
 *
 * Processes both payment redirects and webhooks from Razorpay
 * Handles: payment.captured, payment.failed, refund.created, dispute.created
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

$keyId = $gatewayParams['key_id'];
$keySecret = $gatewayParams['key_secret'];
$webhookSecret = $gatewayParams['webhook_secret'];

// Check if this is a webhook or payment redirect
$isWebhook = isset($_SERVER['HTTP_X_RAZORPAY_SIGNATURE']) || isset($_POST['event']);

if ($isWebhook) {
    handleWebhook($gatewayParams, $webhookSecret);
} else {
    handlePaymentRedirect($gatewayParams);
}

/**
 * Handle payment redirect (after customer completes payment)
 */
function handlePaymentRedirect($gatewayParams)
{
    $paymentId = $_POST['razorpay_payment_id'] ?? '';
    $orderId = $_POST['razorpay_order_id'] ?? '';
    $signature = $_POST['razorpay_signature'] ?? '';
    $invoiceId = $_POST['invoice_id'] ?? '';

    if (empty($paymentId) || empty($orderId) || empty($signature)) {
        logTransaction($gatewayParams['name'], [
            'error' => 'Missing payment details',
        ], 'Payment Error');
        die('Payment details missing');
    }

    // Verify signature
    $expectedSignature = hash_hmac('sha256', $orderId . '|' . $paymentId, $gatewayParams['key_secret']);

    if (!hash_equals($expectedSignature, $signature)) {
        logTransaction($gatewayParams['name'], [
            'error' => 'Signature verification failed',
            'payment_id' => $paymentId,
        ], 'Signature Verification Failed');
        die('Signature verification failed');
    }

    // Fetch payment details from Razorpay
    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.razorpay.com/v1/payments/' . $paymentId);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $gatewayParams['key_id'] . ':' . $gatewayParams['key_secret']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception('Failed to fetch payment details');
        }

        $payment = json_decode($response, true);

        if ($payment['status'] === 'captured' || $payment['status'] === 'authorized') {
            $amount = $payment['amount'] / 100;
            $fee = $payment['fee'] / 100 ?? 0;

            if (empty($invoiceId)) {
                $invoiceId = $payment['notes']['invoice_id'] ?? '';
            }

            if (empty($invoiceId)) {
                throw new Exception('Invoice ID not found');
            }

            // Validate invoice
            $invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);

            // Check if already processed
            $existingPayment = Capsule::table('tblaccounts')
                ->where('transid', $paymentId)
                ->first();

            if (!$existingPayment) {
                // Add payment
                addInvoicePayment(
                    $invoiceId,
                    $paymentId,
                    $amount,
                    $fee,
                    $gatewayParams['name']
                );

                logTransaction($gatewayParams['name'], [
                    'invoice_id' => $invoiceId,
                    'payment_id' => $paymentId,
                    'order_id' => $orderId,
                    'amount' => $amount,
                    'fee' => $fee,
                    'method' => $payment['method'] ?? 'unknown',
                ], 'Payment Successful');

                logActivity('Payment Received via Razorpay - Invoice ID: ' . $invoiceId . ' - Payment ID: ' . $paymentId);
            }

            // Redirect to invoice
            $returnUrl = $_GET['returnurl'] ?? $gatewayParams['systemurl'] . 'viewinvoice.php?id=' . $invoiceId;
            header('Location: ' . $returnUrl);
            exit;
        } else {
            throw new Exception('Payment not successful. Status: ' . $payment['status']);
        }

    } catch (Exception $e) {
        logTransaction($gatewayParams['name'], [
            'error' => $e->getMessage(),
            'payment_id' => $paymentId,
        ], 'Payment Processing Error');

        die('Payment processing error: ' . htmlspecialchars($e->getMessage()));
    }
}

/**
 * Handle webhook from Razorpay
 */
function handleWebhook($gatewayParams, $webhookSecret)
{
    // Get webhook payload
    $payload = file_get_contents('php://input');
    $signature = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';

    // Verify webhook signature
    if (!empty($webhookSecret) && !empty($signature)) {
        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

        if (!hash_equals($expectedSignature, $signature)) {
            logTransaction($gatewayParams['name'], [
                'error' => 'Webhook signature verification failed',
            ], 'Webhook Verification Failed');
            http_response_code(403);
            die('Signature verification failed');
        }
    }

    $webhookData = json_decode($payload, true);

    if (!$webhookData) {
        http_response_code(400);
        die('Invalid webhook data');
    }

    // Log webhook
    logTransaction($gatewayParams['name'], [
        'event' => $webhookData['event'],
    ], 'Webhook Received');

    // Process webhook based on event type
    $event = $webhookData['event'];
    $entity = $webhookData['payload'][$webhookData['contains']] ?? [];

    switch ($event) {
        case 'payment.captured':
            handlePaymentCaptured($entity, $gatewayParams);
            break;

        case 'payment.failed':
            handlePaymentFailed($entity, $gatewayParams);
            break;

        case 'payment.authorized':
            handlePaymentAuthorized($entity, $gatewayParams);
            break;

        case 'refund.created':
            handleRefundCreated($entity, $gatewayParams);
            break;

        case 'refund.processed':
            handleRefundProcessed($entity, $gatewayParams);
            break;

        case 'refund.failed':
            handleRefundFailed($entity, $gatewayParams);
            break;

        case 'dispute.created':
            handleDisputeCreated($entity, $gatewayParams);
            break;

        case 'order.paid':
            handleOrderPaid($entity, $gatewayParams);
            break;

        default:
            logTransaction($gatewayParams['name'], [
                'event' => $event,
                'message' => 'Unhandled webhook event',
            ], 'Webhook Info');
            break;
    }

    http_response_code(200);
    echo json_encode(['status' => 'success']);
}

/**
 * Handle payment captured webhook
 */
function handlePaymentCaptured($payment, $gatewayParams)
{
    $paymentId = $payment['id'];
    $amount = $payment['amount'] / 100;
    $fee = $payment['fee'] / 100 ?? 0;
    $invoiceId = $payment['notes']['invoice_id'] ?? '';

    if (empty($invoiceId)) {
        logTransaction($gatewayParams['name'], [
            'error' => 'Invoice ID not found in payment notes',
            'payment_id' => $paymentId,
        ], 'Webhook Error');
        return;
    }

    // Validate invoice
    $invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);

    // Check if already processed
    $existingPayment = Capsule::table('tblaccounts')
        ->where('transid', $paymentId)
        ->first();

    if (!$existingPayment) {
        addInvoicePayment(
            $invoiceId,
            $paymentId,
            $amount,
            $fee,
            $gatewayParams['name']
        );

        logTransaction($gatewayParams['name'], [
            'invoice_id' => $invoiceId,
            'payment_id' => $paymentId,
            'amount' => $amount,
            'fee' => $fee,
        ], 'Payment Captured Webhook');

        logActivity('Payment Captured via Razorpay Webhook - Invoice ID: ' . $invoiceId . ' - Payment ID: ' . $paymentId);
    }
}

/**
 * Handle payment failed webhook
 */
function handlePaymentFailed($payment, $gatewayParams)
{
    $paymentId = $payment['id'];
    $errorCode = $payment['error_code'] ?? '';
    $errorDescription = $payment['error_description'] ?? 'Unknown error';

    logTransaction($gatewayParams['name'], [
        'payment_id' => $paymentId,
        'error_code' => $errorCode,
        'error_description' => $errorDescription,
    ], 'Payment Failed Webhook');

    $invoiceId = $payment['notes']['invoice_id'] ?? '';
    if ($invoiceId) {
        logActivity('Payment Failed via Razorpay - Invoice ID: ' . $invoiceId . ' - Error: ' . $errorDescription);
    }
}

/**
 * Handle payment authorized webhook
 */
function handlePaymentAuthorized($payment, $gatewayParams)
{
    $paymentId = $payment['id'];
    $amount = $payment['amount'] / 100;

    logTransaction($gatewayParams['name'], [
        'payment_id' => $paymentId,
        'amount' => $amount,
        'status' => 'authorized',
    ], 'Payment Authorized Webhook');
}

/**
 * Handle refund created webhook
 */
function handleRefundCreated($refund, $gatewayParams)
{
    $refundId = $refund['id'];
    $paymentId = $refund['payment_id'];
    $amount = $refund['amount'] / 100;

    logTransaction($gatewayParams['name'], [
        'refund_id' => $refundId,
        'payment_id' => $paymentId,
        'amount' => $amount,
    ], 'Refund Created Webhook');

    // Find original payment
    $payment = Capsule::table('tblaccounts')
        ->where('transid', $paymentId)
        ->first();

    if ($payment) {
        logActivity('Refund Created via Razorpay - Invoice ID: ' . $payment->invoiceid . ' - Refund ID: ' . $refundId . ' - Amount: ' . $amount);
    }
}

/**
 * Handle refund processed webhook
 */
function handleRefundProcessed($refund, $gatewayParams)
{
    $refundId = $refund['id'];
    $paymentId = $refund['payment_id'];
    $amount = $refund['amount'] / 100;

    logTransaction($gatewayParams['name'], [
        'refund_id' => $refundId,
        'payment_id' => $paymentId,
        'amount' => $amount,
        'status' => 'processed',
    ], 'Refund Processed Webhook');
}

/**
 * Handle refund failed webhook
 */
function handleRefundFailed($refund, $gatewayParams)
{
    $refundId = $refund['id'];
    $paymentId = $refund['payment_id'];

    logTransaction($gatewayParams['name'], [
        'refund_id' => $refundId,
        'payment_id' => $paymentId,
        'status' => 'failed',
    ], 'Refund Failed Webhook');
}

/**
 * Handle dispute created webhook
 */
function handleDisputeCreated($dispute, $gatewayParams)
{
    $disputeId = $dispute['id'];
    $paymentId = $dispute['payment_id'];
    $amount = $dispute['amount'] / 100;
    $reason = $dispute['reason_code'] ?? 'Unknown';

    logTransaction($gatewayParams['name'], [
        'dispute_id' => $disputeId,
        'payment_id' => $paymentId,
        'amount' => $amount,
        'reason' => $reason,
    ], 'Dispute Created Webhook');

    // Find original payment
    $payment = Capsule::table('tblaccounts')
        ->where('transid', $paymentId)
        ->first();

    if ($payment) {
        logActivity('Payment Dispute via Razorpay - Invoice ID: ' . $payment->invoiceid . ' - Reason: ' . $reason . ' - Amount: ' . $amount);
    }
}

/**
 * Handle order paid webhook
 */
function handleOrderPaid($order, $gatewayParams)
{
    $orderId = $order['id'];
    $amount = $order['amount_paid'] / 100;

    logTransaction($gatewayParams['name'], [
        'order_id' => $orderId,
        'amount' => $amount,
    ], 'Order Paid Webhook');
}
