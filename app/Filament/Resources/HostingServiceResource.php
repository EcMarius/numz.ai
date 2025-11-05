<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HostingServiceResource\Pages;
use App\Models\HostingService;
use App\Models\HostingProduct;
use App\Models\HostingServer;
use App\Models\User;
use BackedEnum;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class HostingServiceResource extends Resource
{
    protected static ?string $model = HostingService::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Services';

    protected static ?int $navigationSort = 12;

    public static function getNavigationGroup(): ?string
    {
        return 'NUMZ.AI';
    }

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
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Select the customer for this service'),

                        Forms\Components\Select::make('product_id')
                            ->label('Product')
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
                                    }
                                }
                            })
                            ->helperText('Select the hosting product'),

                        Forms\Components\Select::make('server_id')
                            ->label('Server')
                            ->options(HostingServer::where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->helperText('Server where service will be provisioned'),

                        Forms\Components\TextInput::make('domain')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Primary domain for this service'),
                    ])->columns(2),

                Forms\Components\Section::make('Account Details')
                    ->schema([
                        Forms\Components\TextInput::make('username')
                            ->maxLength(255)
                            ->helperText('Account username'),

                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->maxLength(255)
                            ->helperText('Account password'),

                        Forms\Components\TextInput::make('server_account_id')
                            ->maxLength(255)
                            ->helperText('Account ID on the server (auto-populated after provisioning)'),
                    ])->columns(3),

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
                            ->helperText('Next invoice date'),

                        Forms\Components\DatePicker::make('registration_date')
                            ->default(now())
                            ->helperText('Service registration date'),
                    ])->columns(2),

                Forms\Components\Section::make('Status')
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
                    ])->columns(2),

                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull()
                            ->helperText('Internal notes (not visible to customer)'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('domain')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product')
                    ->sortable(),

                Tables\Columns\TextColumn::make('server.name')
                    ->label('Server')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('billing_cycle')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('price')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('next_due_date')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => $record->next_due_date < now() ? 'danger' : 'success'),

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
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('registration_date')
                    ->date()
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
                    ]),
                Tables\Filters\SelectFilter::make('product_id')
                    ->label('Product')
                    ->relationship('product', 'name'),
                Tables\Filters\SelectFilter::make('server_id')
                    ->label('Server')
                    ->relationship('server', 'name'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('provision')
                        ->label('Provision')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->visible(fn ($record) => $record->status === 'pending')
                        ->action(function (HostingService $record) {
                            // TODO: Implement provisioning
                            \Filament\Notifications\Notification::make()
                                ->title('Provisioning not yet implemented')
                                ->warning()
                                ->send();
                        }),
                    Tables\Actions\Action::make('suspend')
                        ->label('Suspend')
                        ->icon('heroicon-o-pause')
                        ->color('warning')
                        ->visible(fn ($record) => $record->status === 'active')
                        ->requiresConfirmation()
                        ->action(function (HostingService $record) {
                            $record->update(['status' => 'suspended']);
                            \Filament\Notifications\Notification::make()
                                ->title('Service suspended')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\Action::make('unsuspend')
                        ->label('Unsuspend')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->visible(fn ($record) => $record->status === 'suspended')
                        ->action(function (HostingService $record) {
                            $record->update(['status' => 'active']);
                            \Filament\Notifications\Notification::make()
                                ->title('Service reactivated')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\Action::make('terminate')
                        ->label('Terminate')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (HostingService $record) {
                            $record->update(['status' => 'terminated']);
                            \Filament\Notifications\Notification::make()
                                ->title('Service terminated')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHostingServices::route('/'),
            'create' => Pages\CreateHostingService::route('/create'),
            'edit' => Pages\EditHostingService::route('/{record}/edit'),
        ];
    }
}
