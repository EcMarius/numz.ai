<?php

namespace App\Filament\Resources\FeatureShowcases\Pages;

use App\Filament\Resources\FeatureShowcases\FeatureShowcaseResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFeatureShowcase extends CreateRecord
{
    protected static string $resource = FeatureShowcaseResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
