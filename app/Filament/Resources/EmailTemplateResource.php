<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmailTemplateResource\Pages;
use App\Models\EmailTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EmailTemplateResource extends Resource
{
    protected static ?string $model = EmailTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Email Templates';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Template Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Used to identify this template in code'),
                        Forms\Components\Select::make('category')
                            ->options([
                                'billing' => 'Billing',
                                'support' => 'Support',
                                'system' => 'System',
                                'marketing' => 'Marketing',
                                'custom' => 'Custom',
                            ])
                            ->required(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                        Forms\Components\Toggle::make('is_system')
                            ->label('System Template')
                            ->helperText('System templates cannot be deleted')
                            ->default(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Email Content')
                    ->schema([
                        Forms\Components\TextInput::make('subject')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Use {{ variable_name }} for dynamic content'),
                        Forms\Components\RichEditor::make('html_body')
                            ->label('HTML Body')
                            ->required()
                            ->columnSpanFull()
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'link',
                                'bulletList',
                                'orderedList',
                                'h2',
                                'h3',
                            ]),
                        Forms\Components\Textarea::make('text_body')
                            ->label('Plain Text Body')
                            ->rows(10)
                            ->helperText('Fallback for email clients that don\'t support HTML')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Available Variables')
                    ->description('Variables that can be used in this template')
                    ->schema([
                        Forms\Components\TagsInput::make('available_variables')
                            ->placeholder('Add variable (e.g., user.name, invoice.number)')
                            ->helperText('These will be shown as help text when using this template')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Email Settings')
                    ->schema([
                        Forms\Components\TextInput::make('from_name')
                            ->maxLength(255)
                            ->helperText('Leave empty to use system default'),
                        Forms\Components\TextInput::make('from_email')
                            ->email()
                            ->maxLength(255)
                            ->helperText('Leave empty to use system default'),
                        Forms\Components\TextInput::make('reply_to')
                            ->email()
                            ->maxLength(255)
                            ->helperText('Reply-to email address'),
                    ])
                    ->columns(3),
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
                Tables\Columns\BadgeColumn::make('category')
                    ->colors([
                        'primary' => 'billing',
                        'success' => 'support',
                        'warning' => 'system',
                        'info' => 'marketing',
                        'secondary' => 'custom',
                    ]),
                Tables\Columns\TextColumn::make('subject')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_system')
                    ->label('System')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'billing' => 'Billing',
                        'support' => 'Support',
                        'system' => 'System',
                        'marketing' => 'Marketing',
                        'custom' => 'Custom',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\Action::make('preview')
                    ->icon('heroicon-o-eye')
                    ->modalContent(fn (EmailTemplate $record) => view('filament.resources.email-template.preview', ['template' => $record]))
                    ->modalHeading('Preview Template')
                    ->modalWidth('5xl'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn (EmailTemplate $record) => $record->is_system),
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
                Tables\Actions\DeleteBulkAction::make()
                    ->action(fn ($records) => $records->filter(fn ($record) => !$record->is_system)->each->delete()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmailTemplates::route('/'),
            'create' => Pages\CreateEmailTemplate::route('/create'),
            'edit' => Pages\EditEmailTemplate::route('/{record}/edit'),
        ];
    }
}
