<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DataDeletionRequestResource\Pages;
use App\Models\DataDeletionRequest;
use App\Services\UserDeletionService;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Illuminate\Database\Eloquent\Collection;
use BackedEnum;

class DataDeletionRequestResource extends Resource
{
    protected static ?string $model = DataDeletionRequest::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-trash';

    protected static ?string $navigationLabel = 'Data Deletion Requests';

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): ?string
    {
        return 'Privacy & Compliance';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\TextInput::make('user.name')
                    ->label('User Name')
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\TextInput::make('facebook_user_id')
                    ->label('Facebook User ID')
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\Textarea::make('reason')
                    ->label('User\'s Reason')
                    ->disabled()
                    ->dehydrated(false)
                    ->columnSpanFull(),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'rejected' => 'Rejected',
                    ])
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\TextInput::make('confirmation_code')
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\TextInput::make('ip_address')
                    ->label('IP Address')
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\TextInput::make('user_agent')
                    ->label('User Agent')
                    ->disabled()
                    ->dehydrated(false)
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('created_at')
                    ->label('Requested At')
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\DateTimePicker::make('completed_at')
                    ->label('Completed At')
                    ->disabled()
                    ->dehydrated(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('facebook_user_id')
                    ->label('Facebook ID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'primary' => 'processing',
                        'success' => 'completed',
                        'danger' => 'rejected',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('confirmation_code')
                    ->label('Code')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('completed_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'rejected' => 'Rejected',
                    ]),
            ])
            ->recordActions([
                Tables\Actions\Action::make('complete')
                    ->label('Complete')
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
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('bulkComplete')
                        ->label('Complete Selected Requests')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Complete Data Deletion Requests?')
                        ->modalDescription('This will permanently delete ALL user data for each selected request. This action CANNOT be undone.')
                        ->action(function (Collection $records) {
                            $deletionService = app(UserDeletionService::class);
                            $successCount = 0;
                            $failedCount = 0;
                            $skippedCount = 0;
                            $totalRecordsDeleted = 0;

                            foreach ($records as $record) {
                                // Skip already completed requests
                                if ($record->status === 'completed') {
                                    $skippedCount++;
                                    continue;
                                }

                                $user = $record->user;

                                if (!$user) {
                                    \Log::warning('User not found for deletion request', ['request_id' => $record->id]);
                                    $skippedCount++;
                                    continue;
                                }

                                // Prevent admin from deleting themselves
                                if ($user->id === auth()->id()) {
                                    \Log::warning('Admin tried to delete own account via bulk action', ['admin_id' => auth()->id()]);
                                    $skippedCount++;
                                    continue;
                                }

                                // Send email notification BEFORE deletion
                                try {
                                    \Mail::to($user->email)->send(new \App\Mail\DataDeletionCompleted($record));
                                } catch (\Exception $e) {
                                    \Log::error('Failed to send bulk deletion email', [
                                        'user_id' => $user->id,
                                        'error' => $e->getMessage(),
                                    ]);
                                }

                                // Delete ALL user data
                                $result = $deletionService->deleteUserCompletely($user, $record);

                                if ($result['success']) {
                                    $successCount++;
                                    $totalRecordsDeleted += array_sum($result['deleted_counts']);
                                } else {
                                    $failedCount++;
                                    \Log::error('Bulk deletion failed for user', [
                                        'user_id' => $user->id,
                                        'error' => $result['message'],
                                    ]);
                                }
                            }

                            // Show summary notification
                            $message = "Successfully deleted: {$successCount} user(s) ({$totalRecordsDeleted} total records).";
                            if ($failedCount > 0) $message .= " Failed: {$failedCount}.";
                            if ($skippedCount > 0) $message .= " Skipped: {$skippedCount}.";

                            Notification::make()
                                ->title('Bulk Deletion Complete')
                                ->body($message)
                                ->success()
                                ->duration(20000)
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDataDeletionRequests::route('/'),
            // Create disabled - requests are submitted by users only
            // 'create' => Pages\CreateDataDeletionRequest::route('/create'),
            'edit' => Pages\EditDataDeletionRequest::route('/{record}/edit'),
        ];
    }
}
