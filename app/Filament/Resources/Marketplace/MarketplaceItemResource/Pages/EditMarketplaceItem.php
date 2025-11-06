<?php

namespace App\Filament\Resources\Marketplace\MarketplaceItemResource\Pages;

use App\Filament\Resources\Marketplace\MarketplaceItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMarketplaceItem extends EditRecord
{
    protected static string $resource = MarketplaceItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
