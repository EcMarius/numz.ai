<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AIPrediction extends Model
{
    use HasFactory;

    protected $table = 'ai_predictions';

    protected $fillable = [
        'prediction_type',
        'user_id',
        'hosting_service_id',
        'invoice_id',
        'prediction_score',
        'factors',
        'recommended_action',
        'action_taken',
        'action_taken_at',
        'prediction_correct',
        'verified_at',
    ];

    protected $casts = [
        'prediction_score' => 'decimal:4',
        'factors' => 'array',
        'action_taken' => 'boolean',
        'action_taken_at' => 'datetime',
        'prediction_correct' => 'boolean',
        'verified_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(HostingService::class, 'hosting_service_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Mark action as taken
     */
    public function markActionTaken(): void
    {
        $this->update([
            'action_taken' => true,
            'action_taken_at' => now(),
        ]);
    }

    /**
     * Verify prediction
     */
    public function verify(bool $wasCorrect): void
    {
        $this->update([
            'prediction_correct' => $wasCorrect,
            'verified_at' => now(),
        ]);
    }

    /**
     * Get risk level
     */
    public function getRiskLevelAttribute(): string
    {
        if ($this->prediction_score >= 0.8) {
            return 'critical';
        } elseif ($this->prediction_score >= 0.6) {
            return 'high';
        } elseif ($this->prediction_score >= 0.4) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Scope for high risk predictions
     */
    public function scopeHighRisk($query)
    {
        return $query->where('prediction_score', '>=', 0.6);
    }

    /**
     * Scope for pending action
     */
    public function scopePendingAction($query)
    {
        return $query->where('action_taken', false);
    }

    /**
     * Get accuracy rate for prediction type
     */
    public static function getAccuracyRate(string $predictionType): float
    {
        $verified = self::where('prediction_type', $predictionType)
            ->whereNotNull('verified_at')
            ->get();

        if ($verified->isEmpty()) {
            return 0;
        }

        $correct = $verified->where('prediction_correct', true)->count();
        return round(($correct / $verified->count()) * 100, 2);
    }
}
