<?php

namespace App\Filament\Pages;

use App\Models\PlatformSchema;
use App\Services\SchemaService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use UnitEnum;

class PlatformSchemaManager extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'phosphor-code-block-duotone';

    protected static ?string $navigationLabel = 'Schema Manager';

    protected static UnitEnum|string|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 11;

    public function getView(): string
    {
        return 'filament.pages.platform-schema-manager';
    }

    public ?string $selectedPlatform = null;
    public ?string $selectedPageType = null;
    public ?string $jsonInput = null;
    public ?array $currentSchema = null;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('loadTemplates')
                ->label('Load Templates')
                ->icon('heroicon-o-sparkles')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Load Platform Schema Templates')
                ->modalDescription('This will load pre-built schemas for LinkedIn, Reddit, and X. Existing schemas will be preserved.')
                ->action(function () {
                    try {
                        \Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\PlatformSchemaTemplatesSeeder']);

                        Notification::make()
                            ->title('Templates Loaded')
                            ->body('Platform schema templates have been loaded successfully.')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Load Failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('export')
                ->label('Export Schema')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->form([
                    Select::make('platform')
                        ->required()
                        ->options(array_combine(
                            PlatformSchema::PLATFORMS,
                            array_map('ucfirst', PlatformSchema::PLATFORMS)
                        )),
                    Select::make('page_type')
                        ->required()
                        ->options(array_combine(
                            PlatformSchema::PAGE_TYPES,
                            array_map('ucfirst', PlatformSchema::PAGE_TYPES)
                        )),
                ])
                ->action(function (array $data) {
                    $schema = SchemaService::exportSchema($data['platform'], $data['page_type']);

                    if ($schema) {
                        $this->currentSchema = $schema;

                        Notification::make()
                            ->title('Schema Exported')
                            ->body('Copy the JSON from the output below.')
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Export Failed')
                            ->body('No schema found for this platform/page type.')
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('import')
                ->label('Import Schema')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->form([
                    Textarea::make('json')
                        ->label('Schema JSON')
                        ->required()
                        ->rows(15)
                        ->placeholder('Paste your schema JSON here...')
                        ->helperText('Paste the complete schema JSON including platform, page_type, and elements.'),
                ])
                ->action(function (array $data) {
                    try {
                        $schemaData = json_decode($data['json'], true);

                        if (json_last_error() !== JSON_ERROR_NONE) {
                            throw new \Exception('Invalid JSON: ' . json_last_error_msg());
                        }

                        $result = SchemaService::importSchema($schemaData);

                        if ($result['success']) {
                            Notification::make()
                                ->title('Import Successful')
                                ->body($result['message'])
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Import Failed')
                                ->body(implode("\n", $result['errors']))
                                ->danger()
                                ->send();
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Import Failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('bulkImport')
                ->label('Bulk Import')
                ->icon('heroicon-o-document-duplicate')
                ->color('warning')
                ->form([
                    Textarea::make('schemas_json')
                        ->label('Schemas Array JSON')
                        ->required()
                        ->rows(20)
                        ->placeholder('[{"platform": "linkedin", "page_type": "post", "elements": [...]}, {...}]')
                        ->helperText('Paste an array of schema objects. Each schema should have platform, page_type, and elements.'),
                ])
                ->action(function (array $data) {
                    try {
                        $schemas = json_decode($data['schemas_json'], true);

                        if (json_last_error() !== JSON_ERROR_NONE) {
                            throw new \Exception('Invalid JSON: ' . json_last_error_msg());
                        }

                        if (!is_array($schemas)) {
                            throw new \Exception('JSON must be an array of schemas');
                        }

                        $result = SchemaService::bulkImport($schemas);

                        if ($result['success']) {
                            Notification::make()
                                ->title('Bulk Import Successful')
                                ->body("Imported {$result['imported']} out of {$result['total']} schemas.")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Bulk Import Completed with Errors')
                                ->body("Imported {$result['imported']}, Failed {$result['failed']}")
                                ->warning()
                                ->send();
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Bulk Import Failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('clearCache')
                ->label('Clear Schema Cache')
                ->icon('heroicon-o-trash')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function () {
                    SchemaService::clearCache();

                    Notification::make()
                        ->title('Cache Cleared')
                        ->body('All schema caches have been cleared.')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function getViewData(): array
    {
        return [
            'platforms' => PlatformSchema::PLATFORMS,
            'pageTypes' => PlatformSchema::PAGE_TYPES,
            'currentSchema' => $this->currentSchema,
        ];
    }
}
