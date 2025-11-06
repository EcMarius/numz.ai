<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FraudDetection extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'invoice_id',
        'payment_transaction_id',
        'risk_score',
        'risk_level',
        'risk_factors',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'automated_action',
    ];

    protected $casts = [
        'risk_score' => 'decimal:2',
        'risk_factors' => 'array',
        'reviewed_at' => 'datetime',
        'automated_action' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function paymentTransaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Approve transaction
     */
    public function approve(int $reviewerId, ?string $notes = null): void
    {
        $this->update([
            'status' => 'approved',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);
    }

    /**
     * Block transaction
     */
    public function block(int $reviewerId, ?string $notes = null): void
    {
        $this->update([
            'status' => 'blocked',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);

        // Suspend user or take automated action
        if ($this->risk_level === 'critical') {
            // Additional fraud prevention measures
        }
    }

    /**
     * Mark as false positive
     */
    public function markAsFalsePositive(int $reviewerId, ?string $notes = null): void
    {
        $this->update([
            'status' => 'false_positive',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);
    }

    /**
     * Calculate risk level from score
     */
    public static function calculateRiskLevel(float $score): string
    {
        if ($score >= 80) {
            return 'critical';
        } elseif ($score >= 60) {
            return 'high';
        } elseif ($score >= 40) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Scope for pending review
     */
    public function scopePendingReview($query)
    {
        return $query->where('status', 'pending_review')
                     ->orderByDesc('risk_score');
    }

    /**
     * Scope for high risk
     */
    public function scopeHighRisk($query)
    {
        return $query->whereIn('risk_level', ['high', 'critical']);
    }
}
