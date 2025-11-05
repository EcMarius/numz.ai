<?php

namespace App\Mail;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketAssigned extends Mailable
{
    use Queueable, SerializesModels;

    public SupportTicket $ticket;
    public User $assignedTo;

    /**
     * Create a new message instance.
     */
    public function __construct(SupportTicket $ticket, User $assignedTo)
    {
        $this->ticket = $ticket;
        $this->assignedTo = $assignedTo;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Ticket #' . $this->ticket->ticket_number . ' Assigned to You - ' . $this->ticket->subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.ticket-assigned',
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
