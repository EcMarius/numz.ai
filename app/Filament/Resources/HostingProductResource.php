<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HostingProductResource\Pages;
use App\Models\HostingProduct;
use BackedEnum;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class HostingProductResource extends Resource
{
    protected static ?string $model = HostingProduct::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-server-stack';

    protected static ?string $navigationLabel = 'Hosting Products';

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): ?string
    {
        return 'NUMZ.AI';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Product Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('URL-friendly identifier'),

                        Forms\Components\Select::make('type')
                            ->required()
                            ->options([
                                'shared' => 'Shared Hosting',
                                'reseller' => 'Reseller Hosting',
                                'vps' => 'VPS',
                                'dedicated' => 'Dedicated Server',
                                'cloud' => 'Cloud Hosting',
                            ])
                            ->default('shared'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Inactive products won\'t be displayed to customers'),
                    ])->columns(2),

                Forms\Components\Section::make('Resources')
                    ->schema([
                        Forms\Components\TextInput::make('disk_space')
                            ->numeric()
                            ->suffix('GB')
                            ->helperText('Disk space in GB (0 for unlimited)'),

                        Forms\Components\TextInput::make('bandwidth')
                            ->numeric()
                            ->suffix('GB')
                            ->helperText('Monthly bandwidth in GB (0 for unlimited)'),

                        Forms\Components\TextInput::make('email_accounts')
                            ->numeric()
                            ->helperText('Number of email accounts (0 for unlimited)'),

                        Forms\Components\TextInput::make('databases')
                            ->numeric()
                            ->helperText('Number of databases (0 for unlimited)'),

                        Forms\Components\TextInput::make('domains')
                            ->numeric()
                            ->helperText('Number of domains allowed (0 for unlimited)'),

                        Forms\Components\TextInput::make('subdomains')
                            ->numeric()
                            ->helperText('Number of subdomains (0 for unlimited)'),
                    ])->columns(3),

                Forms\Components\Section::make('Pricing')
                    ->schema([
                        Forms\Components\TextInput::make('monthly_price')
                            ->numeric()
                            ->prefix('$')
                            ->required(),

                        Forms\Components\TextInput::make('quarterly_price')
                            ->numeric()
                            ->prefix('$'),

                        Forms\Components\TextInput::make('semi_annually_price')
                            ->numeric()
                            ->prefix('$'),

                        Forms\Components\TextInput::make('annually_price')
                            ->numeric()
                            ->prefix('$'),

                        Forms\Components\TextInput::make('biennially_price')
                            ->numeric()
                            ->prefix('$'),

                        Forms\Components\TextInput::make('triennially_price')
                            ->numeric()
                            ->prefix('$'),
                    ])->columns(3),

                Forms\Components\Section::make('Provisioning')
                    ->schema([
                        Forms\Components\Select::make('provisioning_module')
                            ->options([
                                'oneprovider' => 'OneProvider',
                                'cpanel' => 'cPanel/WHM',
                                'plesk' => 'Plesk',
                                'directadmin' => 'DirectAdmin',
                            ])
                            ->helperText('Select the provisioning module for this product'),

                        Forms\Components\KeyValue::make('module_config')
                            ->label('Module Configuration')
                            ->helperText('JSON configuration for the provisioning module')
                            ->columnSpanFull(),
                    ])->columns(1),

                Forms\Components\Section::make('Description')
                    ->schema([
                        Forms\Components\RichEditor::make('description')
                            ->columnSpanFull()
                            ->helperText('Product description shown to customers'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'shared' => 'success',
                        'reseller' => 'info',
                        'vps' => 'warning',
                        'dedicated' => 'danger',
                        'cloud' => 'primary',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('monthly_price')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('disk_space')
                    ->suffix(' GB')
                    ->formatStateUsing(fn ($state) => $state == 0 ? 'Unlimited' : $state),

                Tables\Columns\TextColumn::make('bandwidth')
                    ->suffix(' GB')
                    ->formatStateUsing(fn ($state) => $state == 0 ? 'Unlimited' : $state),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'shared' => 'Shared Hosting',
                        'reseller' => 'Reseller Hosting',
                        'vps' => 'VPS',
                        'dedicated' => 'Dedicated Server',
                        'cloud' => 'Cloud Hosting',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active products'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHostingProducts::route('/'),
            'create' => Pages\CreateHostingProduct::route('/create'),
            'edit' => Pages\EditHostingProduct::route('/{record}/edit'),
        ];
    }
}
