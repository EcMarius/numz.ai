<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApiCredentialResource\Pages;
use App\Models\ApiCredential;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class ApiCredentialResource extends Resource
{
    protected static ?string $model = ApiCredential::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'API Credentials';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Service Information')
                    ->schema([
                        Forms\Components\Select::make('service_name')
                            ->label('Service')
                            ->options([
                                'openai' => 'OpenAI',
                                'anthropic' => 'Anthropic (Claude)',
                                'sendgrid' => 'SendGrid',
                                'twilio' => 'Twilio',
                                'mailgun' => 'Mailgun',
                                'postmark' => 'Postmark',
                                'aws_ses' => 'Amazon SES',
                                'cloudflare' => 'Cloudflare',
                                'digitalocean' => 'DigitalOcean',
                                'vultr' => 'Vultr',
                                'custom' => 'Custom Service',
                            ])
                            ->required()
                            ->searchable(),
                        Forms\Components\TextInput::make('display_name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('credential_type')
                            ->options([
                                'api_key' => 'API Key',
                                'oauth' => 'OAuth',
                                'username_password' => 'Username & Password',
                            ])
                            ->required()
                            ->reactive(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Credentials')
                    ->description('All credentials are encrypted automatically')
                    ->schema([
                        Forms\Components\TextInput::make('api_key')
                            ->label('API Key')
                            ->password()
                            ->revealable()
                            ->maxLength(500)
                            ->visible(fn (callable $get) => in_array($get('credential_type'), ['api_key', 'oauth'])),
                        Forms\Components\TextInput::make('api_secret')
                            ->label('API Secret')
                            ->password()
                            ->revealable()
                            ->maxLength(500)
                            ->visible(fn (callable $get) => $get('credential_type') === 'oauth'),
                        Forms\Components\TextInput::make('access_token')
                            ->label('Access Token')
                            ->password()
                            ->revealable()
                            ->maxLength(500)
                            ->visible(fn (callable $get) => $get('credential_type') === 'oauth'),
                        Forms\Components\TextInput::make('refresh_token')
                            ->label('Refresh Token')
                            ->password()
                            ->revealable()
                            ->maxLength(500)
                            ->visible(fn (callable $get) => $get('credential_type') === 'oauth'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Rate Limiting')
                    ->schema([
                        Forms\Components\TextInput::make('rate_limit')
                            ->label('Rate Limit (requests per hour)')
                            ->numeric()
                            ->helperText('Leave empty if no limit'),
                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Expires At')
                            ->helperText('When these credentials expire'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Additional Configuration')
                    ->schema([
                        Forms\Components\KeyValue::make('additional_config')
                            ->label('Additional Settings')
                            ->keyLabel('Setting Name')
                            ->valueLabel('Value')
                            ->reorderable()
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
                Tables\Columns\BadgeColumn::make('service_name')
                    ->label('Service')
                    ->colors([
                        'primary' => fn ($state) => in_array($state, ['openai', 'anthropic']),
                        'success' => fn ($state) => in_array($state, ['sendgrid', 'mailgun', 'postmark']),
                        'warning' => fn ($state) => in_array($state, ['twilio', 'aws_ses']),
                        'info' => fn ($state) => in_array($state, ['cloudflare', 'digitalocean', 'vultr']),
                    ])
                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state))),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('usage_count')
                    ->label('Uses')
                    ->sortable()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('last_used_at')
                    ->label('Last Used')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rate_limit_remaining')
                    ->label('Rate Limit')
                    ->formatStateUsing(fn (ApiCredential $record) =>
                        $record->rate_limit
                            ? "{$record->rate_limit_remaining}/{$record->rate_limit}"
                            : 'No limit'
                    )
                    ->color(fn (ApiCredential $record) =>
                        $record->rate_limit && $record->rate_limit_remaining < ($record->rate_limit * 0.2)
                            ? 'danger'
                            : 'success'
                    ),
                Tables\Columns\IconColumn::make('is_expired')
                    ->label('Status')
                    ->boolean()
                    ->getStateUsing(fn (ApiCredential $record) => !$record->isExpired())
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('service_name')
                    ->options([
                        'openai' => 'OpenAI',
                        'anthropic' => 'Anthropic',
                        'sendgrid' => 'SendGrid',
                        'twilio' => 'Twilio',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('test')
                    ->icon('heroicon-o-beaker')
                    ->color('warning')
                    ->action(function (ApiCredential $record) {
                        // Test the API credentials
                        Notification::make()
                            ->title('Credentials test initiated')
                            ->info()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApiCredentials::route('/'),
            'create' => Pages\CreateApiCredential::route('/create'),
            'edit' => Pages\EditApiCredential::route('/{record}/edit'),
        ];
    }
}
