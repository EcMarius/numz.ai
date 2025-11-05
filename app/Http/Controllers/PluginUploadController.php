<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Wave\Plugins\PluginInstaller;

class PluginUploadController extends Controller
{
    public function upload(Request $request)
    {
        try {
            $request->validate([
                'plugin' => 'required|file|mimes:zip|max:51200', // 50MB
            ]);

            $installer = new PluginInstaller();

            // Store the uploaded file temporarily
            $file = $request->file('plugin');

            // Use getRealPath() to get actual uploaded file path
            $tempPath = $file->getRealPath();

            // Or manually save to a known location
            $fileName = uniqid('plugin_') . '.zip';
            $fullPath = storage_path('app/temp/' . $fileName);

            // Ensure temp directory exists
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            // Move the file
            move_uploaded_file($tempPath, $fullPath);

            // Verify file was saved
            if (!file_exists($fullPath)) {
                Log::error('Uploaded file not found after move', [
                    'fullPath' => $fullPath,
                    'tempPath' => $tempPath,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'File upload failed - could not save file',
                    'trace' => "Expected path: {$fullPath}",
                ], 500);
            }

            // Install the plugin
            $result = $installer->installFromZip($fullPath);

            // Clean up temp file
            @unlink($fullPath);

            if ($result['success']) {
                $pluginName = $result['plugin'];

                // Add to installed.json
                $installedPath = resource_path('plugins/installed.json');
                $installed = file_exists($installedPath) ? json_decode(file_get_contents($installedPath), true) : [];

                if (!in_array($pluginName, $installed)) {
                    $installed[] = $pluginName;
                    file_put_contents($installedPath, json_encode($installed, JSON_PRETTY_PRINT));
                }

                // Run migrations automatically
                $migrationPath = resource_path("plugins/{$pluginName}/database/migrations");
                if (file_exists($migrationPath) && is_dir($migrationPath)) {
                    try {
                        \Illuminate\Support\Facades\Artisan::call('migrate', [
                            '--path' => "resources/plugins/{$pluginName}/database/migrations",
                            '--force' => true,
                        ]);
                    } catch (\Exception $e) {
                        Log::warning("Migration failed for {$pluginName}: " . $e->getMessage());
                    }
                }

                // Clear cache
                \Illuminate\Support\Facades\Artisan::call('cache:clear');

                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'plugin' => $pluginName,
                ]);
            } else {
                // Build detailed error response
                $errorDetails = [];

                if (isset($result['error_code'])) {
                    $errorDetails[] = "Error Code: " . $result['error_code'];
                }
                if (isset($result['file_path'])) {
                    $errorDetails[] = "File Path: " . $result['file_path'];
                }
                if (isset($result['file_size'])) {
                    $errorDetails[] = "File Size: " . number_format($result['file_size']) . " bytes";
                }

                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'trace' => !empty($errorDetails) ? implode("\n", $errorDetails) : null,
                ], 400);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid file: ' . $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Plugin upload failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Installation failed: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }
}
