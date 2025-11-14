<?php

namespace App\Mail;

use App\Models\DomainRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DomainRegistered extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public DomainRegistration $domain
    ) {}

    public function build()
    {
        return $this->subject('Domain Registered Successfully - ' . $this->domain->domain)
            ->markdown('emails.domain-registered');
    }
}
