<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DunningCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'is_active',
        'retry_schedule',
        'max_retries',
        'suspend_on_failure',
        'cancel_on_failure',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'retry_schedule' => 'array',
        'suspend_on_failure' => 'boolean',
        'cancel_on_failure' => 'boolean',
    ];

    public function attempts(): HasMany
    {
        return $this->hasMany(DunningAttempt::class);
    }

    /**
     * Get the default dunning campaign
     */
    public static function getDefault(): ?self
    {
        return self::where('is_active', true)->first();
    }

    /**
     * Create dunning attempts for an invoice
     */
    public function createAttemptsForInvoice(Invoice $invoice): void
    {
        foreach ($this->retry_schedule as $index => $daysOffset) {
            $this->attempts()->create([
                'invoice_id' => $invoice->id,
                'user_id' => $invoice->user_id,
                'attempt_number' => $index + 1,
                'scheduled_at' => now()->addDays($daysOffset),
            ]);
        }
    }
}
