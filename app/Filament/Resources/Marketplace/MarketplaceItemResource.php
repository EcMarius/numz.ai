<?php

namespace App\Filament\Resources\Marketplace;

use App\Filament\Resources\Marketplace\MarketplaceItemResource\Pages;
use App\Models\Marketplace\MarketplaceItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class MarketplaceItemResource extends Resource
{
    protected static ?string $model = MarketplaceItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationLabel = 'Marketplace Items';

    protected static ?string $navigationGroup = 'Marketplace';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('category_id')
                            ->relationship('category', 'name')
                            ->required(),
                        Forms\Components\Select::make('user_id')
                            ->relationship('creator', 'name')
                            ->required()
                            ->searchable(),
                        Forms\Components\TextInput::make('current_version')
                            ->required()
                            ->default('1.0.0'),
                    ])->columns(2),

                Forms\Components\Section::make('Description')
                    ->schema([
                        Forms\Components\Textarea::make('short_description')
                            ->required()
                            ->maxLength(500)
                            ->rows(3),
                        Forms\Components\RichEditor::make('description')
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\RichEditor::make('installation_instructions')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Pricing')
                    ->schema([
                        Forms\Components\TextInput::make('price')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->default(0),
                        Forms\Components\TextInput::make('creator_revenue_percentage')
                            ->required()
                            ->numeric()
                            ->suffix('%')
                            ->default(70)
                            ->minValue(0)
                            ->maxValue(100),
                        Forms\Components\Toggle::make('is_free')
                            ->label('Free Item'),
                    ])->columns(3),

                Forms\Components\Section::make('URLs')
                    ->schema([
                        Forms\Components\TextInput::make('demo_url')
                            ->url()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('documentation_url')
                            ->url()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('support_url')
                            ->url()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('repository_url')
                            ->url()
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Status & Moderation')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'pending_review' => 'Pending Review',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                                'suspended' => 'Suspended',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('rejection_reason')
                            ->maxLength(1000)
                            ->visible(fn ($get) => $get('status') === 'rejected'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active'),
                        Forms\Components\Toggle::make('is_featured')
                            ->label('Featured'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('icon')
                    ->disk('public')
                    ->size(50),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('creator.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->money('usd')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'pending_review',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'danger' => 'suspended',
                    ]),
                Tables\Columns\TextColumn::make('purchases_count')
                    ->label('Sales')
                    ->sortable(),
                Tables\Columns\TextColumn::make('average_rating')
                    ->label('Rating')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state, 1) . ' ★'),
                Tables\Columns\IconColumn::make('is_featured')
                    ->boolean()
                    ->label('Featured'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'pending_review' => 'Pending Review',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'suspended' => 'Suspended',
                    ]),
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name'),
                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Featured'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
                Tables\Filters\TernaryFilter::make('is_free')
                    ->label('Free Items'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (MarketplaceItem $record) => $record->status === 'pending_review')
                        ->action(function (MarketplaceItem $record) {
                            $record->approve(Auth::user());
                        }),
                    Tables\Actions\Action::make('reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\Textarea::make('rejection_reason')
                                ->required()
                                ->label('Reason for rejection'),
                        ])
                        ->visible(fn (MarketplaceItem $record) => $record->status === 'pending_review')
                        ->action(function (MarketplaceItem $record, array $data) {
                            $record->reject($data['rejection_reason']);
                        }),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListMarketplaceItems::route('/'),
            'create' => Pages\CreateMarketplaceItem::route('/create'),
            'edit' => Pages\EditMarketplaceItem::route('/{record}/edit'),
            'view' => Pages\ViewMarketplaceItem::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending_review')->count();
    }
}
