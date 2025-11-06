<?php

namespace App\Mail;

use App\Models\DomainRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DomainRenewalReminder extends Mailable
{
    use Queueable, SerializesModels;

    public $domain;
    public $daysUntilExpiry;

    public function __construct(DomainRegistration $domain)
    {
        $this->domain = $domain;
        $this->daysUntilExpiry = now()->diffInDays($domain->expiry_date);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Domain Renewal Reminder - ' . $this->domain->domain,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.domain-renewal-reminder',
        );
    }
}
