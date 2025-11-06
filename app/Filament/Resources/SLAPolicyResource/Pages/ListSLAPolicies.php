<?php

namespace App\Filament\Resources\SLAPolicyResource\Pages;

use App\Filament\Resources\SLAPolicyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSLAPolicies extends ListRecords
{
    protected static string $resource = SLAPolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
