<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DomainRegistrationResource\Pages;
use App\Models\DomainRegistration;
use App\Models\User;
use BackedEnum;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DomainRegistrationResource extends Resource
{
    protected static ?string $model = DomainRegistration::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationLabel = 'Domains';

    protected static ?int $navigationSort = 13;

    public static function getNavigationGroup(): ?string
    {
        return 'NUMZ.AI';
    }

    public static function getNavigationBadge(): ?string
    {
        $expiringSoon = static::getModel()::where('expiry_date', '<=', now()->addDays(30))
            ->where('status', 'active')
            ->count();
        return $expiringSoon > 0 ? (string) $expiringSoon : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Domain Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Customer')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Domain owner'),

                        Forms\Components\TextInput::make('domain')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Domain name (e.g., example.com)'),

                        Forms\Components\Select::make('registrar')
                            ->required()
                            ->options([
                                'domainnameapi' => 'DomainNameAPI',
                                'other' => 'Other',
                            ])
                            ->default('domainnameapi')
                            ->helperText('Domain registrar'),

                        Forms\Components\Select::make('status')
                            ->required()
                            ->options([
                                'pending' => 'Pending',
                                'active' => 'Active',
                                'expired' => 'Expired',
                                'cancelled' => 'Cancelled',
                                'transferred' => 'Transferred Out',
                            ])
                            ->default('pending')
                            ->helperText('Current domain status'),
                    ])->columns(2),

                Forms\Components\Section::make('Registration Details')
                    ->schema([
                        Forms\Components\DatePicker::make('registration_date')
                            ->default(now())
                            ->helperText('Domain registration date'),

                        Forms\Components\DatePicker::make('expiry_date')
                            ->required()
                            ->default(now()->addYear())
                            ->helperText('Domain expiration date'),

                        Forms\Components\TextInput::make('renewal_price')
                            ->numeric()
                            ->prefix('$')
                            ->helperText('Annual renewal price'),

                        Forms\Components\Toggle::make('auto_renew')
                            ->label('Auto Renew')
                            ->default(true)
                            ->helperText('Automatically renew before expiration'),
                    ])->columns(2),

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

                Forms\Components\Section::make('Transfer')
                    ->schema([
                        Forms\Components\TextInput::make('epp_code')
                            ->maxLength(255)
                            ->helperText('EPP/Authorization code for transfers'),

                        Forms\Components\Toggle::make('privacy_protection')
                            ->label('Privacy Protection')
                            ->default(false)
                            ->helperText('WHOIS privacy protection'),
                    ])->columns(2),

                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull()
                            ->helperText('Internal notes'),
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

                Tables\Columns\TextColumn::make('registrar')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('registration_date')
                    ->date()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('expiry_date')
                    ->date()
                    ->sortable()
                    ->color(function ($record) {
                        $daysUntilExpiry = now()->diffInDays($record->expiry_date, false);
                        if ($daysUntilExpiry < 0) return 'danger'; // Expired
                        if ($daysUntilExpiry < 30) return 'warning'; // Expiring soon
                        return 'success';
                    })
                    ->formatStateUsing(function ($record, $state) {
                        $daysUntilExpiry = now()->diffInDays($record->expiry_date, false);
                        if ($daysUntilExpiry < 0) {
                            return $state . ' (Expired)';
                        } elseif ($daysUntilExpiry < 30) {
                            return $state . ' (' . abs($daysUntilExpiry) . ' days)';
                        }
                        return $state;
                    }),

                Tables\Columns\TextColumn::make('renewal_price')
                    ->money('USD')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'active' => 'success',
                        'expired' => 'danger',
                        'cancelled' => 'gray',
                        'transferred' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\IconColumn::make('auto_renew')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('privacy_protection')
                    ->boolean()
                    ->label('Privacy')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'active' => 'Active',
                        'expired' => 'Expired',
                        'cancelled' => 'Cancelled',
                        'transferred' => 'Transferred',
                    ]),
                Tables\Filters\SelectFilter::make('registrar')
                    ->options([
                        'domainnameapi' => 'DomainNameAPI',
                        'other' => 'Other',
                    ]),
                Tables\Filters\Filter::make('expiring_soon')
                    ->label('Expiring Soon (30 days)')
                    ->query(fn ($query) => $query->where('expiry_date', '<=', now()->addDays(30))->where('status', 'active')),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('renew')
                        ->label('Renew')
                        ->icon('heroicon-o-arrow-path')
                        ->color('success')
                        ->form([
                            Forms\Components\Select::make('years')
                                ->label('Renewal Period')
                                ->options([
                                    1 => '1 Year',
                                    2 => '2 Years',
                                    3 => '3 Years',
                                    5 => '5 Years',
                                    10 => '10 Years',
                                ])
                                ->default(1)
                                ->required(),
                        ])
                        ->action(function (DomainRegistration $record, array $data) {
                            // TODO: Implement domain renewal
                            $record->update([
                                'expiry_date' => $record->expiry_date->addYears($data['years']),
                            ]);
                            \Filament\Notifications\Notification::make()
                                ->title('Domain renewed successfully')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\Action::make('update_nameservers')
                        ->label('Update NS')
                        ->icon('heroicon-o-server')
                        ->color('info')
                        ->form([
                            Forms\Components\TextInput::make('ns1')
                                ->label('Nameserver 1')
                                ->required(),
                            Forms\Components\TextInput::make('ns2')
                                ->label('Nameserver 2')
                                ->required(),
                            Forms\Components\TextInput::make('ns3')
                                ->label('Nameserver 3'),
                            Forms\Components\TextInput::make('ns4')
                                ->label('Nameserver 4'),
                        ])
                        ->action(function (DomainRegistration $record, array $data) {
                            // TODO: Implement nameserver update
                            $record->update([
                                'nameserver1' => $data['ns1'],
                                'nameserver2' => $data['ns2'],
                                'nameserver3' => $data['ns3'] ?? null,
                                'nameserver4' => $data['ns4'] ?? null,
                            ]);
                            \Filament\Notifications\Notification::make()
                                ->title('Nameservers updated')
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
            ->defaultSort('expiry_date', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDomainRegistrations::route('/'),
            'create' => Pages\CreateDomainRegistration::route('/create'),
            'edit' => Pages\EditDomainRegistration::route('/{record}/edit'),
        ];
    }
}
