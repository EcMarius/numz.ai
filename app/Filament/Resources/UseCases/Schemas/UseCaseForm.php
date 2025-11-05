<?php

namespace App\Filament\Resources\UseCases\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Grid;

class UseCaseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(191)
                            ->placeholder('Find Clients as a Web Developer')
                            ->helperText('Short, catchy title for the use case'),

                        TextInput::make('target_audience')
                            ->maxLength(191)
                            ->placeholder('Web Developers, Freelancers')
                            ->helperText('Who is this use case for? (optional)'),
                    ]),

                Textarea::make('description')
                    ->required()
                    ->rows(3)
                    ->placeholder('Automatically discover Reddit posts from people looking for web development services...')
                    ->helperText('Brief description of how EvenLeads helps in this scenario')
                    ->columnSpanFull(),

                Grid::make(2)
                    ->schema([
                        Select::make('icon')
                            ->label('Icon (Optional)')
                            ->options([
                                'phosphor-code' => 'Code / Development',
                                'phosphor-briefcase' => 'Briefcase / Business',
                                'phosphor-rocket' => 'Rocket / Startup',
                                'phosphor-chart-line' => 'Chart / Analytics',
                                'phosphor-chat-dots' => 'Chat / Feedback',
                                'phosphor-megaphone' => 'Megaphone / Marketing',
                                'phosphor-storefront' => 'Storefront / Retail',
                                'phosphor-users' => 'Users / Community',
                                'phosphor-lightbulb' => 'Lightbulb / Ideas',
                                'phosphor-target' => 'Target / Focus',
                            ])
                            ->searchable()
                            ->helperText('Icon to display with the use case'),

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
                            ->helperText('Color theme for this use case'),
                    ]),

                Grid::make(2)
                    ->schema([
                        TextInput::make('order')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->helperText('Display order (lower numbers first)'),

                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->onIcon('heroicon-m-check')
                            ->offIcon('heroicon-m-x-mark')
                            ->onColor('success')
                            ->offColor('gray')
                            ->helperText('Show this use case on the website'),
                    ]),
            ]);
    }
}
