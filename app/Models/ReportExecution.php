<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReportExecution extends Model
{
    use HasFactory;

    protected $fillable = [
        'custom_report_id',
        'report_schedule_id',
        'executed_by',
        'status',
        'execution_time',
        'result_data',
        'error_message',
        'row_count',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'result_data' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(CustomReport::class, 'custom_report_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ReportSchedule::class, 'report_schedule_id');
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by');
    }

    /**
     * Mark as started
     */
    public function markAsStarted(): void
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted(array $resultData, int $rowCount): void
    {
        $this->update([
            'status' => 'completed',
            'result_data' => $resultData,
            'row_count' => $rowCount,
            'completed_at' => now(),
            'execution_time' => now()->diffInSeconds($this->started_at),
        ]);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'completed_at' => now(),
            'execution_time' => now()->diffInSeconds($this->started_at),
        ]);
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'warning',
            'running' => 'info',
            'completed' => 'success',
            'failed' => 'danger',
            default => 'gray',
        };
    }

    /**
     * Get execution time in human readable format
     */
    public function getExecutionTimeHumanAttribute(): string
    {
        if (!$this->execution_time) {
            return 'N/A';
        }

        if ($this->execution_time < 60) {
            return $this->execution_time . 's';
        }

        return round($this->execution_time / 60, 2) . 'm';
    }

    /**
     * Get recent executions
     */
    public static function getRecentExecutions(int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return self::with(['report', 'executor'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
