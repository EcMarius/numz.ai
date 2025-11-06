<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ModuleConfigurationResource\Pages;
use App\Models\ModuleConfiguration;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class ModuleConfigurationResource extends Resource
{
    protected static ?string $model = ModuleConfiguration::class;

    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Modules';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\Select::make('module_type')
                            ->label('Module Type')
                            ->options([
                                'payment_gateway' => 'Payment Gateway',
                                'provisioning' => 'Provisioning Module',
                                'registrar' => 'Domain Registrar',
                                'integration' => 'Integration',
                            ])
                            ->required()
                            ->reactive(),
                        Forms\Components\TextInput::make('module_name')
                            ->label('Module Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('display_name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('is_enabled')
                            ->label('Enabled')
                            ->default(false)
                            ->helperText('Enable this module for use'),
                        Forms\Components\Toggle::make('test_mode')
                            ->label('Test Mode')
                            ->default(false)
                            ->helperText('Use sandbox/test environment'),
                        Forms\Components\TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Display order (lower numbers first)'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Configuration')
                    ->schema([
                        Forms\Components\KeyValue::make('configuration')
                            ->label('Module Settings')
                            ->keyLabel('Setting Name')
                            ->valueLabel('Value')
                            ->reorderable()
                            ->helperText('Module-specific configuration options')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Credentials')
                    ->description('Sensitive credentials are encrypted automatically')
                    ->schema([
                        Forms\Components\KeyValue::make('credentials')
                            ->label('API Credentials')
                            ->keyLabel('Credential Name')
                            ->valueLabel('Value')
                            ->reorderable()
                            ->helperText('API keys, secrets, tokens, etc. (encrypted)')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Capabilities')
                    ->schema([
                        Forms\Components\TagsInput::make('capabilities')
                            ->label('Supported Capabilities')
                            ->placeholder('Add capability')
                            ->helperText('e.g., refunds, subscriptions, webhooks')
                            ->columnSpanFull(),
                        Forms\Components\TagsInput::make('required_fields')
                            ->label('Required Fields')
                            ->placeholder('Add field')
                            ->helperText('Fields that must be configured')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('display_name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\BadgeColumn::make('module_type')
                    ->label('Type')
                    ->colors([
                        'primary' => 'payment_gateway',
                        'success' => 'provisioning',
                        'warning' => 'registrar',
                        'info' => 'integration',
                    ])
                    ->formatStateUsing(fn (string $state): string => str_replace('_', ' ', ucwords($state, '_'))),
                Tables\Columns\IconColumn::make('is_enabled')
                    ->label('Enabled')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\IconColumn::make('test_mode')
                    ->label('Test Mode')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\IconColumn::make('test_successful')
                    ->label('Last Test')
                    ->boolean()
                    ->sortable()
                    ->tooltip(fn (ModuleConfiguration $record): ?string =>
                        $record->test_error ?? ($record->test_successful ? 'Test passed' : 'Not tested')
                    ),
                Tables\Columns\TextColumn::make('last_tested_at')
                    ->label('Last Tested')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('module_type')
                    ->options([
                        'payment_gateway' => 'Payment Gateway',
                        'provisioning' => 'Provisioning Module',
                        'registrar' => 'Domain Registrar',
                        'integration' => 'Integration',
                    ]),
                Tables\Filters\TernaryFilter::make('is_enabled')
                    ->label('Enabled')
                    ->boolean(),
                Tables\Filters\TernaryFilter::make('test_mode')
                    ->label('Test Mode')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\Action::make('test')
                    ->icon('heroicon-o-beaker')
                    ->color('warning')
                    ->action(function (ModuleConfiguration $record) {
                        $success = $record->testConnection(auth()->id());

                        if ($success) {
                            Notification::make()
                                ->title('Connection test successful')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Connection test failed')
                                ->body($record->test_error ?? 'Unknown error')
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Test Module Connection')
                    ->modalDescription('This will test if the module can connect with the provided credentials.')
                    ->modalSubmitActionLabel('Test Connection'),
                Tables\Actions\Action::make('toggle')
                    ->icon(fn (ModuleConfiguration $record) => $record->is_enabled ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (ModuleConfiguration $record) => $record->is_enabled ? 'danger' : 'success')
                    ->label(fn (ModuleConfiguration $record) => $record->is_enabled ? 'Disable' : 'Enable')
                    ->action(fn (ModuleConfiguration $record) => $record->update(['is_enabled' => !$record->is_enabled]))
                    ->requiresConfirmation(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('enable')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(fn ($records) => $records->each->update(['is_enabled' => true])),
                Tables\Actions\BulkAction::make('disable')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->action(fn ($records) => $records->each->update(['is_enabled' => false])),
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListModuleConfigurations::route('/'),
            'create' => Pages\CreateModuleConfiguration::route('/create'),
            'edit' => Pages\EditModuleConfiguration::route('/{record}/edit'),
        ];
    }
}
