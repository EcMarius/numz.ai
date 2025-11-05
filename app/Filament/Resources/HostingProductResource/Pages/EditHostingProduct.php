<?php

namespace App\Filament\Resources\HostingProductResource\Pages;

use App\Filament\Resources\HostingProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHostingProduct extends EditRecord
{
    protected static string $resource = HostingProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
