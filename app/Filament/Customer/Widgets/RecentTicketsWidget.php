<?php

namespace App\Filament\Customer\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentTicketsWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        // Check if Ticket model exists
        if (!class_exists(\App\Models\Ticket::class)) {
            return $table
                ->query(fn () => new class { public function get() { return collect(); } })
                ->emptyStateHeading('Support Tickets')
                ->emptyStateDescription('Support ticket system coming soon.')
                ->emptyStateIcon('heroicon-o-ticket');
        }

        return $table
            ->query(
                \App\Models\Ticket::query()
                    ->where('user_id', auth()->id())
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('ticket_number')
                    ->label('Ticket #')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('subject')
                    ->label('Subject')
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'low' => 'success',
                        'medium' => 'warning',
                        'high' => 'danger',
                        'urgent' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'open' => 'info',
                        'in_progress' => 'warning',
                        'resolved' => 'success',
                        'closed' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-m-eye')
                    ->url(fn ($record): string => route('filament.customer.resources.tickets.view', ['record' => $record])),
            ])
            ->emptyStateHeading('No Support Tickets')
            ->emptyStateDescription('You haven\'t created any support tickets yet.')
            ->emptyStateIcon('heroicon-o-ticket');
    }
}
