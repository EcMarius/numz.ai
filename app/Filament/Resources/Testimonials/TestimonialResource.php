<?php

namespace App\Filament\Resources\Testimonials;

use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\Testimonials\Pages\ListTestimonials;
use App\Filament\Resources\Testimonials\Pages\CreateTestimonial;
use App\Filament\Resources\Testimonials\Pages\EditTestimonial;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use App\Models\Testimonial;

class TestimonialResource extends Resource
{
    protected static ?string $model = Testimonial::class;

    protected static BackedEnum|string|null $navigationIcon = 'phosphor-chats-circle-duotone';

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationLabel = 'Testimonials';

    protected static ?string $pluralModelLabel = 'Testimonials';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(191)
                            ->placeholder('John Doe')
                            ->helperText('Full name of the person giving the testimonial'),

                        TextInput::make('position')
                            ->required()
                            ->maxLength(191)
                            ->placeholder('CEO, Founder, Developer, etc.')
                            ->helperText('Job title or role'),
                    ]),

                TextInput::make('company')
                    ->maxLength(191)
                    ->placeholder('Company Name (optional)')
                    ->helperText('Company name (optional)'),

                Textarea::make('content')
                    ->required()
                    ->rows(4)
                    ->placeholder('Write the testimonial content here...')
                    ->helperText('The testimonial quote')
                    ->columnSpanFull(),

                Grid::make(2)
                    ->schema([
                        FileUpload::make('avatar')
                            ->label('Avatar Image (Optional)')
                            ->image()
                            ->disk('public')
                            ->directory('testimonials')
                            ->imageEditor()
                            ->imagePreviewHeight('120')
                            ->downloadable()
                            ->openable()
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/*'])
                            ->helperText('Upload a photo of the person. If not provided, gradient avatar with initials will be used.')
                            ->formatStateUsing(function ($state) {
                                if (empty($state)) return null;

                                // Strip /storage/ prefix for FileUpload component
                                if (str_starts_with($state, '/storage/')) {
                                    return substr($state, 9);
                                }

                                return $state;
                            })
                            ->dehydrateStateUsing(function ($state) {
                                if (empty($state)) return null;

                                // Already has /storage/ prefix, keep it
                                if (str_starts_with($state, '/storage/')) {
                                    return $state;
                                }

                                // Relative path from storage disk like "testimonials/file.jpg"
                                if (str_starts_with($state, 'testimonials/')) {
                                    return '/storage/' . $state;
                                }

                                // Bare filename
                                if (!str_starts_with($state, '/')) {
                                    return '/storage/testimonials/' . $state;
                                }

                                return $state;
                            }),

                        TextInput::make('avatar_fallback')
                            ->label('Initials (Fallback)')
                            ->maxLength(10)
                            ->placeholder('JD')
                            ->helperText('Initials to display if no image is uploaded (e.g., "JD" for John Doe)'),
                    ]),

                Grid::make(2)
                    ->schema([
                        Select::make('gradient_from')
                            ->label('Gradient Start Color')
                            ->options([
                                'blue-500' => 'Blue',
                                'purple-500' => 'Purple',
                                'emerald-500' => 'Emerald',
                                'pink-500' => 'Pink',
                                'orange-500' => 'Orange',
                                'red-500' => 'Red',
                                'indigo-500' => 'Indigo',
                                'green-500' => 'Green',
                                'yellow-500' => 'Yellow',
                                'gray-500' => 'Gray',
                            ])
                            ->default('blue-500')
                            ->required()
                            ->helperText('Start color for gradient avatar background'),

                        Select::make('gradient_to')
                            ->label('Gradient End Color')
                            ->options([
                                'blue-600' => 'Blue',
                                'purple-600' => 'Purple',
                                'emerald-600' => 'Emerald',
                                'pink-600' => 'Pink',
                                'orange-600' => 'Orange',
                                'red-600' => 'Red',
                                'indigo-600' => 'Indigo',
                                'green-600' => 'Green',
                                'yellow-600' => 'Yellow',
                                'gray-600' => 'Gray',
                            ])
                            ->default('blue-600')
                            ->required()
                            ->helperText('End color for gradient avatar background'),
                    ]),

                Grid::make(2)
                    ->schema([
                        TextInput::make('order')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->helperText('Order in which testimonial appears (lower numbers first)'),

                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->onIcon('heroicon-m-check')
                            ->offIcon('heroicon-m-x-mark')
                            ->onColor('success')
                            ->offColor('gray')
                            ->helperText('Show this testimonial on the website'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('position')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('content')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),

                TextColumn::make('avatar')
                    ->label('Avatar')
                    ->formatStateUsing(function ($state, $record) {
                        if ($state) {
                            $imagePath = $state;
                            if (str_starts_with($state, 'testimonials/')) {
                                $imagePath = '/storage/' . $state;
                            }
                            return '<img src="' . $imagePath . '" class="h-10 w-10 rounded-full object-cover" />';
                        }

                        // Show gradient avatar preview with initials
                        $initials = $record->initials;
                        $from = str_replace('-', '-', $record->gradient_from);
                        $to = str_replace('-', '-', $record->gradient_to);
                        return '<div class="h-10 w-10 rounded-full bg-gradient-to-br from-' . $from . ' to-' . $to . ' flex items-center justify-center text-white font-bold text-sm">' . $initials . '</div>';
                    })
                    ->html(),

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
            'index' => ListTestimonials::route('/'),
            'create' => CreateTestimonial::route('/create'),
            'edit' => EditTestimonial::route('/{record}/edit'),
        ];
    }
}
