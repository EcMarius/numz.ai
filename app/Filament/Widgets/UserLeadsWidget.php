<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Widgets\TableWidget as BaseWidget;
use Wave\Plugins\EvenLeads\Models\Lead;
use Wave\Plugins\EvenLeads\Models\Campaign;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;

class UserLeadsWidget extends BaseWidget
{
    public ?\Illuminate\Database\Eloquent\Model $record = null;

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 2;

    protected static bool $isDiscovered = false;

    public function table(Table $table): Table
    {
        if (!$this->record) {
            return $table
                ->heading('EvenLeads Leads')
                ->query(Lead::query()->whereRaw('1 = 0')); // Return empty query
        }

        return $table
            ->heading('EvenLeads Leads')
            ->query(
                Lead::query()
                    ->whereHas('campaign', function($query) {
                        $query->where('user_id', $this->record->id);
                    })
                    ->with('campaign')
            )
            ->columns([
                Tables\Columns\TextColumn::make('campaign.name')
                    ->label('Campaign')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Lead Title')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();

                        if (strlen($state) <= 50) {
                            return null;
                        }

                        return $state;
                    }),
                Tables\Columns\TextColumn::make('platform')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'reddit' => 'info',
                        'twitter' => 'primary',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('subreddit')
                    ->label('Subreddit')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('author')
                    ->label('Author')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('confidence_score')
                    ->label('Score')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 8 => 'success',
                        $state >= 5 => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'new' => 'info',
                        'contacted' => 'warning',
                        'closed' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('comments_count')
                    ->label('Comments')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('synced_at')
                    ->label('Post Date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Lead Received')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('campaign_id')
                    ->label('Campaign')
                    ->options(function () {
                        return Campaign::where('user_id', $this->record->id)
                            ->pluck('name', 'id');
                    })
                    ->searchable(),
                SelectFilter::make('status')
                    ->options([
                        'new' => 'New',
                        'contacted' => 'Contacted',
                        'closed' => 'Closed',
                    ]),
                SelectFilter::make('platform')
                    ->options([
                        'reddit' => 'Reddit',
                        'twitter' => 'Twitter',
                    ]),
                Filter::make('strong_match')
                    ->label('Strong Matches (Score ≥ 8)')
                    ->query(fn (Builder $query): Builder => $query->where('confidence_score', '>=', 8))
                    ->toggle(),
                Filter::make('partial_match')
                    ->label('Partial Matches (Score < 8)')
                    ->query(fn (Builder $query): Builder => $query->where('confidence_score', '<', 8))
                    ->toggle(),
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')
                            ->label('From'),
                        DatePicker::make('created_until')
                            ->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Action::make('delete')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Lead $record) {
                        $record->delete();
                        Notification::make()
                            ->success()
                            ->title('Lead Deleted')
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
