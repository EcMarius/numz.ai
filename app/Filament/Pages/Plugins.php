<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;
use Wave\Plugins\PluginInstaller;

class Plugins extends Page
{
    use WithFileUploads;

    protected static BackedEnum|string|null $navigationIcon = 'phosphor-plugs-duotone';

    protected static ?int $navigationSort = 9;

    public function getView(): string
    {
        return 'filament.pages.plugins';
    }

    public $plugins = [];

    public $pluginZip;

    public function mount()
    {
        $this->refreshPlugins();
    }

    private function refreshPlugins()
    {
        $this->plugins = $this->getPluginsFromFolder();
    }

    private function getPluginsFromFolder()
    {
        $plugins = [];
        $plugins_folder = resource_path('plugins');

        if (! file_exists($plugins_folder)) {
            mkdir($plugins_folder);
        }

        $scandirectory = scandir($plugins_folder);

        foreach ($scandirectory as $folder) {
            // Skip system files, hidden files, and non-directories
            if ($folder === '.' || $folder === '..' ||
                $folder === 'installed.json' ||
                str_starts_with($folder, '.') ||
                !is_dir($plugins_folder.'/'.$folder)) {
                continue;
            }

            $studlyFolderName = Str::studly($folder);
            $pluginFile = $plugins_folder.'/'.$folder.'/'.$studlyFolderName.'Plugin.php';

            if (file_exists($pluginFile)) {
                $pluginClass = "Wave\\Plugins\\{$studlyFolderName}\\{$studlyFolderName}Plugin";
                if (class_exists($pluginClass) && method_exists($pluginClass, 'getPluginInfo')) {
                    $plugin = new $pluginClass(app());
                    $info = $plugin->getPluginInfo();
                    $info['folder'] = $folder;
                    $info['active'] = $this->isPluginActive($folder);
                    $plugins[$folder] = $info;
                }
            }
        }

        return $plugins;
    }

    private function isPluginActive($folder)
    {
        $installedPlugins = $this->getInstalledPlugins();

        return in_array($folder, $installedPlugins);
    }

    private function getInstalledPlugins()
    {
        $path = resource_path('plugins/installed.json');

        return File::exists($path) ? File::json($path) : [];
    }

    private function updateInstalledPlugins($plugins)
    {
        $json = json_encode($plugins);
        file_put_contents(resource_path('plugins/installed.json'), $json);
    }

    public function activate($pluginFolder)
    {
        $installedPlugins = $this->getInstalledPlugins();
        if (! in_array($pluginFolder, $installedPlugins)) {
            $installedPlugins[] = $pluginFolder;
            $this->updateInstalledPlugins($installedPlugins);

            $this->runPostActivationCommands($pluginFolder);

            Notification::make()
                ->title('Successfully activated '.$pluginFolder.' plugin')
                ->success()
                ->send();
        }

        $this->refreshPlugins();
    }

    private function runPostActivationCommands($pluginFolder)
    {
        $studlyFolderName = Str::studly($pluginFolder);
        $pluginClass = "Wave\\Plugins\\{$studlyFolderName}\\{$studlyFolderName}Plugin";

        if (class_exists($pluginClass)) {
            $plugin = new $pluginClass(app());

            if (method_exists($plugin, 'getPostActivationCommands')) {
                $commands = $plugin->getPostActivationCommands();

                foreach ($commands as $command) {
                    if (is_string($command)) {
                        Artisan::call($command);
                    } elseif (is_callable($command)) {
                        $command();
                    }
                }
            }

            // Run migrations if they exist
            $migrationPath = resource_path("plugins/{$pluginFolder}/database/migrations");
            if (File::isDirectory($migrationPath)) {
                Artisan::call('migrate', [
                    '--path' => "resources/plugins/{$pluginFolder}/database/migrations",
                    '--force' => true,
                ]);
            }
        }
    }

    public function deactivate($pluginFolder)
    {
        $installedPlugins = $this->getInstalledPlugins();
        $installedPlugins = array_diff($installedPlugins, [$pluginFolder]);
        $this->updateInstalledPlugins($installedPlugins);

        Notification::make()
            ->title('Successfully deactivated '.$pluginFolder.' plugin')
            ->success()
            ->send();

        $this->refreshPlugins();
    }

    public function deletePlugin($pluginFolder)
    {
        $this->deactivate($pluginFolder);

        $pluginPath = resource_path('plugins').'/'.$pluginFolder;
        if (file_exists($pluginPath)) {
            File::deleteDirectory($pluginPath);
        }

        Notification::make()
            ->title('Successfully deleted '.$pluginFolder.' plugin')
            ->success()
            ->send();

        $this->refreshPlugins();
    }

    public function uploadPlugin()
    {
        $this->validate([
            'pluginZip' => 'required|file|mimes:zip|max:51200', // 50MB max
        ]);

        $installer = new PluginInstaller();

        // Store the uploaded file temporarily
        $path = $this->pluginZip->store('temp');
        $fullPath = storage_path('app/' . $path);

        // Install the plugin
        $result = $installer->installFromZip($fullPath);

        // Clean up temp file
        File::delete($fullPath);

        if ($result['success']) {
            Notification::make()
                ->title($result['message'])
                ->success()
                ->send();

            $this->refreshPlugins();
            $this->pluginZip = null;
        } else {
            Notification::make()
                ->title('Installation Failed')
                ->body($result['message'])
                ->danger()
                ->send();
        }
    }

    public function clearCache()
    {
        Artisan::call('cache:clear');

        Notification::make()
            ->title('Cache cleared successfully')
            ->success()
            ->send();
    }

    public function reloadPlugins()
    {
        $this->refreshPlugins();
    }

    public function refreshDatabaseTables($pluginFolder)
    {
        $migrationPath = resource_path("plugins/{$pluginFolder}/database/migrations");

        if (!File::isDirectory($migrationPath)) {
            Notification::make()
                ->title('No migrations found')
                ->body('This plugin does not have any database migrations.')
                ->warning()
                ->send();
            return;
        }

        try {
            Artisan::call('migrate:refresh', [
                '--path' => "resources/plugins/{$pluginFolder}/database/migrations",
                '--force' => true,
            ]);

            Notification::make()
                ->title('Database tables refreshed successfully')
                ->body('All tables for this plugin have been refreshed.')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Failed to refresh tables')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }

        $this->refreshPlugins();
    }

    public function hasMigrations($pluginFolder)
    {
        $migrationPath = resource_path("plugins/{$pluginFolder}/database/migrations");
        return File::isDirectory($migrationPath) && count(File::files($migrationPath)) > 0;
    }
}
