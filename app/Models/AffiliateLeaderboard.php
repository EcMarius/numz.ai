<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AffiliateLeaderboard extends Model
{
    use HasFactory;

    protected $fillable = [
        'affiliate_id',
        'year',
        'month',
        'rank',
        'referrals',
        'sales',
        'commission',
        'conversion_rate',
    ];

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    /**
     * Generate monthly leaderboard
     */
    public static function generateMonthlyLeaderboard(int $year = null, int $month = null): void
    {
        $year = $year ?? now()->year;
        $month = $month ?? now()->month;

        $startDate = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Get affiliate stats for the period
        $affiliates = Affiliate::where('status', 'active')
            ->get()
            ->map(function ($affiliate) use ($startDate, $endDate) {
                $referrals = $affiliate->signups()
                    ->whereBetween('confirmed_at', [$startDate, $endDate])
                    ->count();

                $commissions = $affiliate->commissions()
                    ->whereBetween('earned_date', [$startDate, $endDate])
                    ->where('status', '!=', 'cancelled')
                    ->get();

                return [
                    'affiliate_id' => $affiliate->id,
                    'referrals' => $referrals,
                    'sales' => $commissions->sum('sale_amount'),
                    'commission' => $commissions->sum('commission_amount'),
                    'conversion_rate' => $affiliate->conversion_rate,
                ];
            })
            ->sortByDesc('sales')
            ->values();

        // Assign ranks and save
        foreach ($affiliates as $index => $data) {
            self::updateOrCreate(
                [
                    'affiliate_id' => $data['affiliate_id'],
                    'year' => $year,
                    'month' => $month,
                ],
                array_merge($data, ['rank' => $index + 1])
            );
        }
    }

    /**
     * Get top performers for a month
     */
    public static function getTopPerformers(int $year, int $month, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return self::with('affiliate')
            ->where('year', $year)
            ->where('month', $month)
            ->orderBy('rank')
            ->limit($limit)
            ->get();
    }

    /**
     * Get affiliate rank for a month
     */
    public static function getAffiliateRank(int $affiliateId, int $year, int $month): ?int
    {
        return self::where('affiliate_id', $affiliateId)
            ->where('year', $year)
            ->where('month', $month)
            ->value('rank');
    }
}
