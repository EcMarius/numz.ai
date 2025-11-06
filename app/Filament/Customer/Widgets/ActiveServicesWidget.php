<?php

namespace App\Filament\Customer\Widgets;

use App\Models\Order;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class ActiveServicesWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Order::query()
                    ->where('user_id', auth()->id())
                    ->where('status', 'active')
                    ->with(['product'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Service')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('billing_cycle')
                    ->label('Billing Cycle')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('next_due_date')
                    ->label('Next Due')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total')
                    ->label('Amount')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'suspended' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Manage')
                    ->icon('heroicon-m-eye')
                    ->url(fn (Order $record): string => route('filament.customer.resources.services.view', ['record' => $record]))
                    ->openUrlInNewTab(false),
            ])
            ->emptyStateHeading('No Active Services')
            ->emptyStateDescription('You don\'t have any active services yet.')
            ->emptyStateIcon('heroicon-o-server-stack');
    }
}
