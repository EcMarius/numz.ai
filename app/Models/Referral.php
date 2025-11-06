<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Referral extends Model
{
    use HasFactory;

    protected $fillable = [
        'referral_program_id',
        'referrer_id',
        'referee_id',
        'referral_code',
        'referee_email',
        'status',
        'invoice_id',
        'completed_at',
        'rewarded_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'rewarded_at' => 'datetime',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(ReferralProgram::class, 'referral_program_id');
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referee_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Complete referral when referee makes purchase
     */
    public function complete(Invoice $invoice): void
    {
        // Check if purchase meets minimum requirement
        if ($this->program->minimum_purchase_amount && $invoice->total < $this->program->minimum_purchase_amount) {
            return;
        }

        $this->update([
            'status' => 'completed',
            'invoice_id' => $invoice->id,
            'completed_at' => now(),
        ]);

        // Process rewards
        $this->processRewards();
    }

    /**
     * Process rewards for referrer and referee
     */
    private function processRewards(): void
    {
        $program = $this->program;

        // Reward referrer
        if ($program->referrer_reward_amount) {
            match($program->referrer_reward_type) {
                'credit' => $this->addCreditToUser($this->referrer, $program->referrer_reward_amount, 'Referral reward'),
                'discount' => $this->createCouponForUser($this->referrer, $program->referrer_reward_amount),
                'coupon' => $this->createCouponForUser($this->referrer, $program->referrer_reward_amount),
                default => null,
            };
        }

        // Reward referee
        if ($program->referee_reward_amount && $this->referee) {
            match($program->referee_reward_type) {
                'credit' => $this->addCreditToUser($this->referee, $program->referee_reward_amount, 'Referral welcome bonus'),
                'discount' => $this->createCouponForUser($this->referee, $program->referee_reward_amount),
                'coupon' => $this->createCouponForUser($this->referee, $program->referee_reward_amount),
                default => null,
            };
        }

        $this->update([
            'status' => 'rewarded',
            'rewarded_at' => now(),
        ]);
    }

    /**
     * Add credit to user account
     */
    private function addCreditToUser(User $user, float $amount, string $description): void
    {
        // This would integrate with the credit system
        // CreditBalance logic here
    }

    /**
     * Create coupon for user
     */
    private function createCouponForUser(User $user, float $amount): void
    {
        Coupon::create([
            'code' => 'REF' . strtoupper(substr(uniqid(), -8)),
            'type' => 'fixed',
            'value' => $amount,
            'description' => 'Referral reward',
            'max_uses' => 1,
            'max_uses_per_user' => 1,
            'allowed_user_ids' => [$user->id],
            'is_active' => true,
            'expires_at' => now()->addDays(30),
        ]);
    }

    /**
     * Scope for pending referrals
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for completed referrals
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
