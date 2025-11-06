<?php

namespace App\Filament\Customer\Resources\InvoiceResource\Pages;

use App\Filament\Customer\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Invoices'),

            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                ->badge(fn () => \App\Models\Invoice::where('user_id', auth()->id())
                    ->where('status', 'pending')
                    ->count()),

            'paid' => Tab::make('Paid')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'paid')),

            'overdue' => Tab::make('Overdue')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'overdue'))
                ->badge(fn () => \App\Models\Invoice::where('user_id', auth()->id())
                    ->where('status', 'overdue')
                    ->count())
                ->badgeColor('danger'),
        ];
    }
}
