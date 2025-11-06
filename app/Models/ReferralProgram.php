<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReferralProgram extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'referrer_reward_amount',
        'referrer_reward_type',
        'referee_reward_amount',
        'referee_reward_type',
        'minimum_purchase_amount',
        'max_referrals_per_user',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'referrer_reward_amount' => 'decimal:2',
        'referee_reward_amount' => 'decimal:2',
        'minimum_purchase_amount' => 'integer',
        'max_referrals_per_user' => 'integer',
    ];

    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class);
    }

    /**
     * Get active referral program
     */
    public static function getActive(): ?self
    {
        return self::where('is_active', true)->first();
    }

    /**
     * Check if user can make more referrals
     */
    public function canUserRefer(User $user): bool
    {
        if (!$this->max_referrals_per_user) {
            return true;
        }

        $referralCount = $this->referrals()
            ->where('referrer_id', $user->id)
            ->where('status', 'completed')
            ->count();

        return $referralCount < $this->max_referrals_per_user;
    }

    /**
     * Create referral for user
     */
    public function createReferral(User $referrer, ?string $refereeEmail = null): Referral
    {
        return $this->referrals()->create([
            'referrer_id' => $referrer->id,
            'referee_email' => $refereeEmail,
            'referral_code' => $this->generateReferralCode($referrer),
            'status' => 'pending',
        ]);
    }

    /**
     * Generate unique referral code
     */
    private function generateReferralCode(User $referrer): string
    {
        $base = strtoupper(substr($referrer->name, 0, 3)) . rand(1000, 9999);

        // Ensure uniqueness
        while (Referral::where('referral_code', $base)->exists()) {
            $base = strtoupper(substr($referrer->name, 0, 3)) . rand(1000, 9999);
        }

        return $base;
    }
}
