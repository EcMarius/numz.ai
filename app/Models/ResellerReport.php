<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ResellerReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'reseller_id',
        'report_date',
        'period_type',
        'new_sales',
        'renewals',
        'upgrades',
        'total_revenue',
        'commission_earned',
        'commission_paid',
        'new_customers',
        'active_customers',
        'churned_customers',
        'new_services',
        'active_services',
        'cancelled_services',
    ];

    protected $casts = [
        'report_date' => 'date',
    ];

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class);
    }

    /**
     * Generate daily report for reseller
     */
    public static function generateDailyReport(Reseller $reseller, ?\Carbon\Carbon $date = null): self
    {
        $date = $date ?? now();

        // Calculate metrics for the day
        $metrics = [
            'reseller_id' => $reseller->id,
            'report_date' => $date,
            'period_type' => 'daily',
            'new_sales' => 0, // TODO: Calculate from invoices
            'renewals' => 0,
            'upgrades' => 0,
            'total_revenue' => 0,
            'commission_earned' => $reseller->commissions()
                ->whereDate('earned_date', $date)
                ->sum('commission_amount'),
            'commission_paid' => $reseller->commissions()
                ->whereDate('paid_date', $date)
                ->sum('commission_amount'),
            'new_customers' => 0, // TODO: Calculate
            'active_customers' => $reseller->total_customers,
            'churned_customers' => 0,
            'new_services' => 0,
            'active_services' => $reseller->total_services,
            'cancelled_services' => 0,
        ];

        return self::updateOrCreate(
            [
                'reseller_id' => $reseller->id,
                'report_date' => $date,
                'period_type' => 'daily',
            ],
            $metrics
        );
    }
}
