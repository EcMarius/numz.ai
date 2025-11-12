<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WHMCSModuleResource\Pages;
use App\Numz\WHMCS\ModuleLoader;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;

class WHMCSModuleResource extends Resource
{
    protected static ?string $model = null; // Virtual resource, no model

    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';

    protected static ?string $navigationLabel = 'WHMCS Modules';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 100;

    public static function canCreate(): bool
    {
        return false; // Modules are discovered, not created
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(fn () => self::getModulesQuery())
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Module Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'servers' => 'success',
                        'addons' => 'info',
                        'gateways' => 'warning',
                        'registrars' => 'primary',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('display_name')
                    ->label('Display Name')
                    ->searchable(),

                Tables\Columns\TextColumn::make('version')
                    ->label('Version')
                    ->default('N/A'),

                Tables\Columns\IconColumn::make('enabled')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('functions_count')
                    ->label('Functions')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'servers' => 'Provisioning',
                        'addons' => 'Addons',
                        'gateways' => 'Payment Gateways',
                        'registrars' => 'Domain Registrars',
                        'fraud' => 'Fraud Detection',
                        'notifications' => 'Notifications',
                        'widgets' => 'Widgets',
                        'mail' => 'Mail Providers',
                    ]),

                Tables\Filters\TernaryFilter::make('enabled')
                    ->label('Enabled')
                    ->placeholder('All modules')
                    ->trueLabel('Enabled only')
                    ->falseLabel('Disabled only'),
            ])
            ->actions([
                Tables\Actions\Action::make('configure')
                    ->icon('heroicon-o-cog')
                    ->modalHeading(fn ($record) => 'Configure ' . $record->display_name)
                    ->form(fn ($record) => self::getConfigurationForm($record))
                    ->action(function ($record, array $data) {
                        self::saveModuleConfiguration($record, $data);
                    }),

                Tables\Actions\Action::make('toggle')
                    ->label(fn ($record) => $record->enabled ? 'Disable' : 'Enable')
                    ->icon(fn ($record) => $record->enabled ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record) => $record->enabled ? 'danger' : 'success')
                    ->action(function ($record) {
                        self::toggleModule($record);
                    })
                    ->requiresConfirmation(),

                Tables\Actions\Action::make('test')
                    ->icon('heroicon-o-beaker')
                    ->color('info')
                    ->action(function ($record) {
                        return self::testModule($record);
                    })
                    ->visible(fn ($record) => $record->enabled),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('enable')
                    ->label('Enable Selected')
                    ->icon('heroicon-o-check-circle')
                    ->action(function ($records) {
                        foreach ($records as $record) {
                            self::toggleModule($record, true);
                        }
                    }),

                Tables\Actions\BulkAction::make('disable')
                    ->label('Disable Selected')
                    ->icon('heroicon-o-x-circle')
                    ->action(function ($records) {
                        foreach ($records as $record) {
                            self::toggleModule($record, false);
                        }
                    })
                    ->requiresConfirmation(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('refresh')
                    ->label('Refresh Modules')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function () {
                        Cache::forget('whmcs_modules_list');
                        ModuleLoader::discoverModules();
                    }),
            ]);
    }

    /**
     * Get modules query (collection converted to query builder-like)
     */
    protected static function getModulesQuery()
    {
        $modules = Cache::remember('whmcs_modules_list', 3600, function () {
            return ModuleLoader::discoverModules();
        });

        $flatModules = [];

        foreach ($modules as $type => $moduleList) {
            foreach ($moduleList as $moduleName) {
                try {
                    $metadata = ModuleLoader::loadModule($type, $moduleName);

                    if ($metadata) {
                        $config = self::getModuleConfig($type, $moduleName);

                        $flatModules[] = (object) [
                            'id' => "{$type}.{$moduleName}",
                            'name' => $moduleName,
                            'type' => $type,
                            'display_name' => $metadata['metadata']['DisplayName'] ?? $metadata['metadata']['FriendlyName'] ?? ucfirst($moduleName),
                            'version' => $metadata['metadata']['APIVersion'] ?? $metadata['metadata']['version'] ?? 'N/A',
                            'enabled' => $config['enabled'] ?? false,
                            'functions_count' => count($metadata['functions']),
                            'metadata' => $metadata,
                        ];
                    }
                } catch (\Exception $e) {
                    \Log::warning("Failed to load module {$type}/{$moduleName}: " . $e->getMessage());
                }
            }
        }

        return collect($flatModules);
    }

    /**
     * Get configuration form for module
     */
    protected static function getConfigurationForm($record): array
    {
        $config = self::getModuleConfig($record->type, $record->name);
        $metadata = $record->metadata['metadata'] ?? [];

        // Get module's configuration options
        $configFunction = $record->name . '_' . ($record->type === 'servers' ? 'ConfigOptions' :
                          ($record->type === 'addons' ? 'config' :
                          ($record->type === 'gateways' || $record->type === 'registrars' ? 'getConfigArray' : 'config')));

        $moduleConfig = [];
        if (function_exists($configFunction)) {
            $moduleConfig = call_user_func($configFunction);
        }

        // Build form fields from module configuration
        $fields = [];

        foreach ($moduleConfig as $key => $field) {
            if (!is_array($field)) {
                continue;
            }

            $fieldType = $field['Type'] ?? 'text';
            $default = $config[$key] ?? ($field['Default'] ?? null);

            $formField = match ($fieldType) {
                'text' => Forms\Components\TextInput::make($key)
                    ->label($field['FriendlyName'] ?? ucfirst($key))
                    ->helperText($field['Description'] ?? '')
                    ->default($default)
                    ->maxLength($field['Size'] ?? 255),

                'password' => Forms\Components\TextInput::make($key)
                    ->label($field['FriendlyName'] ?? ucfirst($key))
                    ->password()
                    ->helperText($field['Description'] ?? '')
                    ->default($default),

                'yesno' => Forms\Components\Toggle::make($key)
                    ->label($field['FriendlyName'] ?? ucfirst($key))
                    ->helperText($field['Description'] ?? '')
                    ->default((bool) $default),

                'dropdown' => Forms\Components\Select::make($key)
                    ->label($field['FriendlyName'] ?? ucfirst($key))
                    ->options($field['Options'] ?? [])
                    ->helperText($field['Description'] ?? '')
                    ->default($default),

                'textarea' => Forms\Components\Textarea::make($key)
                    ->label($field['FriendlyName'] ?? ucfirst($key))
                    ->helperText($field['Description'] ?? '')
                    ->default($default)
                    ->rows(3),

                default => Forms\Components\TextInput::make($key)
                    ->label($field['FriendlyName'] ?? ucfirst($key))
                    ->default($default),
            };

            $fields[] = $formField;
        }

        // Add enabled toggle at the top
        array_unshift($fields, Forms\Components\Toggle::make('enabled')
            ->label('Enable Module')
            ->default($config['enabled'] ?? false));

        return $fields;
    }

    /**
     * Save module configuration
     */
    protected static function saveModuleConfiguration($record, array $data): void
    {
        $configKey = "whmcs.modules.{$record->type}.{$record->name}";

        // Save to database configuration table
        foreach ($data as $key => $value) {
            \DB::table('tblconfiguration')->updateOrInsert(
                ['setting' => "{$configKey}.{$key}"],
                ['value' => is_bool($value) ? ($value ? '1' : '0') : $value, 'updated_at' => now()]
            );
        }

        // Clear cache
        Cache::forget('whmcs_modules_list');
        Cache::forget("whmcs_module_config_{$record->type}_{$record->name}");

        \Filament\Notifications\Notification::make()
            ->title('Module Configuration Saved')
            ->success()
            ->send();
    }

    /**
     * Toggle module enabled/disabled status
     */
    protected static function toggleModule($record, ?bool $enable = null): void
    {
        $enabled = $enable ?? !($record->enabled ?? false);

        \DB::table('tblconfiguration')->updateOrInsert(
            ['setting' => "whmcs.modules.{$record->type}.{$record->name}.enabled"],
            ['value' => $enabled ? '1' : '0', 'updated_at' => now()]
        );

        Cache::forget('whmcs_modules_list');
        Cache::forget("whmcs_module_config_{$record->type}_{$record->name}");

        \Filament\Notifications\Notification::make()
            ->title('Module ' . ($enabled ? 'Enabled' : 'Disabled'))
            ->success()
            ->send();
    }

    /**
     * Test module connection/configuration
     */
    protected static function testModule($record): void
    {
        try {
            $testFunction = $record->name . '_TestConnection';

            if (function_exists($testFunction)) {
                $config = self::getModuleConfig($record->type, $record->name);
                $result = call_user_func($testFunction, $config);

                if (isset($result['success']) || $result['status'] === 'success') {
                    \Filament\Notifications\Notification::make()
                        ->title('Connection Successful')
                        ->body($result['description'] ?? 'Module connection test passed')
                        ->success()
                        ->send();
                } else {
                    \Filament\Notifications\Notification::make()
                        ->title('Connection Failed')
                        ->body($result['error'] ?? $result['description'] ?? 'Unknown error')
                        ->danger()
                        ->send();
                }
            } else {
                \Filament\Notifications\Notification::make()
                    ->title('Test Not Available')
                    ->body('This module does not have a test connection function')
                    ->warning()
                    ->send();
            }
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Test Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Get module configuration from database
     */
    protected static function getModuleConfig(string $type, string $moduleName): array
    {
        return Cache::remember("whmcs_module_config_{$type}_{$moduleName}", 3600, function () use ($type, $moduleName) {
            $configKey = "whmcs.modules.{$type}.{$moduleName}";

            $configs = \DB::table('tblconfiguration')
                ->where('setting', 'LIKE', "{$configKey}.%")
                ->get();

            $config = [];
            foreach ($configs as $item) {
                $key = str_replace("{$configKey}.", '', $item->setting);
                $config[$key] = $item->value;
            }

            return $config;
        });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWHMCSModules::route('/'),
        ];
    }
}
