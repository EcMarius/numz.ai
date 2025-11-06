<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AffiliateClick extends Model
{
    use HasFactory;

    protected $fillable = [
        'affiliate_id',
        'ip_address',
        'user_agent',
        'referrer_url',
        'landing_page',
        'country',
        'device_type',
        'browser',
        'clicked_at',
        'converted',
        'converted_at',
    ];

    protected $casts = [
        'clicked_at' => 'datetime',
        'converted_at' => 'datetime',
        'converted' => 'boolean',
    ];

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    /**
     * Mark as converted
     */
    public function markAsConverted(): void
    {
        $this->update([
            'converted' => true,
            'converted_at' => now(),
        ]);
    }

    /**
     * Check if click is still valid (within cookie lifetime)
     */
    public function isValid(): bool
    {
        $cookieLifetime = $this->affiliate->tier->cookie_lifetime_days;
        return $this->clicked_at->addDays($cookieLifetime)->isFuture();
    }
}
