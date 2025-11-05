<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class DashboardWidget extends Widget
{
    protected static ?int $sort = 10;

    protected int|string|array $columnSpan = 'full';

    protected static bool $isLazy = false;

    public function mount(): void
    {
        $this->view = $this->getView();
    }

    public function getView(): string
    {
        return 'filament.widgets.dashboard-widget';
    }
}
