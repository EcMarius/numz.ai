<?php

namespace App\Filament\Resources\Faqs;

use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\Faqs\Pages\ListFaqs;
use App\Filament\Resources\Faqs\Pages\CreateFaq;
use App\Filament\Resources\Faqs\Pages\EditFaq;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use App\Models\Faq;

class FaqResource extends Resource
{
    protected static ?string $model = Faq::class;

    protected static BackedEnum|string|null $navigationIcon = 'phosphor-question-duotone';

    protected static ?int $navigationSort = 7;

    protected static ?string $navigationLabel = 'FAQs';

    protected static ?string $pluralModelLabel = 'FAQs';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('question')
                    ->required()
                    ->rows(3)
                    ->placeholder('What is EvenLeads?')
                    ->helperText('The frequently asked question')
                    ->columnSpanFull(),

                RichEditor::make('answer')
                    ->required()
                    ->toolbarButtons([
                        'bold', 'italic', 'link',
                        'bulletList', 'orderedList',
                        'undo', 'redo',
                    ])
                    ->placeholder('Write the answer here...')
                    ->helperText('Answer to the question (supports basic formatting)')
                    ->columnSpanFull(),

                Grid::make(2)
                    ->schema([
                        TextInput::make('order')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->helperText('Order in which FAQ appears (lower numbers first)'),

                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->onIcon('heroicon-m-check')
                            ->offIcon('heroicon-m-x-mark')
                            ->onColor('success')
                            ->offColor('gray')
                            ->helperText('Show this FAQ on the website'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('question')
                    ->searchable()
                    ->sortable()
                    ->limit(60)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 60) {
                            return null;
                        }
                        return $state;
                    })
                    ->weight('bold'),

                TextColumn::make('answer')
                    ->searchable()
                    ->limit(100)
                    ->html()
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = strip_tags($column->getState());
                        if (strlen($state) <= 100) {
                            return null;
                        }
                        return $state;
                    }),

                TextColumn::make('order')
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('order', 'asc')
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
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
            'index' => ListFaqs::route('/'),
            'create' => CreateFaq::route('/create'),
            'edit' => EditFaq::route('/{record}/edit'),
        ];
    }
}
