<?php

namespace App\Filament\Resources\PlatformSchemas\Schemas;

use App\Models\PlatformSchema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Schemas\Schema;

class PlatformSchemaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Platform Configuration')
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('platform')
                                ->required()
                                ->options(array_combine(
                                    PlatformSchema::PLATFORMS,
                                    array_map('ucfirst', PlatformSchema::PLATFORMS)
                                )),

                            Select::make('page_type')
                                ->required()
                                ->options(array_combine(
                                    PlatformSchema::PAGE_TYPES,
                                    array_map('ucfirst', PlatformSchema::PAGE_TYPES)
                                )),

                            Select::make('element_type')
                                ->required()
                                ->options(array_combine(
                                    PlatformSchema::ELEMENT_TYPES,
                                    array_map(function($type) {
                                        return str_replace('_', ' ', ucwords($type, '_'));
                                    }, PlatformSchema::ELEMENT_TYPES)
                                )),
                        ]),
                    ]),

                Section::make('Selectors')
                    ->schema([
                        Textarea::make('css_selector')
                            ->label('CSS Selector')
                            ->rows(3)
                            ->helperText('Use stable attributes like data-* or IDs. Avoid class names.'),

                        Textarea::make('xpath_selector')
                            ->label('XPath Selector')
                            ->rows(3)
                            ->helperText('XPath expression for element selection.'),

                        TextInput::make('parent_element')
                            ->label('Parent Element Type')
                            ->helperText('If this element is nested, specify the parent element type.'),
                    ]),

                Section::make('Configuration')
                    ->schema([
                        Grid::make(3)->schema([
                            Toggle::make('is_required')
                                ->label('Required Element')
                                ->default(false),

                            Toggle::make('multiple')
                                ->label('Multiple Elements')
                                ->helperText('Can this selector match multiple elements?')
                                ->default(false),

                            Toggle::make('is_active')
                                ->label('Active')
                                ->default(true),
                        ]),

                        TextInput::make('fallback_value')
                            ->label('Fallback Value')
                            ->helperText('Default value if element is not found.'),

                        TextInput::make('version')
                            ->default('1.0.0')
                            ->required(),

                        TextInput::make('order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Display order (lower numbers first).'),
                    ]),

                Section::make('Documentation')
                    ->schema([
                        Textarea::make('description')
                            ->rows(2)
                            ->columnSpanFull(),

                        Textarea::make('notes')
                            ->rows(3)
                            ->helperText('Technical notes, examples, or gotchas.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
