<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReportSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'custom_report_id',
        'name',
        'frequency',
        'schedule_config',
        'delivery_method',
        'recipients',
        'export_format',
        'is_active',
        'last_run_at',
        'next_run_at',
    ];

    protected $casts = [
        'schedule_config' => 'array',
        'recipients' => 'array',
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(CustomReport::class, 'custom_report_id');
    }

    /**
     * Calculate next run time based on frequency
     */
    public function calculateNextRun(): \Carbon\Carbon
    {
        $now = now();

        return match($this->frequency) {
            'daily' => $now->addDay()->setTime(
                $this->schedule_config['hour'] ?? 9,
                $this->schedule_config['minute'] ?? 0
            ),
            'weekly' => $now->addWeek()->setTime(
                $this->schedule_config['hour'] ?? 9,
                $this->schedule_config['minute'] ?? 0
            ),
            'monthly' => $now->addMonth()->setDay(
                $this->schedule_config['day_of_month'] ?? 1
            )->setTime(
                $this->schedule_config['hour'] ?? 9,
                $this->schedule_config['minute'] ?? 0
            ),
            'quarterly' => $now->addMonths(3)->setTime(
                $this->schedule_config['hour'] ?? 9,
                $this->schedule_config['minute'] ?? 0
            ),
            default => $now->addDay(),
        };
    }

    /**
     * Mark as executed
     */
    public function markAsExecuted(): void
    {
        $this->update([
            'last_run_at' => now(),
            'next_run_at' => $this->calculateNextRun(),
        ]);
    }

    /**
     * Get schedules due for execution
     */
    public static function getDueSchedules(): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('is_active', true)
            ->where('next_run_at', '<=', now())
            ->with('report')
            ->get();
    }

    /**
     * Enable schedule
     */
    public function enable(): void
    {
        $this->update([
            'is_active' => true,
            'next_run_at' => $this->calculateNextRun(),
        ]);
    }

    /**
     * Disable schedule
     */
    public function disable(): void
    {
        $this->update([
            'is_active' => false,
        ]);
    }
}
