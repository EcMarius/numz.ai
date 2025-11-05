<?php

namespace App\Filament\Resources\DataDeletionRequestResource\Pages;

use App\Filament\Resources\DataDeletionRequestResource;
use App\Models\DataDeletionRequest;
use App\Services\UserDeletionService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditDataDeletionRequest extends EditRecord
{
    protected static string $resource = DataDeletionRequestResource::class;

    // Disable save and cancel buttons (form is read-only)
    protected function getFormActions(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('proceed')
                ->label('Proceed (Delete User Data)')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Complete Data Deletion Request?')
                ->modalDescription('This will permanently delete ALL user data from the system and send a final notification email. This action CANNOT be undone.')
                ->action(function (DataDeletionRequest $record) {
                    // Skip if already completed
                    if ($record->status === 'completed') {
                        Notification::make()
                            ->title('Already Completed')
                            ->body('This request has already been processed.')
                            ->warning()
                            ->send();
                        return;
                    }

                    $user = $record->user;

                    if (!$user) {
                        Notification::make()
                            ->title('User Not Found')
                            ->body('The user associated with this request no longer exists.')
                            ->danger()
                            ->send();
                        return;
                    }

                    // Prevent admin from deleting themselves
                    if ($user->id === auth()->id()) {
                        Notification::make()
                            ->title('Action Not Allowed')
                            ->body('You cannot delete your own account.')
                            ->danger()
                            ->send();
                        return;
                    }

                    // Send email notification BEFORE deletion (user won't exist after)
                    try {
                        \Mail::to($user->email)->send(new \App\Mail\DataDeletionCompleted($record));
                    } catch (\Exception $e) {
                        \Log::error('Failed to send data deletion completion email', [
                            'user_id' => $user->id,
                            'email' => $user->email,
                            'error' => $e->getMessage(),
                        ]);
                    }

                    // Delete ALL user data using the service
                    $deletionService = app(UserDeletionService::class);
                    $result = $deletionService->deleteUserCompletely($user, $record);

                    if ($result['success']) {
                        Notification::make()
                            ->title('Data Deletion Completed')
                            ->body('User and all data permanently deleted. Deleted: ' . array_sum($result['deleted_counts']) . ' records across ' . count($result['deleted_counts']) . ' tables.')
                            ->success()
                            ->duration(15000)
                            ->send();

                        // Redirect back to list
                        return redirect()->route('filament.admin.resources.data-deletion-requests.index');
                    } else {
                        Notification::make()
                            ->title('Deletion Failed')
                            ->body($result['message'])
                            ->danger()
                            ->duration(15000)
                            ->send();
                    }
                })
                ->visible(fn (DataDeletionRequest $record) => $record->status !== 'completed'),
        ];
    }
}
