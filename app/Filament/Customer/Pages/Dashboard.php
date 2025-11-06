<?php

namespace App\Filament\Customer\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $view = 'filament.customer.pages.dashboard';

    public function getWidgets(): array
    {
        return [
            \App\Filament\Customer\Widgets\AccountOverviewWidget::class,
            \App\Filament\Customer\Widgets\ActiveServicesWidget::class,
            \App\Filament\Customer\Widgets\UpcomingInvoicesWidget::class,
            \App\Filament\Customer\Widgets\RecentTicketsWidget::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return 2;
    }
}
