<?php

namespace App\Console\Commands;

use App\Numz\Services\InvoiceService;
use App\Mail\InvoiceCreated;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class GenerateRenewalInvoices extends Command
{
    protected $signature = 'numz:generate-invoices';
    protected $description = 'Generate renewal invoices for services and domains';

    public function handle(InvoiceService $invoiceService)
    {
        $this->info('Generating renewal invoices...');

        $invoices = $invoiceService->generateRenewalInvoices();

        $this->info('Generated ' . count($invoices) . ' invoices');

        // Send invoice emails
        foreach ($invoices as $invoice) {
            try {
                Mail::to($invoice->user->email)->send(new InvoiceCreated($invoice));
                $this->info("Sent invoice email to {$invoice->user->email}");
            } catch (\Exception $e) {
                $this->error("Failed to send email: {$e->getMessage()}");
            }
        }

        $this->info('Invoice generation completed!');

        return 0;
    }
}
