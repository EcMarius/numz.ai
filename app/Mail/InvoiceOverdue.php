<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceOverdue extends Mailable
{
    use Queueable, SerializesModels;

    public Invoice $invoice;
    public int $daysOverdue;

    /**
     * Create a new message instance.
     */
    public function __construct(Invoice $invoice, int $daysOverdue)
    {
        $this->invoice = $invoice;
        $this->daysOverdue = $daysOverdue;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Overdue Invoice #' . $this->invoice->invoice_number . ' - ' . $this->daysOverdue . ' Days Overdue',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice-overdue',
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
