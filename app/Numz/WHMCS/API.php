<?php

namespace App\Numz\WHMCS;

use App\Models\User;
use App\Models\Service;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Domain;
use App\Models\Ticket;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * WHMCS API Compatibility Layer
 *
 * Provides full backward compatibility with WHMCS API commands
 */
class API
{
    /**
     * API credentials
     */
    protected static ?string $identifier = null;
    protected static ?string $secret = null;

    /**
     * Last API response
     */
    protected static ?array $lastResponse = null;

    /**
     * Initialize API
     */
    public static function init(string $identifier = null, string $secret = null): void
    {
        self::$identifier = $identifier;
        self::$secret = $secret;
    }

    /**
     * Execute API command
     *
     * @param string $command API command name
     * @param array $params Command parameters
     * @param string $adminUser Admin username (for internal API)
     * @return array API response
     */
    public static function execute(string $command, array $params = [], string $adminUser = null): array
    {
        try {
            $method = 'command' . $command;

            if (!method_exists(self::class, $method)) {
                return self::error("Command '{$command}' not found");
            }

            // Execute the command
            $result = self::$method($params, $adminUser);

            self::$lastResponse = $result;

            return $result;
        } catch (\Exception $e) {
            Log::error("WHMCS API Error: {$command}", [
                'error' => $e->getMessage(),
                'params' => $params,
            ]);

            return self::error($e->getMessage());
        }
    }

    /**
     * Success response
     */
    protected static function success(array $data = []): array
    {
        return array_merge([
            'result' => 'success',
        ], $data);
    }

    /**
     * Error response
     */
    protected static function error(string $message): array
    {
        return [
            'result' => 'error',
            'message' => $message,
        ];
    }

    // ===========================================
    // CLIENT API COMMANDS
    // ===========================================

    /**
     * AddClient - Add a new client
     */
    protected static function commandAddClient(array $params): array
    {
        $user = User::create([
            'name' => $params['firstname'] . ' ' . $params['lastname'],
            'first_name' => $params['firstname'],
            'last_name' => $params['lastname'],
            'company' => $params['companyname'] ?? null,
            'email' => $params['email'],
            'password' => Hash::make($params['password'] ?? \Str::random(16)),
            'address1' => $params['address1'] ?? null,
            'address2' => $params['address2'] ?? null,
            'city' => $params['city'] ?? null,
            'state' => $params['state'] ?? null,
            'postcode' => $params['postcode'] ?? null,
            'country' => $params['country'] ?? null,
            'phone' => $params['phonenumber'] ?? null,
        ]);

        return self::success([
            'clientid' => $user->id,
        ]);
    }

    /**
     * GetClientsDetails - Get client details
     */
    protected static function commandGetClientsDetails(array $params): array
    {
        $user = null;

        if (isset($params['clientid'])) {
            $user = User::find($params['clientid']);
        } elseif (isset($params['email'])) {
            $user = User::where('email', $params['email'])->first();
        }

        if (!$user) {
            return self::error("Client not found");
        }

        return self::success([
            'clientid' => $user->id,
            'userid' => $user->id,
            'firstname' => $user->first_name,
            'lastname' => $user->last_name,
            'fullname' => $user->name,
            'companyname' => $user->company,
            'email' => $user->email,
            'address1' => $user->address1,
            'address2' => $user->address2,
            'city' => $user->city,
            'state' => $user->state,
            'postcode' => $user->postcode,
            'country' => $user->country,
            'phonenumber' => $user->phone,
            'datecreated' => $user->created_at->format('Y-m-d'),
            'status' => $user->status ?? 'Active',
        ]);
    }

    /**
     * UpdateClient - Update client details
     */
    protected static function commandUpdateClient(array $params): array
    {
        $user = User::find($params['clientid']);

        if (!$user) {
            return self::error("Client not found");
        }

        $updateData = [];

        if (isset($params['firstname'])) $updateData['first_name'] = $params['firstname'];
        if (isset($params['lastname'])) $updateData['last_name'] = $params['lastname'];
        if (isset($params['companyname'])) $updateData['company'] = $params['companyname'];
        if (isset($params['email'])) $updateData['email'] = $params['email'];
        if (isset($params['address1'])) $updateData['address1'] = $params['address1'];
        if (isset($params['address2'])) $updateData['address2'] = $params['address2'];
        if (isset($params['city'])) $updateData['city'] = $params['city'];
        if (isset($params['state'])) $updateData['state'] = $params['state'];
        if (isset($params['postcode'])) $updateData['postcode'] = $params['postcode'];
        if (isset($params['country'])) $updateData['country'] = $params['country'];
        if (isset($params['phonenumber'])) $updateData['phone'] = $params['phonenumber'];

        if (isset($params['firstname']) || isset($params['lastname'])) {
            $updateData['name'] = ($params['firstname'] ?? $user->first_name) . ' ' . ($params['lastname'] ?? $user->last_name);
        }

        $user->update($updateData);

        return self::success([
            'clientid' => $user->id,
        ]);
    }

    // ===========================================
    // PRODUCT/SERVICE API COMMANDS
    // ===========================================

    /**
     * ModuleCreate - Create a service
     */
    protected static function commandModuleCreate(array $params): array
    {
        $service = Service::find($params['serviceid'] ?? $params['accountid']);

        if (!$service) {
            return self::error("Service not found");
        }

        // Get the module
        $moduleName = $service->product->server_module;

        if (!$moduleName) {
            return self::error("No module configured for this product");
        }

        // Prepare module parameters
        $moduleParams = self::prepareModuleParams($service);

        // Call module create function
        $result = ModuleLoader::callModuleFunction('servers', $moduleName, 'CreateAccount', $moduleParams);

        if (isset($result['error'])) {
            return self::error($result['error']);
        }

        // Update service with module response
        if (isset($result['username'])) {
            $service->username = $result['username'];
        }
        if (isset($result['password'])) {
            $service->password = encrypt($result['password']);
        }

        $service->save();

        return self::success($result);
    }

    /**
     * ModuleSuspend - Suspend a service
     */
    protected static function commandModuleSuspend(array $params): array
    {
        $service = Service::find($params['serviceid'] ?? $params['accountid']);

        if (!$service) {
            return self::error("Service not found");
        }

        $moduleName = $service->product->server_module;
        $moduleParams = self::prepareModuleParams($service);

        $result = ModuleLoader::callModuleFunction('servers', $moduleName, 'SuspendAccount', $moduleParams);

        if (isset($result['error'])) {
            return self::error($result['error']);
        }

        $service->update(['status' => 'suspended']);

        return self::success($result);
    }

    /**
     * ModuleUnsuspend - Unsuspend a service
     */
    protected static function commandModuleUnsuspend(array $params): array
    {
        $service = Service::find($params['serviceid'] ?? $params['accountid']);

        if (!$service) {
            return self::error("Service not found");
        }

        $moduleName = $service->product->server_module;
        $moduleParams = self::prepareModuleParams($service);

        $result = ModuleLoader::callModuleFunction('servers', $moduleName, 'UnsuspendAccount', $moduleParams);

        if (isset($result['error'])) {
            return self::error($result['error']);
        }

        $service->update(['status' => 'active']);

        return self::success($result);
    }

    /**
     * ModuleTerminate - Terminate a service
     */
    protected static function commandModuleTerminate(array $params): array
    {
        $service = Service::find($params['serviceid'] ?? $params['accountid']);

        if (!$service) {
            return self::error("Service not found");
        }

        $moduleName = $service->product->server_module;
        $moduleParams = self::prepareModuleParams($service);

        $result = ModuleLoader::callModuleFunction('servers', $moduleName, 'TerminateAccount', $moduleParams);

        if (isset($result['error'])) {
            return self::error($result['error']);
        }

        $service->update(['status' => 'terminated']);

        return self::success($result);
    }

    // ===========================================
    // INVOICE API COMMANDS
    // ===========================================

    /**
     * CreateInvoice - Create a new invoice
     */
    protected static function commandCreateInvoice(array $params): array
    {
        $invoice = Invoice::create([
            'user_id' => $params['userid'],
            'invoice_number' => $params['invoicenum'] ?? Invoice::generateNumber(),
            'due_date' => $params['duedate'] ?? now()->addDays(14),
            'subtotal' => 0,
            'tax' => 0,
            'total' => 0,
            'status' => $params['status'] ?? 'unpaid',
            'notes' => $params['notes'] ?? null,
        ]);

        return self::success([
            'invoiceid' => $invoice->id,
        ]);
    }

    /**
     * GetInvoice - Get invoice details
     */
    protected static function commandGetInvoice(array $params): array
    {
        $invoice = Invoice::find($params['invoiceid']);

        if (!$invoice) {
            return self::error("Invoice not found");
        }

        return self::success([
            'invoiceid' => $invoice->id,
            'invoicenum' => $invoice->invoice_number,
            'userid' => $invoice->user_id,
            'date' => $invoice->created_at->format('Y-m-d'),
            'duedate' => $invoice->due_date->format('Y-m-d'),
            'datepaid' => $invoice->paid_at?->format('Y-m-d'),
            'subtotal' => number_format($invoice->subtotal, 2, '.', ''),
            'tax' => number_format($invoice->tax, 2, '.', ''),
            'total' => number_format($invoice->total, 2, '.', ''),
            'balance' => number_format($invoice->total, 2, '.', ''),
            'status' => $invoice->status,
            'items' => $invoice->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'description' => $item->description,
                    'amount' => number_format($item->amount, 2, '.', ''),
                ];
            })->toArray(),
        ]);
    }

    // ===========================================
    // DOMAIN API COMMANDS
    // ===========================================

    /**
     * DomainRegister - Register a domain
     */
    protected static function commandDomainRegister(array $params): array
    {
        $domain = Domain::create([
            'user_id' => $params['userid'] ?? $params['clientid'],
            'domain' => $params['domainname'],
            'registrar' => $params['registrar'] ?? null,
            'registration_period' => $params['regperiod'] ?? 1,
            'status' => 'pending',
        ]);

        // Call registrar module if configured
        if ($domain->registrar) {
            $result = ModuleLoader::callModuleFunction('registrars', $domain->registrar, 'RegisterDomain', [
                'domainname' => $domain->domain,
                'regperiod' => $domain->registration_period,
            ]);

            if (isset($result['error'])) {
                return self::error($result['error']);
            }

            $domain->update(['status' => 'active']);
        }

        return self::success([
            'domainid' => $domain->id,
        ]);
    }

    // ===========================================
    // HELPER METHODS
    // ===========================================

    /**
     * Prepare module parameters
     */
    protected static function prepareModuleParams(Service $service): array
    {
        $product = $service->product;
        $user = $service->user;

        return [
            'serviceid' => $service->id,
            'productid' => $product->id,
            'serverid' => $product->server_id,
            'domain' => $service->domain,
            'username' => $service->username,
            'password' => $service->password ? decrypt($service->password) : null,
            'clientsdetails' => [
                'userid' => $user->id,
                'firstname' => $user->first_name,
                'lastname' => $user->last_name,
                'email' => $user->email,
                'fullname' => $user->name,
                'companyname' => $user->company,
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
     * Get last API response
     */
    public static function getLastResponse(): ?array
    {
        return self::$lastResponse;
    }
}

/**
 * Global helper function for local API calls (WHMCS compatibility)
 */
if (!function_exists('localAPI')) {
    function localAPI(string $command, array $params = [], string $adminUser = null): array
    {
        return \App\Numz\WHMCS\API::execute($command, $params, $adminUser);
    }
}
