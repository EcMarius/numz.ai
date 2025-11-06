<?php

namespace App\Numz\Services;

use App\Models\AutomationRule;
use App\Models\AutomationExecution;
use Illuminate\Support\Facades\Log;

class AutomationEngine
{
    /**
     * Process automation rules for a trigger event
     */
    public function processTrigger(string $triggerEvent, array $data): array
    {
        $results = [];

        // Get all active rules for this trigger
        $rules = AutomationRule::getActiveRulesForTrigger($triggerEvent);

        if ($rules->isEmpty()) {
            Log::info("No active automation rules found for trigger: {$triggerEvent}");
            return $results;
        }

        Log::info("Processing {$rules->count()} automation rules for trigger: {$triggerEvent}");

        foreach ($rules as $rule) {
            $results[] = $this->processRule($rule, $triggerEvent, $data);
        }

        return $results;
    }

    /**
     * Process a single automation rule
     */
    public function processRule(AutomationRule $rule, string $triggerEvent, array $data): array
    {
        $startTime = microtime(true);
        $conditionsMet = false;
        $success = false;
        $actionsTaken = [];
        $errorMessage = null;

        try {
            // Check if conditions are met
            $conditionsMet = $rule->checkConditions($data);

            if (!$conditionsMet) {
                Log::info("Automation rule '{$rule->name}' conditions not met", [
                    'rule_id' => $rule->id,
                    'trigger' => $triggerEvent,
                ]);

                $this->logExecution($rule, $triggerEvent, $data, $conditionsMet, [], true, null, microtime(true) - $startTime);

                return [
                    'rule_id' => $rule->id,
                    'rule_name' => $rule->name,
                    'conditions_met' => false,
                    'success' => true,
                ];
            }

            // Execute actions
            Log::info("Executing actions for automation rule '{$rule->name}'", [
                'rule_id' => $rule->id,
                'trigger' => $triggerEvent,
            ]);

            $actionResults = $rule->executeActions($data);
            $actionsTaken = $actionResults;

            // Check if all actions succeeded
            $success = collect($actionResults)->every(fn($result) => $result['success'] ?? false);

            // Increment execution count
            $rule->incrementExecutionCount();

            if ($success) {
                Log::info("Automation rule '{$rule->name}' executed successfully", [
                    'rule_id' => $rule->id,
                    'actions_count' => count($actionResults),
                ]);
            } else {
                $failedActions = collect($actionResults)->where('success', false);
                Log::warning("Some actions failed for automation rule '{$rule->name}'", [
                    'rule_id' => $rule->id,
                    'failed_count' => $failedActions->count(),
                ]);
            }

        } catch (\Exception $e) {
            $success = false;
            $errorMessage = $e->getMessage();

            Log::error("Automation rule '{$rule->name}' failed with exception", [
                'rule_id' => $rule->id,
                'error' => $errorMessage,
                'trace' => $e->getTraceAsString(),
            ]);
        }

        // Log execution
        $executionTime = microtime(true) - $startTime;
        $this->logExecution($rule, $triggerEvent, $data, $conditionsMet, $actionsTaken, $success, $errorMessage, $executionTime);

        return [
            'rule_id' => $rule->id,
            'rule_name' => $rule->name,
            'conditions_met' => $conditionsMet,
            'success' => $success,
            'actions_taken' => $actionsTaken,
            'error' => $errorMessage,
            'execution_time' => $executionTime,
        ];
    }

    /**
     * Log automation execution
     */
    protected function logExecution(
        AutomationRule $rule,
        string $triggerEvent,
        array $triggerData,
        bool $conditionsMet,
        array $actionsTaken,
        bool $success,
        ?string $errorMessage,
        float $executionTime
    ): void {
        AutomationExecution::create([
            'automation_rule_id' => $rule->id,
            'trigger_event' => $triggerEvent,
            'trigger_data' => $triggerData,
            'conditions_met' => $conditionsMet,
            'actions_taken' => $actionsTaken,
            'success' => $success,
            'error_message' => $errorMessage,
            'execution_time' => $executionTime,
        ]);
    }

    /**
     * Test a rule without executing actions
     */
    public function testRule(AutomationRule $rule, array $testData): array
    {
        try {
            $conditionsMet = $rule->checkConditions($testData);

            return [
                'success' => true,
                'conditions_met' => $conditionsMet,
                'message' => $conditionsMet
                    ? 'Conditions are met. Actions would be executed.'
                    : 'Conditions are not met. No actions would be executed.',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Test failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get execution statistics
     */
    public function getStatistics(?\Carbon\Carbon $startDate = null, ?\Carbon\Carbon $endDate = null): array
    {
        return AutomationExecution::getStatistics($startDate, $endDate);
    }
}
