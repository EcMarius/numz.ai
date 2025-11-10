<?php
/**
 * Example Gateway Callback Handler
 *
 * Processes payment notifications from the gateway
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
    die('Module Not Activated');
}

// Get callback data
$invoiceId = $_POST['invoice_id'] ?? '';
$transactionId = $_POST['transaction_id'] ?? '';
$transactionStatus = $_POST['status'] ?? '';
$amount = $_POST['amount'] ?? 0;
$signature = $_POST['signature'] ?? '';

// Validate signature
$dataToVerify = [
    'invoice_id' => $invoiceId,
    'transaction_id' => $transactionId,
    'status' => $transactionStatus,
    'amount' => $amount,
];

$expectedSignature = hash_hmac('sha256', json_encode($dataToVerify), $gatewayParams['api_secret']);

if (!hash_equals($expectedSignature, $signature)) {
    // Invalid signature
    logTransaction($gatewayModuleName, $_POST, 'Invalid Signature');
    die('Invalid Signature');
}

// Validate invoice exists
$invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);

// Check transaction hasn't been processed before
checkCbTransID($transactionId);

// Log callback
logTransaction($gatewayParams['name'], $_POST, 'Callback Received');

// Process based on status
switch ($transactionStatus) {
    case 'completed':
    case 'success':
        // Payment successful
        addInvoicePayment(
            $invoiceId,
            $transactionId,
            $amount,
            0, // Payment fee (optional)
            $gatewayModuleName
        );

        // Log success
        logActivity('Payment Received via ' . $gatewayParams['name'] . ' - Invoice ID: ' . $invoiceId . ' - Transaction ID: ' . $transactionId);

        // Optionally send email confirmation
        // sendMessage('Invoice Payment Confirmation', $invoiceId);

        echo 'Payment recorded successfully';
        break;

    case 'pending':
        // Payment pending
        logTransaction($gatewayParams['name'], $_POST, 'Payment Pending');
        echo 'Payment pending';
        break;

    case 'failed':
    case 'declined':
        // Payment failed
        logTransaction($gatewayParams['name'], $_POST, 'Payment Failed');

        // Optionally notify admin
        logActivity('Payment Failed via ' . $gatewayParams['name'] . ' - Invoice ID: ' . $invoiceId);

        echo 'Payment failed';
        break;

    case 'refunded':
        // Payment refunded
        // You might want to handle refunds here
        logTransaction($gatewayParams['name'], $_POST, 'Payment Refunded');
        echo 'Refund processed';
        break;

    default:
        // Unknown status
        logTransaction($gatewayParams['name'], $_POST, 'Unknown Status: ' . $transactionStatus);
        echo 'Unknown status';
        break;
}
