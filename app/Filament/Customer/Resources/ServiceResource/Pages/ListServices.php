<?php

namespace App\Filament\Customer\Resources\ServiceResource\Pages;

use App\Filament\Customer\Resources\ServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServices extends ListRecords
{
    protected static string $resource = ServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('order_new')
                ->label('Order New Service')
                ->icon('heroicon-m-plus-circle')
                ->color('primary')
                ->url(fn (): string => route('filament.customer.pages.order-service')),
        ];
    }
}
