<?php

namespace App\Filament\Resources\WHMCSModuleResource\Pages;

use App\Filament\Resources\WHMCSModuleResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListWHMCSModules extends ListRecords
{
    protected static string $resource = WHMCSModuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('discover')
                ->label('Discover Modules')
                ->icon('heroicon-o-magnifying-glass')
                ->action(function () {
                    \Illuminate\Support\Facades\Cache::forget('whmcs_modules_list');
                    \App\Numz\WHMCS\ModuleLoader::discoverModules();

                    \Filament\Notifications\Notification::make()
                        ->title('Modules Refreshed')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('documentation')
                ->label('Module Documentation')
                ->icon('heroicon-o-document-text')
                ->url('https://developers.whmcs.com/')
                ->openUrlInNewTab(),
        ];
    }

    public function getTitle(): string
    {
        return 'WHMCS Modules';
    }

    public function getHeading(): string
    {
        return 'WHMCS Module Management';
    }

    public function getSubheading(): ?string
    {
        return 'Manage provisioning, payment, domain, and addon modules for WHMCS compatibility';
    }
}
