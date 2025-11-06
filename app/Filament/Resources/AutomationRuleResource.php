<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AutomationRuleResource\Pages;
use App\Models\AutomationRule;
use App\Numz\Services\AutomationEngine;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class AutomationRuleResource extends Resource
{
    protected static ?string $model = AutomationRule::class;

    protected static ?string $navigationIcon = 'heroicon-o-bolt';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Automation Rules';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Rule Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Descriptive name for this automation rule'),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->maxLength(1000)
                            ->helperText('Explain what this rule does'),
                        Forms\Components\Select::make('trigger_event')
                            ->label('Trigger Event')
                            ->options(AutomationRule::getAvailableTriggers())
                            ->required()
                            ->searchable()
                            ->helperText('When should this rule be triggered?'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Enable or disable this rule'),
                        Forms\Components\TextInput::make('priority')
                            ->numeric()
                            ->default(0)
                            ->helperText('Higher priority rules execute first'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Conditions')
                    ->description('Define when this rule should execute (all conditions must be met)')
                    ->schema([
                        Forms\Components\Repeater::make('conditions')
                            ->schema([
                                Forms\Components\TextInput::make('field')
                                    ->required()
                                    ->helperText('Field name (e.g., amount, status, days_overdue)'),
                                Forms\Components\Select::make('operator')
                                    ->options(AutomationRule::getAvailableOperators())
                                    ->required(),
                                Forms\Components\TextInput::make('value')
                                    ->required()
                                    ->helperText('Value to compare against'),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->addActionLabel('Add Condition')
                            ->collapsible()
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Actions')
                    ->description('Define what should happen when conditions are met')
                    ->schema([
                        Forms\Components\Repeater::make('actions')
                            ->schema([
                                Forms\Components\Select::make('type')
                                    ->label('Action Type')
                                    ->options(AutomationRule::getAvailableActions())
                                    ->required()
                                    ->reactive(),
                                Forms\Components\KeyValue::make('params')
                                    ->label('Parameters')
                                    ->keyLabel('Parameter Name')
                                    ->valueLabel('Value')
                                    ->reorderable()
                                    ->helperText('Action-specific parameters (e.g., email template, amount, reason)'),
                            ])
                            ->columns(1)
                            ->defaultItems(1)
                            ->addActionLabel('Add Action')
                            ->collapsible()
                            ->columnSpanFull(),
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
                    ->weight('bold'),
                Tables\Columns\BadgeColumn::make('trigger_event')
                    ->label('Trigger')
                    ->formatStateUsing(fn (string $state): string =>
                        AutomationRule::getAvailableTriggers()[$state] ?? $state
                    )
                    ->colors([
                        'primary' => fn ($state) => str_contains($state, 'invoice'),
                        'success' => fn ($state) => str_contains($state, 'payment'),
                        'warning' => fn ($state) => str_contains($state, 'service'),
                        'info' => fn ($state) => str_contains($state, 'ticket'),
                    ]),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('priority')
                    ->sortable()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('execution_count')
                    ->label('Executions')
                    ->sortable()
                    ->alignCenter()
                    ->formatStateUsing(fn ($record) => number_format($record->execution_count)),
                Tables\Columns\TextColumn::make('success_rate')
                    ->label('Success Rate')
                    ->formatStateUsing(fn ($record) => $record->success_rate . '%')
                    ->color(fn ($record) => $record->success_rate >= 90 ? 'success' : ($record->success_rate >= 70 ? 'warning' : 'danger'))
                    ->sortable(false),
                Tables\Columns\TextColumn::make('last_executed_at')
                    ->label('Last Executed')
                    ->dateTime()
                    ->sortable()
                    ->since(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('trigger_event')
                    ->options(AutomationRule::getAvailableTriggers()),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\Action::make('test')
                    ->icon('heroicon-o-beaker')
                    ->color('warning')
                    ->form([
                        Forms\Components\KeyValue::make('test_data')
                            ->label('Test Data')
                            ->keyLabel('Field')
                            ->valueLabel('Value')
                            ->helperText('Provide test data to simulate the trigger event'),
                    ])
                    ->action(function (AutomationRule $record, array $data) {
                        $engine = app(AutomationEngine::class);
                        $result = $engine->testRule($record, $data['test_data'] ?? []);

                        if ($result['success']) {
                            Notification::make()
                                ->title('Test completed')
                                ->body($result['message'])
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Test failed')
                                ->body($result['message'])
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('view_executions')
                    ->icon('heroicon-o-clock')
                    ->url(fn (AutomationRule $record): string => route('filament.admin.resources.automation-rules.executions', $record)),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('activate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(fn ($records) => $records->each->update(['is_active' => true])),
                Tables\Actions\BulkAction::make('deactivate')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->action(fn ($records) => $records->each->update(['is_active' => false])),
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('priority', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAutomationRules::route('/'),
            'create' => Pages\CreateAutomationRule::route('/create'),
            'edit' => Pages\EditAutomationRule::route('/{record}/edit'),
            'executions' => Pages\ViewAutomationExecutions::route('/{record}/executions'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)->count();
    }
}
