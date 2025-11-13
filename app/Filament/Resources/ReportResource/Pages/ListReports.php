<?php

namespace App\Filament\Resources\ReportResource\Pages;

use App\Filament\Resources\ReportResource;
use App\Models\Order;
use App\Models\User;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions;

class ListReports extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = ReportResource::class;

    protected static ?string $title = 'Reports & Analytics';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export_full_report')
                ->label('Export Full Report')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    // TODO: Implement full report export
                    \Filament\Notifications\Notification::make()
                        ->title('Full report export started')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('refresh_stats')
                ->label('Refresh Statistics')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    \Filament\Notifications\Notification::make()
                        ->title('Statistics refreshed')
                        ->success()
                        ->send();

                    $this->redirect(static::getUrl());
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return ReportResource::getWidgets();
    }

    public function getTabs(): array
    {
        $now = now();

        return [
            'all_time' => Tab::make('All Time')
                ->badge(fn () => Order::count()),

            'this_month' => Tab::make('This Month')
                ->badge(fn () => Order::whereMonth('created_at', $now->month)
                    ->whereYear('created_at', $now->year)
                    ->count())
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->whereMonth('created_at', $now->month)
                    ->whereYear('created_at', $now->year)),

            'last_month' => Tab::make('Last Month')
                ->badge(fn () => Order::whereMonth('created_at', $now->subMonth()->month)
                    ->whereYear('created_at', $now->year)
                    ->count())
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->whereMonth('created_at', $now->subMonth()->month)
                    ->whereYear('created_at', $now->year)),

            'this_quarter' => Tab::make('This Quarter')
                ->badge(fn () => Order::whereBetween('created_at', [
                    $now->copy()->startOfQuarter(),
                    $now->copy()->endOfQuarter()
                ])->count())
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->whereBetween('created_at', [
                        $now->copy()->startOfQuarter(),
                        $now->copy()->endOfQuarter()
                    ])),

            'this_year' => Tab::make('This Year')
                ->badge(fn () => Order::whereYear('created_at', $now->year)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->whereYear('created_at', $now->year)),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }
}
