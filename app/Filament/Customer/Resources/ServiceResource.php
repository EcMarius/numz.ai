<?php

namespace App\Filament\Customer\Resources;

use App\Filament\Customer\Resources\ServiceResource\Pages;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ServiceResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-server-stack';

    protected static ?string $navigationLabel = 'My Services';

    protected static ?string $modelLabel = 'Service';

    protected static ?string $pluralModelLabel = 'Services';

    protected static ?string $navigationGroup = 'Services';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id())
            ->with(['product', 'invoices']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Service Details')
                    ->schema([
                        Forms\Components\TextInput::make('product.name')
                            ->label('Product/Service')
                            ->disabled(),

                        Forms\Components\TextInput::make('billing_cycle')
                            ->label('Billing Cycle')
                            ->disabled(),

                        Forms\Components\TextInput::make('total')
                            ->label('Price')
                            ->prefix('$')
                            ->disabled(),

                        Forms\Components\TextInput::make('status')
                            ->disabled(),

                        Forms\Components\DatePicker::make('next_due_date')
                            ->label('Next Due Date')
                            ->disabled(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Service Notes')
                            ->disabled()
                            ->rows(3),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Service')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

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
                        'pending' => 'warning',
                        'suspended' => 'warning',
                        'cancelled' => 'danger',
                        'completed' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Order Date')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'pending' => 'Pending',
                        'suspended' => 'Suspended',
                        'cancelled' => 'Cancelled',
                    ]),

                Tables\Filters\SelectFilter::make('billing_cycle')
                    ->options([
                        'monthly' => 'Monthly',
                        'quarterly' => 'Quarterly',
                        'semi_annually' => 'Semi-Annually',
                        'annually' => 'Annually',
                        'biennially' => 'Biennially',
                        'triennially' => 'Triennially',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('upgrade')
                    ->label('Upgrade')
                    ->icon('heroicon-m-arrow-up-circle')
                    ->color('success')
                    ->visible(fn (Order $record): bool => $record->status === 'active')
                    ->action(function (Order $record) {
                        // Upgrade logic would go here
                        \Filament\Notifications\Notification::make()
                            ->title('Upgrade Service')
                            ->body('Contact support to upgrade your service.')
                            ->info()
                            ->send();
                    }),

                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->visible(fn (Order $record): bool => $record->status === 'active')
                    ->requiresConfirmation()
                    ->modalHeading('Cancel Service')
                    ->modalDescription('Are you sure you want to cancel this service? This action cannot be undone.')
                    ->action(function (Order $record) {
                        $record->update([
                            'status' => 'cancelled',
                            'cancelled_at' => now(),
                            'cancellation_reason' => 'customer_request',
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Service Cancelled')
                            ->body('Your service has been cancelled successfully.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([])
            ->emptyStateHeading('No Services')
            ->emptyStateDescription('You don\'t have any services yet. Contact sales to get started!')
            ->emptyStateIcon('heroicon-o-server-stack');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServices::route('/'),
            'view' => Pages\ViewService::route('/{record}'),
        ];
    }
}
