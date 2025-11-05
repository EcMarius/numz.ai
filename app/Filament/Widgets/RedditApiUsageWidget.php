<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Services\ApiUsageTracker;

class RedditApiUsageWidget extends Widget
{
    protected static ?int $sort = 10;

    protected int | string | array $columnSpan = 'full';

    public function mount(): void
    {
        $this->view = $this->getView();
    }

    public function getView(): string
    {
        return 'filament.widgets.reddit-api-usage';
    }

    public function getStats(): array
    {
        $status = ApiUsageTracker::getCurrentRateLimitStatus('reddit');
        $stats = ApiUsageTracker::getUsageStats('reddit');

        return [
            'today' => $stats['per_day'] ?? 0,
            'this_hour' => $stats['per_hour'] ?? 0,
            'rate_limit' => $status,
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }
}
