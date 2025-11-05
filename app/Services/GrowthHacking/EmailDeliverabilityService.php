<?php

namespace App\Services\GrowthHacking;

use App\Models\GrowthHackingCampaign;
use App\Models\GrowthHackingEmail;
use App\Models\UnsubscribeRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EmailDeliverabilityService
{
    /**
     * Check if we can send email to this address
     */
    public function canSendEmail(string $email): array
    {
        // Check if unsubscribed
        if (UnsubscribeRequest::hasUnsubscribed($email)) {
            return [
                'can_send' => false,
                'reason' => 'Email has unsubscribed',
            ];
        }

        // Check if already sent in last 30 days
        $recentEmail = GrowthHackingEmail::where('email_address', $email)
            ->where('created_at', '>=', now()->subDays(30))
            ->exists();

        if ($recentEmail) {
            return [
                'can_send' => false,
                'reason' => 'Already contacted in last 30 days',
            ];
        }

        // Check rate limiting
        if (!$this->checkRateLimit()) {
            return [
                'can_send' => false,
                'reason' => 'Rate limit exceeded (max 50/hour)',
            ];
        }

        return [
            'can_send' => true,
        ];
    }

    /**
     * Check rate limiting (max 50 emails per hour)
     */
    protected function checkRateLimit(): bool
    {
        $key = 'growth_hack_emails_sent_hour';
        $count = Cache::get($key, 0);

        if ($count >= 50) {
            return false;
        }

        // Increment counter (expires in 1 hour)
        Cache::put($key, $count + 1, now()->addHour());

        return true;
    }

    /**
     * Validate email domain has proper SPF/DKIM (simplified check)
     */
    public function checkDomainReputation(string $fromEmail): array
    {
        $domain = substr(strrchr($fromEmail, '@'), 1);

        // Check if domain has MX records
        $mxRecords = [];
        if (!getmxrr($domain, $mxRecords)) {
            return [
                'valid' => false,
                'warning' => 'Domain has no MX records',
            ];
        }

        // Basic validation passed
        return [
            'valid' => true,
        ];
    }

    /**
     * Get email subject without spam triggers
     */
    public function sanitizeSubject(string $subject): string
    {
        $spamWords = [
            'FREE',
            'CLICK HERE',
            'ACT NOW',
            'LIMITED TIME',
            'AMAZING',
            'INCREDIBLE',
            'GUARANTEE',
            'RISK FREE',
            'NO CREDIT CARD',
            'URGENT',
        ];

        foreach ($spamWords as $word) {
            $subject = str_ireplace($word, ucfirst(strtolower($word)), $subject);
        }

        // Remove multiple exclamation marks
        $subject = preg_replace('/!+/', '!', $subject);

        // Remove all caps words (except acronyms < 4 chars)
        $subject = preg_replace_callback('/\b([A-Z]{4,})\b/', function($matches) {
            return ucfirst(strtolower($matches[1]));
        }, $subject);

        return $subject;
    }

    /**
     * Calculate spam score (0-100, lower is better)
     */
    public function calculateSpamScore(string $subject, string $body): int
    {
        $score = 0;

        // Check subject
        if (preg_match('/!{2,}/', $subject)) $score += 10; // Multiple exclamation marks
        if (preg_match('/\b(FREE|CLICK|ACT NOW)\b/i', $subject)) $score += 15; // Spam words
        if (preg_match('/[A-Z]{5,}/', $subject)) $score += 10; // All caps

        // Check body
        if (preg_match('/!{3,}/', $body)) $score += 10; // Excessive exclamation
        if (preg_match_all('/\b(FREE|GUARANTEE|LIMITED TIME)\b/i', $body, $matches) > 3) $score += 15; // Too many spam words
        if (str_word_count($body) < 20) $score += 20; // Too short (suspicious)

        // Check for good practices
        if (preg_match('/unsubscribe/i', $body)) $score -= 10; // Has unsubscribe
        if (str_word_count($body) >= 50 && str_word_count($body) <= 200) $score -= 10; // Good length

        return max(0, min(100, $score));
    }
}
