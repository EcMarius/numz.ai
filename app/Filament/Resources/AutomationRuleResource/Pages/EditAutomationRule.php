<?php

namespace App\Filament\Resources\AutomationRuleResource\Pages;

use App\Filament\Resources\AutomationRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAutomationRule extends EditRecord
{
    protected static string $resource = AutomationRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('view_executions')
                ->label('View Executions')
                ->icon('heroicon-o-clock')
                ->url(fn () => AutomationRuleResource::getUrl('executions', ['record' => $this->record])),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
