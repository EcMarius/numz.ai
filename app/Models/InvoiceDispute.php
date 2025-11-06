<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvoiceDispute extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'user_id',
        'reason',
        'status',
        'assigned_to',
        'resolution_notes',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Assign dispute to staff member
     */
    public function assignTo(int $userId): void
    {
        $this->update([
            'assigned_to' => $userId,
            'status' => 'in_review',
        ]);
    }

    /**
     * Resolve dispute
     */
    public function resolve(string $resolutionNotes): void
    {
        $this->update([
            'status' => 'resolved',
            'resolution_notes' => $resolutionNotes,
            'resolved_at' => now(),
        ]);
    }

    /**
     * Reject dispute
     */
    public function reject(string $resolutionNotes): void
    {
        $this->update([
            'status' => 'rejected',
            'resolution_notes' => $resolutionNotes,
            'resolved_at' => now(),
        ]);
    }

    /**
     * Scope for open disputes
     */
    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'in_review']);
    }
}
