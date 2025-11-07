<?php

namespace App\Numz\Filament\Pages;

use App\Models\SystemUpdate;
use App\Models\UpdateBackup;
use App\Models\VersionCheck;
use App\Numz\Services\BackupService;
use App\Numz\Services\UpdateService;
use App\Numz\Services\VersionCheckerService;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;

class SystemUpdates extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-circle';

    protected static string $view = 'numz.filament.pages.system-updates';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'System Updates';

    protected static ?int $navigationSort = 99;

    public ?VersionCheck $latestCheck = null;
    public string $currentVersion = '';
    public bool $updateAvailable = false;
    public bool $updateInProgress = false;
    public ?SystemUpdate $currentUpdate = null;

    public function mount(): void
    {
        $this->loadUpdateStatus();
    }

    protected function loadUpdateStatus(): void
    {
        $this->currentVersion = config('updater.current_version', '1.0.0');
        $this->latestCheck = VersionCheck::getLatest();

        if ($this->latestCheck && $this->latestCheck->update_available) {
            $this->updateAvailable = true;
        }

        $updateService = app(UpdateService::class);
        $this->updateInProgress = $updateService->isUpdateInProgress();

        if ($this->updateInProgress) {
            $this->currentUpdate = $updateService->getLatestUpdate();
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(SystemUpdate::query()->orderBy('created_at', 'desc'))
            ->columns([
                TextColumn::make('version')
                    ->label('Version')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('previous_version')
                    ->label('From Version')
                    ->sortable(),

                TextColumn::make('update_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'major' => 'danger',
                        'minor' => 'warning',
                        'patch' => 'success',
                        'hotfix' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'failed' => 'danger',
                        'rolled_back' => 'warning',
                        'pending' => 'gray',
                        'downloading' => 'info',
                        'installing' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('progress_percentage')
                    ->label('Progress')
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->visible(fn ($record) => in_array($record->status, ['pending', 'downloading', 'installing'])),

                TextColumn::make('initiated_by.name')
                    ->label('Initiated By')
                    ->sortable(),

                TextColumn::make('started_at')
                    ->label('Started')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('completed_at')
                    ->label('Completed')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Action::make('view_changelog')
                    ->label('Changelog')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->modalHeading(fn ($record) => 'Changelog - Version ' . $record->version)
                    ->modalContent(fn ($record) => view('numz.filament.modals.changelog-modal', [
                        'changelog' => $record->changelog,
                    ]))
                    ->visible(fn ($record) => !empty($record->changelog)),

                Action::make('view_error')
                    ->label('View Error')
                    ->icon('heroicon-o-exclamation-circle')
                    ->color('danger')
                    ->modalHeading(fn ($record) => 'Update Error - Version ' . $record->version)
                    ->modalContent(fn ($record) => view('numz.filament.modals.error-modal', [
                        'error' => $record->error_message,
                    ]))
                    ->visible(fn ($record) => $record->status === 'failed' && !empty($record->error_message)),

                Action::make('rollback')
                    ->label('Rollback')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Rollback Update')
                    ->modalDescription('Are you sure you want to rollback this update? This will restore your system to the previous version.')
                    ->action(fn ($record) => $this->rollbackUpdate($record))
                    ->visible(fn ($record) => $record->canRollback()),
            ])
            ->filters([
                // Add filters if needed
            ]);
    }

    public function checkForUpdates(): void
    {
        try {
            $versionChecker = app(VersionCheckerService::class);
            $this->latestCheck = $versionChecker->checkForUpdates(force: true);
            $this->updateAvailable = $this->latestCheck->update_available;

            if ($this->updateAvailable) {
                Notification::make()
                    ->title('Update Available')
                    ->success()
                    ->body('Version ' . $this->latestCheck->latest_version . ' is now available!')
                    ->send();
            } else {
                Notification::make()
                    ->title('Up to Date')
                    ->success()
                    ->body('Your system is running the latest version.')
                    ->send();
            }

        } catch (\Exception $e) {
            Notification::make()
                ->title('Check Failed')
                ->danger()
                ->body('Failed to check for updates: ' . $e->getMessage())
                ->send();
        }

        $this->loadUpdateStatus();
    }

    public function startUpdate(): void
    {
        try {
            if (!$this->latestCheck || !$this->updateAvailable) {
                throw new \Exception('No update available');
            }

            if ($this->updateInProgress) {
                throw new \Exception('Update already in progress');
            }

            $updateService = app(UpdateService::class);

            // Start update in background
            dispatch(function() use ($updateService) {
                $updateService->applyUpdate($this->latestCheck, auth()->id());
            })->afterResponse();

            Notification::make()
                ->title('Update Started')
                ->warning()
                ->body('System update has begun. The page will refresh to show progress.')
                ->send();

            // Reload page after 2 seconds
            $this->js('setTimeout(() => window.location.reload(), 2000)');

        } catch (\Exception $e) {
            Notification::make()
                ->title('Update Failed')
                ->danger()
                ->body('Failed to start update: ' . $e->getMessage())
                ->send();
        }
    }

    public function rollbackUpdate(SystemUpdate $systemUpdate): void
    {
        try {
            $updateService = app(UpdateService::class);
            $updateService->rollbackUpdate($systemUpdate);

            Notification::make()
                ->title('Rollback Complete')
                ->success()
                ->body('System has been rolled back to version ' . $systemUpdate->previous_version)
                ->send();

            $this->loadUpdateStatus();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Rollback Failed')
                ->danger()
                ->body('Failed to rollback: ' . $e->getMessage())
                ->send();
        }
    }

    public function createManualBackup(): void
    {
        try {
            $backupService = app(BackupService::class);

            // Create a dummy system update for backup tracking
            $systemUpdate = SystemUpdate::create([
                'version' => $this->currentVersion,
                'previous_version' => $this->currentVersion,
                'update_type' => 'manual_backup',
                'status' => 'completed',
                'initiated_by' => auth()->id(),
            ]);

            $backup = $backupService->createBackup($systemUpdate);

            Notification::make()
                ->title('Backup Created')
                ->success()
                ->body('Manual backup created successfully. Size: ' . $this->formatBytes($backup->total_size))
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Backup Failed')
                ->danger()
                ->body('Failed to create backup: ' . $e->getMessage())
                ->send();
        }
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' B';
        }
    }

    public function getBackups(): array
    {
        return UpdateBackup::orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($backup) {
                return [
                    'id' => $backup->id,
                    'version' => $backup->version,
                    'created_at' => $backup->created_at->format('M d, Y g:i A'),
                    'total_size' => $this->formatBytes($backup->total_size),
                    'is_restorable' => $backup->is_restorable,
                    'files_exist' => $backup->filesExist(),
                ];
            })
            ->toArray();
    }

    public function deleteBackup(int $backupId): void
    {
        try {
            $backup = UpdateBackup::findOrFail($backupId);
            $backup->deleteFiles();
            $backup->delete();

            Notification::make()
                ->title('Backup Deleted')
                ->success()
                ->body('Backup has been deleted successfully.')
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Delete Failed')
                ->danger()
                ->body('Failed to delete backup: ' . $e->getMessage())
                ->send();
        }
    }

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->role === 'admin';
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('check_updates')
                ->label('Check for Updates')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->disabled($this->updateInProgress)
                ->action('checkForUpdates'),

            \Filament\Actions\Action::make('manual_backup')
                ->label('Create Backup')
                ->icon('heroicon-o-shield-check')
                ->color('info')
                ->disabled($this->updateInProgress)
                ->action('createManualBackup'),
        ];
    }
}
