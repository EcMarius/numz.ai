<?php

namespace App\Filament\Resources\PlatformSchemas\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Models\PlatformSchema;

class PlatformSchemaTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('platform')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'linkedin' => 'info',
                        'reddit' => 'warning',
                        'facebook' => 'primary',
                        'x' => 'gray',
                        default => 'success',
                    }),

                TextColumn::make('page_type')
                    ->searchable()
                    ->sortable()
                    ->badge(),

                TextColumn::make('element_type')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string =>
                        str_replace('_', ' ', ucwords($state, '_'))
                    ),

                TextColumn::make('css_selector')
                    ->label('CSS')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->css_selector)
                    ->toggleable(),

                TextColumn::make('xpath_selector')
                    ->label('XPath')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->xpath_selector)
                    ->toggleable(),

                IconColumn::make('is_required')
                    ->label('Required')
                    ->boolean()
                    ->sortable(),

                IconColumn::make('multiple')
                    ->label('Multiple')
                    ->boolean()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('version')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('order')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('platform')
                    ->options(array_combine(
                        PlatformSchema::PLATFORMS,
                        array_map('ucfirst', PlatformSchema::PLATFORMS)
                    )),

                SelectFilter::make('page_type')
                    ->options(array_combine(
                        PlatformSchema::PAGE_TYPES,
                        array_map('ucfirst', PlatformSchema::PAGE_TYPES)
                    )),

                SelectFilter::make('is_active')
                    ->options([
                        1 => 'Active',
                        0 => 'Inactive',
                    ])
                    ->label('Status'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('platform');
    }
}
