<?php

namespace App\Mail;

use App\Models\DataDeletionRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DataDeletionCompleted extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public DataDeletionRequest $deletionRequest
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Data Has Been Deleted - EvenLeads',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.data-deletion-completed',
            with: [
                'deletionRequest' => $this->deletionRequest,
                'userName' => $this->deletionRequest->user->name ?? 'User',
                'confirmationCode' => $this->deletionRequest->confirmation_code,
                'completedAt' => $this->deletionRequest->completed_at,
            ],
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
