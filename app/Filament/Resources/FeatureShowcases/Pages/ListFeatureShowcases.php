<?php

namespace App\Filament\Resources\FeatureShowcases\Pages;

use App\Filament\Resources\FeatureShowcases\FeatureShowcaseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFeatureShowcases extends ListRecords
{
    protected static string $resource = FeatureShowcaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
