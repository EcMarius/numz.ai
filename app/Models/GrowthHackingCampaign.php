<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GrowthHackingCampaign extends Model
{
    protected $fillable = [
        'admin_user_id',
        'name',
        'description',
        'website_urls',
        'email_method',
        'smtp_config_id',
        'custom_smtp_config',
        'auto_create_accounts',
        'email_subject_template',
        'email_body_template',
        'status',
        'total_prospects',
        'emails_sent',
        'accounts_created',
        'emails_opened',
        'emails_clicked',
        'logged_in_count',
    ];

    protected $casts = [
        'auto_create_accounts' => 'boolean',
        'custom_smtp_config' => 'array',
        'total_prospects' => 'integer',
        'emails_sent' => 'integer',
        'accounts_created' => 'integer',
        'emails_opened' => 'integer',
        'emails_clicked' => 'integer',
        'logged_in_count' => 'integer',
    ];

    /**
     * Admin who created this campaign
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    /**
     * SMTP configuration used
     */
    public function smtpConfig(): BelongsTo
    {
        return $this->belongsTo(SmtpConfig::class);
    }

    /**
     * Prospects in this campaign
     */
    public function prospects(): HasMany
    {
        return $this->hasMany(GrowthHackingProspect::class, 'campaign_id');
    }

    /**
     * Emails sent in this campaign
     */
    public function emails(): HasMany
    {
        return $this->hasMany(GrowthHackingEmail::class, 'campaign_id');
    }

    /**
     * Get website URLs as array
     */
    public function getWebsiteUrlsArrayAttribute(): array
    {
        return array_filter(array_map('trim', explode("\n", $this->website_urls)));
    }

    /**
     * Calculate open rate percentage
     */
    public function getOpenRateAttribute(): float
    {
        if ($this->emails_sent === 0) {
            return 0;
        }
        return round(($this->emails_opened / $this->emails_sent) * 100, 2);
    }

    /**
     * Calculate click rate percentage
     */
    public function getClickRateAttribute(): float
    {
        if ($this->emails_sent === 0) {
            return 0;
        }
        return round(($this->emails_clicked / $this->emails_sent) * 100, 2);
    }

    /**
     * Calculate conversion rate (logged in / emails sent)
     */
    public function getConversionRateAttribute(): float
    {
        if ($this->emails_sent === 0) {
            return 0;
        }
        return round(($this->logged_in_count / $this->emails_sent) * 100, 2);
    }
}
