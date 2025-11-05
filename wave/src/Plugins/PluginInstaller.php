<?php

namespace Wave\Plugins;

use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;

class PluginInstaller
{
    protected $pluginsPath;

    public function __construct()
    {
        $this->pluginsPath = resource_path('plugins');

        if (!File::exists($this->pluginsPath)) {
            File::makeDirectory($this->pluginsPath, 0755, true);
        }
    }

    /**
     * Install a plugin from a zip file
     *
     * @param string $zipPath Path to the zip file
     * @return array ['success' => bool, 'message' => string, 'plugin' => string]
     */
    public function installFromZip(string $zipPath): array
    {
        try {
            // Check if file exists
            if (!file_exists($zipPath)) {
                return [
                    'success' => false,
                    'message' => 'ZIP file not found at: ' . $zipPath,
                ];
            }

            // Check if file is readable
            if (!is_readable($zipPath)) {
                return [
                    'success' => false,
                    'message' => 'ZIP file is not readable. Check file permissions.',
                ];
            }

            // Check file size
            $fileSize = filesize($zipPath);
            if ($fileSize === 0) {
                return [
                    'success' => false,
                    'message' => 'ZIP file is empty (0 bytes)',
                ];
            }

            $zip = new ZipArchive;
            $openResult = $zip->open($zipPath);

            if ($openResult !== true) {
                $errorMessages = [
                    ZipArchive::ER_EXISTS => 'File already exists',
                    ZipArchive::ER_INCONS => 'ZIP archive inconsistent',
                    ZipArchive::ER_INVAL => 'Invalid argument',
                    ZipArchive::ER_MEMORY => 'Memory allocation failure',
                    ZipArchive::ER_NOENT => 'No such file',
                    ZipArchive::ER_NOZIP => 'Not a valid ZIP archive',
                    ZipArchive::ER_OPEN => 'Can\'t open file',
                    ZipArchive::ER_READ => 'Read error',
                    ZipArchive::ER_SEEK => 'Seek error',
                ];

                $errorMsg = $errorMessages[$openResult] ?? 'Unknown error (code: ' . $openResult . ')';

                return [
                    'success' => false,
                    'message' => 'Failed to open ZIP file: ' . $errorMsg,
                    'error_code' => $openResult,
                    'file_path' => $zipPath,
                    'file_size' => $fileSize,
                ];
            }

            // Detect plugin structure (folder vs files)
            $structure = $this->detectPluginStructure($zip);

            if (!$structure['valid']) {
                $zip->close();
                return [
                    'success' => false,
                    'message' => $structure['message'],
                ];
            }

            // Extract to a temporary location first
            $tempPath = storage_path('app/temp/plugin-' . uniqid());
            File::makeDirectory($tempPath, 0755, true);

            $zip->extractTo($tempPath);
            $zip->close();

            // If files were zipped directly (no root folder), move them into a folder
            if ($structure['type'] === 'files') {
                $pluginName = $structure['pluginName'];
                $newPath = $tempPath . '/' . $pluginName;
                File::makeDirectory($newPath, 0755, true);

                // Move all files into the new folder
                $files = File::allFiles($tempPath);
                $dirs = File::directories($tempPath);

                foreach ($files as $file) {
                    $relativePath = str_replace($tempPath . '/', '', $file->getPathname());
                    $newFilePath = $newPath . '/' . $relativePath;
                    File::ensureDirectoryExists(dirname($newFilePath));
                    File::move($file->getPathname(), $newFilePath);
                }

                foreach ($dirs as $dir) {
                    if (basename($dir) !== $pluginName) {
                        $relativePath = str_replace($tempPath . '/', '', $dir);
                        File::moveDirectory($dir, $newPath . '/' . $relativePath);
                    }
                }

                $rootFolder = $pluginName;
            } else {
                $rootFolder = $structure['rootFolder'];
            }

            // Validate plugin structure
            $validation = $this->validatePluginStructure($tempPath, $rootFolder);

            if (!$validation['valid']) {
                File::deleteDirectory($tempPath);
                return [
                    'success' => false,
                    'message' => $validation['message'],
                ];
            }

            // Move to plugins directory
            $pluginName = $validation['pluginName'];
            $targetPath = $this->pluginsPath . '/' . $pluginName;

            if (File::exists($targetPath)) {
                File::deleteDirectory($tempPath);
                return [
                    'success' => false,
                    'message' => "Plugin '{$pluginName}' already exists. Please delete it first.",
                ];
            }

            // Move the extracted folder
            File::move($tempPath . '/' . $rootFolder, $targetPath);
            File::deleteDirectory($tempPath);

            return [
                'success' => true,
                'message' => "Plugin '{$pluginName}' installed successfully",
                'plugin' => $pluginName,
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Installation failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Detect plugin structure - handles both folder and direct file zips
     *
     * Case 1: ZIP contains a folder (EvenLeads/EvenLeadsPlugin.php)
     * Case 2: ZIP contains files directly (EvenLeadsPlugin.php at root)
     */
    protected function detectPluginStructure(ZipArchive $zip): array
    {
        $rootFolders = [];
        $rootFiles = [];
        $pluginFile = null;

        // Analyze ZIP contents
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $name = $stat['name'];

            // Skip macOS metadata
            if (strpos($name, '__MACOSX') !== false || strpos($name, '.DS_Store') !== false) {
                continue;
            }

            $parts = explode('/', $name);

            if (count($parts) === 1) {
                // File at root level
                $rootFiles[] = $name;
                if (preg_match('/^([A-Z][a-zA-Z0-9]*)Plugin\.php$/', $name, $matches)) {
                    $pluginFile = $name;
                }
            } else {
                // Inside a folder
                $rootFolders[$parts[0]] = true;
            }
        }

        $rootFolders = array_keys($rootFolders);

        // Case 1: Single root folder (standard structure)
        if (count($rootFolders) === 1 && count($rootFiles) === 0) {
            return [
                'valid' => true,
                'type' => 'folder',
                'rootFolder' => $rootFolders[0],
            ];
        }

        // Case 2: Files zipped directly (no root folder)
        if (count($rootFolders) === 0 || ($pluginFile && count($rootFiles) > 0)) {
            if (!$pluginFile) {
                return [
                    'valid' => false,
                    'message' => 'No plugin file (*Plugin.php) found in ZIP',
                ];
            }

            // Extract plugin name from file (e.g., "EvenLeadsPlugin.php" -> "EvenLeads")
            preg_match('/^([A-Z][a-zA-Z0-9]*)Plugin\.php$/', $pluginFile, $matches);
            $pluginName = $matches[1];

            return [
                'valid' => true,
                'type' => 'files',
                'pluginName' => $pluginName,
            ];
        }

        // Case 3: Multiple root folders (invalid)
        return [
            'valid' => false,
            'message' => 'Invalid plugin structure: Multiple root folders found (' . implode(', ', $rootFolders) . ')',
        ];
    }

    /**
     * Validate plugin structure
     */
    protected function validatePluginStructure(string $tempPath, string $rootFolder): array
    {
        $pluginPath = $tempPath . '/' . $rootFolder;

        if (!File::exists($pluginPath)) {
            return [
                'valid' => false,
                'message' => 'Invalid plugin structure',
            ];
        }

        // Look for the main plugin file
        $studlyName = Str::studly($rootFolder);
        $pluginFile = $pluginPath . '/' . $studlyName . 'Plugin.php';

        if (!File::exists($pluginFile)) {
            return [
                'valid' => false,
                'message' => "Plugin file '{$studlyName}Plugin.php' not found in root directory",
            ];
        }

        // Check if version.json exists
        $versionFile = $pluginPath . '/version.json';
        if (!File::exists($versionFile)) {
            return [
                'valid' => false,
                'message' => 'version.json file not found',
            ];
        }

        return [
            'valid' => true,
            'pluginName' => $rootFolder,
        ];
    }

    /**
     * Uninstall a plugin
     */
    public function uninstall(string $pluginName): array
    {
        $pluginPath = $this->pluginsPath . '/' . $pluginName;

        if (!File::exists($pluginPath)) {
            return [
                'success' => false,
                'message' => "Plugin '{$pluginName}' not found",
            ];
        }

        try {
            File::deleteDirectory($pluginPath);

            return [
                'success' => true,
                'message' => "Plugin '{$pluginName}' uninstalled successfully",
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Uninstall failed: ' . $e->getMessage(),
            ];
        }
    }
}
