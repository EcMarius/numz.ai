<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class GrowthHackingEmail extends Model
{
    protected $fillable = [
        'campaign_id',
        'prospect_id',
        'email_address',
        'subject',
        'body',
        'sent_at',
        'opened_at',
        'clicked_at',
        'status',
        'unsubscribe_token',
        'bounce_reason',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($email) {
            if (!$email->unsubscribe_token) {
                $email->unsubscribe_token = Str::random(64);
            }
        });
    }

    /**
     * Campaign this email belongs to
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(GrowthHackingCampaign::class, 'campaign_id');
    }

    /**
     * Prospect this email was sent to
     */
    public function prospect(): BelongsTo
    {
        return $this->belongsTo(GrowthHackingProspect::class, 'prospect_id');
    }

    /**
     * Mark email as sent
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark email as opened
     */
    public function markAsOpened(): void
    {
        if (!$this->opened_at) {
            $this->update([
                'opened_at' => now(),
            ]);

            // Increment campaign counter
            $this->campaign->increment('emails_opened');
        }
    }

    /**
     * Mark email as clicked
     */
    public function markAsClicked(): void
    {
        if (!$this->clicked_at) {
            $this->update([
                'clicked_at' => now(),
            ]);

            // Increment campaign counter
            $this->campaign->increment('emails_clicked');
        }
    }

    /**
     * Mark email as bounced
     */
    public function markAsBounced(?string $reason = null): void
    {
        $this->update([
            'status' => 'bounced',
            'bounce_reason' => $reason,
        ]);
    }

    /**
     * Mark email as unsubscribed
     */
    public function markAsUnsubscribed(): void
    {
        $this->update([
            'status' => 'unsubscribed',
        ]);
    }

    /**
     * Get body with tracking pixel and wrapped links
     */
    public function getBodyWithTracking(): string
    {
        $body = $this->body;

        // Add tracking pixel
        $trackingUrl = route('growth-hack.track-open', ['token' => $this->unsubscribe_token]);
        $trackingPixel = "<img src=\"{$trackingUrl}\" width=\"1\" height=\"1\" alt=\"\" />";

        // Wrap links with click tracking
        $body = preg_replace_callback(
            '/<a([^>]*)href=["\']([^"\']+)["\']([^>]*)>/i',
            function ($matches) {
                $clickTrackUrl = route('growth-hack.track-click', [
                    'token' => $this->unsubscribe_token,
                    'url' => urlencode($matches[2])
                ]);
                return "<a{$matches[1]}href=\"{$clickTrackUrl}\"{$matches[3]}>";
            },
            $body
        );

        return $body . $trackingPixel;
    }
}
