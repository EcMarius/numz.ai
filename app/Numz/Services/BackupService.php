<?php

namespace App\Numz\Services;

use App\Models\UpdateBackup;
use App\Models\SystemUpdate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class BackupService
{
    protected string $backupPath;

    public function __construct()
    {
        $this->backupPath = config('updater.backup_path');

        // Ensure backup directory exists
        if (!File::exists($this->backupPath)) {
            File::makeDirectory($this->backupPath, 0755, true);
        }
    }

    /**
     * Create full backup before update
     */
    public function createBackup(SystemUpdate $systemUpdate): UpdateBackup
    {
        Log::info('Creating backup for update', [
            'version' => $systemUpdate->version,
            'update_id' => $systemUpdate->id,
        ]);

        $timestamp = now()->format('Y-m-d_His');
        $version = $systemUpdate->version;

        try {
            // Create database backup
            $dbBackupPath = null;
            if (config('updater.backup_before_update')) {
                $dbBackupPath = $this->backupDatabase($version, $timestamp);
            }

            // Create files backup
            $filesBackupPath = $this->backupFiles($version, $timestamp);

            // Calculate total size
            $totalSize = 0;
            if ($dbBackupPath && File::exists($dbBackupPath)) {
                $totalSize += File::size($dbBackupPath);
            }
            if (File::exists($filesBackupPath)) {
                $totalSize += File::size($filesBackupPath);
            }

            // Create backup record
            $backup = UpdateBackup::create([
                'system_update_id' => $systemUpdate->id,
                'version' => $systemUpdate->previous_version ?? config('updater.current_version'),
                'backup_type' => 'full',
                'database_backup_path' => $dbBackupPath ? str_replace($this->backupPath . '/', '', $dbBackupPath) : null,
                'files_backup_path' => str_replace($this->backupPath . '/', '', $filesBackupPath),
                'backup_size' => $totalSize,
                'is_restorable' => true,
                'created_at' => now(),
                'expires_at' => now()->addDays(30),
                'notes' => "Backup created before updating to version {$version}",
            ]);

            Log::info('Backup created successfully', [
                'backup_id' => $backup->id,
                'size' => $backup->formatted_size,
            ]);

            return $backup;

        } catch (\Exception $e) {
            Log::error('Backup creation failed', [
                'error' => $e->getMessage(),
                'version' => $version,
            ]);

            throw new \Exception('Failed to create backup: ' . $e->getMessage());
        }
    }

    /**
     * Backup database
     */
    protected function backupDatabase(string $version, string $timestamp): string
    {
        $filename = "database_{$version}_{$timestamp}.sql";
        $filepath = $this->backupPath . '/' . $filename;

        $database = config('database.default');
        $connection = config("database.connections.{$database}");

        if ($database === 'mysql') {
            $this->backupMySQLDatabase($connection, $filepath);
        } elseif ($database === 'pgsql') {
            $this->backupPostgreSQLDatabase($connection, $filepath);
        } elseif ($database === 'sqlite') {
            $this->backupSQLiteDatabase($connection, $filepath);
        } else {
            throw new \Exception("Unsupported database driver: {$database}");
        }

        // Compress the SQL file
        $zipFilepath = $filepath . '.gz';
        $this->compressFile($filepath, $zipFilepath);
        File::delete($filepath);

        return $zipFilepath;
    }

    /**
     * Backup MySQL database
     */
    protected function backupMySQLDatabase(array $connection, string $filepath): void
    {
        $command = sprintf(
            'mysqldump --user=%s --password=%s --host=%s --port=%s %s > %s 2>&1',
            escapeshellarg($connection['username']),
            escapeshellarg($connection['password']),
            escapeshellarg($connection['host']),
            escapeshellarg($connection['port'] ?? '3306'),
            escapeshellarg($connection['database']),
            escapeshellarg($filepath)
        );

        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            throw new \Exception('MySQL backup failed: ' . implode("\n", $output));
        }
    }

    /**
     * Backup PostgreSQL database
     */
    protected function backupPostgreSQLDatabase(array $connection, string $filepath): void
    {
        $command = sprintf(
            'PGPASSWORD=%s pg_dump --username=%s --host=%s --port=%s %s > %s 2>&1',
            escapeshellarg($connection['password']),
            escapeshellarg($connection['username']),
            escapeshellarg($connection['host']),
            escapeshellarg($connection['port'] ?? '5432'),
            escapeshellarg($connection['database']),
            escapeshellarg($filepath)
        );

        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            throw new \Exception('PostgreSQL backup failed: ' . implode("\n", $output));
        }
    }

    /**
     * Backup SQLite database
     */
    protected function backupSQLiteDatabase(array $connection, string $filepath): void
    {
        $dbPath = $connection['database'];

        if (!File::exists($dbPath)) {
            throw new \Exception("SQLite database file not found: {$dbPath}");
        }

        File::copy($dbPath, $filepath);
    }

    /**
     * Backup application files
     */
    protected function backupFiles(string $version, string $timestamp): string
    {
        $filename = "files_{$version}_{$timestamp}.zip";
        $filepath = $this->backupPath . '/' . $filename;

        $zip = new ZipArchive();

        if ($zip->open($filepath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            throw new \Exception('Could not create zip file for files backup');
        }

        $basePath = base_path();
        $excludedPaths = config('updater.excluded_paths', []);

        // Add all files except excluded paths
        $this->addFilesToZip($zip, $basePath, '', $excludedPaths);

        $zip->close();

        return $filepath;
    }

    /**
     * Recursively add files to zip
     */
    protected function addFilesToZip(ZipArchive $zip, string $basePath, string $localPath, array $excludedPaths): void
    {
        $fullPath = $basePath . '/' . $localPath;

        if (!File::exists($fullPath)) {
            return;
        }

        // Check if path is excluded
        foreach ($excludedPaths as $excluded) {
            if (str_starts_with($localPath, $excluded) || $localPath === $excluded) {
                return;
            }
        }

        if (File::isDirectory($fullPath)) {
            $files = File::files($fullPath);
            $directories = File::directories($fullPath);

            foreach ($files as $file) {
                $relativePath = $localPath ? $localPath . '/' . $file->getFilename() : $file->getFilename();
                $zip->addFile($file->getPathname(), $relativePath);
            }

            foreach ($directories as $directory) {
                $dirName = basename($directory);
                $relativePath = $localPath ? $localPath . '/' . $dirName : $dirName;
                $this->addFilesToZip($zip, $basePath, $relativePath, $excludedPaths);
            }
        }
    }

    /**
     * Compress file using gzip
     */
    protected function compressFile(string $source, string $destination): void
    {
        $file = fopen($source, 'rb');
        $gz = gzopen($destination, 'wb9');

        while (!feof($file)) {
            gzwrite($gz, fread($file, 1024 * 512));
        }

        fclose($file);
        gzclose($gz);
    }

    /**
     * Restore backup
     */
    public function restoreBackup(UpdateBackup $backup): void
    {
        Log::info('Restoring backup', [
            'backup_id' => $backup->id,
            'version' => $backup->version,
        ]);

        try {
            // Restore database
            if ($backup->database_backup_path) {
                $this->restoreDatabase($this->backupPath . '/' . $backup->database_backup_path);
            }

            // Restore files
            if ($backup->files_backup_path) {
                $this->restoreFiles($this->backupPath . '/' . $backup->files_backup_path);
            }

            Log::info('Backup restored successfully', [
                'backup_id' => $backup->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Backup restoration failed', [
                'backup_id' => $backup->id,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Failed to restore backup: ' . $e->getMessage());
        }
    }

    /**
     * Restore database from backup
     */
    protected function restoreDatabase(string $backupPath): void
    {
        // Decompress if needed
        if (str_ends_with($backupPath, '.gz')) {
            $sqlPath = str_replace('.gz', '', $backupPath);
            $this->decompressFile($backupPath, $sqlPath);
        } else {
            $sqlPath = $backupPath;
        }

        $database = config('database.default');
        $connection = config("database.connections.{$database}");

        if ($database === 'mysql') {
            $this->restoreMySQLDatabase($connection, $sqlPath);
        } elseif ($database === 'pgsql') {
            $this->restorePostgreSQLDatabase($connection, $sqlPath);
        } elseif ($database === 'sqlite') {
            $this->restoreSQLiteDatabase($connection, $sqlPath);
        }

        // Clean up decompressed file
        if ($sqlPath !== $backupPath && File::exists($sqlPath)) {
            File::delete($sqlPath);
        }
    }

    /**
     * Restore MySQL database
     */
    protected function restoreMySQLDatabase(array $connection, string $filepath): void
    {
        $command = sprintf(
            'mysql --user=%s --password=%s --host=%s --port=%s %s < %s 2>&1',
            escapeshellarg($connection['username']),
            escapeshellarg($connection['password']),
            escapeshellarg($connection['host']),
            escapeshellarg($connection['port'] ?? '3306'),
            escapeshellarg($connection['database']),
            escapeshellarg($filepath)
        );

        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            throw new \Exception('MySQL restore failed: ' . implode("\n", $output));
        }
    }

    /**
     * Restore PostgreSQL database
     */
    protected function restorePostgreSQLDatabase(array $connection, string $filepath): void
    {
        $command = sprintf(
            'PGPASSWORD=%s psql --username=%s --host=%s --port=%s %s < %s 2>&1',
            escapeshellarg($connection['password']),
            escapeshellarg($connection['username']),
            escapeshellarg($connection['host']),
            escapeshellarg($connection['port'] ?? '5432'),
            escapeshellarg($connection['database']),
            escapeshellarg($filepath)
        );

        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            throw new \Exception('PostgreSQL restore failed: ' . implode("\n", $output));
        }
    }

    /**
     * Restore SQLite database
     */
    protected function restoreSQLiteDatabase(array $connection, string $filepath): void
    {
        $dbPath = $connection['database'];
        File::copy($filepath, $dbPath);
    }

    /**
     * Restore files from backup
     */
    protected function restoreFiles(string $backupPath): void
    {
        $zip = new ZipArchive();

        if ($zip->open($backupPath) !== TRUE) {
            throw new \Exception('Could not open backup zip file');
        }

        $basePath = base_path();
        $zip->extractTo($basePath);
        $zip->close();
    }

    /**
     * Decompress gzip file
     */
    protected function decompressFile(string $source, string $destination): void
    {
        $gz = gzopen($source, 'rb');
        $file = fopen($destination, 'wb');

        while (!gzeof($gz)) {
            fwrite($file, gzread($gz, 1024 * 512));
        }

        gzclose($gz);
        fclose($file);
    }
}
