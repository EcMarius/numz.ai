<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use App\Services\ApiUsageTracker;
use App\Models\ApiUsageLog;

class ApiUsageMonitor extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'API Usage Monitor';

    protected static ?int $navigationSort = 100;

    public $platform = 'reddit';
    public $stats = [];
    public $rateLimitStatus = null;
    public $hourlyData = [];

    public function getView(): string
    {
        return 'filament.pages.api-usage-monitor';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'System';
    }

    public function mount()
    {
        $this->loadStats();
    }

    public function loadStats()
    {
        $this->stats = ApiUsageTracker::getUsageStats($this->platform);
        $this->rateLimitStatus = ApiUsageTracker::getCurrentRateLimitStatus($this->platform);
        $this->hourlyData = ApiUsageTracker::getHourlyChartData($this->platform, 24);
    }

    public function refreshStats()
    {
        $this->loadStats();
    }

    public function changePlatform($platform)
    {
        $this->platform = $platform;
        $this->loadStats();
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->action('refreshStats'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }
}
