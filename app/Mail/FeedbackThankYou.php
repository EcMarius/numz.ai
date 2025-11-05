<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Wave\Plugins\EvenLeads\Models\Feedback;

class FeedbackThankYou extends Mailable
{
    use Queueable, SerializesModels;

    public $feedback;

    public function __construct(Feedback $feedback)
    {
        $this->feedback = $feedback;
    }

    public function build()
    {
        return $this->subject('Thank You for Your Feedback!')
            ->markdown('emails.feedback.thank-you');
    }
}
