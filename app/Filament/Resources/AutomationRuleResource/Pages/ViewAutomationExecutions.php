<?php

namespace App\Filament\Resources\AutomationRuleResource\Pages;

use App\Filament\Resources\AutomationRuleResource;
use App\Models\AutomationExecution;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class ViewAutomationExecutions extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = AutomationRuleResource::class;

    protected static string $view = 'filament.resources.automation-rule.view-executions';

    public AutomationRule $record;

    public function mount($record): void
    {
        $this->record = $this->getResource()::resolveRecordRouteBinding($record);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(AutomationExecution::query()->where('automation_rule_id', $this->record->id))
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status_text')
                    ->label('Status')
                    ->colors([
                        'success' => fn ($record) => $record->success && $record->conditions_met,
                        'danger' => fn ($record) => !$record->success && $record->conditions_met,
                        'gray' => fn ($record) => !$record->conditions_met,
                    ]),
                Tables\Columns\TextColumn::make('trigger_event')
                    ->label('Trigger')
                    ->searchable(),
                Tables\Columns\TextColumn::make('execution_time')
                    ->label('Time (s)')
                    ->numeric(2)
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Executed At')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('error_message')
                    ->label('Error')
                    ->limit(50)
                    ->searchable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('success')
                    ->options([
                        '1' => 'Success',
                        '0' => 'Failed',
                    ]),
                Tables\Filters\SelectFilter::make('conditions_met')
                    ->label('Conditions Met')
                    ->options([
                        '1' => 'Yes',
                        '0' => 'No',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('view_details')
                    ->icon('heroicon-o-eye')
                    ->modalContent(fn (AutomationExecution $record) =>
                        view('filament.resources.automation-rule.execution-details', ['execution' => $record])
                    )
                    ->modalHeading('Execution Details')
                    ->modalWidth('5xl'),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100]);
    }

    public function getTitle(): string
    {
        return 'Executions: ' . $this->record->name;
    }
}
