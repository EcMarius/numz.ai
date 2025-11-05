<?php

namespace App\Filament\Resources\PlatformSchemas\Pages;

use App\Filament\Resources\PlatformSchemas\PlatformSchemaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPlatformSchemas extends ListRecords
{
    protected static string $resource = PlatformSchemaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
