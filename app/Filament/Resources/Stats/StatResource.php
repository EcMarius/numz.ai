<?php

namespace App\Filament\Resources\Stats;

use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\Stats\Pages\ListStats;
use App\Filament\Resources\Stats\Pages\CreateStat;
use App\Filament\Resources\Stats\Pages\EditStat;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use App\Models\Stat;

class StatResource extends Resource
{
    protected static ?string $model = Stat::class;

    protected static BackedEnum|string|null $navigationIcon = 'phosphor-chart-bar-duotone';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Stats';

    protected static ?string $pluralModelLabel = 'Stats';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        TextInput::make('label')
                            ->required()
                            ->maxLength(191)
                            ->placeholder('Posts Scanned Daily')
                            ->helperText('Label for the statistic'),

                        TextInput::make('value')
                            ->required()
                            ->maxLength(191)
                            ->placeholder('25,000+')
                            ->helperText('Value to display (can include symbols like +, %, etc.)'),
                    ]),

                Grid::make(2)
                    ->schema([
                        Select::make('icon')
                            ->label('Icon (Optional)')
                            ->options([
                                'phosphor-magnifying-glass' => 'Search/Magnifying Glass',
                                'phosphor-users' => 'Users/People',
                                'phosphor-target' => 'Target/Aim',
                                'phosphor-chart-line' => 'Chart Line',
                                'phosphor-chart-bar' => 'Chart Bar',
                                'phosphor-rocket' => 'Rocket',
                                'phosphor-lightning' => 'Lightning/Bolt',
                                'phosphor-star' => 'Star',
                                'phosphor-fire' => 'Fire/Trending',
                                'phosphor-check-circle' => 'Check Circle',
                            ])
                            ->searchable()
                            ->helperText('Icon to display with the stat'),

                        Select::make('color')
                            ->required()
                            ->options([
                                'blue' => 'Blue',
                                'emerald' => 'Emerald/Green',
                                'purple' => 'Purple',
                                'pink' => 'Pink',
                                'orange' => 'Orange',
                                'red' => 'Red',
                                'indigo' => 'Indigo',
                                'yellow' => 'Yellow',
                                'gray' => 'Gray',
                            ])
                            ->default('blue')
                            ->helperText('Color theme for this stat'),
                    ]),

                Grid::make(2)
                    ->schema([
                        TextInput::make('order')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->helperText('Order in which stat appears (lower numbers first)'),

                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->onIcon('heroicon-m-check')
                            ->offIcon('heroicon-m-x-mark')
                            ->onColor('success')
                            ->offColor('gray')
                            ->helperText('Show this stat on the website'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('value')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn ($record) => $record->color),

                TextColumn::make('color')
                    ->badge()
                    ->color(fn (string $state): string => $state),

                TextColumn::make('order')
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('order', 'asc')
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStats::route('/'),
            'create' => CreateStat::route('/create'),
            'edit' => EditStat::route('/{record}/edit'),
        ];
    }
}
