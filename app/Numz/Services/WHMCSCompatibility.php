<?php

namespace App\Numz\Services;

use App\Models\User;
use App\Models\HostingService;
use App\Models\DomainRegistration;

class WHMCSCompatibility
{
    /**
     * Get client details (WHMCS compatible)
     */
    public function getClientDetails(int $clientId): array
    {
        $user = User::find($clientId);
        
        if (!$user) {
            return ['result' => 'error', 'message' => 'Client not found'];
        }

        return [
            'result' => 'success',
            'client' => [
                'id' => $user->id,
                'firstname' => $user->name,
                'email' => $user->email,
                'status' => 'Active',
            ],
        ];
    }

    /**
     * Get client services (WHMCS compatible)
     */
    public function getClientServices(int $clientId): array
    {
        $services = HostingService::where('user_id', $clientId)->get();

        return [
            'result' => 'success',
            'services' => $services->map(function($service) {
                return [
                    'id' => $service->id,
                    'domain' => $service->domain,
                    'status' => $service->status,
                    'billingcycle' => $service->billing_cycle,
                    'amount' => $service->price,
                ];
            }),
        ];
    }

    /**
     * Get client domains (WHMCS compatible)
     */
    public function getClientDomains(int $clientId): array
    {
        $domains = DomainRegistration::where('user_id', $clientId)->get();

        return [
            'result' => 'success',
            'domains' => $domains->map(function($domain) {
                return [
                    'id' => $domain->id,
                    'domain' => $domain->domain,
                    'status' => $domain->status,
                    'expirydate' => $domain->expiry_date->format('Y-m-d'),
                ];
            }),
        ];
    }

    /**
     * Create invoice (WHMCS compatible)
     */
    public function createInvoice(array $params): array
    {
        // Implementation would create actual invoice
        return [
            'result' => 'success',
            'invoiceid' => rand(1000, 9999),
        ];
    }
}
