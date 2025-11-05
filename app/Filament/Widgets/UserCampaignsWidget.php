<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Widgets\TableWidget as BaseWidget;
use Wave\Plugins\EvenLeads\Models\Campaign;
use Filament\Notifications\Notification;

class UserCampaignsWidget extends BaseWidget
{
    public ?\Illuminate\Database\Eloquent\Model $record = null;

    protected int | string | array $columnSpan = 'full';

    protected static bool $isDiscovered = false;

    public function table(Table $table): Table
    {
        if (!$this->record) {
            return $table
                ->heading('EvenLeads Campaigns')
                ->query(Campaign::query()->whereRaw('1 = 0')); // Return empty query
        }

        return $table
            ->heading('EvenLeads Campaigns')
            ->query(
                Campaign::query()
                    ->where('user_id', $this->record->id)
                    ->withCount('leads')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Campaign Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'paused' => 'warning',
                        'completed' => 'gray',
                        default => 'primary',
                    }),
                Tables\Columns\TextColumn::make('leads_count')
                    ->label('Leads')
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_sync_at')
                    ->label('Last Sync')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Action::make('delete')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Campaign $record) {
                        $record->delete();
                        Notification::make()
                            ->success()
                            ->title('Campaign Deleted')
                            ->send();
                    }),
            ]);
    }
}
