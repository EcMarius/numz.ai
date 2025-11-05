<?php

namespace App\Mail;

use App\Models\HostingService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ServiceActivated extends Mailable
{
    use Queueable, SerializesModels;

    public $service;

    public function __construct(HostingService $service)
    {
        $this->service = $service;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Service Activated - ' . $this->service->product->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.service-activated',
        );
    }
}
