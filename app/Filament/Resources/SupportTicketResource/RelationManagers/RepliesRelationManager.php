<?php

namespace App\Filament\Resources\SupportTicketResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class RepliesRelationManager extends RelationManager
{
    protected static string $relationship = 'replies';

    protected static ?string $recordTitleAttribute = 'message';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\RichEditor::make('message')
                    ->required()
                    ->columnSpanFull(),

                Forms\Components\Toggle::make('is_staff_reply')
                    ->default(true)
                    ->label('Staff Reply'),

                Forms\Components\Toggle::make('is_internal_note')
                    ->default(false)
                    ->label('Internal Note (hidden from customer)')
                    ->helperText('Internal notes are only visible to staff'),

                Forms\Components\FileUpload::make('attachments')
                    ->multiple()
                    ->directory('ticket-attachments')
                    ->maxSize(10240)
                    ->acceptedFileTypes(['image/*', 'application/pdf', '.doc', '.docx', '.txt', '.zip'])
                    ->label('Attachments'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('From')
                    ->badge()
                    ->color(fn ($record) => $record->is_staff_reply ? 'success' : 'primary'),

                Tables\Columns\TextColumn::make('message')
                    ->html()
                    ->limit(100)
                    ->wrap(),

                Tables\Columns\IconColumn::make('is_internal_note')
                    ->boolean()
                    ->label('Internal')
                    ->tooltip('Internal Note'),

                Tables\Columns\TextColumn::make('attachments_count')
                    ->counts('attachments')
                    ->label('Files')
                    ->badge()
                    ->icon('heroicon-o-paper-clip'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_staff_reply')
                    ->label('Staff Replies'),

                Tables\Filters\TernaryFilter::make('is_internal_note')
                    ->label('Internal Notes'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data) {
                        $data['user_id'] = auth()->id();
                        return $data;
                    })
                    ->after(function () {
                        $this->ownerRecord->update([
                            'last_reply_at' => now(),
                        ]);
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'asc');
    }
}
