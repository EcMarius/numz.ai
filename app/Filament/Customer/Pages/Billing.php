<?php

namespace App\Filament\Customer\Pages;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;

class Billing extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Account';

    protected static ?int $navigationSort = 9;

    protected static string $view = 'filament.customer.pages.billing';

    public ?array $data = [];

    public function mount(): void
    {
        $user = auth()->user();

        $this->form->fill([
            'payment_method_type' => $user->payment_method_type ?? 'credit_card',
            'billing_email' => $user->billing_email ?? $user->email,
            'billing_address' => $user->billing_address ?? $user->address,
            'billing_city' => $user->billing_city ?? $user->city,
            'billing_state' => $user->billing_state ?? $user->state,
            'billing_postal_code' => $user->billing_postal_code ?? $user->postal_code,
            'billing_country' => $user->billing_country ?? $user->country,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Primary Payment Method')
                    ->schema([
                        Forms\Components\Radio::make('payment_method_type')
                            ->label('Payment Method')
                            ->options([
                                'credit_card' => 'Credit Card',
                                'paypal' => 'PayPal',
                                'bank_transfer' => 'Bank Transfer',
                            ])
                            ->default('credit_card')
                            ->reactive()
                            ->columnSpanFull(),

                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('card_number')
                                ->label('Card Number')
                                ->placeholder('**** **** **** 1234')
                                ->maxLength(19),

                            Forms\Components\TextInput::make('card_name')
                                ->label('Cardholder Name'),

                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('expiry')
                                        ->label('Expiry (MM/YY)')
                                        ->placeholder('12/25')
                                        ->maxLength(5),

                                    Forms\Components\TextInput::make('cvv')
                                        ->label('CVV')
                                        ->placeholder('123')
                                        ->maxLength(4),
                                ]),
                        ])
                        ->visible(fn (Forms\Get $get): bool => $get('payment_method_type') === 'credit_card'),
                    ]),

                Forms\Components\Section::make('Billing Information')
                    ->schema([
                        Forms\Components\TextInput::make('billing_email')
                            ->label('Billing Email')
                            ->email()
                            ->required(),

                        Forms\Components\Textarea::make('billing_address')
                            ->label('Billing Address')
                            ->rows(2)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('billing_city')
                            ->label('City'),

                        Forms\Components\TextInput::make('billing_state')
                            ->label('State/Province'),

                        Forms\Components\TextInput::make('billing_postal_code')
                            ->label('Postal Code'),

                        Forms\Components\TextInput::make('billing_country')
                            ->label('Country'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Account Credit')
                    ->schema([
                        Forms\Components\Placeholder::make('account_credit')
                            ->label('Available Credit')
                            ->content(fn (): string => '$' . number_format(auth()->user()->account_credit ?? 0, 2)),

                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('add_credit')
                                ->label('Add Credit')
                                ->icon('heroicon-m-plus-circle')
                                ->color('success')
                                ->form([
                                    Forms\Components\TextInput::make('amount')
                                        ->label('Amount to Add')
                                        ->numeric()
                                        ->prefix('$')
                                        ->required()
                                        ->minValue(10)
                                        ->maxValue(10000),
                                ])
                                ->action(function (array $data) {
                                    Notification::make()
                                        ->title('Add Credit')
                                        ->body('This feature will redirect you to payment processing.')
                                        ->info()
                                        ->send();
                                }),
                        ]),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                \App\Models\Transaction::query()
                    ->where('user_id', auth()->id())
                    ->orderBy('created_at', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('transaction_id')
                    ->label('Transaction ID')
                    ->searchable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'payment' => 'success',
                        'refund' => 'warning',
                        'credit' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('amount')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Payment Method')
                    ->badge(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->heading('Transaction History')
            ->emptyStateHeading('No Transactions')
            ->emptyStateDescription('You don\'t have any transactions yet.')
            ->emptyStateIcon('heroicon-o-banknotes');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        auth()->user()->update([
            'payment_method_type' => $data['payment_method_type'],
            'billing_email' => $data['billing_email'],
            'billing_address' => $data['billing_address'] ?? null,
            'billing_city' => $data['billing_city'] ?? null,
            'billing_state' => $data['billing_state'] ?? null,
            'billing_postal_code' => $data['billing_postal_code'] ?? null,
            'billing_country' => $data['billing_country'] ?? null,
        ]);

        Notification::make()
            ->title('Billing Information Updated')
            ->body('Your billing information has been updated successfully.')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Forms\Components\Actions\Action::make('save')
                ->label('Save Changes')
                ->submit('save')
                ->color('primary'),
        ];
    }
}
