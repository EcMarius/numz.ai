<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AutomationRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'trigger_event',
        'conditions',
        'actions',
        'is_active',
        'priority',
        'execution_count',
        'last_executed_at',
    ];

    protected $casts = [
        'conditions' => 'array',
        'actions' => 'array',
        'is_active' => 'boolean',
        'last_executed_at' => 'datetime',
    ];

    /**
     * Get automation executions
     */
    public function executions(): HasMany
    {
        return $this->hasMany(AutomationExecution::class);
    }

    /**
     * Get successful executions
     */
    public function successfulExecutions(): HasMany
    {
        return $this->executions()->where('success', true);
    }

    /**
     * Get failed executions
     */
    public function failedExecutions(): HasMany
    {
        return $this->executions()->where('success', false);
    }

    /**
     * Get success rate
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->execution_count === 0) {
            return 0;
        }

        $successful = $this->successfulExecutions()->count();
        return round(($successful / $this->execution_count) * 100, 2);
    }

    /**
     * Get average execution time
     */
    public function getAverageExecutionTimeAttribute(): float
    {
        return $this->executions()
            ->whereNotNull('execution_time')
            ->avg('execution_time') ?? 0;
    }

    /**
     * Check if conditions are met
     */
    public function checkConditions(array $data): bool
    {
        if (empty($this->conditions)) {
            return true;
        }

        foreach ($this->conditions as $condition) {
            if (!$this->evaluateCondition($condition, $data)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluate a single condition
     */
    protected function evaluateCondition(array $condition, array $data): bool
    {
        $field = $condition['field'] ?? null;
        $operator = $condition['operator'] ?? '==';
        $value = $condition['value'] ?? null;

        if (!$field || !isset($data[$field])) {
            return false;
        }

        $actualValue = $data[$field];

        return match($operator) {
            '==' => $actualValue == $value,
            '!=' => $actualValue != $value,
            '>' => $actualValue > $value,
            '>=' => $actualValue >= $value,
            '<' => $actualValue < $value,
            '<=' => $actualValue <= $value,
            'contains' => str_contains((string)$actualValue, (string)$value),
            'not_contains' => !str_contains((string)$actualValue, (string)$value),
            'starts_with' => str_starts_with((string)$actualValue, (string)$value),
            'ends_with' => str_ends_with((string)$actualValue, (string)$value),
            'in' => in_array($actualValue, (array)$value),
            'not_in' => !in_array($actualValue, (array)$value),
            default => false,
        };
    }

    /**
     * Execute actions
     */
    public function executeActions(array $data): array
    {
        $results = [];

        if (empty($this->actions)) {
            return $results;
        }

        foreach ($this->actions as $action) {
            try {
                $results[] = $this->performAction($action, $data);
            } catch (\Exception $e) {
                $results[] = [
                    'action' => $action['type'] ?? 'unknown',
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Perform a single action
     */
    protected function performAction(array $action, array $data): array
    {
        $type = $action['type'] ?? null;
        $params = $action['params'] ?? [];

        if (!$type) {
            throw new \Exception('Action type is required');
        }

        // Get action handler
        $handler = app("App\\Numz\\Automation\\Actions\\{$type}Action");

        return $handler->execute($params, $data);
    }

    /**
     * Increment execution count
     */
    public function incrementExecutionCount(): void
    {
        $this->increment('execution_count');
        $this->update(['last_executed_at' => now()]);
    }

    /**
     * Get rules for a specific trigger
     */
    public static function getActiveRulesForTrigger(string $trigger): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('trigger_event', $trigger)
            ->where('is_active', true)
            ->orderBy('priority', 'desc')
            ->get();
    }

    /**
     * Get available triggers
     */
    public static function getAvailableTriggers(): array
    {
        return [
            'invoice.created' => 'Invoice Created',
            'invoice.paid' => 'Invoice Paid',
            'invoice.overdue' => 'Invoice Overdue',
            'invoice.cancelled' => 'Invoice Cancelled',
            'payment.received' => 'Payment Received',
            'payment.failed' => 'Payment Failed',
            'service.created' => 'Service Created',
            'service.activated' => 'Service Activated',
            'service.suspended' => 'Service Suspended',
            'service.terminated' => 'Service Terminated',
            'service.renewed' => 'Service Renewed',
            'ticket.created' => 'Support Ticket Created',
            'ticket.replied' => 'Support Ticket Replied',
            'ticket.closed' => 'Support Ticket Closed',
            'user.registered' => 'User Registered',
            'user.login' => 'User Login',
        ];
    }

    /**
     * Get available actions
     */
    public static function getAvailableActions(): array
    {
        return [
            'SendEmail' => 'Send Email',
            'SendSMS' => 'Send SMS',
            'SuspendService' => 'Suspend Service',
            'TerminateService' => 'Terminate Service',
            'ApplyCredit' => 'Apply Account Credit',
            'CreateTicket' => 'Create Support Ticket',
            'SendNotification' => 'Send Notification',
            'UpdateStatus' => 'Update Status',
            'TriggerWebhook' => 'Trigger Webhook',
            'AddTag' => 'Add Tag',
            'RemoveTag' => 'Remove Tag',
        ];
    }

    /**
     * Get available operators
     */
    public static function getAvailableOperators(): array
    {
        return [
            '==' => 'Equals',
            '!=' => 'Not Equals',
            '>' => 'Greater Than',
            '>=' => 'Greater Than or Equal',
            '<' => 'Less Than',
            '<=' => 'Less Than or Equal',
            'contains' => 'Contains',
            'not_contains' => 'Does Not Contain',
            'starts_with' => 'Starts With',
            'ends_with' => 'Ends With',
            'in' => 'In List',
            'not_in' => 'Not In List',
        ];
    }
}
