<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Orders'),
            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'pending')),
            'active' => Tab::make('Active')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'active')),
            'suspended' => Tab::make('Suspended')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'suspended')),
            'cancelled' => Tab::make('Cancelled')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'cancelled')),
        ];
    }
}
