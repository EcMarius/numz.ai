<?php

namespace Wave\Plugins;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PluginAutoloader
{
    /**
     * Indicates if the autoloader has been registered.
     *
     * @var bool
     */
    protected static $registered = false;

    public static function register()
    {
        if (self::$registered) {
            return;
        }

        spl_autoload_register(function ($class) {
            $prefix = 'Wave\\Plugins\\';
            $base_dir = resource_path('plugins/');

            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                return;
            }

            $relative_class = substr($class, $len);
            $parts = explode('\\', $relative_class);

            if (count($parts) < 2) {
                return;
            }

            $plugin_name = $parts[0];
            $class_file = implode('/', array_slice($parts, 1)).'.php';

            // Try multiple naming conventions
            $possiblePaths = [
                $base_dir.$plugin_name.'/'.$class_file,                    // Exact match (EvenLeads)
                $base_dir.$plugin_name.'/src/'.$class_file,                // Exact match with src
                $base_dir.Str::kebab($plugin_name).'/'.$class_file,        // Kebab case (one-to-lead)
                $base_dir.Str::kebab($plugin_name).'/src/'.$class_file,    // Kebab case with src
            ];

            foreach ($possiblePaths as $possiblePath) {
                if (File::exists($possiblePath)) {
                    require $possiblePath;
                    return;
                }
            }
        });

        self::$registered = true;
    }
}
