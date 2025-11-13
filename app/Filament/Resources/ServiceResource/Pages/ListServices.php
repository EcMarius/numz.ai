<?php

namespace App\Filament\Resources\ServiceResource\Pages;

use App\Filament\Resources\ServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListServices extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = ServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return ServiceResource::getWidgets();
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Services'),

            'pending' => Tab::make('Pending')
                ->badge(fn () => $this->getModel()::where('status', 'pending')->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending')),

            'active' => Tab::make('Active')
                ->badge(fn () => $this->getModel()::where('status', 'active')->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'active')),

            'suspended' => Tab::make('Suspended')
                ->badge(fn () => $this->getModel()::where('status', 'suspended')->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'suspended')),

            'overdue' => Tab::make('Overdue')
                ->badge(fn () => $this->getModel()::where('next_due_date', '<', now())
                    ->whereIn('status', ['active', 'pending'])
                    ->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('next_due_date', '<', now())
                    ->whereIn('status', ['active', 'pending'])),
        ];
    }
}
