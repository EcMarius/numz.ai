<?php

namespace App\Filament\Resources\AutomationRuleResource\Pages;

use App\Filament\Resources\AutomationRuleResource;
use App\Models\AutomationExecution;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;

class ListAutomationRules extends ListRecords
{
    protected static string $resource = AutomationRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AutomationRuleResource\Widgets\AutomationStatsWidget::class,
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Rules'),
            'active' => Tab::make('Active')
                ->modifyQueryUsing(fn ($query) => $query->where('is_active', true)),
            'inactive' => Tab::make('Inactive')
                ->modifyQueryUsing(fn ($query) => $query->where('is_active', false)),
        ];
    }
}
