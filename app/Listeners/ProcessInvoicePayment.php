<?php

namespace App\Listeners;

use App\Events\InvoicePaid;
use App\Events\ServiceCreated;
use App\Models\HostingService;
use Illuminate\Support\Facades\Log;

class ProcessInvoicePayment
{
    public function handle(InvoicePaid $event): void
    {
        $invoice = $event->invoice;

        // Process each invoice item
        foreach ($invoice->items as $item) {
            if ($item->item_type === 'hosting_service') {
                $this->activateService($item->item_id);
            } elseif ($item->item_type === 'domain_registration') {
                $this->registerDomain($item->item_id);
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

    protected function registerDomain(int $domainId): void
    {
        // TODO: Implement domain registration
        Log::info("Domain registration triggered for domain ID: {$domainId}");
    }
}
