<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DunningAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'dunning_campaign_id',
        'invoice_id',
        'user_id',
        'attempt_number',
        'status',
        'scheduled_at',
        'attempted_at',
        'gateway',
        'error_message',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'attempted_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(DunningCampaign::class, 'dunning_campaign_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if attempt is due
     */
    public function isDue(): bool
    {
        return $this->status === 'pending' && $this->scheduled_at <= now();
    }

    /**
     * Mark attempt as success
     */
    public function markAsSuccess(): void
    {
        $this->update([
            'status' => 'success',
            'attempted_at' => now(),
        ]);
    }

    /**
     * Mark attempt as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'attempted_at' => now(),
            'error_message' => $errorMessage,
        ]);

        // Check if this was the last attempt
        $maxRetries = $this->campaign->max_retries;
        if ($this->attempt_number >= $maxRetries) {
            $this->handleFinalFailure();
        }
    }

    /**
     * Handle final failure after all retries
     */
    private function handleFinalFailure(): void
    {
        if ($this->campaign->suspend_on_failure) {
            // Suspend services related to the invoice
            foreach ($this->invoice->items as $item) {
                if ($item->item_type === 'service' && $item->item_id) {
                    $service = $item->item();
                    if ($service && $service->status === 'active') {
                        $service->suspend('Payment retry failed');
                    }
                }
            }
        }

        if ($this->campaign->cancel_on_failure) {
            // Cancel the invoice
            $this->invoice->update(['status' => 'cancelled']);
        }
    }

    /**
     * Scope for due attempts
     */
    public function scopeDue($query)
    {
        return $query->where('status', 'pending')
                     ->where('scheduled_at', '<=', now());
    }
}
