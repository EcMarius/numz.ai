<?php

namespace App\Filament\Customer\Resources\InvoiceResource\Pages;

use App\Filament\Customer\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('pay')
                ->label('Pay Now')
                ->icon('heroicon-m-credit-card')
                ->color('success')
                ->visible(fn (): bool => in_array($this->record->status, ['pending', 'overdue']))
                ->url(fn (): string => route('filament.customer.resources.invoices.pay', ['record' => $this->record])),

            Actions\Action::make('download')
                ->label('Download PDF')
                ->icon('heroicon-m-arrow-down-tray')
                ->color('info')
                ->action(function () {
                    \Filament\Notifications\Notification::make()
                        ->title('Downloading Invoice')
                        ->body('Invoice PDF is being generated...')
                        ->info()
                        ->send();
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Invoice Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('invoice_number')
                            ->label('Invoice Number'),

                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'paid' => 'success',
                                'pending' => 'warning',
                                'overdue' => 'danger',
                                'cancelled' => 'gray',
                                'refunded' => 'info',
                                default => 'gray',
                            }),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Invoice Date')
                            ->date(),

                        Infolists\Components\TextEntry::make('due_date')
                            ->label('Due Date')
                            ->date(),

                        Infolists\Components\TextEntry::make('order.product.name')
                            ->label('Service'),

                        Infolists\Components\TextEntry::make('order.billing_cycle')
                            ->label('Billing Cycle')
                            ->badge(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Invoice Items')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('items')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('description')
                                    ->label('Description'),

                                Infolists\Components\TextEntry::make('quantity')
                                    ->label('Qty'),

                                Infolists\Components\TextEntry::make('unit_price')
                                    ->label('Unit Price')
                                    ->money('USD'),

                                Infolists\Components\TextEntry::make('total')
                                    ->label('Total')
                                    ->money('USD'),
                            ])
                            ->columns(4)
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Invoice Totals')
                    ->schema([
                        Infolists\Components\TextEntry::make('subtotal')
                            ->label('Subtotal')
                            ->money('USD'),

                        Infolists\Components\TextEntry::make('tax')
                            ->label('Tax')
                            ->money('USD'),

                        Infolists\Components\TextEntry::make('discount')
                            ->label('Discount')
                            ->money('USD')
                            ->visible(fn ($state): bool => $state > 0),

                        Infolists\Components\TextEntry::make('total')
                            ->label('Total Amount')
                            ->money('USD')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight('bold'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Payment History')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('transactions')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('transaction_id')
                                    ->label('Transaction ID'),

                                Infolists\Components\TextEntry::make('amount')
                                    ->money('USD'),

                                Infolists\Components\TextEntry::make('payment_method')
                                    ->label('Payment Method')
                                    ->badge(),

                                Infolists\Components\TextEntry::make('status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'completed' => 'success',
                                        'pending' => 'warning',
                                        'failed' => 'danger',
                                        default => 'gray',
                                    }),

                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Date')
                                    ->dateTime(),
                            ])
                            ->columns(5)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (): bool => $this->record->transactions->isNotEmpty())
                    ->collapsible(),
            ]);
    }
}
