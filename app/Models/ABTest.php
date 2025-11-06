<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ABTest extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ab_tests';

    protected $fillable = [
        'created_by',
        'name',
        'description',
        'test_type',
        'variant_a_name',
        'variant_a_config',
        'variant_b_name',
        'variant_b_config',
        'traffic_split',
        'success_metric',
        'status',
        'started_at',
        'ended_at',
        'winner',
        'variant_a_conversions',
        'variant_a_views',
        'variant_b_conversions',
        'variant_b_views',
        'confidence_level',
        'statistical_significance',
    ];

    protected $casts = [
        'variant_a_config' => 'array',
        'variant_b_config' => 'array',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get variant for a user (consistent assignment)
     */
    public function getVariantForUser(int $userId): string
    {
        // Use modulo to consistently assign users to variants
        $hash = crc32($this->id . '-' . $userId);
        return ($hash % 100) < $this->traffic_split ? 'a' : 'b';
    }

    /**
     * Track view for variant
     */
    public function trackView(string $variant): void
    {
        if ($variant === 'a') {
            $this->increment('variant_a_views');
        } else {
            $this->increment('variant_b_views');
        }
    }

    /**
     * Track conversion for variant
     */
    public function trackConversion(string $variant): void
    {
        if ($variant === 'a') {
            $this->increment('variant_a_conversions');
        } else {
            $this->increment('variant_b_conversions');
        }

        $this->calculateStatistics();
    }

    /**
     * Calculate conversion rates
     */
    public function getConversionRates(): array
    {
        $variantARate = $this->variant_a_views > 0
            ? ($this->variant_a_conversions / $this->variant_a_views) * 100
            : 0;

        $variantBRate = $this->variant_b_views > 0
            ? ($this->variant_b_conversions / $this->variant_b_views) * 100
            : 0;

        return [
            'variant_a' => round($variantARate, 2),
            'variant_b' => round($variantBRate, 2),
            'improvement' => $variantARate > 0
                ? round((($variantBRate - $variantARate) / $variantARate) * 100, 2)
                : 0,
        ];
    }

    /**
     * Calculate statistical significance
     */
    protected function calculateStatistics(): void
    {
        if ($this->variant_a_views < 100 || $this->variant_b_views < 100) {
            return; // Not enough data
        }

        $rates = $this->getConversionRates();

        // Simplified z-score calculation
        $p1 = $rates['variant_a'] / 100;
        $p2 = $rates['variant_b'] / 100;
        $n1 = $this->variant_a_views;
        $n2 = $this->variant_b_views;

        $pooledP = ($this->variant_a_conversions + $this->variant_b_conversions) / ($n1 + $n2);
        $se = sqrt($pooledP * (1 - $pooledP) * ((1 / $n1) + (1 / $n2)));

        if ($se > 0) {
            $zScore = abs($p2 - $p1) / $se;
            $confidenceLevel = $this->zScoreToConfidence($zScore);

            $this->update([
                'confidence_level' => round($confidenceLevel, 2),
                'statistical_significance' => $confidenceLevel >= 95,
            ]);

            // Auto-determine winner if statistically significant
            if ($confidenceLevel >= 95 && !$this->winner) {
                $this->update([
                    'winner' => $rates['variant_b'] > $rates['variant_a'] ? 'b' : 'a',
                ]);
            }
        }
    }

    /**
     * Convert z-score to confidence level
     */
    protected function zScoreToConfidence(float $zScore): float
    {
        // Simplified conversion (should use proper statistical tables)
        if ($zScore >= 2.58) return 99;
        if ($zScore >= 1.96) return 95;
        if ($zScore >= 1.65) return 90;
        if ($zScore >= 1.28) return 80;
        return 50;
    }

    /**
     * Start test
     */
    public function start(): void
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    /**
     * End test
     */
    public function end(?string $winner = null): void
    {
        $this->update([
            'status' => 'completed',
            'ended_at' => now(),
            'winner' => $winner ?? $this->winner,
        ]);
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'draft' => 'gray',
            'running' => 'info',
            'completed' => 'success',
            'paused' => 'warning',
            default => 'gray',
        };
    }

    /**
     * Get active tests
     */
    public static function getActiveTests(): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('status', 'running')
            ->orderBy('started_at', 'desc')
            ->get();
    }
}
