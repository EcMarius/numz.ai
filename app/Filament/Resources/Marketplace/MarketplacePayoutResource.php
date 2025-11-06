<?php

namespace App\Filament\Resources\Marketplace;

use App\Filament\Resources\Marketplace\MarketplacePayoutResource\Pages;
use App\Models\Marketplace\MarketplacePayout;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class MarketplacePayoutResource extends Resource
{
    protected static ?string $model = MarketplacePayout::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Payouts';

    protected static ?string $navigationGroup = 'Marketplace';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('creator_id')
                    ->relationship('creator', 'name')
                    ->required()
                    ->searchable(),
                Forms\Components\TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                Forms\Components\Select::make('method')
                    ->options([
                        'stripe' => 'Stripe',
                        'paypal' => 'PayPal',
                        'bank_transfer' => 'Bank Transfer',
                    ])
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('transaction_id')
                    ->maxLength(255),
                Forms\Components\Textarea::make('failure_reason')
                    ->maxLength(1000),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('creator.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->money('usd')
                    ->sortable(),
                Tables\Columns\TextColumn::make('earnings_count')
                    ->label('Items')
                    ->sortable(),
                Tables\Columns\TextColumn::make('method')
                    ->badge()
                    ->colors([
                        'success' => 'stripe',
                        'info' => 'paypal',
                        'warning' => 'bank_transfer',
                    ]),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'processing',
                        'success' => 'completed',
                        'danger' => 'failed',
                        'secondary' => 'cancelled',
                    ]),
                Tables\Columns\TextColumn::make('requested_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('completed_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('method')
                    ->options([
                        'stripe' => 'Stripe',
                        'paypal' => 'PayPal',
                        'bank_transfer' => 'Bank Transfer',
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\Action::make('process')
                        ->icon('heroicon-o-arrow-path')
                        ->color('info')
                        ->requiresConfirmation()
                        ->visible(fn (MarketplacePayout $record) => $record->status === 'pending')
                        ->action(function (MarketplacePayout $record) {
                            $record->markProcessing();
                            Notification::make()
                                ->success()
                                ->title('Payout marked as processing')
                                ->send();
                        }),
                    Tables\Actions\Action::make('complete')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\TextInput::make('transaction_id')
                                ->required()
                                ->label('Transaction ID'),
                        ])
                        ->visible(fn (MarketplacePayout $record) => in_array($record->status, ['pending', 'processing']))
                        ->action(function (MarketplacePayout $record, array $data) {
                            $record->markCompleted($data['transaction_id']);
                            Notification::make()
                                ->success()
                                ->title('Payout marked as completed')
                                ->body('Creator will be notified.')
                                ->send();
                        }),
                    Tables\Actions\Action::make('fail')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\Textarea::make('failure_reason')
                                ->required()
                                ->label('Reason for failure'),
                        ])
                        ->visible(fn (MarketplacePayout $record) => in_array($record->status, ['pending', 'processing']))
                        ->action(function (MarketplacePayout $record, array $data) {
                            $record->markFailed($data['failure_reason']);
                            Notification::make()
                                ->danger()
                                ->title('Payout marked as failed')
                                ->body('Creator will be notified.')
                                ->send();
                        }),
                ]),
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('requested_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMarketplacePayouts::route('/'),
            'view' => Pages\ViewMarketplacePayout::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count();
    }
}
