<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxExemption extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'exemption_type',
        'tax_id',
        'country',
        'reason',
        'exempt_categories',
        'status',
        'approved_by',
        'approved_at',
        'expires_at',
    ];

    protected $casts = [
        'exempt_categories' => 'array',
        'approved_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Check if exemption is currently valid
     */
    public function isValid(): bool
    {
        if ($this->status !== 'approved') {
            return false;
        }

        if ($this->expires_at && now()->gt($this->expires_at)) {
            return false;
        }

        return true;
    }

    /**
     * Approve exemption
     */
    public function approve(User $approver): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);
    }

    /**
     * Reject exemption
     */
    public function reject(User $approver, string $reason = null): void
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'reason' => $reason ?? $this->reason,
        ]);
    }

    /**
     * Check if exemption applies to category
     */
    public function appliesToCategory(string $category): bool
    {
        if ($this->exemption_type === 'full') {
            return true;
        }

        if ($this->exemption_type === 'category_specific' && $this->exempt_categories) {
            return in_array($category, $this->exempt_categories);
        }

        return false;
    }

    /**
     * Scope: Approved exemptions
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            });
    }

    /**
     * Scope: Pending exemptions
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
