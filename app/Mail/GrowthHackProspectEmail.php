<?php

namespace App\Mail;

use App\Models\GrowthHackingProspect;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GrowthHackProspectEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public GrowthHackingProspect $prospect,
        public string $emailSubject,
        public string $htmlBody,
        public string $unsubscribeToken
    ) {}

    /**
     * Build the message.
     */
    public function build()
    {
        $unsubscribeUrl = route('growth-hack.unsubscribe', ['token' => $this->unsubscribeToken]);

        return $this
            ->subject($this->emailSubject)
            ->html($this->htmlBody)
            ->withSymfonyMessage(function ($message) use ($unsubscribeUrl) {
                $headers = $message->getHeaders();

                // Add List-Unsubscribe header (RFC 2369)
                $headers->addTextHeader('List-Unsubscribe', "<{$unsubscribeUrl}>");
                $headers->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');

                // Add other deliverability headers
                $headers->addTextHeader('X-Auto-Response-Suppress', 'OOF, DR, RN, NRN, AutoReply');
                $headers->addTextHeader('Precedence', 'bulk');
            });
    }
}
