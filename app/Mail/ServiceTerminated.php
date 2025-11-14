<?php

namespace App\Mail;

use App\Models\HostingService;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ServiceTerminated extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public HostingService $service,
        public Invoice $invoice
    ) {}

    public function build()
    {
        return $this->subject('Service Terminated - ' . $this->service->domain)
            ->markdown('emails.service-terminated');
    }
}
