<?php

namespace App\Filament\Customer\Resources\InvoiceResource\Pages;

use App\Filament\Customer\Resources\InvoiceResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;
use Filament\Notifications\Notification;

class PayInvoice extends Page
{
    protected static string $resource = InvoiceResource::class;

    protected static string $view = 'filament.customer.pages.pay-invoice';

    public ?array $data = [];

    public function mount(): void
    {
        // Check if invoice can be paid
        if (!in_array($this->record->status, ['pending', 'overdue'])) {
            Notification::make()
                ->title('Invoice cannot be paid')
                ->body('This invoice has already been paid or cancelled.')
                ->warning()
                ->send();

            redirect()->route('filament.customer.resources.invoices.view', ['record' => $this->record]);
        }

        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Invoice Summary')
                    ->schema([
                        Forms\Components\Placeholder::make('invoice_number')
                            ->label('Invoice Number')
                            ->content($this->record->invoice_number),

                        Forms\Components\Placeholder::make('amount')
                            ->label('Amount Due')
                            ->content('$' . number_format($this->record->total, 2)),

                        Forms\Components\Placeholder::make('due_date')
                            ->label('Due Date')
                            ->content($this->record->due_date->format('M d, Y')),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Payment Method')
                    ->schema([
                        Forms\Components\Radio::make('payment_method')
                            ->label('Select Payment Method')
                            ->options([
                                'credit_card' => 'Credit Card',
                                'paypal' => 'PayPal',
                                'bank_transfer' => 'Bank Transfer',
                                'account_credit' => 'Account Credit',
                            ])
                            ->required()
                            ->default('credit_card')
                            ->reactive(),

                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('card_number')
                                ->label('Card Number')
                                ->placeholder('1234 5678 9012 3456')
                                ->required()
                                ->maxLength(19),

                            Forms\Components\TextInput::make('card_name')
                                ->label('Cardholder Name')
                                ->required(),

                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('expiry')
                                        ->label('Expiry (MM/YY)')
                                        ->placeholder('12/25')
                                        ->required()
                                        ->maxLength(5),

                                    Forms\Components\TextInput::make('cvv')
                                        ->label('CVV')
                                        ->placeholder('123')
                                        ->required()
                                        ->maxLength(4),
                                ]),
                        ])
                        ->visible(fn (Forms\Get $get): bool => $get('payment_method') === 'credit_card'),

                        Forms\Components\Placeholder::make('paypal_info')
                            ->label('')
                            ->content('You will be redirected to PayPal to complete your payment.')
                            ->visible(fn (Forms\Get $get): bool => $get('payment_method') === 'paypal'),

                        Forms\Components\Placeholder::make('bank_transfer_info')
                            ->label('Bank Transfer Instructions')
                            ->content('Please transfer the amount to our bank account. Payment will be processed within 2-3 business days.')
                            ->visible(fn (Forms\Get $get): bool => $get('payment_method') === 'bank_transfer'),

                        Forms\Components\Placeholder::make('account_credit_info')
                            ->label('Account Credit Balance')
                            ->content(fn (): string => 'Available Credit: $' . number_format(auth()->user()->account_credit ?? 0, 2))
                            ->visible(fn (Forms\Get $get): bool => $get('payment_method') === 'account_credit'),

                        Forms\Components\Checkbox::make('save_card')
                            ->label('Save this card for future payments')
                            ->visible(fn (Forms\Get $get): bool => $get('payment_method') === 'credit_card'),

                        Forms\Components\Checkbox::make('agree_terms')
                            ->label('I agree to the terms and conditions')
                            ->required()
                            ->accepted(),
                    ]),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        try {
            // Payment processing logic would go here
            // This would integrate with payment gateways like Stripe, PayPal, etc.

            // For now, just mark as paid
            $this->record->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            // Create transaction record
            $this->record->transactions()->create([
                'transaction_id' => 'TXN-' . strtoupper(uniqid()),
                'amount' => $this->record->total,
                'payment_method' => $data['payment_method'],
                'status' => 'completed',
                'processed_at' => now(),
            ]);

            Notification::make()
                ->title('Payment Successful')
                ->body('Your invoice has been paid successfully.')
                ->success()
                ->send();

            redirect()->route('filament.customer.resources.invoices.view', ['record' => $this->record]);
        } catch (\Exception $e) {
            Notification::make()
                ->title('Payment Failed')
                ->body('There was an error processing your payment. Please try again.')
                ->danger()
                ->send();
        }
    }

    protected function getFormActions(): array
    {
        return [
            Forms\Components\Actions\Action::make('submit')
                ->label('Process Payment')
                ->submit('submit')
                ->color('success'),
        ];
    }
}
