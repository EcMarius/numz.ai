<?php

namespace App\Filament\Resources\Settings;

use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use App\Filament\Resources\Settings\Pages\ListSettings;
use App\Filament\Resources\Settings\Pages\CreateSetting;
use App\Filament\Resources\Settings\Pages\EditSetting;
use App\Filament\Resources\SettingResource\Pages;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Group;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Wave\Setting;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;

    protected static BackedEnum|string|null $navigationIcon = 'phosphor-gear-fine-duotone';

    protected static ?int $navigationSort = 9;

    protected static function getValueFieldForType(string $type, string $key)
    {
        // Special handling for specific settings
        if ($key === 'api.scribe_auth_key') {
            return Group::make([
                Placeholder::make('api_key_helper')
                    ->label('')
                    ->content(new \Illuminate\Support\HtmlString('
                        <div class="rounded-lg bg-blue-50 dark:bg-blue-950/20 p-4 border border-blue-200 dark:border-blue-900/50">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div class="flex-1">
                                    <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-200 mb-1">
                                        API Key Required
                                    </h4>
                                    <p class="text-sm text-blue-700 dark:text-blue-300 mb-3">
                                        This key is used to test API endpoints in the interactive documentation.
                                    </p>
                                    <a href="/settings/api" target="_blank" class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg">
                                        Get API Key
                                    </a>
                                </div>
                            </div>
                        </div>
                    ')),
                TextInput::make('value')
                    ->label('API Key Value')
                    ->placeholder('Paste your API key here')
                    ->maxLength(191)
                    ->password()
                    ->revealable()
                    ->helperText('This key will be used to test endpoints in the API documentation playground.'),
            ])->columnSpanFull();
        }

        if ($key === 'site.currency_format') {
            return Select::make('value')
                ->label('Value')
                ->options([
                    'symbol' => 'Symbol (€, $, £)',
                    'code' => 'Code (EUR, USD, GBP)',
                ]);
        }

        if ($key === 'site.currency_position') {
            return Select::make('value')
                ->label('Value')
                ->options([
                    'prepend' => 'Prepend (before price)',
                    'append' => 'Append (after price)',
                ]);
        }

        // Standard field types
        return match ($type) {
            'checkbox' => Toggle::make('value')
                ->label('Enabled')
                ->onIcon('heroicon-m-check')
                ->offIcon('heroicon-m-x-mark')
                ->onColor('success')
                ->offColor('gray')
                ->formatStateUsing(fn ($state) => $state === '1' || $state === 1 || $state === true)
                ->dehydrateStateUsing(fn ($state) => $state ? '1' : '0')
                ->helperText('Toggle to enable or disable this setting'),

            'textarea' => Textarea::make('value')
                ->label('Value')
                ->rows(5)
                ->columnSpanFull(),

            'rich_text' => RichEditor::make('value')
                ->label('Value')
                ->columnSpanFull()
                ->toolbarButtons([
                    'bold', 'italic', 'strike', 'link',
                    'h2', 'h3', 'bulletList', 'orderedList', 'blockquote',
                    'codeBlock', 'undo', 'redo',
                ]),

            'image' => FileUpload::make('value')
                ->label('Image')
                ->image()
                ->disk('public')
                ->directory('settings')
                ->imagePreviewHeight('200')
                ->maxSize(5120)
                ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg', 'image/gif', 'image/webp', 'image/svg+xml'])
                ->visibility('public')
                ->uploadingMessage('Uploading image...'),

            'file' => FileUpload::make('value')
                ->label('File')
                ->disk('public')
                ->directory('settings')
                ->downloadable()
                ->openable()
                ->maxSize(10240)
                ->visibility('public'),

            default => TextInput::make('value')
                ->label('Value')
                ->maxLength(191),
        };
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('key')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(191),
                TextInput::make('display_name')
                    ->required()
                    ->maxLength(191),
                Select::make('type')
                    ->required()
                    ->options([
                        'text' => 'Text',
                        'textarea' => 'Textarea',
                        'rich_text' => 'Rich Text',
                        'image' => 'Image',
                        'file' => 'File',
                        'checkbox' => 'Checkbox (Toggle Switch)',
                    ])
                    ->default('text')
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set) {
                        // When type changes, ensure value remains a string
                        $set('value', '');
                    }),
                Group::make(function ($record, $get) {
                    $type = $get('type') ?? $record?->type ?? 'text';
                    $key = $get('key') ?? $record?->key ?? '';

                    return [static::getValueFieldForType($type, $key)];
                })->columnSpanFull(),
                Textarea::make('details')
                    ->columnSpanFull(),
                TextInput::make('order')
                    ->required()
                    ->numeric()
                    ->default(1),
                TextInput::make('group')
                    ->maxLength(191),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')
                    ->searchable(),
                TextColumn::make('value')
                    ->searchable()
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->type === 'checkbox' && !empty($state)) {
                            return $state === '1' || $state === 1 || $state === true
                                ? '<span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-md bg-green-100 text-green-800">✓ Enabled</span>'
                                : '<span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-md bg-gray-100 text-gray-800">✗ Disabled</span>';
                        } elseif ($record->type === 'image' && !empty($state)) {
                            // Handle various path formats
                            if (str_starts_with($state, '/storage/')) {
                                // Storage path
                                $imagePath = $state;
                            } elseif (str_starts_with($state, '/images/') || str_starts_with($state, '/')) {
                                // Public folder path
                                $imagePath = $state;
                            } elseif (str_starts_with($state, 'settings/')) {
                                // Relative path from storage
                                $imagePath = '/storage/' . $state;
                            } else {
                                // Unknown format - show for debugging
                                return '<span class="text-xs text-gray-500">Invalid: ' . htmlspecialchars($state) . '</span>';
                            }

                            // Clean up double slashes
                            $imagePath = preg_replace('#/+#', '/', $imagePath);

                            return '<div class="flex"><img src="' . $imagePath . '" class="h-12 w-auto rounded object-contain" /></div>';
                        } elseif ($record->type === 'file' && !empty($state)) {
                            // Show file icon for files
                            $extension = pathinfo($state, PATHINFO_EXTENSION);
                            $icon = match(strtolower($extension)) {
                                'pdf' => '📄',
                                'doc', 'docx' => '📝',
                                'xls', 'xlsx' => '📊',
                                'zip', 'rar' => '📦',
                                default => '📎'
                            };
                            return $icon . ' ' . basename($state);
                        }
                        return $state;
                    })
                    ->html()
                    ->limit(50),
                TextColumn::make('type')
                    ->searchable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'text' => 'gray',
                        'image' => 'success',
                        'file' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('order')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('group')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
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
            'index' => ListSettings::route('/'),
            'create' => CreateSetting::route('/create'),
            'edit' => EditSetting::route('/{record}/edit'),
        ];
    }
}
