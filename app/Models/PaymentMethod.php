<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'gateway',
        'gateway_payment_method_id',
        'type',
        'last_four',
        'brand',
        'exp_month',
        'exp_year',
        'holder_name',
        'is_default',
        'is_verified',
        'metadata',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_verified' => 'boolean',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get masked card number
     */
    public function getMaskedNumberAttribute(): string
    {
        return '****' . $this->last_four;
    }

    /**
     * Check if card is expired
     */
    public function isExpired(): bool
    {
        if (!$this->exp_month || !$this->exp_year) {
            return false;
        }

        $expiryDate = \Carbon\Carbon::createFromDate($this->exp_year, $this->exp_month, 1)->endOfMonth();
        return $expiryDate->isPast();
    }

    /**
     * Set as default payment method
     */
    public function setAsDefault(): void
    {
        // Remove default from all other payment methods for this user
        self::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }

    /**
     * Scope for default payment methods
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope for verified payment methods
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }
}
