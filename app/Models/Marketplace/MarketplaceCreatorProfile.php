<?php

namespace App\Models\Marketplace;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceCreatorProfile extends Model
{
    protected $fillable = [
        'user_id',
        'business_name',
        'bio',
        'website',
        'github',
        'twitter',
        'avatar',
        'preferred_payout_method',
        'stripe_account_id',
        'paypal_email',
        'bank_details',
        'tax_id',
        'country',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'postal_code',
        'is_verified',
        'can_receive_payouts',
        'verified_at',
        'total_earnings',
        'available_balance',
        'pending_balance',
        'total_sales',
    ];

    protected $casts = [
        'bank_details' => 'encrypted:array',
        'is_verified' => 'boolean',
        'can_receive_payouts' => 'boolean',
        'verified_at' => 'datetime',
        'total_earnings' => 'decimal:2',
        'available_balance' => 'decimal:2',
        'pending_balance' => 'decimal:2',
        'total_sales' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Update balances based on earnings
     */
    public function updateBalances(): void
    {
        $availableBalance = MarketplaceEarning::where('creator_id', $this->user_id)
            ->available()
            ->whereNull('payout_id')
            ->sum('amount');

        $pendingBalance = MarketplaceEarning::where('creator_id', $this->user_id)
            ->pending()
            ->sum('amount');

        $totalEarnings = MarketplaceEarning::where('creator_id', $this->user_id)
            ->whereIn('status', ['available', 'paid'])
            ->sum('amount');

        $totalSales = MarketplacePurchase::whereHas('item', function ($query) {
            $query->where('user_id', $this->user_id);
        })->where('payment_status', 'completed')->count();

        $this->update([
            'available_balance' => $availableBalance,
            'pending_balance' => $pendingBalance,
            'total_earnings' => $totalEarnings,
            'total_sales' => $totalSales,
        ]);
    }

    /**
     * Check if creator can request payout
     */
    public function canRequestPayout(float $minimumAmount = 50.00): bool
    {
        return $this->can_receive_payouts
            && $this->is_verified
            && $this->available_balance >= $minimumAmount;
    }
}
