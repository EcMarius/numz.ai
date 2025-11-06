<?php

namespace App\Filament\Resources\AutomationRuleResource\Pages;

use App\Filament\Resources\AutomationRuleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAutomationRule extends CreateRecord
{
    protected static string $resource = AutomationRuleResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['execution_count'] = 0;

        return $data;
    }
}
