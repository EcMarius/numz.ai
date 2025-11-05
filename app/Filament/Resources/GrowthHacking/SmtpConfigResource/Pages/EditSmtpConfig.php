<?php

namespace App\Filament\Resources\GrowthHacking\SmtpConfigResource\Pages;

use App\Filament\Resources\GrowthHacking\SmtpConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSmtpConfig extends EditRecord
{
    protected static string $resource = SmtpConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
