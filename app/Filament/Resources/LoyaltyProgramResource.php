<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoyaltyProgramResource\Pages;
use App\Models\LoyaltyProgram;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LoyaltyProgramResource extends Resource
{
    protected static ?string $model = LoyaltyProgram::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_active')
                    ->default(true),
                Forms\Components\TextInput::make('points_per_dollar')
                    ->numeric()
                    ->required()
                    ->default(1)
                    ->minValue(0),
                Forms\Components\TextInput::make('minimum_spend')
                    ->numeric()
                    ->prefix('$')
                    ->default(0),
                Forms\Components\KeyValue::make('tier_rules')
                    ->keyLabel('Tier Name')
                    ->valueLabel('Minimum Points')
                    ->reorderable()
                    ->columnSpanFull(),
                Forms\Components\KeyValue::make('redemption_rules')
                    ->keyLabel('Rule')
                    ->valueLabel('Value')
                    ->reorderable()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('points_expiry_days')
                    ->numeric()
                    ->nullable()
                    ->helperText('Leave empty for no expiration'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('points_per_dollar')
                    ->sortable(),
                Tables\Columns\TextColumn::make('minimum_spend')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('userPoints_count')
                    ->counts('userPoints')
                    ->label('Active Users')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoyaltyPrograms::route('/'),
            'create' => Pages\CreateLoyaltyProgram::route('/create'),
            'edit' => Pages\EditLoyaltyProgram::route('/{record}/edit'),
        ];
    }
}
