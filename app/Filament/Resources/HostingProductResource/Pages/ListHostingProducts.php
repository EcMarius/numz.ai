<?php

namespace App\Filament\Resources\HostingProductResource\Pages;

use App\Filament\Resources\HostingProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHostingProducts extends ListRecords
{
    protected static string $resource = HostingProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
