<?php

namespace App\Numz\Filament\Widgets;

use App\Models\SystemUpdate;
use App\Models\VersionCheck;
use App\Numz\Services\UpdateService;
use App\Numz\Services\VersionCheckerService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Log;

class SystemUpdateWidget extends Widget
{
    protected static string $view = 'numz.filament.widgets.system-update-widget';

    protected static ?int $sort = -10; // Show at top

    protected int | string | array $columnSpan = 'full';

    public ?VersionCheck $latestCheck = null;
    public ?SystemUpdate $currentUpdate = null;
    public string $currentVersion = '';
    public bool $updateAvailable = false;
    public bool $updateInProgress = false;

    public function mount(): void
    {
        $this->loadUpdateStatus();
    }

    protected function loadUpdateStatus(): void
    {
        $this->currentVersion = config('updater.current_version', '1.0.0');

        // Get latest version check
        $this->latestCheck = VersionCheck::getLatest();

        // Check if update is available
        if ($this->latestCheck && $this->latestCheck->update_available) {
            $this->updateAvailable = true;
        }

        // Check if update is in progress
        $updateService = app(UpdateService::class);
        $this->updateInProgress = $updateService->isUpdateInProgress();

        // Get current update if in progress
        if ($this->updateInProgress) {
            $this->currentUpdate = $updateService->getLatestUpdate();
        }
    }

    public function checkForUpdates(): void
    {
        try {
            $versionChecker = app(VersionCheckerService::class);
            $this->latestCheck = $versionChecker->checkForUpdates(force: true);

            $this->updateAvailable = $this->latestCheck->update_available;

            if ($this->updateAvailable) {
                $this->dispatch('update-available', [
                    'version' => $this->latestCheck->latest_version,
                ]);
            } else {
                $this->dispatch('no-update-available');
            }

        } catch (\Exception $e) {
            Log::error('Failed to check for updates', ['error' => $e->getMessage()]);
            $this->dispatch('update-check-failed', ['error' => $e->getMessage()]);
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

            $this->dispatch('update-started');
            $this->loadUpdateStatus();

        } catch (\Exception $e) {
            Log::error('Failed to start update', ['error' => $e->getMessage()]);
            $this->dispatch('update-start-failed', ['error' => $e->getMessage()]);
        }
    }

    public function getUpdateProgress(): ?array
    {
        if (!$this->currentUpdate) {
            return null;
        }

        return [
            'percentage' => $this->currentUpdate->progress_percentage,
            'status' => $this->currentUpdate->status,
            'steps' => $this->currentUpdate->update_steps,
        ];
    }

    public static function canView(): bool
    {
        // Only show to admins
        return auth()->check() && auth()->user()->role === 'admin';
    }
}
