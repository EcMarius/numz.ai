<?php
/**
 * Stripe Complete Callback Handler
 *
 * Processes webhooks from Stripe with signature verification
 * Handles: payment_intent.succeeded, checkout.session.completed, charge.refunded
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

// Load Stripe PHP library
$stripePath = __DIR__ . '/../stripe_complete/stripe-php/init.php';
if (!file_exists($stripePath)) {
    http_response_code(500);
    die('Stripe PHP library not found');
}
require_once $stripePath;

// Get webhook payload
$payload = @file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

$webhookSecret = $gatewayParams['webhook_secret'];
$secretKey = $gatewayParams['secret_key'];

\Stripe\Stripe::setApiKey($secretKey);
\Stripe\Stripe::setApiVersion('2023-10-16');

// Verify webhook signature
try {
    if (empty($webhookSecret)) {
        // If no webhook secret configured, proceed without verification (not recommended)
        $event = json_decode($payload, true);
        logTransaction($gatewayModuleName, ['warning' => 'Webhook processed without signature verification'], 'Warning');
    } else {
        // Verify signature
        $event = \Stripe\Webhook::constructEvent(
            $payload,
            $sigHeader,
            $webhookSecret
        );
    }
} catch (\UnexpectedValueException $e) {
    // Invalid payload
    logTransaction($gatewayModuleName, ['error' => 'Invalid payload'], 'Webhook Error');
    http_response_code(400);
    exit();
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    // Invalid signature
    logTransaction($gatewayModuleName, ['error' => 'Invalid signature'], 'Webhook Error');
    http_response_code(400);
    exit();
}

// Log webhook received
logTransaction($gatewayModuleName, [
    'event_type' => $event['type'],
    'event_id' => $event['id'],
], 'Webhook Received');

// Handle different event types
switch ($event['type']) {
    case 'checkout.session.completed':
        handleCheckoutSessionCompleted($event['data']['object'], $gatewayModuleName, $gatewayParams);
        break;

    case 'payment_intent.succeeded':
        handlePaymentIntentSucceeded($event['data']['object'], $gatewayModuleName, $gatewayParams);
        break;

    case 'payment_intent.payment_failed':
        handlePaymentIntentFailed($event['data']['object'], $gatewayModuleName, $gatewayParams);
        break;

    case 'charge.refunded':
        handleChargeRefunded($event['data']['object'], $gatewayModuleName, $gatewayParams);
        break;

    case 'charge.dispute.created':
        handleDisputeCreated($event['data']['object'], $gatewayModuleName, $gatewayParams);
        break;

    default:
        // Log unhandled event
        logTransaction($gatewayModuleName, [
            'event_type' => $event['type'],
            'message' => 'Unhandled event type',
        ], 'Webhook Info');
        break;
}

http_response_code(200);
echo json_encode(['status' => 'success']);

/**
 * Handle checkout session completed
 */
function handleCheckoutSessionCompleted($session, $gatewayModuleName, $gatewayParams)
{
    $invoiceId = $session['metadata']['invoice_id'] ?? null;
    $paymentIntentId = $session['payment_intent'] ?? null;

    if (!$invoiceId || !$paymentIntentId) {
        logTransaction($gatewayModuleName, [
            'session_id' => $session['id'],
            'error' => 'Missing invoice_id or payment_intent',
        ], 'Checkout Session Error');
        return;
    }

    // Validate invoice exists
    $invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);

    // Check if payment already processed
    $existingPayment = Capsule::table('tblaccounts')
        ->where('transid', $paymentIntentId)
        ->where('invoiceid', $invoiceId)
        ->first();

    if ($existingPayment) {
        logTransaction($gatewayModuleName, [
            'payment_intent' => $paymentIntentId,
            'message' => 'Payment already processed',
        ], 'Duplicate Payment');
        return;
    }

    // Retrieve payment intent for details
    try {
        $paymentIntent = \Stripe\PaymentIntent::retrieve($paymentIntentId);

        if ($paymentIntent->status === 'succeeded') {
            $amount = $paymentIntent->amount / 100;

            // Calculate fees
            $fee = 0;
            if (!empty($paymentIntent->charges->data[0]->balance_transaction)) {
                $balanceTransaction = \Stripe\BalanceTransaction::retrieve(
                    $paymentIntent->charges->data[0]->balance_transaction
                );
                $fee = $balanceTransaction->fee / 100;
            }

            // Add payment to invoice
            addInvoicePayment(
                $invoiceId,
                $paymentIntentId,
                $amount,
                $fee,
                $gatewayModuleName
            );

            logTransaction($gatewayModuleName, [
                'invoice_id' => $invoiceId,
                'payment_intent' => $paymentIntentId,
                'amount' => $amount,
                'fee' => $fee,
            ], 'Payment Successful');

            // Log activity
            logActivity('Payment Received via Stripe - Invoice ID: ' . $invoiceId . ' - Transaction ID: ' . $paymentIntentId);
        }
    } catch (\Exception $e) {
        logTransaction($gatewayModuleName, [
            'error' => $e->getMessage(),
            'payment_intent' => $paymentIntentId,
        ], 'Payment Intent Retrieval Failed');
    }
}

/**
 * Handle payment intent succeeded
 */
function handlePaymentIntentSucceeded($paymentIntent, $gatewayModuleName, $gatewayParams)
{
    $invoiceId = $paymentIntent['metadata']['invoice_id'] ?? null;
    $paymentIntentId = $paymentIntent['id'];

    if (!$invoiceId) {
        // No invoice ID in metadata - might be from checkout session
        return;
    }

    // Validate invoice exists
    $invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);

    // Check if payment already processed
    $existingPayment = Capsule::table('tblaccounts')
        ->where('transid', $paymentIntentId)
        ->where('invoiceid', $invoiceId)
        ->first();

    if ($existingPayment) {
        return;
    }

    $amount = $paymentIntent['amount'] / 100;

    // Calculate fees
    $fee = 0;
    if (!empty($paymentIntent['charges']['data'][0]['balance_transaction'])) {
        try {
            $balanceTransaction = \Stripe\BalanceTransaction::retrieve(
                $paymentIntent['charges']['data'][0]['balance_transaction']
            );
            $fee = $balanceTransaction->fee / 100;
        } catch (\Exception $e) {
            // Fee calculation failed, continue without it
        }
    }

    // Add payment to invoice
    addInvoicePayment(
        $invoiceId,
        $paymentIntentId,
        $amount,
        $fee,
        $gatewayModuleName
    );

    logTransaction($gatewayModuleName, [
        'invoice_id' => $invoiceId,
        'payment_intent' => $paymentIntentId,
        'amount' => $amount,
        'fee' => $fee,
    ], 'Payment Intent Succeeded');
}

/**
 * Handle payment intent failed
 */
function handlePaymentIntentFailed($paymentIntent, $gatewayModuleName, $gatewayParams)
{
    $invoiceId = $paymentIntent['metadata']['invoice_id'] ?? null;
    $paymentIntentId = $paymentIntent['id'];
    $errorMessage = $paymentIntent['last_payment_error']['message'] ?? 'Unknown error';

    logTransaction($gatewayModuleName, [
        'invoice_id' => $invoiceId,
        'payment_intent' => $paymentIntentId,
        'error' => $errorMessage,
    ], 'Payment Failed');

    if ($invoiceId) {
        logActivity('Payment Failed via Stripe - Invoice ID: ' . $invoiceId . ' - Error: ' . $errorMessage);
    }
}

/**
 * Handle charge refunded
 */
function handleChargeRefunded($charge, $gatewayModuleName, $gatewayParams)
{
    $paymentIntentId = $charge['payment_intent'];
    $refundAmount = $charge['amount_refunded'] / 100;

    // Find the original payment
    $payment = Capsule::table('tblaccounts')
        ->where('transid', $paymentIntentId)
        ->first();

    if (!$payment) {
        logTransaction($gatewayModuleName, [
            'payment_intent' => $paymentIntentId,
            'error' => 'Original payment not found',
        ], 'Refund Error');
        return;
    }

    logTransaction($gatewayModuleName, [
        'invoice_id' => $payment->invoiceid,
        'payment_intent' => $paymentIntentId,
        'refund_amount' => $refundAmount,
    ], 'Charge Refunded');

    // Log activity
    logActivity('Refund Processed via Stripe - Invoice ID: ' . $payment->invoiceid . ' - Amount: ' . $refundAmount);
}

/**
 * Handle dispute created
 */
function handleDisputeCreated($dispute, $gatewayModuleName, $gatewayParams)
{
    $chargeId = $dispute['charge'];
    $amount = $dispute['amount'] / 100;
    $reason = $dispute['reason'];

    logTransaction($gatewayModuleName, [
        'charge_id' => $chargeId,
        'dispute_id' => $dispute['id'],
        'amount' => $amount,
        'reason' => $reason,
    ], 'Dispute Created');

    // Find the payment
    $payment = Capsule::table('tblaccounts')
        ->where('transid', 'LIKE', '%' . $chargeId . '%')
        ->first();

    if ($payment) {
        logActivity('Payment Dispute via Stripe - Invoice ID: ' . $payment->invoiceid . ' - Reason: ' . $reason . ' - Amount: ' . $amount);
    }
}
