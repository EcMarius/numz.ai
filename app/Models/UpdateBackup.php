<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class UpdateBackup extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'system_update_id',
        'version',
        'backup_type',
        'database_backup_path',
        'files_backup_path',
        'backup_size',
        'is_restorable',
        'created_at',
        'expires_at',
        'notes',
    ];

    protected $casts = [
        'is_restorable' => 'boolean',
        'created_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the system update
     */
    public function systemUpdate(): BelongsTo
    {
        return $this->belongsTo(SystemUpdate::class);
    }

    /**
     * Check if backup has expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if backup files exist
     */
    public function filesExist(): bool
    {
        $dbExists = !$this->database_backup_path || Storage::disk('local')->exists($this->database_backup_path);
        $filesExist = !$this->files_backup_path || Storage::disk('local')->exists($this->files_backup_path);

        return $dbExists && $filesExist;
    }

    /**
     * Get formatted backup size
     */
    public function getFormattedSizeAttribute(): string
    {
        if (!$this->backup_size) {
            return 'Unknown';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->backup_size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }

    /**
     * Delete backup files
     */
    public function deleteFiles(): bool
    {
        $success = true;

        if ($this->database_backup_path && Storage::disk('local')->exists($this->database_backup_path)) {
            $success = Storage::disk('local')->delete($this->database_backup_path) && $success;
        }

        if ($this->files_backup_path && Storage::disk('local')->exists($this->files_backup_path)) {
            $success = Storage::disk('local')->delete($this->files_backup_path) && $success;
        }

        return $success;
    }

    /**
     * Clean up expired backups
     */
    public static function cleanupExpired(): int
    {
        $expired = self::where('expires_at', '<', now())->get();
        $count = 0;

        foreach ($expired as $backup) {
            $backup->deleteFiles();
            $backup->delete();
            $count++;
        }

        return $count;
    }

    /**
     * Clean up old backups keeping only the retention count
     */
    public static function enforceRetention(): int
    {
        $retention = config('updater.backup_retention', 3);
        $backups = self::orderBy('created_at', 'desc')->get();

        if ($backups->count() <= $retention) {
            return 0;
        }

        $toDelete = $backups->slice($retention);
        $count = 0;

        foreach ($toDelete as $backup) {
            $backup->deleteFiles();
            $backup->delete();
            $count++;
        }

        return $count;
    }
}
