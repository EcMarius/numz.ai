<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Wave\Plugins\EvenLeads\Models\Campaign;
use Illuminate\Support\Collection;

class NewLeadsFoundMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Campaign $campaign;
    public Collection $sampleLeads;
    public int $totalNewLeads;
    public int $strongMatchesCount;
    public int $partialMatchesCount;
    public string $unsubscribeUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(Campaign $campaign, Collection $sampleLeads, int $totalNewLeads)
    {
        $this->campaign = $campaign;
        $this->sampleLeads = $sampleLeads;
        $this->totalNewLeads = $totalNewLeads;

        // Calculate strong and partial matches from all new leads
        $allNewLeads = $campaign->leads()->where('created_at', '>=', now()->subHour())->get();
        $this->strongMatchesCount = $allNewLeads->where('match_type', 'strong')->count();
        $this->partialMatchesCount = $allNewLeads->where('match_type', 'partial')->count();

        // Generate unsubscribe URL
        $this->unsubscribeUrl = url('/settings/profile?unsubscribe=1&highlight=email_prefs');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->totalNewLeads . ' lead' . ($this->totalNewLeads !== 1 ? 's' : '') . ' found - ' . $this->campaign->name;

        return new Envelope(
            subject: $subject,
            using: [
                function (\Symfony\Component\Mime\Email $message) {
                    // Anti-spam headers
                    $message->getHeaders()
                        ->addTextHeader('List-Unsubscribe', '<' . $this->unsubscribeUrl . '>')
                        ->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click')
                        ->addTextHeader('Precedence', 'bulk')
                        ->addTextHeader('X-Auto-Response-Suppress', 'OOF, DR, RN, NRN, AutoReply');
                },
            ],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.new-leads-found',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
