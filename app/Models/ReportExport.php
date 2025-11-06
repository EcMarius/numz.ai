<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReportExport extends Model
{
    use HasFactory;

    protected $fillable = [
        'custom_report_id',
        'report_execution_id',
        'exported_by',
        'export_format',
        'file_path',
        'file_size',
        'download_count',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(CustomReport::class, 'custom_report_id');
    }

    public function execution(): BelongsTo
    {
        return $this->belongsTo(ReportExecution::class, 'report_execution_id');
    }

    public function exporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'exported_by');
    }

    /**
     * Check if export is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && now()->isAfter($this->expires_at);
    }

    /**
     * Increment download count
     */
    public function incrementDownloads(): void
    {
        $this->increment('download_count');
    }

    /**
     * Get file size in human readable format
     */
    public function getFileSizeHumanAttribute(): string
    {
        if (!$this->file_size) {
            return 'N/A';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->file_size;

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Delete file from storage
     */
    public function deleteFile(): bool
    {
        if ($this->file_path && \Storage::exists($this->file_path)) {
            return \Storage::delete($this->file_path);
        }

        return false;
    }

    /**
     * Clean up expired exports
     */
    public static function cleanupExpired(): int
    {
        $expired = self::where('expires_at', '<', now())->get();

        foreach ($expired as $export) {
            $export->deleteFile();
            $export->delete();
        }

        return $expired->count();
    }

    /**
     * Get format icon
     */
    public function getFormatIconAttribute(): string
    {
        return match($this->export_format) {
            'pdf' => 'heroicon-o-document',
            'csv' => 'heroicon-o-table-cells',
            'xlsx' => 'heroicon-o-table-cells',
            'json' => 'heroicon-o-code-bracket',
            default => 'heroicon-o-document',
        };
    }
}
