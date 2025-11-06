<?php

namespace App\Filament\Customer\Resources\ServiceResource\Pages;

use App\Filament\Customer\Resources\ServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewService extends ViewRecord
{
    protected static string $resource = ServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('upgrade')
                ->label('Upgrade Service')
                ->icon('heroicon-m-arrow-up-circle')
                ->color('success')
                ->visible(fn (): bool => $this->record->status === 'active')
                ->action(function () {
                    \Filament\Notifications\Notification::make()
                        ->title('Upgrade Service')
                        ->body('Contact support to upgrade your service.')
                        ->info()
                        ->send();
                }),

            Actions\Action::make('cancel')
                ->label('Cancel Service')
                ->icon('heroicon-m-x-circle')
                ->color('danger')
                ->visible(fn (): bool => $this->record->status === 'active')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update([
                        'status' => 'cancelled',
                        'cancelled_at' => now(),
                        'cancellation_reason' => 'customer_request',
                    ]);

                    \Filament\Notifications\Notification::make()
                        ->title('Service Cancelled')
                        ->success()
                        ->send();

                    return redirect()->route('filament.customer.resources.services.index');
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Service Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('product.name')
                            ->label('Product/Service'),

                        Infolists\Components\TextEntry::make('billing_cycle')
                            ->label('Billing Cycle')
                            ->badge(),

                        Infolists\Components\TextEntry::make('total')
                            ->label('Price')
                            ->money('USD'),

                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'active' => 'success',
                                'pending' => 'warning',
                                'suspended' => 'warning',
                                'cancelled' => 'danger',
                                default => 'gray',
                            }),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Order Date')
                            ->date(),

                        Infolists\Components\TextEntry::make('next_due_date')
                            ->label('Next Due Date')
                            ->date(),

                        Infolists\Components\TextEntry::make('notes')
                            ->label('Service Notes')
                            ->columnSpanFull()
                            ->placeholder('No notes'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Service Configuration')
                    ->schema([
                        Infolists\Components\TextEntry::make('configuration')
                            ->label('Configuration Details')
                            ->formatStateUsing(fn ($state): string => $state ? json_encode($state, JSON_PRETTY_PRINT) : 'No configuration')
                            ->html()
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Infolists\Components\Section::make('Recent Invoices')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('invoices')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('invoice_number')
                                    ->label('Invoice #'),

                                Infolists\Components\TextEntry::make('due_date')
                                    ->label('Due Date')
                                    ->date(),

                                Infolists\Components\TextEntry::make('total')
                                    ->money('USD'),

                                Infolists\Components\TextEntry::make('status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'paid' => 'success',
                                        'pending' => 'warning',
                                        'overdue' => 'danger',
                                        default => 'gray',
                                    }),
                            ])
                            ->columns(4)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }
}
