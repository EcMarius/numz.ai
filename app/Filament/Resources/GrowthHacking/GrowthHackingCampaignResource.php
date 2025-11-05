<?php

namespace App\Filament\Resources\GrowthHacking;

use App\Filament\Resources\GrowthHacking\GrowthHackingCampaignResource\Pages;
use App\Models\GrowthHackingCampaign;
use BackedEnum;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class GrowthHackingCampaignResource extends Resource
{
    protected static ?string $model = GrowthHackingCampaign::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationLabel = 'Campaigns';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): string
    {
        return 'Growth Hacking';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Campaign Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->rows(2),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->description),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'processing',
                        'info' => 'review',
                        'success' => 'sent',
                        'primary' => 'completed',
                    ]),

                Tables\Columns\TextColumn::make('total_prospects')
                    ->label('Prospects')
                    ->sortable(),

                Tables\Columns\TextColumn::make('emails_sent')
                    ->sortable(),

                Tables\Columns\TextColumn::make('open_rate')
                    ->label('Open Rate')
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->sortable(),

                Tables\Columns\TextColumn::make('conversion_rate')
                    ->label('Conversion')
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'processing' => 'Processing',
                        'review' => 'Review',
                        'sent' => 'Sent',
                        'completed' => 'Completed',
                    ]),
            ])
            ->actions([
                Action::make('review')
                    ->label('Review & Send')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => route('filament.admin.resources.growth-hacking-campaigns.review', ['record' => $record]))
                    ->visible(fn ($record) => $record->status === 'review'),

                Action::make('view')
                    ->label('View Stats')
                    ->icon('heroicon-o-chart-bar')
                    ->url(fn ($record) => route('filament.admin.resources.growth-hacking-campaigns.view', ['record' => $record]))
                    ->visible(fn ($record) => in_array($record->status, ['sent', 'completed'])),

                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGrowthHackingCampaigns::route('/'),
            'review' => Pages\ReviewCampaign::route('/{record}/review'),
            'view' => Pages\ViewCampaignStats::route('/{record}/view'),
        ];
    }
}
