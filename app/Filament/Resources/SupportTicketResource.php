<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupportTicketResource\Pages;
use App\Filament\Resources\SupportTicketResource\RelationManagers;
use App\Models\SupportTicket;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SupportTicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'Support';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Ticket Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->required()
                            ->label('Customer'),

                        Forms\Components\TextInput::make('ticket_number')
                            ->disabled()
                            ->default(fn () => 'TKT-' . strtoupper(substr(uniqid(), -8))),

                        Forms\Components\TextInput::make('subject')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('department')
                            ->options([
                                'general' => 'General Support',
                                'billing' => 'Billing',
                                'technical' => 'Technical',
                                'sales' => 'Sales',
                            ])
                            ->required()
                            ->default('general'),

                        Forms\Components\Select::make('priority')
                            ->options([
                                'low' => 'Low',
                                'medium' => 'Medium',
                                'high' => 'High',
                                'critical' => 'Critical',
                            ])
                            ->required()
                            ->default('medium'),

                        Forms\Components\Select::make('status')
                            ->options([
                                'open' => 'Open',
                                'in_progress' => 'In Progress',
                                'waiting_customer' => 'Waiting on Customer',
                                'waiting_staff' => 'Waiting on Staff',
                                'closed' => 'Closed',
                            ])
                            ->required()
                            ->default('open'),

                        Forms\Components\Select::make('assigned_to')
                            ->relationship('assignedTo', 'name')
                            ->searchable()
                            ->label('Assigned To')
                            ->placeholder('Unassigned'),

                        Forms\Components\Select::make('related_service_id')
                            ->relationship('relatedService', 'domain')
                            ->searchable()
                            ->label('Related Service')
                            ->placeholder('None'),

                        Forms\Components\Select::make('related_domain_id')
                            ->relationship('relatedDomain', 'domain')
                            ->searchable()
                            ->label('Related Domain')
                            ->placeholder('None'),

                        Forms\Components\Select::make('related_invoice_id')
                            ->relationship('relatedInvoice', 'invoice_number')
                            ->searchable()
                            ->label('Related Invoice')
                            ->placeholder('None'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ticket_number')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('subject')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('department')
                    ->colors([
                        'primary' => 'general',
                        'success' => 'billing',
                        'warning' => 'technical',
                        'info' => 'sales',
                    ]),

                Tables\Columns\BadgeColumn::make('priority')
                    ->colors([
                        'secondary' => 'low',
                        'primary' => 'medium',
                        'warning' => 'high',
                        'danger' => 'critical',
                    ]),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'open',
                        'info' => 'in_progress',
                        'warning' => 'waiting_customer',
                        'secondary' => 'waiting_staff',
                        'danger' => 'closed',
                    ]),

                Tables\Columns\TextColumn::make('assignedTo.name')
                    ->label('Assigned To')
                    ->default('Unassigned')
                    ->badge()
                    ->color(fn ($state) => $state === 'Unassigned' ? 'gray' : 'success'),

                Tables\Columns\TextColumn::make('replies_count')
                    ->counts('replies')
                    ->label('Replies')
                    ->badge(),

                Tables\Columns\TextColumn::make('last_reply_at')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->since(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'open' => 'Open',
                        'in_progress' => 'In Progress',
                        'waiting_customer' => 'Waiting on Customer',
                        'waiting_staff' => 'Waiting on Staff',
                        'closed' => 'Closed',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('department')
                    ->options([
                        'general' => 'General Support',
                        'billing' => 'Billing',
                        'technical' => 'Technical',
                        'sales' => 'Sales',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('priority')
                    ->options([
                        'low' => 'Low',
                        'medium' => 'Medium',
                        'high' => 'High',
                        'critical' => 'Critical',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('assigned_to')
                    ->relationship('assignedTo', 'name')
                    ->label('Assigned To'),

                Tables\Filters\Filter::make('unassigned')
                    ->query(fn (Builder $query) => $query->whereNull('assigned_to'))
                    ->toggle(),

                Tables\Filters\Filter::make('my_tickets')
                    ->query(fn (Builder $query) => $query->where('assigned_to', auth()->id()))
                    ->toggle()
                    ->label('My Tickets'),
            ])
            ->actions([
                Tables\Actions\Action::make('assign_to_me')
                    ->icon('heroicon-o-user')
                    ->color('success')
                    ->visible(fn (SupportTicket $record) => !$record->assigned_to)
                    ->action(function (SupportTicket $record) {
                        $record->assignTo(auth()->user());
                    }),

                Tables\Actions\Action::make('take_over')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (SupportTicket $record) => $record->assigned_to && $record->assigned_to !== auth()->id())
                    ->action(function (SupportTicket $record) {
                        $record->assignTo(auth()->user());
                    })
                    ->requiresConfirmation(),

                Tables\Actions\Action::make('close')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (SupportTicket $record) => !$record->isClosed())
                    ->action(function (SupportTicket $record) {
                        $record->close();
                    })
                    ->requiresConfirmation(),

                Tables\Actions\Action::make('reopen')
                    ->icon('heroicon-o-arrow-path-rounded-square')
                    ->color('success')
                    ->visible(fn (SupportTicket $record) => $record->isClosed())
                    ->action(function (SupportTicket $record) {
                        $record->reopen();
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('assign')
                        ->icon('heroicon-o-user-plus')
                        ->form([
                            Forms\Components\Select::make('assigned_to')
                                ->label('Assign To')
                                ->options(User::pluck('name', 'id'))
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            $user = User::find($data['assigned_to']);
                            foreach ($records as $record) {
                                $record->assignTo($user);
                            }
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('close')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn ($records) => $records->each->close())
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('change_priority')
                        ->icon('heroicon-o-flag')
                        ->form([
                            Forms\Components\Select::make('priority')
                                ->options([
                                    'low' => 'Low',
                                    'medium' => 'Medium',
                                    'high' => 'High',
                                    'critical' => 'Critical',
                                ])
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            foreach ($records as $record) {
                                $record->update(['priority' => $data['priority']]);
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\RepliesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupportTickets::route('/'),
            'create' => Pages\CreateSupportTicket::route('/create'),
            'edit' => Pages\EditSupportTicket::route('/{record}/edit'),
            'view' => Pages\ViewSupportTicket::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereIn('status', ['open', 'in_progress'])->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
