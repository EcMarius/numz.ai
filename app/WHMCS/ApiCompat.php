<?php

namespace App\WHMCS;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Service;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

/**
 * WHMCS API Compatibility Layer
 * Provides compatibility with WHMCS API commands
 */
class ApiCompat
{
    public static function execute($command, $postData, $adminUser = null)
    {
        $method = 'api_' . $command;

        if (method_exists(self::class, $method)) {
            try {
                return self::$method($postData);
            } catch (\Exception $e) {
                return [
                    'result' => 'error',
                    'message' => $e->getMessage(),
                ];
            }
        }

        return [
            'result' => 'error',
            'message' => "Command not found: {$command}",
        ];
    }

    protected static function api_GetClients($params)
    {
        $query = Client::query();

        if (!empty($params['search'])) {
            $query->where(function ($q) use ($params) {
                $q->where('firstname', 'like', "%{$params['search']}%")
                  ->orWhere('lastname', 'like', "%{$params['search']}%")
                  ->orWhere('email', 'like', "%{$params['search']}%");
            });
        }

        $clients = $query->limit($params['limitnum'] ?? 25)
                        ->offset($params['limitstart'] ?? 0)
                        ->get();

        return [
            'result' => 'success',
            'totalresults' => $clients->count(),
            'clients' => ['client' => $clients->toArray()],
        ];
    }

    protected static function api_GetClientsDetails($params)
    {
        $client = Client::with(['services', 'invoices'])->find($params['clientid']);

        if (!$client) {
            return ['result' => 'error', 'message' => 'Client not found'];
        }

        return [
            'result' => 'success',
            'client' => $client->toArray(),
        ];
    }

    protected static function api_GetInvoice($params)
    {
        $invoice = Invoice::with(['items', 'client'])->find($params['invoiceid']);

        if (!$invoice) {
            return ['result' => 'error', 'message' => 'Invoice not found'];
        }

        return [
            'result' => 'success',
            'invoice' => $invoice->toArray(),
        ];
    }

    protected static function api_CreateInvoice($params)
    {
        DB::beginTransaction();
        
        try {
            $invoice = Invoice::create([
                'client_id' => $params['userid'],
                'invoice_number' => 'INV-' . str_pad(Invoice::max('id') + 1, 6, '0', STR_PAD_LEFT),
                'date' => $params['date'] ?? now(),
                'due_date' => $params['duedate'] ?? now()->addDays(14),
                'status' => $params['status'] ?? 'unpaid',
            ]);

            if (!empty($params['itemdescription'])) {
                $descriptions = (array) $params['itemdescription'];
                $amounts = (array) $params['itemamount'];

                foreach ($descriptions as $index => $description) {
                    $invoice->items()->create([
                        'description' => $description,
                        'amount' => $amounts[$index] ?? 0,
                    ]);
                }
            }

            $invoice->load('items');
            $invoice->update([
                'subtotal' => $invoice->items->sum('amount'),
                'total' => $invoice->items->sum('amount'),
            ]);

            DB::commit();

            return [
                'result' => 'success',
                'invoiceid' => $invoice->id,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return ['result' => 'error', 'message' => $e->getMessage()];
        }
    }

    protected static function api_GetOrders($params)
    {
        $query = Service::with(['client', 'product']);

        if (!empty($params['userid'])) {
            $query->where('client_id', $params['userid']);
        }

        $services = $query->limit($params['limitnum'] ?? 25)->get();

        return [
            'result' => 'success',
            'totalresults' => $services->count(),
            'orders' => ['order' => $services->toArray()],
        ];
    }

    protected static function api_GetProducts($params)
    {
        $products = Product::with('productGroup')
                           ->where('is_active', true)
                           ->get();

        return [
            'result' => 'success',
            'totalresults' => $products->count(),
            'products' => ['product' => $products->toArray()],
        ];
    }

    protected static function api_AddClient($params)
    {
        $client = Client::create([
            'firstname' => $params['firstname'],
            'lastname' => $params['lastname'],
            'companyname' => $params['companyname'] ?? null,
            'email' => $params['email'],
            'address1' => $params['address1'] ?? null,
            'city' => $params['city'] ?? null,
            'state' => $params['state'] ?? null,
            'postcode' => $params['postcode'] ?? null,
            'country' => $params['country'] ?? 'US',
            'phonenumber' => $params['phonenumber'] ?? null,
            'password' => bcrypt($params['password']),
        ]);

        return [
            'result' => 'success',
            'clientid' => $client->id,
        ];
    }

    protected static function api_ModuleCreate($params)
    {
        $service = Service::find($params['serviceid']);
        
        if (!$service) {
            return ['result' => 'error', 'message' => 'Service not found'];
        }

        $result = ModuleCompat::provisionService($service);

        return [
            'result' => $result['success'] ? 'success' : 'error',
            'message' => $result['message'],
        ];
    }
}
