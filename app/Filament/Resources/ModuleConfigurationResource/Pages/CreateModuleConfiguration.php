<?php

namespace App\Filament\Resources\ModuleConfigurationResource\Pages;

use App\Filament\Resources\ModuleConfigurationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateModuleConfiguration extends CreateRecord
{
    protected static string $resource = ModuleConfigurationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['is_available'] = true;

        return $data;
    }
}
