<?php

namespace App\Filament\Resources\GrowthHacking;

use App\Filament\Resources\GrowthHacking\SmtpConfigResource\Pages;
use App\Models\SmtpConfig;
use BackedEnum;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Support\Facades\Auth;

class SmtpConfigResource extends Resource
{
    protected static ?string $model = SmtpConfig::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-server';

    protected static ?string $navigationLabel = 'SMTP Configs';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): string
    {
        return 'Growth Hacking';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('SMTP Configuration')
                    ->description('Configure custom SMTP server for sending growth hacking emails')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., SendGrid Production'),

                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('host')
                                    ->required()
                                    ->placeholder('smtp.example.com'),

                                Forms\Components\TextInput::make('port')
                                    ->required()
                                    ->numeric()
                                    ->default(587),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('username')
                                    ->required(),

                                Forms\Components\TextInput::make('password')
                                    ->required()
                                    ->password()
                                    ->revealable()
                                    ->dehydrated(fn ($state) => filled($state))
                                    ->dehydrateStateUsing(fn ($state) => $state),
                            ]),

                        Forms\Components\Select::make('encryption')
                            ->required()
                            ->options([
                                'ssl' => 'SSL',
                                'tls' => 'TLS',
                                'none' => 'None',
                            ])
                            ->default('tls'),

                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('from_address')
                                    ->required()
                                    ->email()
                                    ->placeholder('noreply@example.com'),

                                Forms\Components\TextInput::make('from_name')
                                    ->required()
                                    ->placeholder('EvenLeads'),
                            ]),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
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

                Tables\Columns\TextColumn::make('host')
                    ->searchable(),

                Tables\Columns\TextColumn::make('port'),

                Tables\Columns\TextColumn::make('from_address')
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSmtpConfigs::route('/'),
            'create' => Pages\CreateSmtpConfig::route('/create'),
            'edit' => Pages\EditSmtpConfig::route('/{record}/edit'),
        ];
    }
}
