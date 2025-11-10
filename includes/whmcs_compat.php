<?php
/**
 * WHMCS Compatibility Functions
 *
 * Additional helper functions for WHMCS backward compatibility
 */

use Illuminate\Support\Facades\DB;
use App\Models\Invoice;
use App\Models\User;
use App\Models\Service;
use App\Models\Transaction;

/**
 * Get gateway configuration variables
 */
function getGatewayVariables($gatewayName)
{
    $config = config("whmcs.gateways.{$gatewayName}", []);

    if (empty($config)) {
        // Try to load gateway module to get config
        $gatewayFile = base_path("modules/gateways/{$gatewayName}/{$gatewayName}.php");
        if (file_exists($gatewayFile)) {
            require_once $gatewayFile;

            $configFunc = $gatewayName . '_config';
            if (function_exists($configFunc)) {
                $moduleConfig = call_user_func($configFunc);

                // Convert module config format to params
                $config = ['type' => $gatewayName, 'name' => $gatewayName];
                foreach ($moduleConfig as $key => $field) {
                    if (is_array($field) && isset($field['Value'])) {
                        $config[$key] = $field['Value'];
                    }
                }
            }
        }
    }

    return $config;
}

/**
 * Check and validate invoice ID from callback
 */
function checkCbInvoiceID($invoiceId, $gatewayName = '')
{
    $invoice = Invoice::find($invoiceId);

    if (!$invoice) {
        logTransaction($gatewayName, ['invoice_id' => $invoiceId], 'Invoice Not Found');
        exit('Invoice Not Found');
    }

    if ($invoice->status === 'paid') {
        logTransaction($gatewayName, ['invoice_id' => $invoiceId], 'Invoice Already Paid');
        exit('Invoice Already Paid');
    }

    return $invoiceId;
}

/**
 * Check if transaction ID already exists
 */
function checkCbTransID($transactionId)
{
    if (empty($transactionId)) {
        return false;
    }

    $exists = Transaction::where('transaction_id', $transactionId)->exists();

    if ($exists) {
        logTransaction('', ['transaction_id' => $transactionId], 'Transaction Already Processed');
        exit('Transaction Already Processed');
    }

    return true;
}

/**
 * Add invoice payment
 */
function addInvoicePayment(
    $invoiceId,
    $transactionId,
    $paymentAmount,
    $paymentFee = 0,
    $gatewayModule = ''
)
{
    try {
        $invoice = Invoice::findOrFail($invoiceId);

        // Create transaction record
        $transaction = Transaction::create([
            'user_id' => $invoice->user_id,
            'invoice_id' => $invoiceId,
            'transaction_id' => $transactionId,
            'gateway' => $gatewayModule,
            'amount' => $paymentAmount,
            'fee' => $paymentFee,
            'currency' => $invoice->currency,
            'status' => 'completed',
            'description' => "Payment for Invoice #{$invoiceId}",
            'created_at' => now(),
        ]);

        // Update invoice status
        $invoice->amount_paid += $paymentAmount;

        if ($invoice->amount_paid >= $invoice->total) {
            $invoice->status = 'paid';
            $invoice->paid_at = now();

            // Run hook for invoice paid
            run_hook('InvoicePaid', [
                'invoiceid' => $invoiceId,
            ]);

            // Activate related services
            activateServicesForInvoice($invoiceId);
        } else {
            $invoice->status = 'partial';
        }

        $invoice->save();

        // Run hook for invoice payment added
        run_hook('InvoicePaymentAdded', [
            'invoiceid' => $invoiceId,
            'transid' => $transactionId,
            'amount' => $paymentAmount,
            'fee' => $paymentFee,
            'gateway' => $gatewayModule,
        ]);

        return $transaction->id;

    } catch (\Exception $e) {
        logActivity("Failed to add invoice payment: " . $e->getMessage());
        return false;
    }
}

/**
 * Activate services for paid invoice
 */
function activateServicesForInvoice($invoiceId)
{
    $services = Service::where('invoice_id', $invoiceId)
        ->where('status', 'pending')
        ->get();

    foreach ($services as $service) {
        try {
            // Update service status
            $service->status = 'active';
            $service->save();

            // Run provisioning if auto-provision is enabled
            if (config('whmcs.provisioning.auto_create', false)) {
                $moduleName = $service->product->server_module ?? null;

                if ($moduleName) {
                    $result = \App\Numz\WHMCS\ModuleLoader::callModuleFunction(
                        'servers',
                        $moduleName,
                        'CreateAccount',
                        prepareModuleParams($service)
                    );

                    if (isset($result['error'])) {
                        logActivity("Failed to provision service #{$service->id}: " . $result['error']);
                    }
                }
            }

            // Run hook
            run_hook('AfterModuleCreate', ['serviceid' => $service->id]);

        } catch (\Exception $e) {
            logActivity("Failed to activate service #{$service->id}: " . $e->getMessage());
        }
    }
}

/**
 * Prepare module parameters for service
 */
function prepareModuleParams($service)
{
    $product = $service->product;
    $user = $service->user;
    $server = $product->server ?? null;

    return [
        'serviceid' => $service->id,
        'userid' => $user->id,
        'productid' => $product->id,
        'serverid' => $server->id ?? 0,
        'domain' => $service->domain,
        'username' => $service->username,
        'password' => $service->password ? decrypt($service->password) : '',
        'serverip' => $server->ip_address ?? '',
        'serverport' => $server->port ?? 0,
        'serverusername' => $server->username ?? '',
        'serverpassword' => $server->password ? decrypt($server->password) : '',
        'serversecure' => $server->secure ?? true,
        'clientsdetails' => [
            'userid' => $user->id,
            'firstname' => $user->first_name,
            'lastname' => $user->last_name,
            'companyname' => $user->company,
            'email' => $user->email,
            'address1' => $user->address1,
            'address2' => $user->address2,
            'city' => $user->city,
            'state' => $user->state,
            'postcode' => $user->postcode,
            'country' => $user->country,
            'phonenumber' => $user->phone,
        ],
        'customfields' => $service->custom_fields ?? [],
        'configoptions' => $service->config_options ?? [],
    ];
}

/**
 * Log transaction
 */
function logTransaction($gateway, $data, $action = '')
{
    try {
        DB::table('tblmodulelog')->insert([
            'module' => $gateway ?: 'Gateway',
            'action' => $action,
            'request' => is_array($data) ? json_encode($data) : $data,
            'response' => '',
            'arrdata' => '',
            'created_at' => now(),
        ]);
    } catch (\Exception $e) {
        \Log::error('Failed to log transaction: ' . $e->getMessage());
    }
}

/**
 * Get currency
 */
function getCurrency($userId = 0, $currencyId = 0)
{
    if ($currencyId > 0) {
        return DB::table('currencies')->where('id', $currencyId)->first();
    }

    if ($userId > 0) {
        $user = User::find($userId);
        if ($user && $user->currency) {
            return DB::table('currencies')->where('code', $user->currency)->first();
        }
    }

    // Return default currency
    $defaultCode = config('whmcs.currency.default', 'USD');
    return DB::table('currencies')->where('code', $defaultCode)->first();
}

/**
 * Format currency amount
 */
function formatCurrency($amount, $currencyId = 0)
{
    $currency = getCurrency(0, $currencyId);

    if (!$currency) {
        return '$' . number_format($amount, 2);
    }

    $prefix = $currency->prefix ?? '$';
    $suffix = $currency->suffix ?? '';
    $decimals = $currency->decimals ?? 2;

    return $prefix . number_format($amount, $decimals) . $suffix;
}

/**
 * Send email message
 */
function sendMessage($messageName, $relId = 0, $customVars = [])
{
    try {
        // This would integrate with your email system
        // For now, just log it
        logActivity("Email '{$messageName}' queued for sending (ID: {$relId})");
        return true;
    } catch (\Exception $e) {
        logActivity("Failed to send email: " . $e->getMessage());
        return false;
    }
}

/**
 * Get configuration value
 */
function getConfigValue($setting)
{
    return DB::table('tblconfiguration')
        ->where('setting', $setting)
        ->value('value');
}

/**
 * Update configuration value
 */
function updateConfigValue($setting, $value)
{
    return DB::table('tblconfiguration')
        ->updateOrInsert(
            ['setting' => $setting],
            ['value' => $value, 'updated_at' => now()]
        );
}

/**
 * Decrypt value (WHMCS uses special encryption)
 */
function whmcs_decrypt($string)
{
    // In Laravel, we use the built-in decrypt
    try {
        return decrypt($string);
    } catch (\Exception $e) {
        return $string;
    }
}

/**
 * Encrypt value (WHMCS uses special encryption)
 */
function whmcs_encrypt($string)
{
    // In Laravel, we use the built-in encrypt
    try {
        return encrypt($string);
    } catch (\Exception $e) {
        return $string;
    }
}

/**
 * Get admin details
 */
function getAdminDetails($adminId = 0)
{
    if ($adminId > 0) {
        return DB::table('admins')->where('id', $adminId)->first();
    }

    // Return first admin
    return DB::table('admins')->first();
}

/**
 * Check if function is available
 */
function whmcs_function_exists($functionName)
{
    return function_exists($functionName);
}

/**
 * Safe redirect
 */
function whmcs_redirect($url, $queryString = '')
{
    if ($queryString) {
        $url .= (strpos($url, '?') !== false ? '&' : '?') . $queryString;
    }

    header('Location: ' . $url);
    exit;
}

/**
 * Get language string
 */
function getLang($key)
{
    return __('whmcs.' . $key);
}
