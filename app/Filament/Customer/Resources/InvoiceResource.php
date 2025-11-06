<?php

namespace App\Filament\Customer\Resources;

use App\Filament\Customer\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Invoices';

    protected static ?string $modelLabel = 'Invoice';

    protected static ?string $pluralModelLabel = 'Invoices';

    protected static ?string $navigationGroup = 'Billing';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id())
            ->with(['order.product', 'transactions']);
    }

    public static function getNavigationBadge(): ?string
    {
        $unpaid = Invoice::where('user_id', auth()->id())
            ->whereIn('status', ['pending', 'overdue'])
            ->count();

        return $unpaid > 0 ? (string) $unpaid : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Invoice Details')
                    ->schema([
                        Forms\Components\TextInput::make('invoice_number')
                            ->disabled(),

                        Forms\Components\TextInput::make('total')
                            ->prefix('$')
                            ->disabled(),

                        Forms\Components\TextInput::make('status')
                            ->disabled(),

                        Forms\Components\DatePicker::make('due_date')
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('order.product.name')
                    ->label('Service')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Invoice Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date()
                    ->sortable()
                    ->color(fn (Invoice $record): string =>
                        $record->due_date && $record->due_date->isPast() && $record->status !== 'paid' ? 'danger' : 'gray'
                    ),

                Tables\Columns\TextColumn::make('total')
                    ->label('Amount')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'overdue' => 'danger',
                        'cancelled' => 'gray',
                        'refunded' => 'info',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'overdue' => 'Overdue',
                        'cancelled' => 'Cancelled',
                        'refunded' => 'Refunded',
                    ])
                    ->default('pending'),

                Tables\Filters\Filter::make('overdue')
                    ->label('Overdue Only')
                    ->query(fn (Builder $query): Builder => $query->where('status', 'overdue')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('pay')
                    ->label('Pay Now')
                    ->icon('heroicon-m-credit-card')
                    ->color('success')
                    ->visible(fn (Invoice $record): bool => in_array($record->status, ['pending', 'overdue']))
                    ->url(fn (Invoice $record): string => route('filament.customer.resources.invoices.pay', ['record' => $record])),

                Tables\Actions\Action::make('download')
                    ->label('Download PDF')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('info')
                    ->action(function (Invoice $record) {
                        // PDF generation logic would go here
                        \Filament\Notifications\Notification::make()
                            ->title('Downloading Invoice')
                            ->body('Invoice PDF is being generated...')
                            ->info()
                            ->send();
                    }),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No Invoices')
            ->emptyStateDescription('You don\'t have any invoices yet.')
            ->emptyStateIcon('heroicon-o-document-text');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'view' => Pages\ViewInvoice::route('/{record}'),
            'pay' => Pages\PayInvoice::route('/{record}/pay'),
        ];
    }
}
