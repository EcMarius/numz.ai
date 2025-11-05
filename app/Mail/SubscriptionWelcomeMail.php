<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Wave\Plan;
use Wave\Subscription;
use App\Models\User;

class SubscriptionWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $subscription;
    public $plan;
    public $isTrial;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, Subscription $subscription, Plan $plan, bool $isTrial = false)
    {
        $this->user = $user;
        $this->subscription = $subscription;
        $this->plan = $plan;
        $this->isTrial = $isTrial;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->isTrial
            ? 'Welcome to Your EvenLeads Trial!'
            : 'Welcome to EvenLeads - ' . $this->plan->name;

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.subscriptions.welcome',
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
