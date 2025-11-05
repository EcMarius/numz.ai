<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FeedbackResource\Pages;
use Wave\Plugins\EvenLeads\Models\Feedback;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\EditAction;
use Illuminate\Database\Eloquent\Collection;
use BackedEnum;

class FeedbackResource extends Resource
{
    protected static ?string $model = Feedback::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'User Feedback';

    protected static ?int $navigationSort = 15;

    public static function getNavigationGroup(): ?string
    {
        return 'System';
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'new')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('user.name')
                        ->label('User Name')
                        ->disabled()
                        ->dehydrated(false),
                    Forms\Components\TextInput::make('user.email')
                        ->label('User Email')
                        ->disabled()
                        ->dehydrated(false),
                ]),

                Forms\Components\Grid::make(3)->schema([
                    Forms\Components\Select::make('type')
                        ->options([
                            'bug' => 'Bug Report',
                            'feature' => 'Feature Request',
                            'improvement' => 'Improvement',
                            'other' => 'Other',
                        ])
                        ->disabled()
                        ->dehydrated(false),
                    Forms\Components\Select::make('priority')
                        ->options([
                            'low' => 'Low',
                            'medium' => 'Medium',
                            'high' => 'High',
                        ])
                        ->disabled()
                        ->dehydrated(false),
                    Forms\Components\Select::make('status')
                        ->options([
                            'new' => 'New',
                            'in_progress' => 'In Progress',
                            'resolved' => 'Resolved',
                            'closed' => 'Closed',
                        ])
                        ->required(),
                ]),

                Forms\Components\TextInput::make('subject')
                    ->disabled()
                    ->dehydrated(false)
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('message')
                    ->label('User\'s Message')
                    ->disabled()
                    ->dehydrated(false)
                    ->rows(4)
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('admin_response')
                    ->label('Admin Response / Notes')
                    ->rows(4)
                    ->columnSpanFull()
                    ->helperText('Internal notes or response to the user'),

                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\DateTimePicker::make('created_at')
                        ->label('Submitted At')
                        ->disabled()
                        ->dehydrated(false),
                    Forms\Components\DateTimePicker::make('responded_at')
                        ->label('Responded At')
                        ->disabled()
                        ->dehydrated(false),
                ]),

                Forms\Components\Section::make('Reward Information')
                    ->schema([
                        Forms\Components\Toggle::make('reward_given')
                            ->label('Reward Given')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('reward_type')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Textarea::make('reward_details')
                            ->disabled()
                            ->dehydrated(false)
                            ->rows(2),
                        Forms\Components\DateTimePicker::make('reward_given_at')
                            ->label('Reward Given At')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->collapsed()
                    ->visible(fn ($record) => $record && $record->reward_given),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'danger' => 'bug',
                        'success' => 'feature',
                        'warning' => 'improvement',
                        'secondary' => 'other',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('subject')
                    ->searchable()
                    ->limit(50)
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('priority')
                    ->colors([
                        'secondary' => 'low',
                        'warning' => 'medium',
                        'danger' => 'high',
                    ])
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'new',
                        'primary' => 'in_progress',
                        'success' => 'resolved',
                        'secondary' => 'closed',
                    ])
                    ->sortable(),
                Tables\Columns\IconColumn::make('reward_given')
                    ->label('Rewarded')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'bug' => 'Bug Report',
                        'feature' => 'Feature Request',
                        'improvement' => 'Improvement',
                        'other' => 'Other',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'new' => 'New',
                        'in_progress' => 'In Progress',
                        'resolved' => 'Resolved',
                        'closed' => 'Closed',
                    ]),
                Tables\Filters\SelectFilter::make('priority')
                    ->options([
                        'low' => 'Low',
                        'medium' => 'Medium',
                        'high' => 'High',
                    ]),
                Tables\Filters\TernaryFilter::make('reward_given')
                    ->label('Rewarded')
                    ->placeholder('All feedback')
                    ->trueLabel('Rewarded only')
                    ->falseLabel('Not rewarded'),
            ])
            ->recordActions([
                EditAction::make(),
                Tables\Actions\Action::make('sendThankYou')
                    ->label('Send Thank You Email')
                    ->icon('heroicon-o-envelope')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Send Thank You Email')
                    ->modalDescription('Send a thank you email to the user for their valuable feedback.')
                    ->action(function (Feedback $record) {
                        try {
                            // Send thank you email
                            \Mail::to($record->user->email)->send(new \App\Mail\FeedbackThankYou($record));

                            // Update responded_at if not set
                            if (!$record->responded_at) {
                                $record->responded_at = now();
                                $record->save();
                            }

                            Notification::make()
                                ->title('Thank You Email Sent')
                                ->body('Email sent successfully to ' . $record->user->email)
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Log::error('Failed to send feedback thank you email', [
                                'feedback_id' => $record->id,
                                'error' => $e->getMessage(),
                            ]);

                            Notification::make()
                                ->title('Email Failed')
                                ->body('Failed to send email. Check logs for details.')
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('giveReward')
                    ->label('Give Reward')
                    ->icon('heroicon-o-gift')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('reward_type')
                            ->label('Reward Type')
                            ->options([
                                'trial_extension' => 'Extend Trial (+7 days)',
                                'bonus_credits' => 'Bonus AI Credits',
                                'custom' => 'Custom Reward',
                            ])
                            ->required()
                            ->reactive(),
                        Forms\Components\TextInput::make('credit_amount')
                            ->label('Number of AI Credits')
                            ->numeric()
                            ->default(50)
                            ->visible(fn ($get) => $get('reward_type') === 'bonus_credits'),
                        Forms\Components\Textarea::make('reward_details')
                            ->label('Reward Details / Notes')
                            ->rows(3)
                            ->visible(fn ($get) => $get('reward_type') === 'custom'),
                    ])
                    ->action(function (Feedback $record, array $data) {
                        $user = $record->user;

                        if (!$user) {
                            Notification::make()
                                ->title('User Not Found')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Apply the reward
                        switch ($data['reward_type']) {
                            case 'trial_extension':
                                // Extend trial by 7 days
                                if ($user->trial_ends_at) {
                                    $user->trial_ends_at = $user->trial_ends_at->addDays(7);
                                } else {
                                    $user->trial_ends_at = now()->addDays(7);
                                }
                                $user->save();
                                $rewardDetails = 'Trial extended by 7 days';
                                break;

                            case 'bonus_credits':
                                // Add bonus AI credits
                                $creditAmount = $data['credit_amount'] ?? 50;
                                // Assuming you have a credits system
                                // $user->ai_credits += $creditAmount;
                                // $user->save();
                                $rewardDetails = "Bonus {$creditAmount} AI credits added";
                                break;

                            case 'custom':
                                $rewardDetails = $data['reward_details'] ?? 'Custom reward';
                                break;

                            default:
                                $rewardDetails = 'Reward given';
                        }

                        // Update feedback record
                        $record->update([
                            'reward_given' => true,
                            'reward_type' => $data['reward_type'],
                            'reward_details' => $rewardDetails,
                            'reward_given_at' => now(),
                        ]);

                        // Send email notification to user
                        try {
                            \Mail::to($user->email)->send(new \App\Mail\FeedbackReward($record, $rewardDetails));
                        } catch (\Exception $e) {
                            \Log::error('Failed to send reward email', [
                                'feedback_id' => $record->id,
                                'error' => $e->getMessage(),
                            ]);
                        }

                        Notification::make()
                            ->title('Reward Given')
                            ->body("Reward sent to {$user->name}")
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Feedback $record) => !$record->reward_given),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('markInProgress')
                        ->label('Mark as In Progress')
                        ->icon('heroicon-o-clock')
                        ->color('primary')
                        ->action(function (Collection $records) {
                            $records->each->update(['status' => 'in_progress']);

                            Notification::make()
                                ->title('Status Updated')
                                ->body($records->count() . ' feedback item(s) marked as in progress')
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('markResolved')
                        ->label('Mark as Resolved')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $records->each->update(['status' => 'resolved']);

                            Notification::make()
                                ->title('Status Updated')
                                ->body($records->count() . ' feedback item(s) marked as resolved')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFeedback::route('/'),
            'edit' => Pages\EditFeedback::route('/{record}/edit'),
        ];
    }
}
