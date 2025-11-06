<?php

namespace App\Filament\Customer\Widgets;

use App\Models\Invoice;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class UpcomingInvoicesWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Invoice::query()
                    ->where('user_id', auth()->id())
                    ->whereIn('status', ['pending', 'overdue'])
                    ->orderBy('due_date')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date()
                    ->sortable()
                    ->color(fn (Invoice $record): string =>
                        $record->due_date->isPast() ? 'danger' : 'success'
                    ),

                Tables\Columns\TextColumn::make('total')
                    ->label('Amount')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'overdue' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('pay')
                    ->label('Pay Now')
                    ->icon('heroicon-m-credit-card')
                    ->color('success')
                    ->url(fn (Invoice $record): string => route('filament.customer.resources.invoices.pay', ['record' => $record]))
                    ->visible(fn (Invoice $record): bool => in_array($record->status, ['pending', 'overdue'])),

                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-m-eye')
                    ->url(fn (Invoice $record): string => route('filament.customer.resources.invoices.view', ['record' => $record])),
            ])
            ->emptyStateHeading('No Pending Invoices')
            ->emptyStateDescription('You don\'t have any invoices due.')
            ->emptyStateIcon('heroicon-o-document-text');
    }
}
