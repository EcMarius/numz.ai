<?php

namespace App\Mail;

use App\Models\HostingService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ServiceSuspended extends Mailable
{
    use Queueable, SerializesModels;

    public $service;
    public $reason;

    public function __construct(HostingService $service, $reason = 'Non-payment')
    {
        $this->service = $service;
        $this->reason = $reason;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Service Suspended - ' . $this->service->domain,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.service-suspended',
        );
    }
}
