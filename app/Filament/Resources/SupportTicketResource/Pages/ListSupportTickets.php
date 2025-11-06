<?php

namespace App\Filament\Resources\SupportTicketResource\Pages;

use App\Filament\Resources\SupportTicketResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListSupportTickets extends ListRecords
{
    protected static string $resource = SupportTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Tickets'),

            'open' => Tab::make('Open')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'open'))
                ->badge(fn () => \App\Models\SupportTicket::where('status', 'open')->count()),

            'in_progress' => Tab::make('In Progress')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'in_progress'))
                ->badge(fn () => \App\Models\SupportTicket::where('status', 'in_progress')->count()),

            'waiting_customer' => Tab::make('Waiting on Customer')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'waiting_customer')),

            'my_tickets' => Tab::make('My Tickets')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('assigned_to', auth()->id()))
                ->badge(fn () => \App\Models\SupportTicket::where('assigned_to', auth()->id())->count()),

            'unassigned' => Tab::make('Unassigned')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('assigned_to'))
                ->badge(fn () => \App\Models\SupportTicket::whereNull('assigned_to')->count())
                ->badgeColor('danger'),

            'closed' => Tab::make('Closed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'closed')),
        ];
    }
}
