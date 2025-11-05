<?php

namespace App\Filament\Resources\DomainRegistrationResource\Pages;

use App\Filament\Resources\DomainRegistrationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDomainRegistrations extends ListRecords
{
    protected static string $resource = DomainRegistrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
