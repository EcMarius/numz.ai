<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AutomationExecution extends Model
{
    use HasFactory;

    protected $fillable = [
        'automation_rule_id',
        'trigger_event',
        'trigger_data',
        'conditions_met',
        'actions_taken',
        'success',
        'error_message',
        'execution_time',
    ];

    protected $casts = [
        'trigger_data' => 'array',
        'actions_taken' => 'array',
        'conditions_met' => 'boolean',
        'success' => 'boolean',
    ];

    /**
     * Get the automation rule
     */
    public function automationRule(): BelongsTo
    {
        return $this->belongsTo(AutomationRule::class);
    }

    /**
     * Get execution status badge color
     */
    public function getStatusColorAttribute(): string
    {
        if (!$this->conditions_met) {
            return 'gray';
        }

        return $this->success ? 'success' : 'danger';
    }

    /**
     * Get execution status text
     */
    public function getStatusTextAttribute(): string
    {
        if (!$this->conditions_met) {
            return 'Conditions Not Met';
        }

        return $this->success ? 'Success' : 'Failed';
    }

    /**
     * Get recent executions for a rule
     */
    public static function getRecentForRule(int $ruleId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('automation_rule_id', $ruleId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get failed executions
     */
    public static function getRecentFailures(int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('success', false)
            ->where('conditions_met', true)
            ->with('automationRule')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get execution statistics
     */
    public static function getStatistics(?\Carbon\Carbon $startDate = null, ?\Carbon\Carbon $endDate = null): array
    {
        $query = self::query();

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $total = $query->count();
        $successful = (clone $query)->where('success', true)->count();
        $failed = (clone $query)->where('success', false)->where('conditions_met', true)->count();
        $conditionsNotMet = (clone $query)->where('conditions_met', false)->count();

        return [
            'total' => $total,
            'successful' => $successful,
            'failed' => $failed,
            'conditions_not_met' => $conditionsNotMet,
            'success_rate' => $total > 0 ? round(($successful / $total) * 100, 2) : 0,
            'average_execution_time' => $query->avg('execution_time') ?? 0,
        ];
    }
}
