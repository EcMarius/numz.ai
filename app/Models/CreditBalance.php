<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class CreditBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'balance',
        'total_earned',
        'total_spent',
        'total_purchased',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'total_earned' => 'decimal:2',
        'total_spent' => 'decimal:2',
        'total_purchased' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(CreditTransaction::class, 'user_id', 'user_id');
    }

    /**
     * Add credits to balance
     */
    public function addCredits(float $amount, string $type, string $description, array $metadata = []): CreditTransaction
    {
        // Validate amount
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Credit amount must be positive');
        }

        // Use transaction with row locking to prevent race conditions
        return DB::transaction(function() use ($amount, $type, $description, $metadata) {
            $balance = self::where('id', $this->id)
                ->lockForUpdate()
                ->first();

            $balance->balance += $amount;

            if ($type === 'purchase') {
                $balance->total_purchased += $amount;
            } elseif (in_array($type, ['grant', 'bonus', 'refund'])) {
                $balance->total_earned += $amount;
            }

            $balance->save();

            return $balance->transactions()->create([
                'type' => $type,
                'amount' => $amount,
                'balance_after' => $balance->balance,
                'description' => $description,
                'metadata' => $metadata,
            ]);
        });
    }

    /**
     * Deduct credits from balance
     */
    public function deductCredits(float $amount, string $type, string $description, array $metadata = []): CreditTransaction
    {
        // Validate amount
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Deduct amount must be positive');
        }

        // Use transaction with row locking to prevent race conditions
        return DB::transaction(function() use ($amount, $type, $description, $metadata) {
            $balance = self::where('id', $this->id)
                ->lockForUpdate()
                ->first();

            // Re-check balance after lock
            if ($balance->balance < $amount) {
                throw new \Exception('Insufficient credit balance');
            }

            $balance->balance -= $amount;

            if ($type === 'payment') {
                $balance->total_spent += $amount;
            }

            $balance->save();

            return $balance->transactions()->create([
                'type' => $type,
                'amount' => -$amount,
                'balance_after' => $balance->balance,
                'description' => $description,
                'metadata' => $metadata,
            ]);
        });
    }

    /**
     * Check if user has sufficient credits
     */
    public function hasSufficientCredits(float $amount): bool
    {
        return $this->balance >= $amount;
    }

    /**
     * Get formatted balance
     */
    public function getFormattedBalanceAttribute(): string
    {
        return '$' . number_format($this->balance, 2);
    }
}
