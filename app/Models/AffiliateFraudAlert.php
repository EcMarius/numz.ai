<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AffiliateFraudAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'affiliate_id',
        'fraud_type',
        'severity',
        'description',
        'evidence',
        'status',
        'reviewed_by',
        'reviewed_at',
        'resolution_notes',
    ];

    protected $casts = [
        'evidence' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Create fraud alert
     */
    public static function createAlert(
        Affiliate $affiliate,
        string $fraudType,
        string $severity,
        string $description,
        array $evidence = []
    ): self {
        return self::create([
            'affiliate_id' => $affiliate->id,
            'fraud_type' => $fraudType,
            'severity' => $severity,
            'description' => $description,
            'evidence' => $evidence,
            'status' => 'open',
        ]);
    }

    /**
     * Mark as investigating
     */
    public function investigate(int $reviewerId): void
    {
        $this->update([
            'status' => 'investigating',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Resolve as fraudulent
     */
    public function resolve(string $notes): void
    {
        $this->update([
            'status' => 'resolved',
            'resolution_notes' => $notes,
        ]);

        // Optionally ban or suspend affiliate
        if ($this->severity === 'critical') {
            $this->affiliate->ban('Fraud detected: ' . $this->fraud_type);
        }
    }

    /**
     * Mark as false positive
     */
    public function markAsFalsePositive(string $notes): void
    {
        $this->update([
            'status' => 'false_positive',
            'resolution_notes' => $notes,
        ]);
    }

    /**
     * Detect duplicate IP fraud
     */
    public static function checkDuplicateIp(Affiliate $affiliate): void
    {
        $clicks = $affiliate->clicks()
            ->where('created_at', '>=', now()->subDays(7))
            ->get();

        $ipCounts = $clicks->countBy('ip_address');
        $suspicious = $ipCounts->filter(fn($count) => $count > 10);

        if ($suspicious->isNotEmpty()) {
            self::createAlert(
                $affiliate,
                'duplicate_ip',
                $suspicious->max() > 50 ? 'critical' : 'medium',
                'Multiple clicks from same IP addresses detected',
                ['ip_addresses' => $suspicious->toArray()]
            );
        }
    }

    /**
     * Detect self-referral fraud
     */
    public static function checkSelfReferral(Affiliate $affiliate, User $referredUser): void
    {
        // Check if IP addresses match
        $affiliateIp = $affiliate->user->last_login_ip ?? null;
        $referredIp = $referredUser->last_login_ip ?? request()->ip();

        if ($affiliateIp && $affiliateIp === $referredIp) {
            self::createAlert(
                $affiliate,
                'self_referral',
                'high',
                'Possible self-referral detected - matching IP addresses',
                [
                    'affiliate_ip' => $affiliateIp,
                    'referred_ip' => $referredIp,
                    'referred_user_id' => $referredUser->id,
                ]
            );
        }
    }

    /**
     * Get open alerts
     */
    public static function getOpenAlerts(string $severity = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = self::whereIn('status', ['open', 'investigating'])
            ->with('affiliate');

        if ($severity) {
            $query->where('severity', $severity);
        }

        return $query->orderBy('severity', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'open' => 'warning',
            'investigating' => 'info',
            'resolved' => 'danger',
            'false_positive' => 'success',
            default => 'gray',
        };
    }

    /**
     * Get severity badge color
     */
    public function getSeverityColorAttribute(): string
    {
        return match($this->severity) {
            'low' => 'info',
            'medium' => 'warning',
            'high' => 'danger',
            'critical' => 'danger',
            default => 'gray',
        };
    }
}
