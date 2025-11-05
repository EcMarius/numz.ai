<?php

namespace App\Filament\Resources\Stats\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\Stats\StatResource;
use Filament\Resources\Pages\EditRecord;

class EditStat extends EditRecord
{
    protected static string $resource = StatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
