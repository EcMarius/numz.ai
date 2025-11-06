<?php

namespace App\Filament\Resources\HostingServerResource\Pages;

use App\Filament\Resources\HostingServerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHostingServer extends EditRecord
{
    protected static string $resource = HostingServerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
