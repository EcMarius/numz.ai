<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Models\HostingService;
use App\Models\HostingProduct;
use App\Models\HostingServer;
use App\Models\User;
use App\Models\Invoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;

class ServiceResource extends Resource
{
    protected static ?string $model = HostingService::class;

    protected static ?string $navigationIcon = 'heroicon-o-server';

    protected static ?string $navigationLabel = 'Services';

    protected static ?string $navigationGroup = 'Services';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Service';

    protected static ?string $pluralModelLabel = 'Services';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Service Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Customer')
                            ->relationship('user', 'name')
                            ->searchable(['name', 'email'])
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required(),
                                Forms\Components\TextInput::make('email')
                                    ->email()
                                    ->required(),
                                Forms\Components\TextInput::make('password')
                                    ->password()
                                    ->required(),
                            ])
                            ->helperText('Select the customer for this service'),

                        Forms\Components\Select::make('product_id')
                            ->label('Product/Package')
                            ->options(HostingProduct::where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $product = HostingProduct::find($state);
                                    if ($product) {
                                        $set('price', $product->monthly_price);
                                        $set('server_id', $product->server_id);
                                    }
                                }
                            })
                            ->helperText('Select the hosting product/package'),

                        Forms\Components\Select::make('server_id')
                            ->label('Server')
                            ->options(HostingServer::where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->helperText('Server where service will be provisioned'),

                        Forms\Components\TextInput::make('domain')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('example.com')
                            ->helperText('Primary domain for this service'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Account Details')
                    ->schema([
                        Forms\Components\TextInput::make('username')
                            ->maxLength(255)
                            ->helperText('Account username (auto-generated if empty)'),

                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->maxLength(255)
                            ->helperText('Account password (auto-generated if empty)'),

                        Forms\Components\TextInput::make('server_account_id')
                            ->maxLength(255)
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Account ID on the server (populated after provisioning)'),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Forms\Components\Section::make('Billing')
                    ->schema([
                        Forms\Components\Select::make('billing_cycle')
                            ->required()
                            ->options([
                                'monthly' => 'Monthly',
                                'quarterly' => 'Quarterly (3 months)',
                                'semi_annually' => 'Semi-Annually (6 months)',
                                'annually' => 'Annually (12 months)',
                                'biennially' => 'Biennially (24 months)',
                                'triennially' => 'Triennially (36 months)',
                            ])
                            ->default('monthly'),

                        Forms\Components\TextInput::make('price')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->helperText('Price for the billing cycle'),

                        Forms\Components\DatePicker::make('next_due_date')
                            ->required()
                            ->default(now()->addMonth())
                            ->helperText('Next invoice/renewal date'),

                        Forms\Components\DatePicker::make('registration_date')
                            ->default(now())
                            ->helperText('Service registration date'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Status & Options')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->required()
                            ->options([
                                'pending' => 'Pending',
                                'active' => 'Active',
                                'suspended' => 'Suspended',
                                'terminated' => 'Terminated',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('pending')
                            ->helperText('Current service status'),

                        Forms\Components\Toggle::make('auto_renew')
                            ->label('Auto Renew')
                            ->default(true)
                            ->helperText('Automatically renew this service'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull()
                            ->helperText('Internal notes (not visible to customer)'),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable(['users.name', 'users.email'])
                    ->sortable()
                    ->description(fn (HostingService $record): string => $record->user->email ?? ''),

                Tables\Columns\TextColumn::make('domain')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->icon('heroicon-o-globe-alt'),

                Tables\Columns\TextColumn::make('product.name')
                    ->label('Package')
                    ->sortable()
                    ->searchable()
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 30 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('server.name')
                    ->label('Server')
                    ->sortable()
                    ->toggleable()
                    ->icon('heroicon-o-server'),

                Tables\Columns\TextColumn::make('billing_cycle')
                    ->badge()
                    ->sortable()
                    ->color('info'),

                Tables\Columns\TextColumn::make('price')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('next_due_date')
                    ->label('Next Due')
                    ->date()
                    ->sortable()
                    ->color(fn (HostingService $record) => $record->next_due_date && $record->next_due_date < now() ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'active' => 'success',
                        'suspended' => 'danger',
                        'terminated' => 'gray',
                        'cancelled' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\IconColumn::make('auto_renew')
                    ->label('Auto Renew')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('registration_date')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
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
                        'terminated' => 'Terminated',
                        'cancelled' => 'Cancelled',
                    ])
                    ->multiple()
                    ->default(['active', 'pending']),

                Tables\Filters\SelectFilter::make('product_id')
                    ->label('Package')
                    ->relationship('product', 'name')
                    ->multiple()
                    ->searchable(),

                Tables\Filters\SelectFilter::make('server_id')
                    ->label('Server')
                    ->relationship('server', 'name')
                    ->multiple()
                    ->searchable(),

                Tables\Filters\SelectFilter::make('billing_cycle')
                    ->options([
                        'monthly' => 'Monthly',
                        'quarterly' => 'Quarterly',
                        'semi_annually' => 'Semi-Annually',
                        'annually' => 'Annually',
                        'biennially' => 'Biennially',
                        'triennially' => 'Triennially',
                    ])
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('auto_renew')
                    ->label('Auto Renew')
                    ->placeholder('All services')
                    ->trueLabel('Auto renew enabled')
                    ->falseLabel('Auto renew disabled'),

                Tables\Filters\Filter::make('overdue')
                    ->label('Overdue')
                    ->query(fn (Builder $query) => $query->where('next_due_date', '<', now())->whereIn('status', ['active', 'pending']))
                    ->toggle(),

                Tables\Filters\Filter::make('due_soon')
                    ->label('Due in 7 Days')
                    ->query(fn (Builder $query) => $query->whereBetween('next_due_date', [now(), now()->addDays(7)])->whereIn('status', ['active', 'pending']))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('provision')
                        ->label('Provision Now')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->visible(fn (HostingService $record) => $record->status === 'pending')
                        ->requiresConfirmation()
                        ->action(function (HostingService $record) {
                            // TODO: Implement actual provisioning logic
                            $record->update(['status' => 'active']);
                            \Filament\Notifications\Notification::make()
                                ->title('Service provisioning initiated')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('suspend')
                        ->label('Suspend')
                        ->icon('heroicon-o-pause')
                        ->color('warning')
                        ->visible(fn (HostingService $record) => $record->status === 'active')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->label('Suspension Reason')
                                ->required(),
                        ])
                        ->action(function (HostingService $record, array $data) {
                            $record->update([
                                'status' => 'suspended',
                                'notes' => ($record->notes ?? '') . "\n[" . now() . "] Suspended: " . $data['reason'],
                            ]);
                            \Filament\Notifications\Notification::make()
                                ->title('Service suspended successfully')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('unsuspend')
                        ->label('Unsuspend')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->visible(fn (HostingService $record) => $record->status === 'suspended')
                        ->requiresConfirmation()
                        ->action(function (HostingService $record) {
                            $record->update([
                                'status' => 'active',
                                'notes' => ($record->notes ?? '') . "\n[" . now() . "] Service reactivated",
                            ]);
                            \Filament\Notifications\Notification::make()
                                ->title('Service reactivated successfully')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('terminate')
                        ->label('Terminate')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalDescription('This will permanently terminate the service. This action cannot be undone.')
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->label('Termination Reason')
                                ->required(),
                        ])
                        ->action(function (HostingService $record, array $data) {
                            $record->update([
                                'status' => 'terminated',
                                'notes' => ($record->notes ?? '') . "\n[" . now() . "] Terminated: " . $data['reason'],
                            ]);
                            \Filament\Notifications\Notification::make()
                                ->title('Service terminated')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('change_package')
                        ->label('Change Package')
                        ->icon('heroicon-o-arrow-path')
                        ->color('info')
                        ->visible(fn (HostingService $record) => in_array($record->status, ['active', 'pending']))
                        ->form([
                            Forms\Components\Select::make('new_product_id')
                                ->label('New Package')
                                ->options(HostingProduct::where('is_active', true)->pluck('name', 'id'))
                                ->required()
                                ->searchable(),
                            Forms\Components\Toggle::make('immediate')
                                ->label('Apply Immediately')
                                ->default(false)
                                ->helperText('If disabled, change will apply on next renewal'),
                        ])
                        ->action(function (HostingService $record, array $data) {
                            if ($data['immediate']) {
                                $record->update(['product_id' => $data['new_product_id']]);
                                \Filament\Notifications\Notification::make()
                                    ->title('Package changed successfully')
                                    ->success()
                                    ->send();
                            } else {
                                $record->update([
                                    'notes' => ($record->notes ?? '') . "\n[" . now() . "] Package upgrade scheduled for next renewal",
                                ]);
                                \Filament\Notifications\Notification::make()
                                    ->title('Package change scheduled')
                                    ->success()
                                    ->send();
                            }
                        }),

                    Tables\Actions\Action::make('change_billing_cycle')
                        ->label('Change Billing Cycle')
                        ->icon('heroicon-o-calendar')
                        ->color('info')
                        ->form([
                            Forms\Components\Select::make('billing_cycle')
                                ->options([
                                    'monthly' => 'Monthly',
                                    'quarterly' => 'Quarterly',
                                    'semi_annually' => 'Semi-Annually',
                                    'annually' => 'Annually',
                                    'biennially' => 'Biennially',
                                    'triennially' => 'Triennially',
                                ])
                                ->required(),
                            Forms\Components\TextInput::make('price')
                                ->numeric()
                                ->prefix('$')
                                ->required(),
                        ])
                        ->fillForm(fn (HostingService $record) => [
                            'billing_cycle' => $record->billing_cycle,
                            'price' => $record->price,
                        ])
                        ->action(function (HostingService $record, array $data) {
                            $record->update($data);
                            \Filament\Notifications\Notification::make()
                                ->title('Billing cycle updated')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('transfer_service')
                        ->label('Transfer to Another Client')
                        ->icon('heroicon-o-arrow-right-circle')
                        ->color('warning')
                        ->form([
                            Forms\Components\Select::make('new_user_id')
                                ->label('New Customer')
                                ->options(User::pluck('name', 'id'))
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function (HostingService $record, array $data) {
                            $record->update(['user_id' => $data['new_user_id']]);
                            \Filament\Notifications\Notification::make()
                                ->title('Service transferred successfully')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('adjust_due_date')
                        ->label('Adjust Due Date')
                        ->icon('heroicon-o-calendar-days')
                        ->color('info')
                        ->form([
                            Forms\Components\DatePicker::make('next_due_date')
                                ->label('New Due Date')
                                ->required(),
                        ])
                        ->fillForm(fn (HostingService $record) => [
                            'next_due_date' => $record->next_due_date,
                        ])
                        ->action(function (HostingService $record, array $data) {
                            $record->update($data);
                            \Filament\Notifications\Notification::make()
                                ->title('Due date adjusted')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('suspend')
                        ->label('Suspend Selected')
                        ->icon('heroicon-o-pause')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each(function ($record) {
                            $record->update(['status' => 'suspended']);
                        }))
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('unsuspend')
                        ->label('Unsuspend Selected')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->action(fn ($records) => $records->each(function ($record) {
                            $record->update(['status' => 'active']);
                        }))
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('terminate')
                        ->label('Terminate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalDescription('This will permanently terminate all selected services.')
                        ->action(fn ($records) => $records->each(function ($record) {
                            $record->update(['status' => 'terminated']);
                        }))
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
                Infolists\Components\Tabs::make('Service Details')
                    ->tabs([
                        Infolists\Components\Tabs\Tab::make('Overview')
                            ->schema([
                                Infolists\Components\Section::make('Service Information')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('domain')
                                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                            ->weight('bold')
                                            ->copyable()
                                            ->icon('heroicon-o-globe-alt'),

                                        Infolists\Components\TextEntry::make('status')
                                            ->badge()
                                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large),

                                        Infolists\Components\TextEntry::make('user.name')
                                            ->label('Customer')
                                            ->url(fn (HostingService $record) => route('filament.admin.resources.users.view', $record->user_id)),

                                        Infolists\Components\TextEntry::make('product.name')
                                            ->label('Package'),

                                        Infolists\Components\TextEntry::make('server.name')
                                            ->label('Server')
                                            ->icon('heroicon-o-server'),

                                        Infolists\Components\TextEntry::make('billing_cycle')
                                            ->badge(),
                                    ])
                                    ->columns(2),

                                Infolists\Components\Section::make('Account Details')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('username')
                                            ->copyable(),

                                        Infolists\Components\TextEntry::make('password')
                                            ->default('••••••••'),

                                        Infolists\Components\TextEntry::make('server_account_id')
                                            ->label('Server Account ID')
                                            ->default('Not provisioned'),
                                    ])
                                    ->columns(3),

                                Infolists\Components\Section::make('Billing Information')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('price')
                                            ->money('USD'),

                                        Infolists\Components\TextEntry::make('registration_date')
                                            ->date(),

                                        Infolists\Components\TextEntry::make('next_due_date')
                                            ->date()
                                            ->color(fn (HostingService $record) => $record->next_due_date && $record->next_due_date < now() ? 'danger' : 'success'),

                                        Infolists\Components\IconEntry::make('auto_renew')
                                            ->label('Auto Renew')
                                            ->boolean(),
                                    ])
                                    ->columns(4),

                                Infolists\Components\Section::make('Notes')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('notes')
                                            ->default('No notes')
                                            ->columnSpanFull(),
                                    ])
                                    ->collapsible()
                                    ->collapsed(),
                            ]),

                        Infolists\Components\Tabs\Tab::make('Invoices')
                            ->schema([
                                Infolists\Components\RepeatableEntry::make('invoices')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('invoice_number')
                                            ->label('Invoice #'),
                                        Infolists\Components\TextEntry::make('total')
                                            ->money('USD'),
                                        Infolists\Components\TextEntry::make('status')
                                            ->badge(),
                                        Infolists\Components\TextEntry::make('due_date')
                                            ->date(),
                                    ])
                                    ->columns(4)
                                    ->columnSpanFull(),
                            ])
                            ->badge(fn (HostingService $record) => $record->invoices()->count()),

                        Infolists\Components\Tabs\Tab::make('Activity Log')
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Service Created')
                                    ->dateTime(),

                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label('Last Modified')
                                    ->dateTime(),
                            ])
                            ->columns(2),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
            'view' => Pages\ViewService::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user', 'product', 'server']);
    }
}
