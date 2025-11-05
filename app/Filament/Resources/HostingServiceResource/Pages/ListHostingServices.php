<?php

namespace App\Filament\Resources\HostingServiceResource\Pages;

use App\Filament\Resources\HostingServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHostingServices extends ListRecords
{
    protected static string $resource = HostingServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
