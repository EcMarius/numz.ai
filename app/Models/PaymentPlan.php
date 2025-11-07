<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'user_id',
        'total_amount',
        'installments',
        'frequency',
        'status',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(PaymentPlanInstallment::class);
    }

    /**
     * Create installments for the payment plan
     */
    public function createInstallments(): void
    {
        // Validate installments count (prevent division by zero)
        if ($this->installments <= 0) {
            throw new \InvalidArgumentException('Installments must be greater than 0');
        }

        $installmentAmount = round($this->total_amount / $this->installments, 2);
        $currentDate = $this->start_date;

        for ($i = 1; $i <= $this->installments; $i++) {
            // Adjust last installment to account for rounding
            $amount = ($i === $this->installments)
                ? $this->total_amount - ($installmentAmount * ($this->installments - 1))
                : $installmentAmount;

            $this->installments()->create([
                'installment_number' => $i,
                'amount' => $amount,
                'due_date' => $currentDate,
                'status' => 'pending',
            ]);

            // Calculate next due date based on frequency
            $currentDate = $this->calculateNextDueDate($currentDate);
        }
    }

    /**
     * Calculate next due date based on frequency
     */
    private function calculateNextDueDate($currentDate)
    {
        return match($this->frequency) {
            'weekly' => $currentDate->addWeek(),
            'biweekly' => $currentDate->addWeeks(2),
            'monthly' => $currentDate->addMonth(),
            default => $currentDate->addMonth(),
        };
    }

    /**
     * Get progress percentage
     */
    public function getProgressPercentage(): float
    {
        // Prevent division by zero
        if ($this->total_amount == 0) {
            return 100; // Consider 0-amount plan as 100% complete
        }

        $paidAmount = $this->installments()
            ->where('status', 'paid')
            ->sum('amount');

        return round(($paidAmount / $this->total_amount) * 100, 2);
    }

    /**
     * Check if plan is completed
     */
    public function checkCompletion(): void
    {
        $allPaid = $this->installments()
            ->where('status', '!=', 'paid')
            ->count() === 0;

        if ($allPaid) {
            $this->update(['status' => 'completed']);
        }
    }

    /**
     * Mark plan as defaulted
     */
    public function markAsDefaulted(): void
    {
        $this->update(['status' => 'defaulted']);
    }
}
