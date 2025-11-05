<?php

namespace App\Filament\Resources\PlatformSchemas\Pages;

use App\Filament\Resources\PlatformSchemas\PlatformSchemaResource;
use App\Services\SchemaService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPlatformSchema extends EditRecord
{
    protected static string $resource = PlatformSchemaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        // Clear schema cache after saving
        SchemaService::clearCache(
            $this->record->platform,
            $this->record->page_type
        );
    }
}
