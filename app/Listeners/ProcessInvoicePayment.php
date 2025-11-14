<?php

namespace App\Listeners;

use App\Events\InvoicePaid;
use App\Events\ServiceCreated;
use App\Models\HostingService;
use App\Models\DomainRegistration;
use App\Numz\Services\DomainRegistrationService;
use App\Mail\DomainRegistered;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProcessInvoicePayment
{
    public function __construct(
        protected DomainRegistrationService $domainService
    ) {}

    public function handle(InvoicePaid $event): void
    {
        $invoice = $event->invoice;

        // Process each invoice item
        foreach ($invoice->items as $item) {
            if ($item->item_type === 'hosting_service' || $item->item_type === 'service') {
                $this->activateService($item->item_id);
            } elseif ($item->item_type === 'domain_registration' || $item->item_type === 'domain') {
                $this->registerDomain($item->item_id, $item->quantity);
            }
        }
    }

    protected function activateService(int $serviceId): void
    {
        $service = HostingService::find($serviceId);

        if (!$service) {
            return;
        }

        // Fire event to provision service
        event(new ServiceCreated($service));
    }

    protected function registerDomain(int $domainId, int $years = 1): void
    {
        try {
            $domain = DomainRegistration::find($domainId);

            if (!$domain) {
                Log::warning("Domain not found for registration", ['domain_id' => $domainId]);
                return;
            }

            // Skip if already registered
            if ($domain->status === 'active' && $domain->registration_date) {
                Log::info("Domain already registered, processing as renewal", [
                    'domain' => $domain->domain,
                ]);

                // This is a renewal
                $result = $this->domainService->renewDomain($domain, $years);
            } else {
                // New registration
                $result = $this->domainService->registerDomain($domain, $years);
            }

            if ($result['success']) {
                // Send confirmation email
                Mail::to($domain->user->email)->queue(new DomainRegistered($domain));

                Log::info("Domain processed successfully", [
                    'domain' => $domain->domain,
                    'type' => $domain->registration_date ? 'renewal' : 'registration',
                ]);
            } else {
                Log::error("Domain registration/renewal failed", [
                    'domain' => $domain->domain,
                    'message' => $result['message'] ?? 'Unknown error',
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Domain processing exception", [
                'domain_id' => $domainId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
