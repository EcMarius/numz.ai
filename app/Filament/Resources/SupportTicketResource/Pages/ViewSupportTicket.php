<?php

namespace App\Filament\Resources\SupportTicketResource\Pages;

use App\Filament\Resources\SupportTicketResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSupportTicket extends ViewRecord
{
    protected static string $resource = SupportTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\Action::make('assign_to_me')
                ->icon('heroicon-o-user')
                ->color('success')
                ->visible(fn () => !$this->record->assigned_to)
                ->action(function () {
                    $this->record->assignTo(auth()->user());
                }),

            Actions\Action::make('close')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => !$this->record->isClosed())
                ->action(function () {
                    $this->record->close();
                })
                ->requiresConfirmation(),

            Actions\Action::make('reopen')
                ->icon('heroicon-o-arrow-path-rounded-square')
                ->color('success')
                ->visible(fn () => $this->record->isClosed())
                ->action(function () {
                    $this->record->reopen();
                }),
        ];
    }
}
