<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentTransactionResource\Pages;
use App\Models\PaymentTransaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentTransactionResource extends Resource
{
    protected static ?string $model = PaymentTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Billing';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Transaction Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->required()
                            ->label('Customer'),

                        Forms\Components\TextInput::make('transaction_id')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->label('Transaction ID'),

                        Forms\Components\Select::make('gateway')
                            ->options([
                                'stripe' => 'Stripe',
                                'paypal' => 'PayPal',
                                'coinbase' => 'Coinbase',
                                'razorpay' => 'Razorpay',
                                'paysafecard' => 'Paysafecard',
                                '2checkout' => '2Checkout',
                                'bank_transfer' => 'Bank Transfer',
                                'cash' => 'Cash',
                                'credits' => 'Credits',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('amount')
                            ->numeric()
                            ->prefix('$')
                            ->required(),

                        Forms\Components\TextInput::make('fee')
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->label('Gateway Fee'),

                        Forms\Components\Select::make('currency')
                            ->options([
                                'USD' => 'USD',
                                'EUR' => 'EUR',
                                'GBP' => 'GBP',
                            ])
                            ->default('USD')
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'completed' => 'Completed',
                                'failed' => 'Failed',
                                'refunded' => 'Refunded',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required()
                            ->default('pending'),

                        Forms\Components\Select::make('invoice_id')
                            ->relationship('invoice', 'invoice_number')
                            ->searchable()
                            ->label('Related Invoice')
                            ->placeholder('None'),

                        Forms\Components\KeyValue::make('metadata')
                            ->label('Additional Data'),

                        Forms\Components\Textarea::make('notes')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transaction_id')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('gateway')
                    ->colors([
                        'primary' => 'stripe',
                        'warning' => 'paypal',
                        'success' => 'coinbase',
                        'info' => 'razorpay',
                        'secondary' => ['paysafecard', '2checkout', 'bank_transfer', 'cash', 'credits'],
                    ]),

                Tables\Columns\TextColumn::make('amount')
                    ->money('usd')
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('fee')
                    ->money('usd')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'completed',
                        'danger' => 'failed',
                        'secondary' => 'refunded',
                        'gray' => 'cancelled',
                    ]),

                Tables\Columns\TextColumn::make('invoice.invoice_number')
                    ->label('Invoice')
                    ->searchable()
                    ->url(fn ($record) => $record->invoice ? route('filament.admin.resources.invoices.view', $record->invoice) : null),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->since(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('gateway')
                    ->options([
                        'stripe' => 'Stripe',
                        'paypal' => 'PayPal',
                        'coinbase' => 'Coinbase',
                        'razorpay' => 'Razorpay',
                        'paysafecard' => 'Paysafecard',
                        '2checkout' => '2Checkout',
                        'bank_transfer' => 'Bank Transfer',
                        'cash' => 'Cash',
                        'credits' => 'Credits',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                        'cancelled' => 'Cancelled',
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('mark_completed')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (PaymentTransaction $record) => $record->status === 'pending')
                    ->action(function (PaymentTransaction $record) {
                        $record->update(['status' => 'completed']);
                    })
                    ->requiresConfirmation(),

                Tables\Actions\Action::make('mark_failed')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (PaymentTransaction $record) => $record->status === 'pending')
                    ->action(function (PaymentTransaction $record) {
                        $record->update(['status' => 'failed']);
                    })
                    ->requiresConfirmation(),

                Tables\Actions\Action::make('refund')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->visible(fn (PaymentTransaction $record) => $record->status === 'completed')
                    ->form([
                        Forms\Components\TextInput::make('refund_amount')
                            ->numeric()
                            ->prefix('$')
                            ->label('Refund Amount')
                            ->required(),
                        Forms\Components\Textarea::make('refund_reason')
                            ->label('Reason')
                            ->required(),
                    ])
                    ->action(function (PaymentTransaction $record, array $data) {
                        // TODO: Process refund through gateway
                        $record->update(['status' => 'refunded']);
                    })
                    ->requiresConfirmation(),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ExportBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentTransactions::route('/'),
            'create' => Pages\CreatePaymentTransaction::route('/create'),
            'edit' => Pages\EditPaymentTransaction::route('/{record}/edit'),
            'view' => Pages\ViewPaymentTransaction::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
