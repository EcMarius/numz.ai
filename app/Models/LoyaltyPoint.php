<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoyaltyPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'loyalty_program_id',
        'points',
        'lifetime_points',
        'tier',
    ];

    protected $casts = [
        'points' => 'integer',
        'lifetime_points' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(LoyaltyProgram::class, 'loyalty_program_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(LoyaltyTransaction::class, 'user_id', 'user_id')
                    ->where('loyalty_program_id', $this->loyalty_program_id);
    }

    /**
     * Add points
     */
    public function addPoints(int $points, ?int $invoiceId = null, ?string $description = null, ?\Carbon\Carbon $expiresAt = null): LoyaltyTransaction
    {
        $this->increment('points', $points);
        $this->increment('lifetime_points', $points);

        // Update tier
        $this->updateTier();

        return $this->transactions()->create([
            'loyalty_program_id' => $this->loyalty_program_id,
            'invoice_id' => $invoiceId,
            'type' => 'earned',
            'points' => $points,
            'balance_after' => $this->points,
            'description' => $description ?? 'Points earned',
            'expires_at' => $expiresAt,
        ]);
    }

    /**
     * Redeem points
     */
    public function redeemPoints(int $points, string $description = null): LoyaltyTransaction
    {
        if ($points > $this->points) {
            throw new \Exception('Insufficient points');
        }

        $this->decrement('points', $points);

        // Update tier after redemption
        $this->updateTier();

        return $this->transactions()->create([
            'loyalty_program_id' => $this->loyalty_program_id,
            'type' => 'redeemed',
            'points' => -$points,
            'balance_after' => $this->points,
            'description' => $description ?? 'Points redeemed',
        ]);
    }

    /**
     * Update tier based on lifetime points
     */
    public function updateTier(): void
    {
        $newTier = $this->program->getTierForPoints($this->lifetime_points);

        if ($newTier && $newTier !== $this->tier) {
            $this->update(['tier' => $newTier]);
        }
    }

    /**
     * Expire old points
     */
    public function expireOldPoints(): void
    {
        if (!$this->program->points_expiry_days) {
            return;
        }

        $expiredTransactions = $this->transactions()
            ->where('type', 'earned')
            ->where('expires_at', '<', now())
            ->whereDoesntHave('related_redemptions')
            ->get();

        foreach ($expiredTransactions as $transaction) {
            $this->transactions()->create([
                'loyalty_program_id' => $this->loyalty_program_id,
                'type' => 'expired',
                'points' => -$transaction->points,
                'balance_after' => $this->points - $transaction->points,
                'description' => 'Points expired from ' . $transaction->created_at->format('Y-m-d'),
            ]);

            $this->decrement('points', $transaction->points);
        }
    }
}
