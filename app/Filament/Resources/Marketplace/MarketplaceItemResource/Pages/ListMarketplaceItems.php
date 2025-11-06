<?php

namespace App\Filament\Resources\Marketplace\MarketplaceItemResource\Pages;

use App\Filament\Resources\Marketplace\MarketplaceItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMarketplaceItems extends ListRecords
{
    protected static string $resource = MarketplaceItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
