<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AffiliateResource\Pages;
use App\Models\Affiliate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AffiliateResource extends Resource
{
    protected static ?string $model = Affiliate::class;

    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Affiliate Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('affiliate_tier_id')
                            ->relationship('tier', 'name')
                            ->required(),

                        Forms\Components\TextInput::make('affiliate_code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'active' => 'Active',
                                'suspended' => 'Suspended',
                                'banned' => 'Banned',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('referral_url')
                            ->url()
                            ->maxLength(500),

                        Forms\Components\TextInput::make('payment_email')
                            ->email()
                            ->maxLength(255),

                        Forms\Components\Select::make('payment_method')
                            ->options([
                                'paypal' => 'PayPal',
                                'bank_transfer' => 'Bank Transfer',
                                'check' => 'Check',
                            ]),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Statistics')
                    ->schema([
                        Forms\Components\TextInput::make('total_clicks')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('total_conversions')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('total_sales')
                            ->numeric()
                            ->prefix('$')
                            ->disabled(),

                        Forms\Components\TextInput::make('pending_commission')
                            ->numeric()
                            ->prefix('$')
                            ->disabled(),

                        Forms\Components\TextInput::make('total_commission_paid')
                            ->numeric()
                            ->prefix('$')
                            ->disabled(),
                    ])
                    ->columns(3)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('affiliate_code')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('tier.name')
                    ->label('Tier')
                    ->badge(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'pending' => 'warning',
                        'suspended' => 'danger',
                        'banned' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('total_clicks')
                    ->label('Clicks')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_conversions')
                    ->label('Conversions')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_sales')
                    ->label('Sales')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('pending_commission')
                    ->label('Pending')
                    ->money('USD')
                    ->sortable(),

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
                        'banned' => 'Banned',
                    ]),

                Tables\Filters\SelectFilter::make('tier')
                    ->relationship('tier', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListAffiliates::route('/'),
            'create' => Pages\CreateAffiliate::route('/create'),
            'edit' => Pages\EditAffiliate::route('/{record}/edit'),
            'view' => Pages\ViewAffiliate::route('/{record}'),
        ];
    }
}
