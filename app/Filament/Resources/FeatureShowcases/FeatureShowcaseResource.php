<?php

namespace App\Filament\Resources\FeatureShowcases;

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
use App\Filament\Resources\FeatureShowcases\Pages\ListFeatureShowcases;
use App\Filament\Resources\FeatureShowcases\Pages\CreateFeatureShowcase;
use App\Filament\Resources\FeatureShowcases\Pages\EditFeatureShowcase;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use App\Models\FeatureShowcase;

class FeatureShowcaseResource extends Resource
{
    protected static ?string $model = FeatureShowcase::class;

    protected static BackedEnum|string|null $navigationIcon = 'phosphor-presentation-duotone';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Feature Showcases';

    protected static ?string $pluralModelLabel = 'Feature Showcases';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->maxLength(191)
                    ->placeholder('Find Perfect Leads')
                    ->helperText('Feature title (keep it concise)')
                    ->columnSpanFull(),

                Textarea::make('description')
                    ->required()
                    ->rows(3)
                    ->placeholder('Discover leads looking for your services, track competitors, analyze pain points, and more...')
                    ->helperText('Brief description of the feature')
                    ->columnSpanFull(),

                Grid::make(2)
                    ->schema([
                        Select::make('media_type')
                            ->required()
                            ->options([
                                'image' => 'Image (PNG, JPG, WebP)',
                                'gif' => 'Animated GIF',
                                'video' => 'Video (MP4, WebM)',
                            ])
                            ->default('image')
                            ->live()
                            ->helperText('Type of media to display'),

                        FileUpload::make('media_path')
                            ->label('Media File')
                            ->disk('public')
                            ->directory('features')
                            ->acceptedFileTypes(function ($get) {
                                return match ($get('media_type')) {
                                    'gif' => ['image/gif'],
                                    'video' => ['video/mp4', 'video/webm', 'video/quicktime'],
                                    default => ['image/png', 'image/jpeg', 'image/jpg', 'image/webp'],
                                };
                            })
                            ->previewable()
                            ->downloadable()
                            ->openable()
                            ->maxSize(10240) // 10MB max
                            ->helperText('Upload the media file (Images: PNG/JPG/WebP, Animated GIF, Video: MP4/WebM/MOV - max 10MB)')
                            ->formatStateUsing(function ($state) {
                                if (empty($state)) return null;
                                if (str_starts_with($state, '/storage/')) {
                                    return substr($state, 9);
                                }
                                return $state;
                            })
                            ->dehydrateStateUsing(function ($state) {
                                if (empty($state)) return null;
                                if (str_starts_with($state, '/storage/')) {
                                    return $state;
                                }
                                return '/storage/' . $state;
                            }),
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
                            ->helperText('Show this feature on homepage'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                ImageColumn::make('media_path')
                    ->label('Media')
                    ->size(60)
                    ->defaultImageUrl('/images/placeholder.png'),

                TextColumn::make('media_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'gif' => 'success',
                        'video' => 'warning',
                        default => 'info',
                    })
                    ->formatStateUsing(fn (string $state): string => strtoupper($state)),

                TextColumn::make('order')
                    ->sortable()
                    ->alignCenter(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->alignCenter(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('order', 'asc')
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
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
            'index' => ListFeatureShowcases::route('/'),
            'create' => CreateFeatureShowcase::route('/create'),
            'edit' => EditFeatureShowcase::route('/{record}/edit'),
        ];
    }
}
