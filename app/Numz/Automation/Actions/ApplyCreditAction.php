<?php

namespace App\Numz\Automation\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class ApplyCreditAction implements ActionInterface
{
    public function execute(array $params, array $data): array
    {
        try {
            $userId = $params['user_id'] ?? $data['user_id'] ?? null;
            $amount = $params['amount'] ?? null;
            $description = $params['description'] ?? 'Automated credit application';

            if (!$userId) {
                throw new \Exception('User ID is required');
            }

            if (!$amount || $amount <= 0) {
                throw new \Exception('Valid credit amount is required');
            }

            $user = User::find($userId);

            if (!$user) {
                throw new \Exception("User with ID {$userId} not found");
            }

            // Apply credit to user account
            $currentCredit = $user->account_credit ?? 0;
            $newCredit = $currentCredit + $amount;

            $user->update([
                'account_credit' => $newCredit,
            ]);

            Log::info("Credit applied by automation", [
                'user_id' => $userId,
                'amount' => $amount,
                'new_balance' => $newCredit,
            ]);

            // TODO: Create credit transaction record if you have such a table

            return [
                'success' => true,
                'action' => 'apply_credit',
                'message' => "Applied {$amount} credit to user {$userId}. New balance: {$newCredit}",
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'action' => 'apply_credit',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getName(): string
    {
        return 'Apply Account Credit';
    }

    public function getDescription(): string
    {
        return 'Add credit to a user account';
    }

    public function getRequiredParams(): array
    {
        return [
            'user_id' => 'User ID to credit',
            'amount' => 'Credit amount',
            'description' => 'Transaction description (optional)',
        ];
    }
}
