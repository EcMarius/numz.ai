<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SLAPolicyResource\Pages;
use App\Models\SLAPolicy;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SLAPolicyResource extends Resource
{
    protected static ?string $model = SLAPolicy::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'Support';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'SLA Policy';

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
                Forms\Components\Toggle::make('is_default')
                    ->helperText('This will be the default policy for new tickets'),
                Forms\Components\TextInput::make('first_response_time')
                    ->numeric()
                    ->required()
                    ->suffix('minutes')
                    ->helperText('Expected time for first response'),
                Forms\Components\TextInput::make('resolution_time')
                    ->numeric()
                    ->required()
                    ->suffix('minutes')
                    ->helperText('Expected time for resolution'),
                Forms\Components\KeyValue::make('priority_multipliers')
                    ->keyLabel('Priority')
                    ->valueLabel('Multiplier')
                    ->helperText('Different SLA times based on priority (e.g., urgent: 0.5, low: 2)')
                    ->columnSpanFull(),
                Forms\Components\KeyValue::make('working_hours')
                    ->keyLabel('Day')
                    ->valueLabel('Hours (e.g., 09:00-17:00)')
                    ->helperText('Business hours for SLA calculation')
                    ->columnSpanFull(),
                Forms\Components\Repeater::make('holidays')
                    ->simple(
                        Forms\Components\DatePicker::make('date')
                    )
                    ->columnSpanFull(),
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
                Tables\Columns\IconColumn::make('is_default')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('first_response_time')
                    ->suffix(' min')
                    ->sortable(),
                Tables\Columns\TextColumn::make('resolution_time')
                    ->suffix(' min')
                    ->sortable(),
                Tables\Columns\TextColumn::make('tickets_count')
                    ->counts('tickets')
                    ->label('Tickets')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Filters\TernaryFilter::make('is_default')
                    ->label('Default')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('setAsDefault')
                    ->action(fn (SLAPolicy $record) => $record->setAsDefault())
                    ->requiresConfirmation()
                    ->visible(fn (SLAPolicy $record) => !$record->is_default)
                    ->color('warning')
                    ->icon('heroicon-o-star'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSLAPolicies::route('/'),
            'create' => Pages\CreateSLAPolicy::route('/create'),
            'edit' => Pages\EditSLAPolicy::route('/{record}/edit'),
        ];
    }
}
