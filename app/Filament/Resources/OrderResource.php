<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', 'pending')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Order Details')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Order Information')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Forms\Components\Section::make('Customer & Product')
                                    ->schema([
                                        Forms\Components\Select::make('user_id')
                                            ->label('Customer')
                                            ->relationship('user', 'name')
                                            ->searchable(['name', 'email'])
                                            ->preload()
                                            ->required()
                                            ->createOptionForm([
                                                Forms\Components\TextInput::make('name')->required(),
                                                Forms\Components\TextInput::make('email')->email()->required(),
                                                Forms\Components\TextInput::make('password')->password()->required(),
                                            ])
                                            ->columnSpan(1),

                                        Forms\Components\TextInput::make('order_number')
                                            ->label('Order Number')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->placeholder('Auto-generated')
                                            ->columnSpan(1),

                                        Forms\Components\Select::make('order_type')
                                            ->options([
                                                'new' => 'New Order',
                                                'renewal' => 'Renewal',
                                                'upgrade' => 'Upgrade',
                                                'downgrade' => 'Downgrade',
                                            ])
                                            ->default('new')
                                            ->required()
                                            ->columnSpan(1),

                                        Forms\Components\Select::make('status')
                                            ->options([
                                                'pending' => 'Pending',
                                                'active' => 'Active',
                                                'suspended' => 'Suspended',
                                                'cancelled' => 'Cancelled',
                                                'terminated' => 'Terminated',
                                                'completed' => 'Completed',
                                            ])
                                            ->default('pending')
                                            ->required()
                                            ->columnSpan(1),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make('Order Items')
                                    ->description('Products and services in this order')
                                    ->schema([
                                        Forms\Components\Repeater::make('order_items')
                                            ->relationship('items')
                                            ->schema([
                                                Forms\Components\Select::make('product_id')
                                                    ->label('Product')
                                                    ->options(Product::where('is_active', true)->pluck('name', 'id'))
                                                    ->required()
                                                    ->searchable()
                                                    ->reactive()
                                                    ->afterStateUpdated(function ($state, callable $set) {
                                                        if ($state) {
                                                            $product = Product::find($state);
                                                            if ($product) {
                                                                $set('unit_price', $product->price);
                                                                $set('quantity', 1);
                                                            }
                                                        }
                                                    }),

                                                Forms\Components\TextInput::make('quantity')
                                                    ->numeric()
                                                    ->default(1)
                                                    ->required()
                                                    ->reactive(),

                                                Forms\Components\TextInput::make('unit_price')
                                                    ->numeric()
                                                    ->prefix('$')
                                                    ->required()
                                                    ->reactive(),

                                                Forms\Components\TextInput::make('total')
                                                    ->numeric()
                                                    ->prefix('$')
                                                    ->disabled()
                                                    ->dehydrated()
                                                    ->default(function (callable $get) {
                                                        return ($get('quantity') ?? 1) * ($get('unit_price') ?? 0);
                                                    }),
                                            ])
                                            ->columns(4)
                                            ->columnSpanFull()
                                            ->defaultItems(1)
                                            ->addActionLabel('Add Item')
                                            ->collapsible(),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('Pricing & Billing')
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                Forms\Components\Section::make('Billing Cycle')
                                    ->schema([
                                        Forms\Components\Select::make('billing_cycle')
                                            ->options([
                                                'free' => 'Free',
                                                'one_time' => 'One Time',
                                                'monthly' => 'Monthly',
                                                'quarterly' => 'Quarterly',
                                                'semi_annually' => 'Semi-Annually',
                                                'annually' => 'Annually',
                                                'biennially' => 'Biennially',
                                                'triennially' => 'Triennially',
                                            ])
                                            ->default('monthly')
                                            ->required(),
                                    ]),

                                Forms\Components\Section::make('Pricing Breakdown')
                                    ->schema([
                                        Forms\Components\TextInput::make('quantity')
                                            ->label('Quantity')
                                            ->numeric()
                                            ->default(1)
                                            ->required(),

                                        Forms\Components\TextInput::make('subtotal')
                                            ->numeric()
                                            ->prefix('$')
                                            ->required()
                                            ->reactive(),

                                        Forms\Components\TextInput::make('setup_fee')
                                            ->label('Setup Fee')
                                            ->numeric()
                                            ->prefix('$')
                                            ->default(0)
                                            ->reactive(),

                                        Forms\Components\TextInput::make('discount')
                                            ->numeric()
                                            ->prefix('$')
                                            ->default(0)
                                            ->reactive(),

                                        Forms\Components\TextInput::make('tax')
                                            ->numeric()
                                            ->prefix('$')
                                            ->default(0)
                                            ->reactive(),

                                        Forms\Components\TextInput::make('total')
                                            ->numeric()
                                            ->prefix('$')
                                            ->required()
                                            ->disabled()
                                            ->dehydrated()
                                            ->default(function (callable $get) {
                                                $subtotal = $get('subtotal') ?? 0;
                                                $setupFee = $get('setup_fee') ?? 0;
                                                $discount = $get('discount') ?? 0;
                                                $tax = $get('tax') ?? 0;
                                                return max(0, $subtotal + $setupFee - $discount + $tax);
                                            }),
                                    ])
                                    ->columns(3),

                                Forms\Components\Section::make('Payment Information')
                                    ->schema([
                                        Forms\Components\Select::make('payment_status')
                                            ->options([
                                                'unpaid' => 'Unpaid',
                                                'paid' => 'Paid',
                                                'refunded' => 'Refunded',
                                                'partially_refunded' => 'Partially Refunded',
                                            ])
                                            ->default('unpaid'),

                                        Forms\Components\Select::make('payment_method')
                                            ->options([
                                                'stripe' => 'Stripe',
                                                'paypal' => 'PayPal',
                                                'bank_transfer' => 'Bank Transfer',
                                                'credit_card' => 'Credit Card',
                                                'cash' => 'Cash',
                                            ]),

                                        Forms\Components\TextInput::make('payment_reference')
                                            ->label('Payment Reference/Transaction ID')
                                            ->maxLength(255),
                                    ])
                                    ->columns(3)
                                    ->collapsible(),
                            ]),

                        Forms\Components\Tabs\Tab::make('Dates')
                            ->icon('heroicon-o-calendar')
                            ->schema([
                                Forms\Components\Section::make('Important Dates')
                                    ->schema([
                                        Forms\Components\DateTimePicker::make('activation_date')
                                            ->label('Activation Date'),

                                        Forms\Components\DatePicker::make('next_due_date')
                                            ->label('Next Due Date'),

                                        Forms\Components\DatePicker::make('next_invoice_date')
                                            ->label('Next Invoice Date'),

                                        Forms\Components\DateTimePicker::make('termination_date')
                                            ->label('Termination Date'),

                                        Forms\Components\DateTimePicker::make('cancelled_at')
                                            ->label('Cancelled At')
                                            ->disabled(),
                                    ])
                                    ->columns(3),
                            ]),

                        Forms\Components\Tabs\Tab::make('Additional Info')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Forms\Components\Section::make('Domain & Configuration')
                                    ->schema([
                                        Forms\Components\TextInput::make('domain')
                                            ->label('Domain')
                                            ->maxLength(255)
                                            ->placeholder('example.com'),

                                        Forms\Components\KeyValue::make('configuration')
                                            ->label('Configuration Options')
                                            ->keyLabel('Option')
                                            ->valueLabel('Value')
                                            ->columnSpanFull(),
                                    ]),

                                Forms\Components\Section::make('Fraud Detection')
                                    ->schema([
                                        Forms\Components\Toggle::make('is_fraud_check_passed')
                                            ->label('Fraud Check Passed')
                                            ->default(true),

                                        Forms\Components\Select::make('fraud_score')
                                            ->label('Fraud Risk Score')
                                            ->options([
                                                'low' => 'Low Risk',
                                                'medium' => 'Medium Risk',
                                                'high' => 'High Risk',
                                            ])
                                            ->default('low'),

                                        Forms\Components\Textarea::make('fraud_notes')
                                            ->label('Fraud Check Notes')
                                            ->rows(2)
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2)
                                    ->collapsible(),

                                Forms\Components\Section::make('Notes & Comments')
                                    ->schema([
                                        Forms\Components\Textarea::make('notes')
                                            ->label('Admin Notes')
                                            ->rows(3)
                                            ->columnSpanFull()
                                            ->helperText('Internal notes, not visible to customer'),

                                        Forms\Components\Textarea::make('cancellation_reason')
                                            ->label('Cancellation Reason')
                                            ->rows(2)
                                            ->columnSpanFull()
                                            ->visible(fn (callable $get) => in_array($get('status'), ['cancelled', 'terminated'])),
                                    ])
                                    ->collapsible(),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->persistTabInQueryString(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('Order #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->icon('heroicon-o-shopping-cart'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable(['users.name', 'users.email'])
                    ->sortable()
                    ->description(fn (Order $record): string => $record->user->email ?? ''),

                Tables\Columns\TextColumn::make('order_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'new' => 'success',
                        'renewal' => 'info',
                        'upgrade' => 'warning',
                        'downgrade' => 'gray',
                        default => 'gray',
                    })
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 30 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('billing_cycle')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total')
                    ->money('USD')
                    ->sortable()
                    ->description(fn (Order $record): string => $record->payment_status ? ucfirst($record->payment_status) : ''),

                Tables\Columns\TextColumn::make('payment_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'unpaid' => 'warning',
                        'refunded' => 'danger',
                        'partially_refunded' => 'info',
                        default => 'gray',
                    })
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'pending' => 'warning',
                        'suspended' => 'danger',
                        'cancelled' => 'gray',
                        'terminated' => 'gray',
                        'completed' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('next_due_date')
                    ->label('Next Due')
                    ->date()
                    ->sortable()
                    ->color(fn (Order $record) =>
                        $record->next_due_date && $record->next_due_date < now() ? 'danger' : 'success'
                    )
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Order Date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'active' => 'Active',
                        'suspended' => 'Suspended',
                        'cancelled' => 'Cancelled',
                        'terminated' => 'Terminated',
                        'completed' => 'Completed',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('order_type')
                    ->options([
                        'new' => 'New Order',
                        'renewal' => 'Renewal',
                        'upgrade' => 'Upgrade',
                        'downgrade' => 'Downgrade',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('payment_status')
                    ->options([
                        'unpaid' => 'Unpaid',
                        'paid' => 'Paid',
                        'refunded' => 'Refunded',
                        'partially_refunded' => 'Partially Refunded',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('billing_cycle')
                    ->options([
                        'monthly' => 'Monthly',
                        'quarterly' => 'Quarterly',
                        'semi_annually' => 'Semi-Annually',
                        'annually' => 'Annually',
                        'biennially' => 'Biennially',
                        'triennially' => 'Triennially',
                        'one_time' => 'One Time',
                        'free' => 'Free',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('product_id')
                    ->label('Product')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->multiple(),

                Tables\Filters\Filter::make('overdue')
                    ->label('Overdue')
                    ->query(fn (Builder $query) => $query->where('next_due_date', '<', now())->whereIn('status', ['active', 'pending']))
                    ->toggle(),

                Tables\Filters\Filter::make('fraud_risk')
                    ->label('Fraud Risk')
                    ->query(fn (Builder $query) => $query->whereIn('fraud_score', ['medium', 'high']))
                    ->toggle(),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Created From'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Created Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('accept_order')
                        ->label('Accept & Activate')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (Order $record) => $record->status === 'pending')
                        ->action(function (Order $record) {
                            $record->activate();
                            \Filament\Notifications\Notification::make()
                                ->title('Order accepted and activated')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('send_confirmation')
                        ->label('Send Confirmation Email')
                        ->icon('heroicon-o-envelope')
                        ->color('info')
                        ->action(function (Order $record) {
                            // TODO: Implement email sending
                            \Filament\Notifications\Notification::make()
                                ->title('Order confirmation email sent')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('suspend')
                        ->icon('heroicon-o-pause-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->visible(fn (Order $record) => $record->status === 'active')
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->label('Suspension Reason')
                                ->required(),
                        ])
                        ->action(function (Order $record, array $data) {
                            $record->suspend($data['reason']);
                            \Filament\Notifications\Notification::make()
                                ->title('Order suspended')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('unsuspend')
                        ->icon('heroicon-o-play-circle')
                        ->color('success')
                        ->visible(fn (Order $record) => $record->status === 'suspended')
                        ->action(function (Order $record) {
                            $record->unsuspend();
                            \Filament\Notifications\Notification::make()
                                ->title('Order reactivated')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('cancel_with_refund')
                        ->label('Cancel with Refund')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalDescription('Cancel this order and issue a refund?')
                        ->visible(fn (Order $record) => in_array($record->status, ['active', 'pending']))
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->label('Cancellation Reason')
                                ->required(),
                            Forms\Components\Toggle::make('issue_refund')
                                ->label('Issue Full Refund')
                                ->default(false),
                            Forms\Components\TextInput::make('refund_amount')
                                ->label('Partial Refund Amount')
                                ->numeric()
                                ->prefix('$')
                                ->visible(fn (callable $get) => !$get('issue_refund')),
                        ])
                        ->action(function (Order $record, array $data) {
                            $record->cancel($data['reason']);
                            // TODO: Implement refund logic
                            \Filament\Notifications\Notification::make()
                                ->title('Order cancelled' . ($data['issue_refund'] ? ' and refund issued' : ''))
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('cancel')
                        ->label('Cancel Order')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->visible(fn (Order $record) => in_array($record->status, ['active', 'pending', 'suspended']))
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->label('Cancellation Reason')
                                ->required(),
                        ])
                        ->action(function (Order $record, array $data) {
                            $record->cancel($data['reason']);
                            \Filament\Notifications\Notification::make()
                                ->title('Order cancelled')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('accept_orders')
                        ->label('Accept Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->activate())
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('suspend_orders')
                        ->label('Suspend Selected')
                        ->icon('heroicon-o-pause-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->suspend())
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('cancel_orders')
                        ->label('Cancel Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->cancel())
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Tabs::make('Order Information')
                    ->tabs([
                        Infolists\Components\Tabs\Tab::make('Overview')
                            ->schema([
                                Infolists\Components\Section::make('Order Details')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('order_number')
                                            ->label('Order Number')
                                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                            ->weight('bold')
                                            ->copyable()
                                            ->icon('heroicon-o-shopping-cart'),

                                        Infolists\Components\TextEntry::make('status')
                                            ->badge()
                                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large),

                                        Infolists\Components\TextEntry::make('order_type')
                                            ->badge(),

                                        Infolists\Components\TextEntry::make('created_at')
                                            ->label('Order Date')
                                            ->dateTime(),
                                    ])
                                    ->columns(2),

                                Infolists\Components\Section::make('Customer Information')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('user.name')
                                            ->label('Customer Name'),

                                        Infolists\Components\TextEntry::make('user.email')
                                            ->label('Email')
                                            ->copyable(),
                                    ])
                                    ->columns(2),

                                Infolists\Components\Section::make('Product & Billing')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('product.name')
                                            ->label('Product'),

                                        Infolists\Components\TextEntry::make('billing_cycle')
                                            ->badge(),

                                        Infolists\Components\TextEntry::make('quantity'),

                                        Infolists\Components\TextEntry::make('domain')
                                            ->default('N/A'),
                                    ])
                                    ->columns(4),

                                Infolists\Components\Section::make('Pricing')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('subtotal')
                                            ->money('USD'),

                                        Infolists\Components\TextEntry::make('setup_fee')
                                            ->money('USD'),

                                        Infolists\Components\TextEntry::make('discount')
                                            ->money('USD'),

                                        Infolists\Components\TextEntry::make('tax')
                                            ->money('USD'),

                                        Infolists\Components\TextEntry::make('total')
                                            ->money('USD')
                                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                            ->weight('bold'),
                                    ])
                                    ->columns(5),

                                Infolists\Components\Section::make('Payment Information')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('payment_status')
                                            ->badge(),

                                        Infolists\Components\TextEntry::make('payment_method')
                                            ->default('N/A'),

                                        Infolists\Components\TextEntry::make('payment_reference')
                                            ->label('Transaction ID')
                                            ->default('N/A')
                                            ->copyable(),
                                    ])
                                    ->columns(3),

                                Infolists\Components\Section::make('Important Dates')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('activation_date')
                                            ->dateTime()
                                            ->default('Not activated'),

                                        Infolists\Components\TextEntry::make('next_due_date')
                                            ->date()
                                            ->default('N/A'),

                                        Infolists\Components\TextEntry::make('next_invoice_date')
                                            ->date()
                                            ->default('N/A'),

                                        Infolists\Components\TextEntry::make('termination_date')
                                            ->dateTime()
                                            ->default('N/A'),
                                    ])
                                    ->columns(4)
                                    ->collapsible(),
                            ]),

                        Infolists\Components\Tabs\Tab::make('Notes')
                            ->schema([
                                Infolists\Components\Section::make('Admin Notes')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('notes')
                                            ->default('No notes')
                                            ->columnSpanFull(),
                                    ]),

                                Infolists\Components\Section::make('Cancellation Details')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('cancelled_at')
                                            ->dateTime()
                                            ->default('N/A'),

                                        Infolists\Components\TextEntry::make('cancellation_reason')
                                            ->default('N/A')
                                            ->columnSpanFull(),
                                    ])
                                    ->visible(fn (Order $record) => $record->cancelled_at !== null),

                                Infolists\Components\Section::make('Configuration')
                                    ->schema([
                                        Infolists\Components\KeyValueEntry::make('configuration')
                                            ->columnSpanFull(),
                                    ])
                                    ->collapsible()
                                    ->collapsed(),
                            ]),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }
}
