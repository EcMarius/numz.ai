<?php

namespace App\Filament\Resources\Stats\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\Stats\StatResource;
use Filament\Resources\Pages\ListRecords;

class ListStats extends ListRecords
{
    protected static string $resource = StatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
