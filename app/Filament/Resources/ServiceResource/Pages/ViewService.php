<?php

namespace App\Filament\Resources\ServiceResource\Pages;

use App\Filament\Resources\ServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewService extends ViewRecord
{
    protected static string $resource = ServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),

            Actions\Action::make('provision')
                ->label('Provision Service')
                ->icon('heroicon-o-play')
                ->color('success')
                ->visible(fn () => $this->record->status === 'pending')
                ->requiresConfirmation()
                ->action(function () {
                    // TODO: Implement provisioning
                    $this->record->update(['status' => 'active']);
                    \Filament\Notifications\Notification::make()
                        ->title('Service provisioning initiated')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('suspend')
                ->icon('heroicon-o-pause')
                ->color('warning')
                ->visible(fn () => $this->record->status === 'active')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['status' => 'suspended']);
                    \Filament\Notifications\Notification::make()
                        ->title('Service suspended')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('unsuspend')
                ->icon('heroicon-o-play')
                ->color('success')
                ->visible(fn () => $this->record->status === 'suspended')
                ->action(function () {
                    $this->record->update(['status' => 'active']);
                    \Filament\Notifications\Notification::make()
                        ->title('Service reactivated')
                        ->success()
                        ->send();
                }),
        ];
    }
}
