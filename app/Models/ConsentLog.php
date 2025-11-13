<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsentLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'consent_type',
        'consent_text',
        'ip_address',
        'user_agent',
        'consented_at',
        'withdrawn_at',
        'version',
    ];

    protected $casts = [
        'consented_at' => 'datetime',
        'withdrawn_at' => 'datetime',
    ];

    /**
     * Consent types
     */
    const TYPE_PRIVACY_POLICY = 'privacy_policy';
    const TYPE_TERMS_OF_SERVICE = 'terms_of_service';
    const TYPE_MARKETING = 'marketing';
    const TYPE_COOKIES = 'cookies';
    const TYPE_DATA_PROCESSING = 'data_processing';

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if consent is active
     */
    public function isActive(): bool
    {
        return $this->consented_at && !$this->withdrawn_at;
    }

    /**
     * Withdraw consent
     */
    public function withdraw(): bool
    {
        $this->withdrawn_at = now();
        return $this->save();
    }

    /**
     * Scope for active consents
     */
    public function scopeActive($query)
    {
        return $query->whereNotNull('consented_at')->whereNull('withdrawn_at');
    }

    /**
     * Scope for withdrawn consents
     */
    public function scopeWithdrawn($query)
    {
        return $query->whereNotNull('withdrawn_at');
    }

    /**
     * Scope by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('consent_type', $type);
    }
}
