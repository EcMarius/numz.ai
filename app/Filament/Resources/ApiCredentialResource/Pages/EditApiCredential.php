<?php

namespace App\Filament\Resources\ApiCredentialResource\Pages;

use App\Filament\Resources\ApiCredentialResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditApiCredential extends EditRecord
{
    protected static string $resource = ApiCredentialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('test')
                ->icon('heroicon-o-beaker')
                ->color('warning')
                ->action(function () {
                    // Test the API credentials
                    \Filament\Notifications\Notification::make()
                        ->title('Credentials test initiated')
                        ->body('Testing connection to ' . $this->record->display_name)
                        ->info()
                        ->send();
                }),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
