<?php

namespace App\Filament\Resources\ModuleConfigurationResource\Pages;

use App\Filament\Resources\ModuleConfigurationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListModuleConfigurations extends ListRecords
{
    protected static string $resource = ModuleConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
