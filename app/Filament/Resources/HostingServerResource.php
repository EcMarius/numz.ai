<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HostingServerResource\Pages;
use App\Models\HostingServer;
use BackedEnum;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class HostingServerResource extends Resource
{
    protected static ?string $model = HostingServer::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-server';

    protected static ?string $navigationLabel = 'Servers';

    protected static ?int $navigationSort = 11;

    public static function getNavigationGroup(): ?string
    {
        return 'NUMZ.AI';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Server Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Friendly name for the server'),

                        Forms\Components\TextInput::make('hostname')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Server hostname or domain'),

                        Forms\Components\TextInput::make('ip_address')
                            ->required()
                            ->ip()
                            ->helperText('Server IP address'),

                        Forms\Components\TextInput::make('port')
                            ->numeric()
                            ->default(2087)
                            ->helperText('API port (2087 for cPanel, 8443 for Plesk)'),

                        Forms\Components\Select::make('type')
                            ->required()
                            ->options([
                                'cpanel' => 'cPanel/WHM',
                                'plesk' => 'Plesk',
                                'directadmin' => 'DirectAdmin',
                                'oneprovider' => 'OneProvider',
                                'custom' => 'Custom',
                            ])
                            ->default('cpanel')
                            ->helperText('Server control panel type'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Inactive servers won\'t be used for provisioning'),
                    ])->columns(2),

                Forms\Components\Section::make('Authentication')
                    ->schema([
                        Forms\Components\TextInput::make('username')
                            ->maxLength(255)
                            ->helperText('API username or access key'),

                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->maxLength(255)
                            ->helperText('API password or secret key'),

                        Forms\Components\Textarea::make('api_token')
                            ->rows(3)
                            ->helperText('API token (if using token-based auth)'),
                    ])->columns(1),

                Forms\Components\Section::make('Nameservers')
                    ->schema([
                        Forms\Components\TextInput::make('nameserver1')
                            ->maxLength(255)
                            ->helperText('Primary nameserver'),

                        Forms\Components\TextInput::make('nameserver2')
                            ->maxLength(255)
                            ->helperText('Secondary nameserver'),

                        Forms\Components\TextInput::make('nameserver3')
                            ->maxLength(255)
                            ->helperText('Tertiary nameserver (optional)'),

                        Forms\Components\TextInput::make('nameserver4')
                            ->maxLength(255)
                            ->helperText('Quaternary nameserver (optional)'),
                    ])->columns(2),

                Forms\Components\Section::make('Capacity')
                    ->schema([
                        Forms\Components\TextInput::make('max_accounts')
                            ->numeric()
                            ->default(0)
                            ->helperText('Maximum accounts (0 for unlimited)'),

                        Forms\Components\TextInput::make('current_accounts')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->helperText('Current number of accounts on this server'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('hostname')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('ip_address')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'cpanel' => 'success',
                        'plesk' => 'info',
                        'directadmin' => 'warning',
                        'oneprovider' => 'primary',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('current_accounts')
                    ->label('Accounts')
                    ->formatStateUsing(function ($record) {
                        $max = $record->max_accounts;
                        $current = $record->current_accounts;
                        return $max > 0 ? "{$current}/{$max}" : $current;
                    })
                    ->sortable(),

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
                        'cpanel' => 'cPanel/WHM',
                        'plesk' => 'Plesk',
                        'directadmin' => 'DirectAdmin',
                        'oneprovider' => 'OneProvider',
                        'custom' => 'Custom',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active servers'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('test_connection')
                    ->label('Test')
                    ->icon('heroicon-o-signal')
                    ->action(function (HostingServer $record) {
                        // TODO: Implement connection test
                        \Filament\Notifications\Notification::make()
                            ->title('Connection test not yet implemented')
                            ->warning()
                            ->send();
                    }),
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
            'index' => Pages\ListHostingServers::route('/'),
            'create' => Pages\CreateHostingServer::route('/create'),
            'edit' => Pages\EditHostingServer::route('/{record}/edit'),
        ];
    }
}
