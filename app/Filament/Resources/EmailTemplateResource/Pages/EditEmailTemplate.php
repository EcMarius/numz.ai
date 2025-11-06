<?php

namespace App\Filament\Resources\EmailTemplateResource\Pages;

use App\Filament\Resources\EmailTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmailTemplate extends EditRecord
{
    protected static string $resource = EmailTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('preview')
                ->icon('heroicon-o-eye')
                ->modalContent(fn () => view('filament.resources.email-template.preview', ['template' => $this->record]))
                ->modalHeading('Preview Template')
                ->modalWidth('5xl'),
            Actions\DeleteAction::make()
                ->hidden(fn () => $this->record->is_system),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
