<?php

namespace App\Filament\Resources\ModuleConfigurationResource\Pages;

use App\Filament\Resources\ModuleConfigurationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditModuleConfiguration extends EditRecord
{
    protected static string $resource = ModuleConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('test')
                ->icon('heroicon-o-beaker')
                ->color('warning')
                ->action(function () {
                    $success = $this->record->testConnection(auth()->id());

                    if ($success) {
                        \Filament\Notifications\Notification::make()
                            ->title('Connection test successful')
                            ->success()
                            ->send();
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title('Connection test failed')
                            ->body($this->record->test_error ?? 'Unknown error')
                            ->danger()
                            ->send();
                    }
                }),
            Actions\DeleteAction::make(),
        ];
    }
}
