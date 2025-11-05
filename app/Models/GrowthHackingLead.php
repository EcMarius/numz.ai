<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrowthHackingLead extends Model
{
    protected $fillable = [
        'prospect_id',
        'user_id',
        'campaign_id',
        'lead_data',
        'confidence_score',
        'copied_to_account',
        'added_at',
    ];

    protected $casts = [
        'lead_data' => 'array',
        'confidence_score' => 'decimal:2',
        'copied_to_account' => 'boolean',
        'added_at' => 'datetime',
    ];

    /**
     * Prospect this lead belongs to
     */
    public function prospect(): BelongsTo
    {
        return $this->belongsTo(GrowthHackingProspect::class, 'prospect_id');
    }

    /**
     * User account (prospect's account)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get lead title from lead_data
     */
    public function getTitleAttribute(): ?string
    {
        return $this->lead_data['title'] ?? 'Untitled Lead';
    }

    /**
     * Get lead description from lead_data
     */
    public function getDescriptionAttribute(): ?string
    {
        return $this->lead_data['description'] ?? '';
    }

    /**
     * Get platform from lead_data
     */
    public function getPlatformAttribute(): ?string
    {
        return $this->lead_data['platform'] ?? 'reddit';
    }

    /**
     * Get author from lead_data
     */
    public function getAuthorAttribute(): ?string
    {
        return $this->lead_data['author'] ?? 'Unknown';
    }

    /**
     * Get URL from lead_data
     */
    public function getUrlAttribute(): ?string
    {
        return $this->lead_data['url'] ?? '#';
    }
}
