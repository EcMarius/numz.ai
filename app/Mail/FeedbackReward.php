<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Wave\Plugins\EvenLeads\Models\Feedback;

class FeedbackReward extends Mailable
{
    use Queueable, SerializesModels;

    public $feedback;
    public $rewardDetails;

    public function __construct(Feedback $feedback, string $rewardDetails)
    {
        $this->feedback = $feedback;
        $this->rewardDetails = $rewardDetails;
    }

    public function build()
    {
        return $this->subject('Thank You! Your Feedback Has Been Rewarded')
            ->markdown('emails.feedback.reward');
    }
}
