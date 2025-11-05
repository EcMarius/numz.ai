<?php

namespace App\Filament\Resources\FeatureShowcases\Pages;

use App\Filament\Resources\FeatureShowcases\FeatureShowcaseResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFeatureShowcase extends EditRecord
{
    protected static string $resource = FeatureShowcaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
