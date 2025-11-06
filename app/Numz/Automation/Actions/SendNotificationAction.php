<?php

namespace App\Numz\Automation\Actions;

use App\Models\User;
use Filament\Notifications\Notification;

class SendNotificationAction implements ActionInterface
{
    public function execute(array $params, array $data): array
    {
        try {
            $userId = $params['user_id'] ?? $data['user_id'] ?? null;
            $title = $params['title'] ?? 'Notification';
            $body = $params['body'] ?? '';
            $type = $params['type'] ?? 'info'; // success, warning, danger, info

            if (!$userId) {
                throw new \Exception('User ID is required');
            }

            $user = User::find($userId);

            if (!$user) {
                throw new \Exception("User with ID {$userId} not found");
            }

            // Send Filament notification
            $notification = Notification::make()
                ->title($title)
                ->body($body);

            // Set notification type
            match($type) {
                'success' => $notification->success(),
                'warning' => $notification->warning(),
                'danger' => $notification->danger(),
                default => $notification->info(),
            };

            $notification->sendToDatabase($user);

            return [
                'success' => true,
                'action' => 'send_notification',
                'message' => "Notification sent to user {$userId}",
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'action' => 'send_notification',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getName(): string
    {
        return 'Send Notification';
    }

    public function getDescription(): string
    {
        return 'Send an in-app notification to a user';
    }

    public function getRequiredParams(): array
    {
        return [
            'user_id' => 'User ID to notify',
            'title' => 'Notification title',
            'body' => 'Notification body',
            'type' => 'Notification type (success, warning, danger, info)',
        ];
    }
}
