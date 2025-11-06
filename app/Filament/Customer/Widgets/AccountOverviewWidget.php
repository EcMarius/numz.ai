<?php

namespace App\Filament\Customer\Widgets;

use App\Models\Invoice;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AccountOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = auth()->user();

        $activeServices = Order::where('user_id', $user->id)
            ->where('status', 'active')
            ->count();

        $unpaidInvoices = Invoice::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'overdue'])
            ->count();

        $totalSpent = Invoice::where('user_id', $user->id)
            ->where('status', 'paid')
            ->sum('total');

        $nextInvoice = Invoice::where('user_id', $user->id)
            ->where('status', 'pending')
            ->orderBy('due_date')
            ->first();

        return [
            Stat::make('Active Services', $activeServices)
                ->description('Currently active')
                ->descriptionIcon('heroicon-m-server-stack')
                ->color('success'),

            Stat::make('Unpaid Invoices', $unpaidInvoices)
                ->description($unpaidInvoices > 0 ? 'Requires payment' : 'All paid')
                ->descriptionIcon('heroicon-m-document-text')
                ->color($unpaidInvoices > 0 ? 'warning' : 'success'),

            Stat::make('Total Spent', '$' . number_format($totalSpent, 2))
                ->description('Lifetime value')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('primary'),

            Stat::make('Next Invoice', $nextInvoice ? '$' . number_format($nextInvoice->total, 2) : 'N/A')
                ->description($nextInvoice ? 'Due ' . $nextInvoice->due_date->format('M d, Y') : 'No pending invoices')
                ->descriptionIcon('heroicon-m-calendar')
                ->color($nextInvoice ? 'info' : 'gray'),
        ];
    }
}
