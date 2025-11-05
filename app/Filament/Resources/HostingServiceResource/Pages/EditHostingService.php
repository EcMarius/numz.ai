<?php

namespace App\Filament\Resources\HostingServiceResource\Pages;

use App\Filament\Resources\HostingServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHostingService extends EditRecord
{
    protected static string $resource = HostingServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
