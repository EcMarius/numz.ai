<?php

namespace App\Filament\Resources\HostingServerResource\Pages;

use App\Filament\Resources\HostingServerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHostingServers extends ListRecords
{
    protected static string $resource = HostingServerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
