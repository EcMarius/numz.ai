<?php

/**
 * WHMCS Compatibility Functions
 * These functions provide backwards compatibility with WHMCS modules and themes
 */

if (!function_exists('localAPI')) {
    /**
     * Execute a local API call (WHMCS compatible)
     */
    function localAPI($command, $postData = [], $adminUser = null) {
        return \App\WHMCS\ApiCompat::execute($command, $postData, $adminUser);
    }
}

if (!function_exists('getRegistrarConfigOptions')) {
    function getRegistrarConfigOptions($registrar) {
        return \App\WHMCS\ModuleCompat::getRegistrarConfig($registrar);
    }
}

if (!function_exists('getGatewayVariables')) {
    function getGatewayVariables($gateway) {
        return \App\WHMCS\ModuleCompat::getGatewayConfig($gateway);
    }
}

if (!function_exists('logTransaction')) {
    function logTransaction($gateway, $data, $result) {
        return \App\Models\Transaction::create([
            'client_id' => $data['client_id'] ?? null,
            'invoice_id' => $data['invoice_id'] ?? null,
            'transaction_id' => $data['transaction_id'] ?? uniqid(),
            'gateway' => $gateway,
            'date' => now(),
            'description' => $data['description'] ?? '',
            'amount_in' => $data['amount'] ?? 0,
            'status' => $result,
            'gateway_response' => $data,
        ]);
    }
}

if (!function_exists('addInvoicePayment')) {
    function addInvoicePayment($invoiceId, $transactionId, $amount, $fees = 0, $gateway = '') {
        $invoice = \App\Models\Invoice::find($invoiceId);
        if ($invoice) {
            $invoice->update([
                'status' => 'paid',
                'date_paid' => now(),
                'payment_method' => $gateway,
            ]);

            \App\Models\Transaction::create([
                'client_id' => $invoice->client_id,
                'invoice_id' => $invoiceId,
                'transaction_id' => $transactionId,
                'gateway' => $gateway,
                'date' => now(),
                'amount_in' => $amount,
                'fees' => $fees,
                'status' => 'success',
            ]);

            return true;
        }
        return false;
    }
}

if (!function_exists('encryptPassword')) {
    function encryptPassword($password) {
        return encrypt($password);
    }
}

if (!function_exists('decryptPassword')) {
    function decryptPassword($encrypted) {
        try {
            return decrypt($encrypted);
        } catch (\Exception $e) {
            return $encrypted;
        }
    }
}

if (!function_exists('getClientDetails')) {
    function getClientDetails($clientId) {
        $client = \App\Models\Client::with(['services', 'invoices'])->find($clientId);
        return $client ? $client->toArray() : [];
    }
}

if (!function_exists('sendMessage')) {
    function sendMessage($template, $clientId, $customVars = []) {
        // Email sending logic
        return \App\Services\EmailService::send($template, $clientId, $customVars);
    }
}
