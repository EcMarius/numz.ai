<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class GrowthHackingProspect extends Model
{
    protected $fillable = [
        'campaign_id',
        'website_url',
        'business_name',
        'email',
        'phone',
        'contact_person_name',
        'contact_person_email',
        'inbound_links',
        'website_content',
        'ai_analysis',
        'status',
        'user_id',
        'secure_token',
        'token_expires_at',
        'leads_found',
    ];

    protected $casts = [
        'inbound_links' => 'array',
        'ai_analysis' => 'array',
        'token_expires_at' => 'datetime',
        'leads_found' => 'integer',
    ];

    /**
     * Campaign this prospect belongs to
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(GrowthHackingCampaign::class, 'campaign_id');
    }

    /**
     * User account created for this prospect
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Leads found for this prospect
     */
    public function leads(): HasMany
    {
        return $this->hasMany(GrowthHackingLead::class, 'prospect_id');
    }

    /**
     * Email sent to this prospect
     */
    public function email(): BelongsTo
    {
        return $this->belongsTo(GrowthHackingEmail::class, 'id', 'prospect_id');
    }

    /**
     * Generate a secure token for password setup
     */
    public function generateSecureToken(): void
    {
        $this->secure_token = Str::random(64);
        $this->token_expires_at = now()->addDays(7);
        $this->save();
    }

    /**
     * Check if token is valid
     */
    public function isTokenValid(): bool
    {
        return $this->secure_token && $this->token_expires_at && $this->token_expires_at->isFuture();
    }

    /**
     * Get primary email (prefer contact_person_email, fallback to email)
     */
    public function getPrimaryEmailAttribute(): ?string
    {
        return $this->contact_person_email ?? $this->email;
    }

    /**
     * Get display name (prefer contact_person_name, fallback to business_name)
     */
    public function getDisplayNameAttribute(): ?string
    {
        return $this->contact_person_name ?? $this->business_name ?? 'there';
    }

    /**
     * Get business industry from AI analysis
     */
    public function getIndustryAttribute(): ?string
    {
        return $this->ai_analysis['industry'] ?? 'unknown';
    }

    /**
     * Get business description from AI analysis
     */
    public function getBusinessDescriptionAttribute(): ?string
    {
        return $this->ai_analysis['description'] ?? '';
    }
}
