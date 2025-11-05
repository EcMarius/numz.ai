<?php

namespace App\Jobs;

use App\Models\GrowthHackingCampaign;
use App\Models\GrowthHackingProspect;
use App\Models\GrowthHackingEmail;
use App\Mail\GrowthHackProspectEmail;
use App\Services\GrowthHacking\EmailDeliverabilityService;
use App\Services\GrowthHacking\EmailContentGeneratorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendGrowthHackingEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public GrowthHackingCampaign $campaign,
        public GrowthHackingProspect $prospect
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        EmailDeliverabilityService $deliverability,
        EmailContentGeneratorService $emailGenerator
    ): void {
        try {
            $email = $this->prospect->primary_email;

            if (!$email) {
                Log::warning("No email for prospect {$this->prospect->id}, skipping");
                return;
            }

            // Check if we can send email
            $canSend = $deliverability->canSendEmail($email);

            if (!$canSend['can_send']) {
                Log::warning("Cannot send email to {$email}: {$canSend['reason']}");
                return;
            }

            // Generate email content if not already generated
            $subject = $this->campaign->email_subject_template;
            $body = $this->campaign->email_body_template;

            if (!$subject || !$body) {
                $result = $emailGenerator->generateEmail($this->prospect);
                if ($result['success']) {
                    $subject = $result['subject'];
                    $body = $result['body'];
                } else {
                    throw new \Exception('Failed to generate email content');
                }
            }

            // Sanitize subject
            $subject = $deliverability->sanitizeSubject($subject);

            // Calculate spam score
            $spamScore = $deliverability->calculateSpamScore($subject, $body);

            if ($spamScore > 50) {
                Log::warning("Email spam score too high ({$spamScore}) for prospect {$this->prospect->id}");
            }

            // Create email record
            $emailRecord = GrowthHackingEmail::create([
                'campaign_id' => $this->campaign->id,
                'prospect_id' => $this->prospect->id,
                'email_address' => $email,
                'subject' => $subject,
                'body' => $body,
                'status' => 'pending',
            ]);

            // Setup URL
            $setupUrl = route('growth-hack.welcome', ['token' => $this->prospect->secure_token]);

            // Unsubscribe URL
            $unsubscribeUrl = route('growth-hack.unsubscribe', ['token' => $emailRecord->unsubscribe_token]);

            // Convert to HTML
            $htmlBody = $emailGenerator->convertToHTML($body, $setupUrl, $unsubscribeUrl);

            // Configure mailer based on campaign settings
            $this->configureMailer();

            // Send email
            Mail::to($email)->send(new GrowthHackProspectEmail(
                $this->prospect,
                $subject,
                $htmlBody,
                $emailRecord->unsubscribe_token
            ));

            // Mark as sent
            $emailRecord->markAsSent();

            // Update campaign stats
            $this->campaign->increment('emails_sent');

            // Update prospect status
            $this->prospect->update(['status' => 'email_sent']);

            Log::info("Growth hack email sent to {$email}", [
                'campaign_id' => $this->campaign->id,
                'prospect_id' => $this->prospect->id,
                'spam_score' => $spamScore,
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to send growth hack email for prospect {$this->prospect->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Configure mailer based on campaign settings
     */
    protected function configureMailer(): void
    {
        if ($this->campaign->email_method === 'custom_smtp' && $this->campaign->smtpConfig) {
            $config = $this->campaign->smtpConfig->toMailConfig();

            config(['mail.mailers.smtp' => $config]);
        }
        // Otherwise use default site SMTP from .env
    }
}
